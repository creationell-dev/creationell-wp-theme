<?php
/**
 * Update manifest of the theme.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Update;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Checked values of the update manifest (schema v1 of the release chain).
 *
 * The method from_decoded() reads a decoded manifest, from the network or from the cache,
 * and checks every field the updater acts on. A manifest that fails one check is
 * rejected whole: a half-trusted manifest is worse than none. Unknown fields are
 * ignored, so a newer release chain can add fields. A missing checksum is kept as
 * null, never as an empty value; the updater then offers nothing and its gate
 * refuses the package. A present but malformed checksum rejects the manifest.
 *
 * Example:
 *
 *     $manifest = Manifest::from_decoded( json_decode( $body, true ) );
 *     if ( null !== $manifest && $manifest->supports_line( 5 ) ) {
 *         echo $manifest->version;
 *     }
 *
 * @api
 * @since 1.0.0
 */
final class Manifest {

	/**
	 * Allowed version: a letter or digit, then up to 31 letters, digits and ". _ - +".
	 *
	 * @since 1.0.0
	 */
	public const VERSION_PATTERN = '~^[0-9A-Za-z][0-9A-Za-z._\-+]{0,31}$~';

	/**
	 * Allowed WordPress and PHP versions of requires, requires_php and tested: two to four numbers.
	 *
	 * @since 1.0.0
	 */
	public const REQUIREMENT_PATTERN = '~^[0-9]{1,4}(?:\.[0-9]{1,4}){1,3}$~';

	/**
	 * Allowed part of the package URL after the release prefix: tag and file name.
	 *
	 * @since 1.0.0
	 */
	public const PACKAGE_PATH_PATTERN = '~^[A-Za-z0-9][A-Za-z0-9._+\-]*/[A-Za-z0-9][A-Za-z0-9._+\-]*$~';

	/**
	 * Allowed SHA-256 checksum: 64 hexadecimal digits.
	 *
	 * @since 1.0.0
	 */
	public const CHECKSUM_PATTERN = '~^[0-9a-fA-F]{64}$~';

	/**
	 * Longest accepted URL.
	 *
	 * @since 1.0.0
	 */
	public const MAX_URL_LENGTH = 2048;

	/**
	 * Bootstrap lines a package may contain.
	 *
	 * @since 1.0.0
	 */
	public const BOOTSTRAP_LINES = array( 5, 6 );

	/**
	 * Stores the checked values; use from_decoded().
	 *
	 * @since 1.0.0
	 *
	 * @param string          $version         Version of the offered release.
	 * @param string          $download_url    Package URL below the release prefix.
	 * @param string|null     $checksum_sha256 SHA-256 of the package in lower case, null when missing.
	 * @param array<int, int> $bootstrap_lines Bootstrap lines of the package, sorted.
	 * @param string          $requires        Lowest WordPress version.
	 * @param string          $requires_php    Lowest PHP version.
	 * @param string          $tested          WordPress version the release was tested with.
	 * @param string          $details_url     Details page on the update host.
	 */
	private function __construct(
		public readonly string $version,
		public readonly string $download_url,
		public readonly ?string $checksum_sha256,
		public readonly array $bootstrap_lines,
		public readonly string $requires,
		public readonly string $requires_php,
		public readonly string $tested,
		public readonly string $details_url
	) {
	}

	/**
	 * Checks a decoded manifest and returns its values, or null when a field is unusable.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $data Decoded JSON object, as array or stdClass, e.g. the cached array.
	 * @return Manifest|null Manifest, or null when the data is no valid manifest.
	 */
	public static function from_decoded( mixed $data ): ?Manifest {
		if ( is_object( $data ) ) {
			$data = get_object_vars( $data );
		}
		if ( ! is_array( $data ) || array() === $data || array_is_list( $data ) ) {
			return null;
		}

		$version      = self::manifest_string( $data['version'] ?? null );
		$download_url = self::manifest_string( $data['download_url'] ?? null );
		$details_url  = self::manifest_string( $data['details_url'] ?? null );
		$requirements = array();
		foreach ( array( 'requires', 'requires_php', 'tested' ) as $key ) {
			$requirements[ $key ] = self::manifest_string( $data[ $key ] ?? null );
			if ( 1 !== preg_match( self::REQUIREMENT_PATTERN, $requirements[ $key ] ) ) {
				return null;
			}
		}
		$checksum = self::checksum( $data );
		$lines    = self::lines( $data['bootstrap_lines'] ?? null );

		if ( 1 !== preg_match( self::VERSION_PATTERN, $version )
			|| ! self::is_package_url( $download_url )
			|| ! self::is_details_url( $details_url )
			|| false === $checksum
			|| null === $lines
		) {
			return null;
		}

		return new self( $version, $download_url, $checksum, $lines, $requirements['requires'], $requirements['requires_php'], $requirements['tested'], $details_url );
	}

	/**
	 * Returns the checked values in the form of the cache.
	 *
	 * @since 1.0.0
	 *
	 * @return array{version: string, download_url: string, checksum_sha256: string|null, bootstrap_lines: array<int, int>, requires: string, requires_php: string, tested: string, details_url: string} Values.
	 */
	public function to_array(): array {
		return array(
			'version'         => $this->version,
			'download_url'    => $this->download_url,
			'checksum_sha256' => $this->checksum_sha256,
			'bootstrap_lines' => $this->bootstrap_lines,
			'requires'        => $this->requires,
			'requires_php'    => $this->requires_php,
			'tested'          => $this->tested,
			'details_url'     => $this->details_url,
		);
	}

	/**
	 * Tells whether the manifest carries a checksum.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True with a checksum.
	 */
	public function has_checksum(): bool {
		return null !== $this->checksum_sha256;
	}

	/**
	 * Tells whether the package contains a Bootstrap line.
	 *
	 * @since 1.0.0
	 *
	 * @param int $line Bootstrap line, e.g. 5.
	 * @return bool True when the package contains the line.
	 */
	public function supports_line( int $line ): bool {
		return in_array( $line, $this->bootstrap_lines, true );
	}

	/**
	 * Returns a manifest field as a string, or an empty string for any other type.
	 *
	 * The fields reach string functions; an array or object must never get there.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw value.
	 * @return string Value, or an empty string.
	 */
	private static function manifest_string( mixed $value ): string {
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Reads the checksum: lower case, null when missing, false when present but malformed.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $data Manifest data.
	 * @return string|false|null Checksum, null when missing, false when malformed.
	 */
	private static function checksum( array $data ): string|false|null {
		if ( ! array_key_exists( 'checksum_sha256', $data ) || null === $data['checksum_sha256'] ) {
			return null;
		}
		$checksum = self::manifest_string( $data['checksum_sha256'] );
		return 1 === preg_match( self::CHECKSUM_PATTERN, $checksum ) ? strtolower( $checksum ) : false;
	}

	/**
	 * Reads the Bootstrap lines: a non-empty list of distinct known lines.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw value.
	 * @return array<int, int>|null Sorted lines, or null when the value is unusable.
	 */
	private static function lines( mixed $value ): ?array {
		if ( ! is_array( $value ) || array() === $value || ! array_is_list( $value ) ) {
			return null;
		}
		foreach ( $value as $line ) {
			if ( ! is_int( $line ) || ! in_array( $line, self::BOOTSTRAP_LINES, true ) ) {
				return null;
			}
		}
		if ( count( array_unique( $value ) ) !== count( $value ) ) {
			return null;
		}
		sort( $value );
		return $value;
	}

	/**
	 * Tells whether a URL is a package URL: https, below the release prefix, tag and file name only.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url URL.
	 * @return bool True for a package URL.
	 */
	private static function is_package_url( string $url ): bool {
		if ( strlen( $url ) > self::MAX_URL_LENGTH || ! str_starts_with( $url, Theme_Updater::PACKAGE_URL_PREFIX ) ) {
			return false;
		}
		return 1 === preg_match( self::PACKAGE_PATH_PATTERN, substr( $url, strlen( Theme_Updater::PACKAGE_URL_PREFIX ) ) );
	}

	/**
	 * Tells whether a URL is a details URL: https on the update host, without user, port or white space.
	 *
	 * The fixed start "https://<host>/" leaves no room for a user, a port or
	 * another host.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url URL.
	 * @return bool True for a details URL.
	 */
	private static function is_details_url( string $url ): bool {
		return strlen( $url ) <= self::MAX_URL_LENGTH
			&& str_starts_with( $url, 'https://' . Theme_Updater::UPDATE_HOST . '/' )
			&& 1 !== preg_match( '~[\s\\\\\x00-\x1f\x7f]~', $url );
	}
}
