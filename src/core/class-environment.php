<?php
/**
 * Requirements of the theme and settings of the site environment.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Checks the PHP and WordPress versions and resolves the update manifest URL.
 *
 * Reads the constants of src/constants.php; loads no translations.
 *
 * @since 1.0.0
 */
final class Environment {

	/**
	 * Official URL of the update manifest.
	 *
	 * @since 1.0.0
	 */
	public const DEFAULT_MANIFEST_URL = 'https://creationell-dev.github.io/creationell-wp-theme/theme_creationell-wp-theme.json';

	/**
	 * Status of the manifest URL: the official URL is used.
	 *
	 * @since 1.0.0
	 */
	public const MANIFEST_DEFAULT = 'default';

	/**
	 * Status of the manifest URL: the site predefined another https URL, which is used.
	 *
	 * @since 1.0.0
	 */
	public const MANIFEST_OVERRIDE = 'override';

	/**
	 * Status of the manifest URL: the site predefined a value that is no https URL; the official URL is used.
	 *
	 * @since 1.0.0
	 */
	public const MANIFEST_REJECTED = 'rejected';

	/**
	 * Tells whether the running PHP and WordPress versions meet the requirements of the theme.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when PHP and WordPress are new enough.
	 */
	public static function meets_requirements(): bool {
		return array() === self::unmet_requirements();
	}

	/**
	 * Returns the requirements that the given or the running versions do not meet.
	 *
	 * Suffixes such as "-RC1" or "-src" count as the release, as in WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $php_version PHP version, null for the running one.
	 * @param string|null $wp_version  WordPress version, null for the running one.
	 * @return array<string, array{required: string, current: string}> Unmet requirements under the keys "php" and "wp".
	 */
	public static function unmet_requirements( ?string $php_version = null, ?string $wp_version = null ): array {
		$checks = array(
			'php' => array( CREATIONELL_WP_THEME_MIN_PHP, $php_version ?? PHP_VERSION ),
			'wp'  => array( CREATIONELL_WP_THEME_MIN_WP, $wp_version ?? self::wp_version() ),
		);
		$unmet  = array();
		foreach ( $checks as $key => $check ) {
			if ( ! self::is_at_least( $check[1], $check[0] ) ) {
				$unmet[ $key ] = array(
					'required' => $check[0],
					'current'  => $check[1],
				);
			}
		}
		return $unmet;
	}

	/**
	 * Returns the running WordPress version.
	 *
	 * @since 1.0.0
	 *
	 * @return string Version, empty when unknown.
	 */
	public static function wp_version(): string {
		return get_bloginfo( 'version' );
	}

	/**
	 * Returns the URL of the update manifest: a predefined https URL, otherwise the official one.
	 *
	 * @since 1.0.0
	 *
	 * @return string Manifest URL.
	 */
	public static function manifest_url(): string {
		$url = self::predefined_manifest_url();
		return self::MANIFEST_OVERRIDE === self::manifest_url_status() && is_string( $url ) ? $url : self::DEFAULT_MANIFEST_URL;
	}

	/**
	 * Tells where the manifest URL comes from.
	 *
	 * @since 1.0.0
	 *
	 * @return string One of MANIFEST_DEFAULT, MANIFEST_OVERRIDE, MANIFEST_REJECTED.
	 */
	public static function manifest_url_status(): string {
		$url = self::predefined_manifest_url();
		if ( self::DEFAULT_MANIFEST_URL === $url ) {
			return self::MANIFEST_DEFAULT;
		}
		return is_string( $url ) && 1 === preg_match( '~^https://[^/\s?#]+[^\s]*$~i', $url ) ? self::MANIFEST_OVERRIDE : self::MANIFEST_REJECTED;
	}

	/**
	 * Returns the value of CREATIONELL_WP_THEME_MANIFEST_URL.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed Value, a string unless a site predefined something else.
	 */
	private static function predefined_manifest_url(): mixed {
		return defined( 'CREATIONELL_WP_THEME_MANIFEST_URL' ) ? constant( 'CREATIONELL_WP_THEME_MANIFEST_URL' ) : self::DEFAULT_MANIFEST_URL;
	}

	/**
	 * Compares a version with a minimum, ignoring suffixes after the first hyphen.
	 *
	 * @since 1.0.0
	 *
	 * @param string $current  Version to check.
	 * @param string $required Minimum version.
	 * @return bool True when the version is at least the minimum.
	 */
	private static function is_at_least( string $current, string $required ): bool {
		$release = strtok( $current, '-' );
		return false !== $release && version_compare( $release, $required, '>=' );
	}
}
