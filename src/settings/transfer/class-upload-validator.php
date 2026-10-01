<?php
/**
 * Checks of an uploaded settings transfer file.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

use Closure;
use RuntimeException;
use SplFileObject;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Reads the uploaded import file from PHP's temporary folder after checking the upload.
 *
 * Checks in this order: the PHP upload error code, that PHP received the file as
 * upload, the size (at most MAX_BYTES), the client file name (a plain name
 * ending in .json, no part "php", "php<n>", "phtml" or "phar"). The file never
 * reaches the media library. Reading stops after MAX_BYTES + 1 bytes, so a
 * wrong size from the client cannot make the theme read more.
 *
 * @since 1.0.0
 */
final class Upload_Validator {

	/**
	 * Largest accepted file in bytes (1 MB).
	 *
	 * @since 1.0.0
	 */
	public const MAX_BYTES = 1048576;

	/**
	 * Name of the file field in the import form, the key in $_FILES.
	 *
	 * @since 1.0.0
	 */
	public const FIELD = 'creationell_wp_theme_import_file';

	/**
	 * Tells whether a path is a file PHP received as upload.
	 *
	 * @var Closure(string): bool
	 */
	private Closure $is_uploaded;

	/**
	 * Takes the upload check; without one it uses is_uploaded_file().
	 *
	 * @since 1.0.0
	 *
	 * @param Closure|null $is_uploaded Tells whether a path is an uploaded file; tests inject it.
	 * @phpstan-param (Closure(string): bool)|null $is_uploaded
	 */
	public function __construct( ?Closure $is_uploaded = null ) {
		$this->is_uploaded = $is_uploaded ?? static fn( string $path ): bool => is_uploaded_file( $path );
	}

	/**
	 * Checks an upload and returns the file content.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $file One entry of $_FILES, e.g. $_FILES[ Upload_Validator::FIELD ].
	 * @return string File content, at most MAX_BYTES bytes.
	 * @throws Transfer_Exception With upload_failed, too_large or wrong_extension.
	 */
	public function read( array $file ): string {
		$error    = $file['error'] ?? null;
		$name     = $file['name'] ?? null;
		$tmp_name = $file['tmp_name'] ?? null;
		if ( UPLOAD_ERR_OK !== $error || ! is_string( $name ) || ! is_string( $tmp_name ) || '' === $tmp_name ) {
			Transfer_Exception::raise( Transfer_Error::UPLOAD_FAILED, array( 'detail' => is_int( $error ) ? 'upload error ' . $error : 'no single file' ) );
		}
		if ( ! ( $this->is_uploaded )( $tmp_name ) ) {
			Transfer_Exception::raise( Transfer_Error::UPLOAD_FAILED, array( 'detail' => 'not an uploaded file' ) );
		}
		$size = $file['size'] ?? 0;
		if ( is_int( $size ) && $size > self::MAX_BYTES ) {
			Transfer_Exception::raise( Transfer_Error::TOO_LARGE );
		}
		if ( ! self::is_json_name( $name ) ) {
			Transfer_Exception::raise( Transfer_Error::WRONG_EXTENSION );
		}
		$content = self::read_limited( $tmp_name );
		if ( strlen( $content ) > self::MAX_BYTES ) {
			Transfer_Exception::raise( Transfer_Error::TOO_LARGE );
		}
		return $content;
	}

	/**
	 * Tells whether a client file name is a plain .json name without a PHP part.
	 *
	 * Rejects every name that contains ".php" in any case (spec 5.6), and also names with a
	 * part that is exactly php<n>, phtml or phar. The D modifier keeps a trailing newline out.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Client file name.
	 * @return bool True for names like "settings.json" or "Settings.JSON".
	 */
	public static function is_json_name( string $name ): bool {
		if ( 1 !== preg_match( '~^[^/\\\\]+\.json$~iD', $name ) || false !== stripos( $name, '.php' ) ) {
			return false;
		}
		$parts = explode( '.', $name );
		array_pop( $parts );
		if ( '' === implode( '', $parts ) ) {
			return false;
		}
		foreach ( $parts as $part ) {
			if ( 1 === preg_match( '~^(?:php\d*|phtml|phar)$~iD', $part ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Reads up to MAX_BYTES + 1 bytes of a file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Path of the temporary file.
	 * @return string Content.
	 * @throws Transfer_Exception With upload_failed when the file cannot be read.
	 */
	private static function read_limited( string $path ): string {
		if ( ! is_file( $path ) || ! is_readable( $path ) ) {
			Transfer_Exception::raise( Transfer_Error::UPLOAD_FAILED, array( 'detail' => 'file not readable' ) );
		}
		try {
			$handle  = new SplFileObject( $path, 'rb' );
			$content = '';
			$missing = self::MAX_BYTES + 1;
			while ( $missing > 0 && ! $handle->eof() ) {
				$chunk = $handle->fread( $missing );
				if ( false === $chunk || '' === $chunk ) {
					break;
				}
				$content .= $chunk;
				$missing -= strlen( $chunk );
			}
		} catch ( RuntimeException ) {
			Transfer_Exception::raise( Transfer_Error::UPLOAD_FAILED, array( 'detail' => 'file not readable' ) );
		}
		return $content;
	}
}
