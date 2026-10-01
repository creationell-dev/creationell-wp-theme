<?php
/**
 * Error codes of the theme updater.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Update;

use WP_Error;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Reasons why the theme updater refuses an update; the value is the WP_Error code.
 *
 * Every code starts with "creationell_wp_theme_", so a site can tell the errors
 * of the theme from those of WordPress. The messages are translated when an
 * error is built, never before init.
 *
 * Example:
 *
 *     $error = Update_Error::CHECKSUM_MISMATCH->to_wp_error();
 *     echo $error->get_error_code(); // creationell_wp_theme_checksum_mismatch
 *
 * @api
 * @since 1.0.0
 */
enum Update_Error: string {

	/**
	 * The package URL does not use https.
	 *
	 * @since 1.0.0
	 */
	case INSECURE_PACKAGE_URL = 'creationell_wp_theme_insecure_package_url';

	/**
	 * The update manifest could not be loaded or is invalid.
	 *
	 * @since 1.0.0
	 */
	case MANIFEST_UNAVAILABLE = 'creationell_wp_theme_manifest_unavailable';

	/**
	 * The update manifest carries no SHA-256 checksum.
	 *
	 * @since 1.0.0
	 */
	case CHECKSUM_MISSING = 'creationell_wp_theme_checksum_missing';

	/**
	 * The downloaded package does not match the checksum.
	 *
	 * @since 1.0.0
	 */
	case CHECKSUM_MISMATCH = 'creationell_wp_theme_checksum_mismatch';

	/**
	 * The package could not be downloaded.
	 *
	 * @since 1.0.0
	 */
	case DOWNLOAD_FAILED = 'creationell_wp_theme_download_failed';

	/**
	 * The package is not the one the current manifest names.
	 *
	 * @since 1.0.0
	 */
	case PACKAGE_MISMATCH = 'creationell_wp_theme_package_mismatch';

	/**
	 * The new version needs a newer WordPress or PHP.
	 *
	 * @since 1.0.0
	 */
	case INCOMPATIBLE = 'creationell_wp_theme_incompatible';

	/**
	 * The new version does not contain the Bootstrap line of the site.
	 *
	 * @since 1.0.0
	 */
	case LINE_UNAVAILABLE = 'creationell_wp_theme_line_unavailable';

	/**
	 * Automatic updates of the theme are locked.
	 *
	 * @since 1.0.0
	 */
	case AUTO_UPDATE_BLOCKED = 'creationell_wp_theme_auto_update_blocked';

	/**
	 * The package does not unpack to the folder of the theme.
	 *
	 * @since 1.0.0
	 */
	case WRONG_ROOT_FOLDER = 'creationell_wp_theme_wrong_root_folder';

	/**
	 * Prefix of every code.
	 *
	 * @since 1.0.0
	 */
	public const PREFIX = 'creationell_wp_theme_';

	/**
	 * Returns the code without the prefix, e.g. "manifest_unavailable".
	 *
	 * @since 1.0.0
	 *
	 * @return string Short code.
	 */
	public function short_code(): string {
		return substr( $this->value, strlen( self::PREFIX ) );
	}

	/**
	 * Returns the translated message for administrators.
	 *
	 * @since 1.0.0
	 *
	 * @return string Message.
	 */
	public function message(): string {
		return match ( $this ) {
			self::INSECURE_PACKAGE_URL => __( 'The update package of the creationell Theme must be downloaded over HTTPS.', 'creationell-wp-theme' ),
			self::MANIFEST_UNAVAILABLE => __( 'The update manifest of the creationell Theme could not be loaded, so the package cannot be verified. Please try again later.', 'creationell-wp-theme' ),
			self::CHECKSUM_MISSING     => __( 'The update manifest of the creationell Theme carries no usable SHA-256 checksum for the package, so the package is not installed.', 'creationell-wp-theme' ),
			self::CHECKSUM_MISMATCH    => __( 'The downloaded package of the creationell Theme does not match the SHA-256 checksum of the update manifest and was deleted.', 'creationell-wp-theme' ),
			self::DOWNLOAD_FAILED      => __( 'The update package of the creationell Theme could not be downloaded.', 'creationell-wp-theme' ),
			self::PACKAGE_MISMATCH     => __( 'The offered package of the creationell Theme is not the one named in the current update manifest. Check for updates again, then retry.', 'creationell-wp-theme' ),
			self::INCOMPATIBLE         => __( 'The new version of the creationell Theme needs a newer WordPress or PHP version than this site runs.', 'creationell-wp-theme' ),
			self::LINE_UNAVAILABLE     => __( 'The new version of the creationell Theme does not contain the Bootstrap line this site uses. Switch the site to a line of the new version before updating.', 'creationell-wp-theme' ),
			self::AUTO_UPDATE_BLOCKED  => __( 'Automatic updates of the creationell Theme are disabled; update the theme by hand.', 'creationell-wp-theme' ),
			self::WRONG_ROOT_FOLDER    => __( 'The update package of the creationell Theme does not unpack to the folder creationell-wp-theme; the installed theme stays unchanged.', 'creationell-wp-theme' ),
		};
	}

	/**
	 * Returns a WP_Error with the code and the translated message.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $data Error data, e.g. array( 'expected' => ..., 'actual' => ... ).
	 * @return WP_Error Error.
	 */
	public function to_wp_error( mixed $data = '' ): WP_Error {
		return new WP_Error( $this->value, $this->message(), $data );
	}
}
