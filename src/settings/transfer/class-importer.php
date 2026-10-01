<?php
/**
 * Import of the theme settings.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

use Creationell\WpTheme\Core\Capabilities;
use Creationell\WpTheme\Settings\Bootstrap_Line;
use Creationell\WpTheme\Settings\Language;
use Creationell\WpTheme\Settings\Setter;
use JsonException;
use RuntimeException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Plans and applies the import of a transfer file through the setter and the module switcher.
 *
 * The plan writes nothing: plan() checks the capability creationell_wp_theme_import_export,
 * refuses "All languages" and the multilingual mode of ACF Extended, plans
 * every chosen section of the file and adds the warnings
 * "migrated:<from>-><to>", "line_differs" and "theme_newer". Sections of the
 * file this theme version does not know get one row "section_unknown".
 *
 * The apply step plans again and stops without writing when a module switch is not
 * confirmed or the person saw fewer live contents than counted now
 * (confirmation_required), or when a row is rejected in strict mode
 * (strict_rejected). A dry run and a plan without writes return the report of
 * the plan. Otherwise it backs up the touched sections, switches the modules,
 * writes the settings per language, commits the setter once, logs the import
 * and fires creationell_wp_theme_settings_imported. Rejected rows stay as they
 * are and are listed in the report.
 *
 * Example:
 *
 *     $importer = new Importer();
 *     $parsed   = Importer::parse( $json );
 *     $options  = new Import_Options( array( 'settings', 'modules' ), null, get_current_user_id(), 'cli' );
 *     $plan     = $importer->plan( $parsed['file'], $options, $parsed['migrated'] );
 *     $report   = $importer->apply( $plan, $options );
 *
 * @since 1.0.0
 */
final class Importer {

	/**
	 * Action after an import that wrote.
	 *
	 * @since 1.0.0
	 */
	public const IMPORTED_ACTION = 'creationell_wp_theme_settings_imported';

	/**
	 * Sections by ID; null for the ones of Sections::all() with the setter.
	 *
	 * @var array<string, Section_Interface>|null
	 */
	private ?array $sections;

	/**
	 * Setter that is committed after the sections wrote; null for the shared one.
	 *
	 * @var Setter|null
	 */
	private ?Setter $setter;

	/**
	 * Backup store; null for the shared one.
	 *
	 * @var Backup_Store|null
	 */
	private ?Backup_Store $store;

	/**
	 * Log; null for the shared one.
	 *
	 * @var Transfer_Log|null
	 */
	private ?Transfer_Log $log;

	/**
	 * Takes the sections, the setter, the backup store and the log.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, Section_Interface>|null $sections Sections by ID; without them the ones of Sections::all() with the setter.
	 * @param Setter|null                           $setter   Setter the section "settings" writes through; without one the shared setter.
	 * @param Backup_Store|null                     $store    Backup store; without one the shared store.
	 * @param Transfer_Log|null                     $log      Log; without one the shared log.
	 */
	public function __construct( ?array $sections = null, ?Setter $setter = null, ?Backup_Store $store = null, ?Transfer_Log $log = null ) {
		$this->sections = $sections;
		$this->setter   = $setter;
		$this->store    = $store;
		$this->log      = $log;
	}

	/**
	 * Decodes, migrates and checks the JSON text of a transfer file.
	 *
	 * @since 1.0.0
	 *
	 * @param string        $json     JSON text.
	 * @param Migrator|null $migrator Migrator; without one the steps of this theme version.
	 * @return array{file: Transfer_File, migrated: list<string>} File and the migration steps, e.g. "1->2".
	 * @throws Transfer_Exception With an error of Json_Codec, Migrator or Transfer_File.
	 */
	public static function parse( string $json, ?Migrator $migrator = null ): array {
		$migrated = ( $migrator ?? new Migrator() )->migrate( Json_Codec::decode( $json ) );
		return array(
			'file'     => Transfer_File::from_array( $migrated['data'] ),
			'migrated' => array_values( $migrated['applied'] ),
		);
	}

	/**
	 * Plans the import without writing.
	 *
	 * @since 1.0.0
	 *
	 * @param Transfer_File      $file     File.
	 * @param Import_Options     $options  Options.
	 * @param array<int, string> $migrated Migration steps the file went through, e.g. "1->2".
	 * @phpstan-param list<string> $migrated
	 * @return Import_Plan Plan.
	 * @throws Transfer_Exception With forbidden, language_all, acfe_multilang or nothing_selected.
	 */
	public function plan( Transfer_File $file, Import_Options $options, array $migrated = array() ): Import_Plan {
		if ( ! user_can( $options->user_id, Capabilities::IMPORT_EXPORT ) ) {
			Transfer_Exception::raise( Transfer_Error::FORBIDDEN );
		}
		$language = Language::instance();
		if ( Language::ALL === $language->current() ) {
			Transfer_Exception::raise( Transfer_Error::LANGUAGE_ALL );
		}
		if ( $language->acfe_multilang_active() ) {
			Transfer_Exception::raise( Transfer_Error::ACFE_MULTILANG );
		}
		$selected = array_values( array_intersect( $file->sections, $options->sections ) );
		if ( array() === $selected ) {
			Transfer_Exception::raise( Transfer_Error::NOTHING_SELECTED );
		}

		$sections = $this->sections();
		$rows     = array();
		foreach ( $selected as $id ) {
			if ( isset( $sections[ $id ] ) ) {
				$rows = array_merge( $rows, $sections[ $id ]->plan( $file, $options ) );
				continue;
			}
			$rows[] = new Plan_Row( $id, $id, null, null, null, Row_Status::SECTION_UNKNOWN, __( 'This theme version does not know this part of the file.', 'creationell-wp-theme' ) );
		}

		$modules   = array();
		$seen_live = array();
		foreach ( $rows as $row ) {
			if ( null === $row->switch_result ) {
				continue;
			}
			$slug      = Transfer_File::module_slug( $row->key );
			$modules[] = array(
				'slug'   => $slug,
				'result' => $row->switch_result,
			);
			if ( Row_Status::MODULE_NEEDS_CONFIRMATION === $row->status ) {
				$seen_live[ $slug ] = null === $row->switch_result->scan ? 0 : $row->switch_result->scan->total_live();
			}
		}
		return new Import_Plan( $file, $rows, $modules, $this->warnings( $file, $migrated ), array() !== $seen_live, $seen_live );
	}

	/**
	 * Applies an import after a fresh plan.
	 *
	 * @since 1.0.0
	 *
	 * @param Import_Plan    $plan    Plan the person saw.
	 * @param Import_Options $options Options with the confirmation.
	 * @return Import_Report Report.
	 * @throws Transfer_Exception With an error of plan(), confirmation_required or strict_rejected; nothing is written then.
	 * @throws RuntimeException When the backup cannot be stored; nothing is written then.
	 * @throws JsonException When the backup or the file cannot be encoded.
	 */
	public function apply( Import_Plan $plan, Import_Options $options ): Import_Report {
		$fresh = $this->plan( $plan->file, $options, $plan->migrated() );
		self::check( $fresh, $options );
		if ( $options->dry_run || ! $fresh->has_writes( $options->confirm_modules ) ) {
			return Import_Report::from_plan( $fresh, false, null );
		}

		$touched = array();
		foreach ( $fresh->rows as $row ) {
			if ( 'write' === $row->group() || Row_Status::MODULE_NEEDS_CONFIRMATION === $row->status ) {
				$touched[ $row->section ] = true;
			}
		}
		$backup_id = ( $this->store ?? Backup_Store::instance() )->create( array_keys( $touched ), $options->reason, $options->user_id, $options->channel );

		$rows = $fresh->rows;
		foreach ( $this->apply_order() as $id => $section ) {
			$indexes = array_keys( array_filter( $rows, static fn( Plan_Row $row ): bool => $id === $row->section ) );
			if ( array() === $indexes ) {
				continue;
			}
			$applied = $section->apply( array_values( array_intersect_key( $rows, array_flip( $indexes ) ) ), $options );
			foreach ( $indexes as $position => $index ) {
				$rows[ $index ] = $applied[ $position ] ?? $rows[ $index ];
			}
		}
		( $this->setter ?? Setter::instance() )->commit();

		$written = false;
		foreach ( $rows as $row ) {
			$written = $written || 'write' === $row->group();
		}
		$report = Import_Report::from_plan( $fresh, $written, $backup_id, array_values( $rows ) );
		( $this->log ?? Transfer_Log::instance() )->add(
			$options->reason,
			$options->channel,
			$options->user_id,
			self::row_sections( $rows ),
			self::row_langs( $rows ),
			$report->counts,
			$backup_id,
			hash( 'sha256', Json_Codec::encode( $plan->file->to_array() ) )
		);

		/**
		 * Fires after an import or a restore wrote settings or module states.
		 *
		 * Dry runs and imports without anything to write do not fire it.
		 *
		 * @since 1.0.0
		 *
		 * @param Import_Report $report Report with the backup ID and every row.
		 */
		do_action( 'creationell_wp_theme_settings_imported', $report );
		return $report;
	}

	/**
	 * Stops an import whose module switches are not confirmed or that rejects rows in strict mode.
	 *
	 * @since 1.0.0
	 *
	 * @param Import_Plan    $plan    Fresh plan.
	 * @param Import_Options $options Options.
	 * @return void
	 * @throws Transfer_Exception With confirmation_required or strict_rejected.
	 */
	private static function check( Import_Plan $plan, Import_Options $options ): void {
		if ( $plan->needs_confirmation ) {
			if ( ! $options->confirm_modules ) {
				Transfer_Exception::raise( Transfer_Error::CONFIRMATION_REQUIRED );
			}
			foreach ( $plan->seen_live as $slug => $live ) {
				if ( $options->seen( $slug ) < $live ) {
					Transfer_Exception::raise( Transfer_Error::CONFIRMATION_REQUIRED, array( 'detail' => $slug ) );
				}
			}
		}
		if ( ! $options->strict ) {
			return;
		}
		foreach ( $plan->rows as $row ) {
			if ( 'reject' === $row->group() && Row_Status::MODULE_NEEDS_CONFIRMATION !== $row->status ) {
				Transfer_Exception::raise( Transfer_Error::STRICT_REJECTED, array( 'detail' => $row->key ) );
			}
		}
	}

	/**
	 * Returns the warnings of a file.
	 *
	 * @since 1.0.0
	 *
	 * @param Transfer_File      $file     File.
	 * @param array<int, string> $migrated Migration steps.
	 * @return array<int, string> Warning codes.
	 * @phpstan-return list<string>
	 */
	private function warnings( Transfer_File $file, array $migrated ): array {
		$warnings = array();
		foreach ( $migrated as $step ) {
			$warnings[] = 'migrated:' . $step;
		}
		if ( Bootstrap_Line::instance()->active() !== $file->bootstrap_line ) {
			$warnings[] = 'line_differs';
		}
		if ( version_compare( $file->theme_version, CREATIONELL_WP_THEME_VERSION, '>' ) ) {
			$warnings[] = 'theme_newer';
		}
		return $warnings;
	}

	/**
	 * Returns the sections in the order they are written: the modules first, then the others.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, Section_Interface> Sections by ID.
	 */
	private function apply_order(): array {
		$sections = $this->sections();
		if ( isset( $sections[ Modules_Section::ID ] ) ) {
			$sections = array( Modules_Section::ID => $sections[ Modules_Section::ID ] ) + $sections;
		}
		return $sections;
	}

	/**
	 * Returns the sections.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, Section_Interface> Sections by ID.
	 */
	private function sections(): array {
		return $this->sections ?? Sections::all( $this->setter );
	}

	/**
	 * Returns the sections of the rows.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, Plan_Row> $rows Rows.
	 * @return array<int, string> Section IDs in row order.
	 * @phpstan-return list<string>
	 */
	private static function row_sections( array $rows ): array {
		return array_values( array_unique( array_map( static fn( Plan_Row $row ): string => $row->section, $rows ) ) );
	}

	/**
	 * Returns the languages of the rows.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, Plan_Row> $rows Rows.
	 * @return array<int, string> Language codes in row order.
	 * @phpstan-return list<string>
	 */
	private static function row_langs( array $rows ): array {
		$langs = array();
		foreach ( $rows as $row ) {
			if ( null !== $row->lang && ! in_array( $row->lang, $langs, true ) ) {
				$langs[] = $row->lang;
			}
		}
		return $langs;
	}
}
