<?php
/**
 * WP-CLI commands of the settings transfer below "wp creationell-theme settings".
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Cli;

use Closure;
use Creationell\WpTheme\Compiler\Atomic_File_Writer;
use Creationell\WpTheme\Core\Capabilities;
use Creationell\WpTheme\Settings\Transfer\Backup_Store;
use Creationell\WpTheme\Settings\Transfer\Export_Request;
use Creationell\WpTheme\Settings\Transfer\Exporter;
use Creationell\WpTheme\Settings\Transfer\Import_Options;
use Creationell\WpTheme\Settings\Transfer\Import_Plan;
use Creationell\WpTheme\Settings\Transfer\Import_Report;
use Creationell\WpTheme\Settings\Transfer\Importer;
use Creationell\WpTheme\Settings\Transfer\Json_Codec;
use Creationell\WpTheme\Settings\Transfer\Sections;
use Creationell\WpTheme\Settings\Transfer\Transfer_Error;
use Creationell\WpTheme\Settings\Transfer\Transfer_Exception;
use Creationell\WpTheme\Settings\Transfer\Transfer_File;
use Creationell\WpTheme\Settings\Transfer\Transfer_Log;
use Creationell\WpTheme\Settings\Transfer\Upload_Validator;
use JsonException;
use LogicException;
use RuntimeException;
use SplFileObject;
use WP_CLI;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Exports, imports, backs up and restores the theme settings and shows the transfer log.
 *
 * Every subcommand is its own command below "creationell-theme settings", next
 * to the settings commands of the settings core, and needs the global parameter
 * --user of a user with the capability creationell_wp_theme_import_export:
 * the log names the person, and the setter checks the capability of every key
 * for that user. Exit code 0 means done or dry run; 1 means nothing was written
 * (every Transfer_Error, a file that cannot be read or written, a missing user).
 * A declined question of WP_CLI::confirm() ends with 0 and writes nothing, as
 * everywhere in WP-CLI.
 *
 * Import and restore show the plan first: a table of every row with section,
 * key, language, current and incoming value, status and message, then the
 * warnings. Without --dry-run the command asks before it writes; --yes answers
 * the question and also confirms the module switches with the live contents
 * counted in the plan.
 *
 * Example:
 *
 *     wp creationell-theme settings export --file=settings.json --user=admin
 *     wp creationell-theme settings import settings.json --dry-run --user=admin
 *     wp creationell-theme settings backup restore 20260925-100000-a1b2c3 --yes --user=admin
 *
 * @since 1.0.0
 */
final class Settings_Transfer_Command {

	/**
	 * Command name of the settings below the theme namespace; the subcommands follow it.
	 *
	 * @since 1.0.0
	 */
	public const NAME = 'creationell-theme settings';

	/**
	 * Priority on cli_init: after the settings command of the settings core (10), so the subcommands join it.
	 *
	 * @since 1.0.0
	 */
	public const PRIORITY = 20;

	/**
	 * Message without a user who may import and export.
	 *
	 * @since 1.0.0
	 */
	public const USER_ERROR = 'This command needs --user=<login> with the capability ' . Capabilities::IMPORT_EXPORT . '.';

	/**
	 * Formats of import and restore.
	 *
	 * @since 1.0.0
	 */
	public const IMPORT_FORMATS = array( 'table', 'json' );

	/**
	 * Formats of the backup list and the log.
	 *
	 * @since 1.0.0
	 */
	public const LIST_FORMATS = array( 'table', 'json' );

	/**
	 * Fields of the preview table of an import.
	 *
	 * @since 1.0.0
	 */
	public const PLAN_FIELDS = array( 'section', 'key', 'lang', 'current', 'incoming', 'status', 'message' );

	/**
	 * Fields of the backup list.
	 *
	 * @since 1.0.0
	 */
	public const BACKUP_FIELDS = array( 'id', 'created_at', 'reason', 'sections', 'user_id' );

	/**
	 * Fields of the log.
	 *
	 * @since 1.0.0
	 */
	public const LOG_FIELDS = array( 'time', 'action', 'channel', 'user_id', 'sections', 'langs', 'counts', 'backup_id' );

	/**
	 * Importer; null for a new one per import.
	 *
	 * @var Importer|null
	 */
	private ?Importer $importer;

	/**
	 * Takes the importer.
	 *
	 * @since 1.0.0
	 *
	 * @param Importer|null $importer Importer; without one a new Importer with the sections of this theme version.
	 */
	public function __construct( ?Importer $importer = null ) {
		$this->importer = $importer;
	}

	/**
	 * Registers every subcommand as its own command; runs on cli_init.
	 *
	 * @since 1.0.0
	 *
	 * @param self|null $command Instance whose methods run the subcommands; without one a new instance.
	 * @return void
	 */
	public static function register( ?self $command = null ): void {
		$command = $command ?? new self();
		WP_CLI::add_command( self::NAME . ' export', array( $command, 'export' ) );
		WP_CLI::add_command( self::NAME . ' import', array( $command, 'import' ) );
		WP_CLI::add_command( self::NAME . ' backup list', array( $command, 'backup_list' ) );
		WP_CLI::add_command( self::NAME . ' backup create', array( $command, 'backup_create' ) );
		WP_CLI::add_command( self::NAME . ' backup restore', array( $command, 'backup_restore' ) );
		WP_CLI::add_command( self::NAME . ' log', array( $command, 'log' ) );
	}

	/**
	 * Exports the settings as JSON file.
	 *
	 * Writes to standard output unless --file names a file. Sensitive keys are
	 * left out and named in the file; the export is logged.
	 *
	 * ## OPTIONS
	 *
	 * [--file=<path>]
	 * : File to write; without it the JSON goes to standard output.
	 *
	 * [--force]
	 * : Replace an existing file.
	 *
	 * [--sections=<sections>]
	 * : Comma-separated sections; default: settings,modules.
	 *
	 * [--lang=<codes>]
	 * : Comma-separated language codes; default: every active language.
	 *
	 * ## EXAMPLES
	 *
	 *     wp creationell-theme settings export --file=settings.json --user=admin
	 *     wp creationell-theme settings export --sections=settings --lang=de --user=admin > de.json
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments; none.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function export( array $args, array $assoc_args ): void {
		unset( $args );
		$user_id = self::user();
		$path    = self::string_arg( $assoc_args, 'file' );
		if ( null !== $path ) {
			if ( is_dir( $path ) ) {
				self::fail( sprintf( 'Cannot write %s: it is a folder.', $path ) );
			}
			if ( file_exists( $path ) && empty( $assoc_args['force'] ) ) {
				self::fail( sprintf( 'The file %s exists. Use --force to replace it.', $path ) );
			}
		}
		$request = self::attempt( static fn(): Export_Request => new Export_Request( self::sections( $assoc_args ), self::langs( $assoc_args ), $user_id, 'cli' ) );
		$json    = self::attempt( static fn(): string => Json_Codec::encode( Exporter::instance()->export( $request )->to_array() ) );
		if ( null === $path ) {
			WP_CLI::line( rtrim( $json, "\n" ) );
			return;
		}
		self::attempt( static fn() => Atomic_File_Writer::write( $path, $json ) );
		WP_CLI::success( sprintf( 'Exported the settings to %s.', $path ) );
	}

	/**
	 * Imports a settings file after a preview.
	 *
	 * The file is checked like an upload (at most 1 MB, a name ending in .json,
	 * no part .php), migrated and planned; the plan shows every key and module.
	 * Before it writes, the command backs up the touched sections. Rejected
	 * values stay as they are and are listed; --strict imports nothing then.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Path of the JSON file.
	 *
	 * [--sections=<sections>]
	 * : Comma-separated sections; default: every section of the file.
	 *
	 * [--lang=<codes>]
	 * : Comma-separated language codes; default: every active language.
	 *
	 * [--dry-run]
	 * : Show the plan and write nothing.
	 *
	 * [--strict]
	 * : Import nothing when a value is rejected.
	 *
	 * [--yes]
	 * : Answer the question with yes and confirm the module switches.
	 *
	 * [--format=<format>]
	 * : Output: the preview table, or the report as JSON; the question without --yes always shows the table first.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp creationell-theme settings import settings.json --dry-run --user=admin
	 *     wp creationell-theme settings import settings.json --yes --format=json --user=admin
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments: the path.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function import( array $args, array $assoc_args ): void {
		$user_id = self::user();
		$format  = self::format( $assoc_args, self::IMPORT_FORMATS );
		$path    = $args[0] ?? '';
		if ( '' === $path ) {
			self::fail( 'Name the JSON file to import.' );
		}
		$json   = self::read_file( $path );
		$parsed = self::attempt( static fn(): array => Importer::parse( $json ) );
		$this->run_import( $parsed['file'], $parsed['migrated'], $assoc_args, $user_id, 'import', $format );
	}

	/**
	 * Lists the backups, newest first, without their data.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp creationell-theme settings backup list --format=json --user=admin
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments; none.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function backup_list( array $args, array $assoc_args ): void {
		unset( $args );
		self::user();
		$format  = self::format( $assoc_args, self::LIST_FORMATS );
		$backups = Backup_Store::instance()->all();
		if ( 'json' === $format ) {
			self::json( $backups );
			return;
		}
		$items = array();
		foreach ( $backups as $backup ) {
			$items[] = array(
				'id'         => $backup['id'],
				'created_at' => $backup['created_at'],
				'reason'     => $backup['reason'],
				'sections'   => implode( ',', $backup['sections'] ),
				'user_id'    => $backup['user_id'],
			);
		}
		\WP_CLI\Utils\format_items( 'table', $items, self::BACKUP_FIELDS );
	}

	/**
	 * Backs up the settings now, in every active language.
	 *
	 * The store keeps the five newest backups.
	 *
	 * ## OPTIONS
	 *
	 * [--sections=<sections>]
	 * : Comma-separated sections; default: settings,modules.
	 *
	 * ## EXAMPLES
	 *
	 *     wp creationell-theme settings backup create --user=admin
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments; none.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function backup_create( array $args, array $assoc_args ): void {
		unset( $args );
		$user_id  = self::user();
		$sections = array_values( array_intersect( self::sections( $assoc_args ), Sections::ids() ) );
		if ( array() === $sections ) {
			self::fail_transfer( new Transfer_Exception( Transfer_Error::NOTHING_SELECTED ) );
		}
		$id = self::attempt( static fn(): string => Backup_Store::instance()->create( $sections, 'manual', $user_id, 'cli' ) );
		WP_CLI::success( sprintf( 'Created backup %s.', $id ) );
	}

	/**
	 * Restores a backup after a preview, with a backup of the current state first.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Backup ID, see "backup list".
	 *
	 * [--dry-run]
	 * : Show the plan and write nothing.
	 *
	 * [--yes]
	 * : Answer the question with yes and confirm the module switches.
	 *
	 * [--format=<format>]
	 * : Output: the preview table, or the report as JSON; the question without --yes always shows the table first.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp creationell-theme settings backup restore 20260925-100000-a1b2c3 --dry-run --user=admin
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments: the backup ID.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function backup_restore( array $args, array $assoc_args ): void {
		$user_id = self::user();
		$format  = self::format( $assoc_args, self::IMPORT_FORMATS );
		$id      = $args[0] ?? '';
		if ( '' === $id ) {
			self::fail( 'Name the backup ID, see wp creationell-theme settings backup list.' );
		}
		$file = self::attempt( static fn(): Transfer_File => Backup_Store::instance()->get( $id ) );
		$this->run_import( $file, array(), array_diff_key( $assoc_args, array_flip( array( 'sections', 'lang', 'strict' ) ) ), $user_id, 'restore', $format );
	}

	/**
	 * Shows the transfer log, newest first; it holds no values.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp creationell-theme settings log --user=admin
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments; none.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function log( array $args, array $assoc_args ): void {
		unset( $args );
		self::user();
		$format  = self::format( $assoc_args, self::LIST_FORMATS );
		$entries = array();
		foreach ( Transfer_Log::instance()->all() as $entry ) {
			$entry['time'] = gmdate( 'Y-m-d\TH:i:s\Z', $entry['time'] );
			$entries[]     = $entry;
		}
		if ( 'json' === $format ) {
			self::json( $entries );
			return;
		}
		$items = array();
		foreach ( $entries as $entry ) {
			$counts = array();
			foreach ( $entry['counts'] as $name => $count ) {
				$counts[] = $name . '=' . $count;
			}
			$items[] = array(
				'time'      => $entry['time'],
				'action'    => $entry['action'],
				'channel'   => $entry['channel'],
				'user_id'   => $entry['user_id'],
				'sections'  => implode( ',', $entry['sections'] ),
				'langs'     => implode( ',', $entry['langs'] ),
				'counts'    => implode( ',', $counts ),
				'backup_id' => $entry['backup_id'] ?? '',
			);
		}
		\WP_CLI\Utils\format_items( 'table', $items, self::LOG_FIELDS );
	}

	/**
	 * Plans an import or a restore, shows the plan, asks and applies it.
	 *
	 * @since 1.0.0
	 *
	 * @param Transfer_File        $file       File.
	 * @param array<int, string>   $migrated   Migration steps the file went through.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @param int                  $user_id    User ID.
	 * @param string               $reason     "import" or "restore".
	 * @param string               $format     "table" or "json".
	 * @phpstan-param list<string> $migrated
	 * @return void
	 */
	private function run_import( Transfer_File $file, array $migrated, array $assoc_args, int $user_id, string $reason, string $format ): void {
		$importer = $this->importer ?? new Importer();
		$dry_run  = ! empty( $assoc_args['dry-run'] );
		$strict   = ! empty( $assoc_args['strict'] );
		$sections = isset( $assoc_args['sections'] ) ? self::sections( $assoc_args ) : $file->sections;
		$langs    = self::langs( $assoc_args );
		$plan     = self::attempt( static fn(): Import_Plan => $importer->plan( $file, new Import_Options( $sections, $langs, $user_id, 'cli', false, array(), $strict, $dry_run, $reason ), $migrated ) );
		// Nobody answers the question without the plan: it shows in every format when the command asks; JSON stays pure with --yes or --dry-run.
		$asks = ! $dry_run && empty( $assoc_args['yes'] ) && $plan->has_writes( true );
		if ( 'table' === $format || $asks ) {
			self::show_plan( $plan );
		}

		// The person saw the plan: --yes or the answer confirms its module switches with the counted live contents.
		$confirmed = self::attempt( static fn(): Import_Options => new Import_Options( $sections, $langs, $user_id, 'cli', true, $plan->seen_live, $strict, $dry_run, $reason ) );
		if ( $dry_run ) {
			self::show_report( self::attempt( static fn(): Import_Report => $importer->apply( $plan, $confirmed ) ), $format, 'Dry run: nothing was written.' );
			return;
		}
		if ( ! $plan->has_writes( true ) ) {
			self::show_report( Import_Report::from_plan( $plan, false, null ), $format, 'Nothing to import: every value is unchanged, skipped or rejected.' );
			return;
		}
		WP_CLI::confirm( self::question( $plan, $reason ), $assoc_args );
		$report = self::attempt( static fn(): Import_Report => $importer->apply( $plan, $confirmed ) );
		self::show_report(
			$report,
			$format,
			sprintf(
				'%1$s %2$d %6$s; %3$d skipped, %4$d rejected. Backup before the change: %5$s.',
				'restore' === $reason ? 'Restored' : 'Imported',
				$report->counts['write'],
				$report->counts['skip'],
				$report->counts['reject'],
				$report->backup_id ?? 'none',
				1 === $report->counts['write'] ? 'change' : 'changes'
			)
		);
	}

	/**
	 * Runs a step of the transfer; stops with exit code 1 when it throws.
	 *
	 * @since 1.0.0
	 *
	 * @template T
	 * @param Closure $step Step.
	 * @phpstan-param Closure(): T $step
	 * @return mixed Result of the step.
	 * @phpstan-return T
	 */
	private static function attempt( Closure $step ): mixed {
		try {
			return $step();
		} catch ( Transfer_Exception $exception ) {
			self::fail_transfer( $exception );
		} catch ( LogicException | RuntimeException | JsonException $exception ) {
			self::fail( $exception->getMessage() );
		}
	}

	/**
	 * Prints the preview table and the warnings of a plan.
	 *
	 * @since 1.0.0
	 *
	 * @param Import_Plan $plan Plan.
	 * @return void
	 */
	private static function show_plan( Import_Plan $plan ): void {
		$items = array();
		foreach ( $plan->rows as $row ) {
			$items[] = array(
				'section'  => $row->section,
				'key'      => $row->key,
				'lang'     => $row->lang ?? '',
				'current'  => self::cell( $row->current ),
				'incoming' => self::cell( $row->incoming ),
				'status'   => $row->status->value,
				'message'  => $row->message,
			);
		}
		\WP_CLI\Utils\format_items( 'table', $items, self::PLAN_FIELDS );
		foreach ( $plan->warnings as $warning ) {
			WP_CLI::warning( self::warning_text( $warning ) );
		}
		if ( $plan->needs_confirmation ) {
			WP_CLI::warning( 'Module switches need a confirmation: ' . self::live_list( $plan->seen_live ) . '. The contents stay; while a module is off, its blocks show nothing.' );
		}
	}

	/**
	 * Prints the report: as JSON alone, or as a line with the rejected rows as warnings.
	 *
	 * @since 1.0.0
	 *
	 * @param Import_Report $report  Report.
	 * @param string        $format  "table" or "json".
	 * @param string        $summary Line for the table format.
	 * @return void
	 */
	private static function show_report( Import_Report $report, string $format, string $summary ): void {
		if ( 'json' === $format ) {
			self::json( $report->to_array() );
			return;
		}
		foreach ( $report->rows as $row ) {
			if ( 'reject' === $row->group() ) {
				WP_CLI::warning( sprintf( 'Rejected %1$s%2$s (%3$s): %4$s', $row->key, null === $row->lang ? '' : ' [' . $row->lang . ']', $row->status->value, $row->message ) );
			}
		}
		if ( $report->written ) {
			WP_CLI::success( $summary );
			return;
		}
		WP_CLI::line( $summary );
	}

	/**
	 * Returns the question before an import or a restore writes.
	 *
	 * @since 1.0.0
	 *
	 * @param Import_Plan $plan   Plan.
	 * @param string      $reason "import" or "restore".
	 * @return string Question.
	 */
	private static function question( Import_Plan $plan, string $reason ): string {
		$writes   = $plan->counts()['write'] + count( $plan->seen_live );
		$question = sprintf( '%1$s %2$d %3$s of the file? A backup is made first.', 'restore' === $reason ? 'Restore' : 'Import', $writes, 1 === $writes ? 'change' : 'changes' );
		if ( $plan->needs_confirmation ) {
			$question .= ' This also confirms the module switches: ' . self::live_list( $plan->seen_live ) . '.';
		}
		return $question;
	}

	/**
	 * Lists module slugs with their live contents, e.g. "consent (3 live contents)".
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, int> $seen_live Live contents by module slug.
	 * @return string List.
	 */
	private static function live_list( array $seen_live ): string {
		$parts = array();
		foreach ( $seen_live as $slug => $live ) {
			$parts[] = sprintf( '%1$s (%2$d live %3$s)', $slug, $live, 1 === $live ? 'content' : 'contents' );
		}
		return implode( ', ', $parts );
	}

	/**
	 * Returns the text of a warning code of the plan.
	 *
	 * @since 1.0.0
	 *
	 * @param string $warning Code, e.g. "line_differs" or "migrated:1->2".
	 * @return string Text with the code.
	 */
	private static function warning_text( string $warning ): string {
		if ( str_starts_with( $warning, 'migrated:' ) ) {
			return sprintf( '%s: the file was converted from an older format.', $warning );
		}
		return match ( $warning ) {
			'line_differs' => 'line_differs: the file comes from another Bootstrap line; the line itself is never imported, and keys are taken over line-neutral.',
			'theme_newer'  => 'theme_newer: the file comes from a newer theme version; keys this version does not know are skipped.',
			default        => $warning,
		};
	}

	/**
	 * Reads an import file with the checks of an upload: size, name, readability.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Path.
	 * @return string Content; stops with too_large, wrong_extension or an unreadable file.
	 */
	private static function read_file( string $path ): string {
		if ( ! is_file( $path ) || ! is_readable( $path ) ) {
			self::fail( sprintf( 'The file %s does not exist or is not readable.', $path ) );
		}
		$size = filesize( $path );
		if ( false === $size || $size > Upload_Validator::MAX_BYTES ) {
			self::fail_transfer( new Transfer_Exception( Transfer_Error::TOO_LARGE ) );
		}
		if ( ! Upload_Validator::is_json_name( basename( $path ) ) ) {
			self::fail_transfer( new Transfer_Exception( Transfer_Error::WRONG_EXTENSION ) );
		}
		try {
			$handle  = new SplFileObject( $path, 'rb' );
			$content = 0 === $size ? '' : $handle->fread( Upload_Validator::MAX_BYTES + 1 );
		} catch ( RuntimeException ) {
			$content = false;
		}
		if ( false === $content ) {
			self::fail( sprintf( 'The file %s does not exist or is not readable.', $path ) );
		}
		if ( strlen( $content ) > Upload_Validator::MAX_BYTES ) {
			self::fail_transfer( new Transfer_Exception( Transfer_Error::TOO_LARGE ) );
		}
		return $content;
	}

	/**
	 * Returns the ID of the WP-CLI user; stops without a user who may import and export.
	 *
	 * WP-CLI runs without a user unless the global parameter --user names one.
	 *
	 * @since 1.0.0
	 *
	 * @return int User ID.
	 */
	private static function user(): int {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 || ! user_can( $user_id, Capabilities::IMPORT_EXPORT ) ) {
			self::fail( self::USER_ERROR );
		}
		return $user_id;
	}

	/**
	 * Reads the sections of --sections; without it every section of this theme version.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return array<int, string> Section IDs; unknown ones stay, so the transfer reports them.
	 * @phpstan-return list<string>
	 */
	private static function sections( array $assoc_args ): array {
		return self::codes( $assoc_args, 'sections' ) ?? Sections::ids();
	}

	/**
	 * Reads the language codes of --lang; without it null for every active language.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return array<int, string>|null Language codes.
	 * @phpstan-return list<string>|null
	 */
	private static function langs( array $assoc_args ): ?array {
		return self::codes( $assoc_args, 'lang' );
	}

	/**
	 * Splits a comma-separated argument into codes.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @param string               $name       Argument name.
	 * @return array<int, string>|null Codes, lower case and without blanks; null without the argument.
	 * @phpstan-return list<string>|null
	 */
	private static function codes( array $assoc_args, string $name ): ?array {
		$value = self::string_arg( $assoc_args, $name );
		if ( null === $value ) {
			return null;
		}
		$codes = array();
		foreach ( explode( ',', $value ) as $code ) {
			$code = strtolower( trim( $code ) );
			if ( '' !== $code && ! in_array( $code, $codes, true ) ) {
				$codes[] = $code;
			}
		}
		return $codes;
	}

	/**
	 * Returns a string argument, or null without it.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @param string               $name       Argument name.
	 * @return string|null Value.
	 */
	private static function string_arg( array $assoc_args, string $name ): ?string {
		$value = $assoc_args[ $name ] ?? null;
		return is_string( $value ) ? $value : null;
	}

	/**
	 * Reads and checks the format argument; stops for an unknown format.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @param array<int, string>   $formats    Allowed formats; the first is the default.
	 * @return string Format.
	 */
	private static function format( array $assoc_args, array $formats ): string {
		$format = $assoc_args['format'] ?? $formats[0];
		if ( ! is_string( $format ) || ! in_array( $format, $formats, true ) ) {
			self::fail( sprintf( 'Unknown format; use one of: %s.', implode( ', ', $formats ) ) );
		}
		return $format;
	}

	/**
	 * Returns a value as table cell: strings as they are, everything else as JSON.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return string Cell.
	 */
	private static function cell( mixed $value ): string {
		if ( is_string( $value ) ) {
			return $value;
		}
		$json = wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		return false === $json ? '' : $json;
	}

	/**
	 * Prints a value as one line of JSON.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return void
	 */
	private static function json( mixed $value ): void {
		$json = wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		WP_CLI::line( false === $json ? 'null' : $json );
	}

	/**
	 * Stops with the message of a transfer error and its code, exit code 1.
	 *
	 * @since 1.0.0
	 *
	 * @param Transfer_Exception $exception Exception.
	 * @return never
	 */
	private static function fail_transfer( Transfer_Exception $exception ): never {
		self::fail( sprintf( '%1$s (%2$s)', $exception->error->message(), html_entity_decode( $exception->getMessage(), ENT_QUOTES, 'UTF-8' ) ) );
	}

	/**
	 * Stops with an error, exit code 1.
	 *
	 * WP_CLI::error() only prints here; the halt ends the command, so the
	 * exit code does not depend on the default of error().
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Message.
	 * @return never
	 */
	private static function fail( string $message ): never {
		WP_CLI::error( $message, false );
		WP_CLI::halt( 1 );
	}
}
