<?php
/**
 * WP-CLI command "wp creationell-theme css".
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Cli;

use Creationell\WpTheme\Compiler\Custom_Stylesheet;
use Creationell\WpTheme\Settings\Language;
use WP_CLI;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Builds the individual stylesheet of the design settings and shows its state.
 *
 * "css build" compiles when the fingerprint changed or with --force and ends
 * with exit code 1 when the build failed; "css status" shows the stored state,
 * whether it is stale and the right-to-left languages, for which the build
 * adds a right-to-left file. Both change no setting and need no --user. The
 * class also collects the section "css" of "wp creationell-theme doctor".
 *
 * Example:
 *
 *     wp creationell-theme css build --force
 *     wp creationell-theme css status --format=json
 *
 * @since 1.0.0
 */
final class Css_Command {

	/**
	 * Command name below the theme namespace.
	 *
	 * @since 1.0.0
	 */
	public const NAME = 'creationell-theme css';

	/**
	 * Key of the doctor section.
	 *
	 * @since 1.0.0
	 */
	public const DOCTOR_SECTION = 'css';

	/**
	 * Formats of the status.
	 *
	 * @since 1.0.0
	 */
	public const FORMATS = array( 'table', 'json' );

	/**
	 * Language codes written right to left, as WordPress.org lists the locales.
	 *
	 * @since 1.0.0
	 */
	public const RTL_LANGUAGES = Language::RTL_LANGUAGES;

	/**
	 * Registers the command and the doctor section; runs on cli_init.
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
	 * Builds the individual stylesheet when the design settings or the SCSS files changed.
	 *
	 * With every design setting at its default and no child SCSS the package
	 * stylesheet applies and the own files are deleted. A failed build keeps the
	 * previous stylesheet active and ends with exit code 1.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Compile even when the fingerprint is unchanged.
	 *
	 * ## EXAMPLES
	 *
	 *     wp creationell-theme css build
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments; none.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function build( array $args, array $assoc_args ): void {
		unset( $args );
		$force  = ! empty( $assoc_args['force'] );
		$before = get_option( Custom_Stylesheet::STATE_OPTION, null );
		$state  = Custom_Stylesheet::instance()->build( 'cli', $force );

		if ( 'failed' === $state['status'] ) {
			WP_CLI::error( sprintf( 'The stylesheet could not be built; the previous one stays active. Error: %s', (string) $state['error'] ) );
			return;
		}
		if ( 'package' === $state['status'] ) {
			WP_CLI::success( 'Every design setting is at its default; the package stylesheet applies.' );
			return;
		}
		if ( ! $force && $before === $state ) {
			WP_CLI::success( sprintf( 'The stylesheet %s is up to date.', $state['files']['ltr'] ) );
			return;
		}
		WP_CLI::success(
			sprintf(
				'Built %1$s for line %2$d in %3$s s (peak memory %4$s MiB).',
				implode( ', ', array_filter( array( $state['files']['ltr'], $state['files']['rtl'] ) ) ),
				$state['line'],
				number_format( $state['seconds'], 2, '.', '' ),
				number_format( $state['peak_bytes'] / 1048576, 1, '.', '' )
			)
		);
	}

	/**
	 * Shows the state of the individual stylesheet.
	 *
	 * Fields: the stored state (status ok, package or failed, line, fingerprint,
	 * files, theme version, time, seconds, peak memory, error), "stale" (a build
	 * would change the stylesheet) and "rtl_languages" (active languages written
	 * right to left; with them the build adds files.rtl). Computes the
	 * fingerprint; the front end never does.
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
	 *     wp creationell-theme css status --format=json
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments; none.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function status( array $args, array $assoc_args ): void {
		unset( $args );
		$format = $assoc_args['format'] ?? 'table';
		if ( ! is_string( $format ) || ! in_array( $format, self::FORMATS, true ) ) {
			WP_CLI::error( sprintf( 'Unknown format; use one of: %s.', implode( ', ', self::FORMATS ) ) );
			return;
		}
		$status = self::collect();
		if ( 'json' === $format ) {
			$json = wp_json_encode( $status, JSON_UNESCAPED_SLASHES );
			WP_CLI::line( false === $json ? '{}' : $json );
			return;
		}
		$rows = array();
		foreach ( $status as $field => $value ) {
			$rows[] = array(
				'field' => $field,
				'value' => self::cell( $value ),
			);
		}
		\WP_CLI\Utils\format_items( 'table', $rows, array( 'field', 'value' ) );
	}

	/**
	 * Collects the doctor section "css": the status of "css status" with warnings.
	 *
	 * Warnings: a failed build, a stale stylesheet, and right-to-left languages
	 * while the individual stylesheet has no right-to-left file (they keep the
	 * package stylesheet until the next build).
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Values; "warnings" when there is something to fix.
	 */
	public static function doctor_section(): array {
		$status   = self::collect();
		$warnings = array();
		if ( 'failed' === $status['status'] ) {
			$warnings[] = sprintf( 'The last build failed; the previous stylesheet stays active. Error: %s', (string) $status['error'] );
		}
		if ( $status['stale'] ) {
			$warnings[] = 'The stylesheet does not match the design settings or the SCSS files; run wp creationell-theme css build.';
		}
		if ( 'package' !== $status['status'] && array() !== $status['rtl_languages'] && '' === $status['files']['rtl'] ) {
			$warnings[] = sprintf( 'The right-to-left languages %s get the package stylesheet without the design settings until the stylesheet is rebuilt; run wp creationell-theme css build.', implode( ', ', $status['rtl_languages'] ) );
		}
		if ( array() !== $warnings ) {
			$status['warnings'] = $warnings;
		}
		return $status;
	}

	/**
	 * Collects the state, whether it is stale and the right-to-left languages.
	 *
	 * @since 1.0.0
	 *
	 * @return array{schema: int, status: string, line: int, fingerprint: string, files: array{ltr: string, rtl: string, root_vars: string}, theme_version: string, built_at: int, seconds: float, peak_bytes: int, error: string|null, stale: bool, rtl_languages: array<int, string>} Status.
	 */
	private static function collect(): array {
		$stylesheet = Custom_Stylesheet::instance();
		$status     = $stylesheet->state();

		$status['stale']         = $stylesheet->is_stale();
		$status['rtl_languages'] = Language::instance()->rtl();
		return $status;
	}

	/**
	 * Formats a value for a table cell.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return string Empty for null, true/false for booleans, JSON for arrays.
	 */
	private static function cell( mixed $value ): string {
		if ( null === $value ) {
			return '';
		}
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}
		if ( is_scalar( $value ) ) {
			return (string) $value;
		}
		$json = wp_json_encode( $value, JSON_UNESCAPED_SLASHES );
		return false === $json ? '' : $json;
	}
}
