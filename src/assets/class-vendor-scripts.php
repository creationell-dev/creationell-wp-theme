<?php
/**
 * Vendored libraries that modules load on demand.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Assets;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Registers the scripts and styles of assets/vendor/ that modules share; never enqueues them.
 *
 * Each row of TABLE registers one handle: the script in the footer with the
 * strategy defer and the style, both with the version from
 * assets/vendor/<name>/VERSION. A module enqueues the handle or names it as a
 * dependency only where it needs the library, e.g. while one of its blocks is on
 * the page. Consent-bound libraries are marked by the module that loads them.
 *
 * @since 1.0.0
 */
final class Vendor_Scripts {

	/**
	 * Priority on init; after the modules have loaded, before any enqueue hook.
	 *
	 * @since 1.0.0
	 */
	public const PRIORITY = 20;

	/**
	 * Libraries by handle.
	 *
	 * Row keys: "name" (folder below assets/vendor/), "js" and "css" (files
	 * relative to that folder, each optional), "deps" (script dependencies,
	 * optional). The rows come with the modules that use them: the slider
	 * library of the post slider and the banner library of the consent module.
	 *
	 * @since 1.0.0
	 */
	public const TABLE = array(
		'creationell-wp-theme-cookieconsent' => array(
			'name' => 'cookieconsent',
			'js'   => 'cookieconsent.umd.js',
			'css'  => 'cookieconsent.css',
		),
		'creationell-wp-theme-swiper'        => array(
			'name' => 'swiper',
			'js'   => 'swiper-bundle.min.js',
			'css'  => 'swiper-bundle.min.css',
		),
	);

	/**
	 * Registers the libraries of TABLE; runs on init.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		self::register_rows( self::TABLE );
	}

	/**
	 * Registers the scripts and styles of the given rows.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, array{name: string, js?: string, css?: string, deps?: array<int, string>}> $rows Libraries by handle.
	 * @return void
	 */
	public static function register_rows( array $rows ): void {
		foreach ( $rows as $handle => $row ) {
			$folder  = 'assets/vendor/' . $row['name'];
			$url     = get_template_directory_uri() . '/' . $folder . '/';
			$version = Assets::vendor_version( $folder . '/VERSION' );
			$version = '' === $version ? CREATIONELL_WP_THEME_VERSION : $version;
			if ( isset( $row['js'] ) ) {
				wp_register_script(
					$handle,
					$url . $row['js'],
					$row['deps'] ?? array(),
					$version,
					array(
						'in_footer' => true,
						'strategy'  => 'defer',
					)
				);
			}
			if ( isset( $row['css'] ) ) {
				wp_register_style( $handle, $url . $row['css'], array(), $version );
			}
		}
	}
}
