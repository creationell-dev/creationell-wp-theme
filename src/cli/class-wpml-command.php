<?php
/**
 * WP-CLI command "wp creationell-theme wpml".
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Cli;

use Creationell\WpTheme\Wpml\Wpml_Integration;
use WP_CLI;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Shows the WPML state of the site; reads only.
 *
 * @since 1.0.0
 */
final class Wpml_Command {

	/**
	 * Command name below the theme namespace.
	 *
	 * @since 1.0.0
	 */
	public const NAME = 'creationell-theme wpml';

	/**
	 * Formats of the status.
	 *
	 * @since 1.0.0
	 */
	public const FORMATS = array( 'table', 'json' );

	/**
	 * Registers the command; runs on cli_init.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		WP_CLI::add_command( self::NAME, self::class );
	}

	/**
	 * Shows the WPML state: versions, languages, URL mode, wpml-config.xml, snapshots and findings.
	 *
	 * The JSON output equals the section "wpml" of "wp creationell-theme doctor".
	 * Writes nothing; stale snapshots are only reported.
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
	 * [--strict]
	 * : Exit with 1 when there is at least one finding of the level warning.
	 *
	 * ## EXAMPLES
	 *
	 *     wp creationell-theme wpml status --format=json --strict
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

		$status = Wpml_Integration::status();
		if ( 'json' === $format ) {
			$json = wp_json_encode( $status, JSON_UNESCAPED_SLASHES );
			WP_CLI::line( false === $json ? '{}' : $json );
		} else {
			self::print_table( $status );
		}

		$warnings = array_filter( $status['findings'], static fn( array $finding ): bool => 'warning' === $finding['level'] );
		if ( ! empty( $assoc_args['strict'] ) && array() !== $warnings ) {
			WP_CLI::halt( 1 );
		}
	}

	/**
	 * Prints the fields as one table and the findings, if any, as a second one.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $status Status of Wpml_Integration::status().
	 * @return void
	 */
	private static function print_table( array $status ): void {
		$rows = array();
		foreach ( $status as $field => $value ) {
			if ( 'findings' === $field ) {
				continue;
			}
			$rows[] = array(
				'field' => $field,
				'value' => self::cell( $value ),
			);
		}
		\WP_CLI\Utils\format_items( 'table', $rows, array( 'field', 'value' ) );

		$findings = is_array( $status['findings'] ?? null ) ? $status['findings'] : array();
		if ( array() !== $findings ) {
			\WP_CLI\Utils\format_items( 'table', $findings, array( 'id', 'level', 'message' ) );
		}
	}

	/**
	 * Formats a value for a table cell.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return string Empty for null, true/false for booleans, a comma-separated list for a list of strings, JSON for other arrays.
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
		if ( is_array( $value ) && array_is_list( $value ) && array() === array_filter( $value, static fn( mixed $item ): bool => ! is_string( $item ) ) ) {
			return implode( ',', $value );
		}
		$json = wp_json_encode( $value, JSON_UNESCAPED_SLASHES );
		return false === $json ? '' : $json;
	}
}
