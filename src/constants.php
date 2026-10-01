<?php
/**
 * Constants of the theme.
 *
 * The theme defines its slug, version, paths, minimum versions and the URL of the
 * update manifest. A site may predefine CREATIONELL_WP_THEME_MANIFEST_URL in
 * wp-config.php for tests; only an https URL is used, and administrators see a
 * notice. The site constants CREATIONELL_WP_THEME_BOOTSTRAP_LINE,
 * CREATIONELL_WP_THEME_MODULE_<SLUG> and CREATIONELL_WP_THEME_ALLOW_AUTO_UPDATE
 * are never defined by the theme. CREATIONELL_WP_THEME_TEST_MODULES_DIR names a
 * folder of test modules, read only in the environment type "local".
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Folder name of the parent theme, also the slug of its updates.
 *
 * @since 1.0.0
 */
define( 'CREATIONELL_WP_THEME_SLUG', 'creationell-wp-theme' );

$creationell_wp_theme_version = wp_get_theme( CREATIONELL_WP_THEME_SLUG )->get( 'Version' );

/**
 * Version of the parent theme from the Version header of its style.css, the only version source.
 *
 * @since 1.0.0
 */
define( 'CREATIONELL_WP_THEME_VERSION', is_string( $creationell_wp_theme_version ) ? $creationell_wp_theme_version : '' );

unset( $creationell_wp_theme_version );

/**
 * Absolute path of the parent theme folder, without trailing slash.
 *
 * @since 1.0.0
 */
define( 'CREATIONELL_WP_THEME_DIR', get_template_directory() );

/**
 * URL of the parent theme folder, without trailing slash.
 *
 * @since 1.0.0
 */
define( 'CREATIONELL_WP_THEME_URL', get_template_directory_uri() );

/**
 * Lowest PHP version the theme runs on.
 *
 * @since 1.0.0
 */
define( 'CREATIONELL_WP_THEME_MIN_PHP', '8.3' );

/**
 * Lowest WordPress version the theme runs on.
 *
 * @since 1.0.0
 */
define( 'CREATIONELL_WP_THEME_MIN_WP', '7.1' );

if ( ! defined( 'CREATIONELL_WP_THEME_MANIFEST_URL' ) ) {
	/**
	 * URL of the update manifest; a site may predefine another https URL for tests.
	 *
	 * @since 1.0.0
	 */
	define( 'CREATIONELL_WP_THEME_MANIFEST_URL', 'https://creationell-dev.github.io/creationell-wp-theme/theme_creationell-wp-theme.json' );
}
