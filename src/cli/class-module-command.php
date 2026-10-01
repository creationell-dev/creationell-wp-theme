<?php
/**
 * WP-CLI command "wp creationell-theme module".
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Cli;

use Creationell\WpTheme\Core\Capabilities;
use Creationell\WpTheme\Core\Scan_Result;
use Creationell\WpTheme\Modules\Module_State;
use Creationell\WpTheme\Modules\Module_Switcher;
use Creationell\WpTheme\Modules\Switch_Request;
use Creationell\WpTheme\Modules\Switch_Result;
use WP_CLI;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Lists, shows, counts and switches the theme modules.
 *
 * "list", "status" and "scan" only read. "enable" and "disable" switch through
 * Module_Switcher (channel "cli") and need --user=<login> of a user with the
 * capability creationell_wp_theme_manage_modules; without it they stop with
 * exit code 1. Switching off a module whose blocks live contents use, or a
 * module with warn_before_off, needs --yes; without it nothing changes and the
 * command exits with 1. "disable" goes to "hidden" when the module knows it, to
 * "off" otherwise; --state=off switches off directly, also from "active". The
 * command also adds the section "modules" to "wp creationell-theme doctor".
 *
 * @since 1.0.0
 */
final class Module_Command {

	/**
	 * Command name below the theme namespace.
	 *
	 * @since 1.0.0
	 */
	public const NAME = 'creationell-theme module';

	/**
	 * Fields of the list.
	 *
	 * @since 1.0.0
	 */
	public const LIST_FIELDS = array( 'slug', 'title', 'configured', 'effective', 'origin', 'reason' );

	/**
	 * Formats of the list.
	 *
	 * @since 1.0.0
	 */
	public const LIST_FORMATS = array( 'table', 'json', 'csv', 'yaml', 'count' );

	/**
	 * Formats of the status.
	 *
	 * @since 1.0.0
	 */
	public const STATUS_FORMATS = array( 'table', 'json' );

	/**
	 * Formats of the scan.
	 *
	 * @since 1.0.0
	 */
	public const SCAN_FORMATS = array( 'table', 'json' );

	/**
	 * Target states of enable.
	 *
	 * @since 1.0.0
	 */
	public const ENABLE_STATES = array( 'active', 'hidden' );

	/**
	 * Target states of disable.
	 *
	 * @since 1.0.0
	 */
	public const DISABLE_STATES = array( 'hidden', 'off' );

	/**
	 * Key of the doctor section.
	 *
	 * @since 1.0.0
	 */
	public const DOCTOR_SECTION = 'modules';

	/**
	 * Message of a writing subcommand without the capability.
	 *
	 * @since 1.0.0
	 */
	public const USER_ERROR = 'This command changes theme modules. Run it with --user=<login> of a user with the capability creationell_wp_theme_manage_modules.';

	/**
	 * Message when switching off needs a confirmation.
	 *
	 * @since 1.0.0
	 */
	public const CONFIRM_ERROR = 'Nothing changed. Re-run with --yes to confirm.';

	/**
	 * Registers the command and the doctor section "modules"; runs on cli_init.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		WP_CLI::add_command( self::NAME, self::class );
		if ( ! in_array( self::DOCTOR_SECTION, Doctor_Command::section_keys(), true ) ) {
			Doctor_Command::add_section( self::DOCTOR_SECTION, array( self::class, 'doctor_section' ) );
		}
	}

	/**
	 * Collects the doctor section: configured and effective state, origin, reason and older plugins per module.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Values by module slug; "warnings" names modules configured on that stay off.
	 */
	public static function doctor_section(): array {
		$state    = Module_State::instance();
		$catalog  = $state->catalog();
		$values   = array();
		$warnings = array();
		foreach ( $state->all() as $slug => $result ) {
			$manifest        = $catalog->manifest( $slug );
			$values[ $slug ] = array(
				'configured'     => $result['configured'],
				'effective'      => $result['state'],
				'origin'         => $result['origin'],
				'reason'         => $result['reason'],
				'legacy_plugins' => null === $manifest ? array() : $manifest->legacy_plugins,
			);
			if ( 'off' !== $result['configured'] && 'off' === $result['state'] ) {
				$warnings[] = sprintf( 'Module %1$s is configured %2$s but off: %3$s.', $slug, $result['configured'], $result['reason'] );
			}
		}
		if ( array() !== $warnings ) {
			$values['warnings'] = $warnings;
		}
		return $values;
	}

	/**
	 * Lists all known modules with configured and effective state, origin and reason.
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
	 *   - csv
	 *   - yaml
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp creationell-theme module list --format=json
	 *
	 * @subcommand list
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments; none.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function list_( array $args, array $assoc_args ): void {
		unset( $args );
		$format = self::format( $assoc_args, self::LIST_FORMATS );
		if ( null === $format ) {
			return;
		}
		$state   = Module_State::instance();
		$catalog = $state->catalog();
		$items   = array();
		foreach ( $state->all() as $slug => $result ) {
			$items[] = array(
				'slug'       => $slug,
				'title'      => $catalog->title( $slug ),
				'configured' => $result['configured'],
				'effective'  => $result['state'],
				'origin'     => $result['origin'],
				'reason'     => $result['reason'],
			);
		}
		\WP_CLI\Utils\format_items( $format, $items, self::LIST_FIELDS );
	}

	/**
	 * Shows the state of one module.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : Module slug, e.g. header-footer.
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
	 *     wp creationell-theme module status header-footer
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments: the slug.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function status( array $args, array $assoc_args ): void {
		$format = self::format( $assoc_args, self::STATUS_FORMATS );
		if ( null === $format ) {
			return;
		}
		$slug = $args[0] ?? '';
		if ( '' === $slug ) {
			WP_CLI::error( 'Name a module slug, e.g. header-footer.' );
			return;
		}
		$state   = Module_State::instance();
		$catalog = $state->catalog();
		if ( ! $catalog->is_known( $slug ) ) {
			WP_CLI::error( sprintf( 'Unknown module: %s. See wp creationell-theme module list.', $slug ) );
			return;
		}
		$manifest = $catalog->manifest( $slug );
		$status   = array(
			'slug'       => $slug,
			'title'      => $catalog->title( $slug ),
			'installed'  => null !== $manifest,
			'type'       => null === $manifest ? '' : $manifest->type,
			'states'     => $catalog->states( $slug ),
			'configured' => $state->configured( $slug ),
			'effective'  => $state->state( $slug ),
			'origin'     => $state->origin( $slug ),
			'reason'     => $state->reason( $slug ),
			'constant'   => Module_State::constant_name( $slug ),
			'error'      => $catalog->errors()[ $slug ] ?? '',
		);

		if ( 'json' === $format ) {
			$json = wp_json_encode( $status, JSON_UNESCAPED_SLASHES );
			WP_CLI::line( false === $json ? '{}' : $json );
			return;
		}
		$rows = array();
		foreach ( $status as $field => $value ) {
			if ( is_bool( $value ) ) {
				$value = $value ? 'true' : 'false';
			} elseif ( is_array( $value ) ) {
				$value = implode( ',', $value );
			}
			$rows[] = array(
				'field' => $field,
				'value' => $value,
			);
		}
		\WP_CLI\Utils\format_items( 'table', $rows, array( 'field', 'value' ) );
	}

	/**
	 * Switches a module on.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : Module slug, e.g. contact-form-7.
	 *
	 * [--state=<state>]
	 * : Target state.
	 * ---
	 * default: active
	 * options:
	 *   - active
	 *   - hidden
	 * ---
	 *
	 * [--dry-run]
	 * : Show what would change; change nothing.
	 *
	 * ## EXAMPLES
	 *
	 *     wp creationell-theme module enable contact-form-7 --user=admin
	 *     wp creationell-theme module enable post-lists --state=hidden --user=admin
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments: the slug.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function enable( array $args, array $assoc_args ): void {
		$slug = self::slug( $args );
		if ( null === $slug || ! self::may_switch() ) {
			return;
		}
		$target = $assoc_args['state'] ?? 'active';
		if ( ! is_string( $target ) || ! in_array( $target, self::ENABLE_STATES, true ) ) {
			WP_CLI::error( sprintf( 'Unknown state %s; use --state=active or --state=hidden.', is_string( $target ) ? $target : '' ) );
			return;
		}
		self::switch_module( $slug, $target, false, ! empty( $assoc_args['dry-run'] ) );
	}

	/**
	 * Switches a module to hidden or off.
	 *
	 * Without --state the module goes to hidden when it knows that state, to off
	 * otherwise. Switching off a module whose blocks live contents use, or a
	 * module that always asks before it goes off, needs --yes.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : Module slug, e.g. post-lists.
	 *
	 * [--state=<state>]
	 * : Target state; default hidden when the module knows it, otherwise off.
	 * ---
	 * options:
	 *   - hidden
	 *   - "off"
	 * ---
	 *
	 * [--yes]
	 * : Confirm the consequences of switching off.
	 *
	 * [--dry-run]
	 * : Show what would change; change nothing.
	 *
	 * ## EXAMPLES
	 *
	 *     wp creationell-theme module disable post-lists --user=admin
	 *     wp creationell-theme module disable post-lists --state=off --yes --user=admin
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments: the slug.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function disable( array $args, array $assoc_args ): void {
		$slug = self::slug( $args );
		if ( null === $slug || ! self::may_switch() ) {
			return;
		}
		$states = Module_Switcher::instance()->state()->catalog()->states( $slug );
		$target = $assoc_args['state'] ?? ( in_array( 'hidden', $states, true ) ? 'hidden' : 'off' );
		if ( ! is_string( $target ) || ! in_array( $target, self::DISABLE_STATES, true ) ) {
			WP_CLI::error( sprintf( 'Unknown state %s; use --state=hidden or --state=off.', is_string( $target ) ? $target : '' ) );
			return;
		}
		self::switch_module( $slug, $target, ! empty( $assoc_args['yes'] ), ! empty( $assoc_args['dry-run'] ) );
	}

	/**
	 * Counts the contents that use the blocks of a module; reads only.
	 *
	 * Live contents are published posts, drafts and the like; stored ones are
	 * revisions, posts in the trash and auto drafts.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : Module slug, e.g. post-lists.
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
	 *     wp creationell-theme module scan post-lists --format=json
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments: the slug.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function scan( array $args, array $assoc_args ): void {
		$format = self::format( $assoc_args, self::SCAN_FORMATS );
		$slug   = null === $format ? null : self::slug( $args );
		if ( null === $format || null === $slug ) {
			return;
		}
		$switcher = Module_Switcher::instance();
		$catalog  = $switcher->state()->catalog();
		if ( ! $catalog->is_known( $slug ) ) {
			WP_CLI::error( sprintf( 'Unknown module: %s. See wp creationell-theme module list.', $slug ) );
			return;
		}
		$manifest = $catalog->manifest( $slug );
		$scan     = $switcher->scanner()->count_blocks( null === $manifest ? array() : $manifest->blocks );
		if ( 'json' === $format ) {
			$json = wp_json_encode(
				array(
					'slug'         => $slug,
					'needles'      => $scan->needles,
					'rows'         => $scan->rows,
					'total_live'   => $scan->total_live(),
					'total_stored' => $scan->total_stored(),
				),
				JSON_UNESCAPED_SLASHES
			);
			WP_CLI::line( false === $json ? '{}' : $json );
			return;
		}
		self::print_rows( $scan );
		WP_CLI::line( sprintf( 'Total: %1$d live, %2$d stored.', $scan->total_live(), $scan->total_stored() ) );
	}

	/**
	 * Switches a module through the module switcher and reports the result.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug      Module slug.
	 * @param string $target    Target state.
	 * @param bool   $confirmed Whether --yes was given.
	 * @param bool   $dry_run   Whether --dry-run was given.
	 * @return void
	 */
	private static function switch_module( string $slug, string $target, bool $confirmed, bool $dry_run ): void {
		$switcher = Module_Switcher::instance();
		$state    = $switcher->state();
		$catalog  = $state->catalog();
		$result   = $switcher->switch( new Switch_Request( $slug, $target, 'cli', $confirmed, $dry_run ) );

		switch ( $result->status ) {
			case Switch_Result::DENIED:
				WP_CLI::error( self::USER_ERROR );
				return;
			case Switch_Result::INVALID:
				if ( ! $catalog->is_known( $slug ) ) {
					WP_CLI::error( sprintf( 'Unknown module: %s. See wp creationell-theme module list.', $slug ) );
				} elseif ( array() === $catalog->states( $slug ) ) {
					WP_CLI::error( sprintf( 'Module %s cannot be switched: its manifest is invalid.', $slug ) );
				} else {
					WP_CLI::error( sprintf( 'Module %1$s has no state %2$s; its states: %3$s.', $slug, $target, implode( ', ', $catalog->states( $slug ) ) ) );
				}
				return;
			case Switch_Result::LOCKED:
				$by = 'constant' === $state->origin( $slug ) ? 'the constant ' . Module_State::constant_name( $slug ) : 'the child theme';
				WP_CLI::error( sprintf( 'Module %1$s is locked to %2$s by %3$s; nothing changed.', $slug, $result->from, $by ) );
				return;
			case Switch_Result::UNCHANGED:
				WP_CLI::success( sprintf( 'Module %1$s is already %2$s.', $slug, $result->from ) );
				return;
		}

		self::print_scan( $slug, $result );
		if ( Switch_Result::NEEDS_CONFIRMATION === $result->status ) {
			self::print_warnings( $slug, $result->warnings );
			if ( $dry_run ) {
				WP_CLI::line( sprintf( 'Dry run: module %1$s would switch from %2$s to %3$s after a confirmation with --yes. Nothing changed.', $slug, $result->from, $result->to ) );
				return;
			}
			WP_CLI::error( self::CONFIRM_ERROR );
			return;
		}
		if ( Switch_Result::DRY_RUN === $result->status ) {
			WP_CLI::line( sprintf( 'Dry run: module %1$s would switch from %2$s to %3$s. Nothing changed.', $slug, $result->from, $result->to ) );
			return;
		}
		self::print_warnings( $slug, $result->warnings );
		WP_CLI::success( sprintf( 'Module %1$s switched from %2$s to %3$s.', $slug, $result->from, $result->to ) );
	}

	/**
	 * Prints the counted contents and why switching off asks for a confirmation.
	 *
	 * @since 1.0.0
	 *
	 * @param string        $slug   Module slug.
	 * @param Switch_Result $result Result with the scan.
	 * @return void
	 */
	private static function print_scan( string $slug, Switch_Result $result ): void {
		$scan = $result->scan;
		if ( null !== $scan && array() !== $scan->rows ) {
			self::print_rows( $scan );
		}
		$live = null === $scan ? 0 : $scan->total_live();
		if ( $live > 0 ) {
			WP_CLI::warning( sprintf( '%1$d live %2$s blocks of module %3$s; while it is off, the blocks show nothing. Nothing is deleted.', $live, 1 === $live ? 'content uses' : 'contents use', $slug ) );
		}
		$manifest = Module_Switcher::instance()->state()->catalog()->manifest( $slug );
		if ( 'off' === $result->to && null !== $manifest && $manifest->warn_before_off && Switch_Result::NEEDS_CONFIRMATION === $result->status ) {
			WP_CLI::warning( sprintf( 'Module %s asks for a confirmation before it goes off.', $slug ) );
		}
	}

	/**
	 * Prints the counts per location as a table.
	 *
	 * @since 1.0.0
	 *
	 * @param Scan_Result $scan Scan.
	 * @return void
	 */
	private static function print_rows( Scan_Result $scan ): void {
		\WP_CLI\Utils\format_items( 'table', $scan->rows, array( 'location', 'live', 'stored' ) );
	}

	/**
	 * Prints the warnings of a switch.
	 *
	 * @since 1.0.0
	 *
	 * @param string             $slug     Module slug.
	 * @param array<int, string> $warnings Warnings of the switch.
	 * @return void
	 */
	private static function print_warnings( string $slug, array $warnings ): void {
		foreach ( $warnings as $warning ) {
			if ( str_starts_with( $warning, Switch_Result::WARNING_DEPENDENCY_MISSING ) ) {
				WP_CLI::warning( sprintf( 'Module %1$s stays off: %2$s.', $slug, substr( $warning, strlen( Switch_Result::WARNING_DEPENDENCY_MISSING ) ) ) );
			} elseif ( Switch_Result::WARNING_CALLBACK_FAILED === $warning ) {
				WP_CLI::warning( sprintf( 'The on_change callback of module %s failed; the new state applies anyway. The PHP error log names the error.', $slug ) );
			} elseif ( Switch_Result::WARNING_BACKUP_HINT === $warning ) {
				WP_CLI::warning( 'No backup of the theme settings is made when a module goes off; export the settings first to keep a copy.' );
			}
		}
	}

	/**
	 * Reads the slug argument; stops with an error without one.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $args Positional arguments.
	 * @return string|null Slug, or null after an error.
	 */
	private static function slug( array $args ): ?string {
		$slug = $args[0] ?? '';
		if ( '' === $slug ) {
			WP_CLI::error( 'Name a module slug, e.g. header-footer.' );
			return null;
		}
		return $slug;
	}

	/**
	 * Tells whether the current user may switch modules; stops with an error otherwise.
	 *
	 * WP-CLI runs without a user unless --user names one, so current_user_can() is false then.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when allowed.
	 */
	private static function may_switch(): bool {
		if ( current_user_can( Capabilities::MANAGE_MODULES ) ) {
			return true;
		}
		WP_CLI::error( self::USER_ERROR );
		return false;
	}

	/**
	 * Reads and checks the format argument; stops with an error for an unknown format.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @param array<int, string>   $formats    Allowed formats.
	 * @return string|null Format, or null after an error.
	 */
	private static function format( array $assoc_args, array $formats ): ?string {
		$format = $assoc_args['format'] ?? 'table';
		if ( is_string( $format ) && in_array( $format, $formats, true ) ) {
			return $format;
		}
		WP_CLI::error( sprintf( 'Unknown format; use one of: %s.', implode( ', ', $formats ) ) );
		return null;
	}
}
