<?php
/**
 * Location and version of the compiled theme stylesheets.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Assets;

use Creationell\WpTheme\Compiler\Custom_Stylesheet;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Tells where the compiled stylesheets of the theme are and which version they carry.
 *
 * The theme ships the stylesheets of Bootstrap line 5 in assets/css/bootstrap-5/.
 * Front end and editor canvas ask this class for the main stylesheet; when the
 * design settings built an individual stylesheet (Custom_Stylesheet) for the
 * active line and the request runs left to right, that file in the uploads
 * folder applies instead, with 12 hex digits of its fingerprint as version.
 * The class reads the autoloaded state only; it never compiles.
 *
 * @since 1.0.0
 */
final class Stylesheet_Locator {

	/**
	 * Main stylesheet of the package, relative to the theme folder.
	 *
	 * @since 1.0.0
	 */
	public const MAIN = 'assets/css/bootstrap-5/theme.min.css';

	/**
	 * Root custom properties of the main stylesheet for the editor screens, relative to the theme folder.
	 *
	 * @since 1.0.0
	 */
	public const ROOT_VARS = 'assets/css/bootstrap-5/root-vars.min.css';

	/**
	 * Number of hex digits of the SHA-256 content hash used as fingerprint.
	 *
	 * @since 1.0.0
	 */
	public const FINGERPRINT_LENGTH = 12;

	/**
	 * Returns the main stylesheet: the individual one when active, otherwise the package.
	 *
	 * The version of the package is the theme version plus the fingerprint of the
	 * file, so a rebuilt stylesheet reaches browsers without a new theme version.
	 * Without a readable file the version is the theme version alone.
	 *
	 * @since 1.0.0
	 *
	 * @return array{url: string, path: string, version: string} URL, absolute path and version.
	 */
	public static function main(): array {
		$custom = Custom_Stylesheet::active( 'ltr' );
		if ( null !== $custom ) {
			return $custom;
		}
		$path        = get_template_directory() . '/' . self::MAIN;
		$version     = self::theme_version();
		$fingerprint = self::fingerprint( $path );
		return array(
			'url'     => get_template_directory_uri() . '/' . self::MAIN,
			'path'    => $path,
			'version' => '' === $fingerprint ? $version : $version . '-' . $fingerprint,
		);
	}

	/**
	 * Returns the root custom properties of the main stylesheet, used on the block editor screens.
	 *
	 * The individual file applies under the same conditions as in main().
	 *
	 * @since 1.0.0
	 *
	 * @return array{url: string, path: string, version: string} URL, absolute path and fingerprint; the version is empty when the file is missing.
	 */
	public static function root_vars(): array {
		$custom = Custom_Stylesheet::active( 'root_vars' );
		if ( null !== $custom ) {
			return $custom;
		}
		$path = get_template_directory() . '/' . self::ROOT_VARS;
		return array(
			'url'     => get_template_directory_uri() . '/' . self::ROOT_VARS,
			'path'    => $path,
			'version' => self::fingerprint( $path ),
		);
	}

	/**
	 * Returns the path of the right-to-left counterpart of a stylesheet.
	 *
	 * Follows WordPress for wp_style_add_data( $handle, 'rtl', 'replace' ) with
	 * the suffix ".min": theme.min.css becomes theme-rtl.min.css, style.css
	 * becomes style-rtl.css.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Path or URL of the left-to-right stylesheet.
	 * @return string Path or URL of the right-to-left stylesheet.
	 */
	public static function rtl_path( string $path ): string {
		$suffix = str_ends_with( $path, '.min.css' ) ? '.min.css' : '.css';
		return substr( $path, 0, -strlen( $suffix ) ) . '-rtl' . $suffix;
	}

	/**
	 * Returns the fingerprint of a file: the first hex digits of its SHA-256.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Absolute file path.
	 * @return string Fingerprint, or an empty string when the file is not readable.
	 */
	public static function fingerprint( string $path ): string {
		$hash = is_readable( $path ) ? hash_file( 'sha256', $path ) : false;
		return false === $hash ? '' : substr( $hash, 0, self::FINGERPRINT_LENGTH );
	}

	/**
	 * Returns the version from the style.css header of the parent theme.
	 *
	 * @since 1.0.0
	 *
	 * @return string Version, or an empty string when the header is missing.
	 */
	public static function theme_version(): string {
		$version = wp_get_theme( get_template() )->get( 'Version' );
		return is_string( $version ) ? $version : '';
	}
}
