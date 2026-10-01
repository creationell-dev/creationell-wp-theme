<?php
/**
 * WP-CLI command "wp creationell-theme update".
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Cli;

use Creationell\WpTheme\Update\Theme_Updater;
use WP_CLI;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Shows the update state of the parent theme; installs nothing.
 *
 * @since 1.0.0
 */
final class Update_Command {

	/**
	 * Command name below the theme namespace.
	 *
	 * @since 1.0.0
	 */
	public const NAME = 'creationell-theme update';

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
	 * Shows the installed version, the offer of the update manifest and why an update cannot be installed.
	 *
	 * The field "status" is update_available, up_to_date, or the reason:
	 * manifest_unavailable, checksum_missing, line_unavailable, incompatible.
	 * The command reads the cached manifest; a failed fetch is retried after
	 * five minutes. Automatic updates of the theme are always disabled.
	 *
	 * ## OPTIONS
	 *
	 * [--refresh]
	 * : Fetch the manifest again instead of reading the cache.
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
	 *     wp creationell-theme update status
	 *     wp creationell-theme update status --refresh --format=json
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
		$status = Theme_Updater::instance()->status( ! empty( $assoc_args['refresh'] ) );

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
				'value' => (string) $value,
			);
		}
		\WP_CLI\Utils\format_items( 'table', $rows, array( 'field', 'value' ) );
	}
}
