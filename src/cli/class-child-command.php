<?php
/**
 * WP-CLI command "wp creationell-theme child".
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Cli;

use Creationell\WpTheme\Child\Child_Rules;
use Creationell\WpTheme\Child\Child_Theme;
use WP_CLI;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Checks a child theme against the child theme rules; reads only.
 *
 * Registering the command also adds the doctor section "child" with the
 * declared Bootstrap lines of the active child theme.
 *
 * @since 1.0.0
 */
final class Child_Command {

	/**
	 * Command name below the theme namespace.
	 *
	 * @since 1.0.0
	 */
	public const NAME = 'creationell-theme child';

	/**
	 * Key of the doctor section.
	 *
	 * @since 1.0.0
	 */
	public const DOCTOR_SECTION = 'child';

	/**
	 * Fields of a finding.
	 *
	 * @since 1.0.0
	 */
	public const FIELDS = array( 'rule', 'file', 'message' );

	/**
	 * Output formats.
	 *
	 * @since 1.0.0
	 */
	public const FORMATS = array( 'table', 'json' );

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
			Doctor_Command::add_section( self::DOCTOR_SECTION, array( Child_Theme::class, 'doctor_section' ) );
		}
	}

	/**
	 * Checks a child theme folder against the rules in the README of the child theme.
	 *
	 * Prints one row per finding and ends with exit code 1 when there is any.
	 *
	 * ## OPTIONS
	 *
	 * [<path>]
	 * : Folder of the child theme, absolute or relative to the working directory. Default: the active child theme.
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
	 *     wp creationell-theme child check
	 *     wp creationell-theme child check wp-content/themes/creationell-wp-child-theme --format=json
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments: the folder, optional.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function check( array $args, array $assoc_args ): void {
		$format = $assoc_args['format'] ?? 'table';
		if ( ! is_string( $format ) || ! in_array( $format, self::FORMATS, true ) ) {
			WP_CLI::error( sprintf( 'Unknown format; use one of: %s.', implode( ', ', self::FORMATS ) ) );
			return;
		}
		$dir = self::folder( $args[0] ?? '' );
		if ( null === $dir ) {
			return;
		}

		$findings = Child_Rules::check( $dir );
		$count    = count( $findings );
		if ( 'json' === $format ) {
			$json = wp_json_encode( $findings, JSON_UNESCAPED_SLASHES );
			WP_CLI::line( false === $json ? '[]' : $json );
		} elseif ( 0 < $count ) {
			\WP_CLI\Utils\format_items( 'table', $findings, self::FIELDS );
		}
		if ( 0 === $count ) {
			if ( 'table' === $format ) {
				WP_CLI::success( '0 violations.' );
			}
			return;
		}
		WP_CLI::error( sprintf( 1 === $count ? '%d violation.' : '%d violations.', $count ) );
	}

	/**
	 * Resolves the folder to check; stops with an error when there is none.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Path argument; empty for the active child theme.
	 * @return string|null Absolute folder, or null after an error.
	 */
	private static function folder( string $path ): ?string {
		if ( '' === $path ) {
			$dir = Child_Theme::instance()->dir();
			if ( null === $dir ) {
				WP_CLI::error( 'No child theme is active. Name the folder of a child theme: wp creationell-theme child check <path>.' );
				return null;
			}
			return $dir;
		}
		$absolute = str_starts_with( $path, '/' ) || 1 === preg_match( '~^[A-Za-z]:[\\\\/]~', $path );
		$dir      = $absolute ? $path : getcwd() . '/' . $path;
		if ( ! is_dir( $dir ) ) {
			WP_CLI::error( sprintf( 'The folder %s does not exist.', $path ) );
			return null;
		}
		return $dir;
	}
}
