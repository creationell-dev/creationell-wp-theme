<?php
/**
 * Fingerprint of the inputs of a stylesheet compilation.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Compiler;

use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * SHA-256 over everything that shapes a compiled stylesheet.
 *
 * Inputs: Bootstrap line, component versions, the contents of the SCSS files,
 * variables, directions, theme version and child theme version. The order of
 * files, variables, versions and directions does not matter, and neither does the
 * folder the files live in: the same theme gives the same fingerprint in every copy.
 *
 * @since 1.0.0
 */
final class Fingerprint {

	/**
	 * Computes the fingerprint.
	 *
	 * @since 1.0.0
	 *
	 * @param int                       $line          Bootstrap line.
	 * @param array<string, string>     $versions      Component name and exact version.
	 * @param array<int, string>        $source_files  Absolute paths of the SCSS files.
	 * @param array<string, Scss_Value> $variables     SCSS variables.
	 * @param array<int, string>        $directions    Directions "ltr" and "rtl".
	 * @param string                    $theme_version Version of the parent theme.
	 * @param string|null               $child_version Version of the child theme, null without child SCSS.
	 * @return string Fingerprint, 64 lower-case hex digits.
	 * @throws InvalidArgumentException When a source file cannot be read.
	 */
	public static function compute( int $line, array $versions, array $source_files, array $variables, array $directions, string $theme_version, ?string $child_version ): string {
		$hashes = array();
		foreach ( $source_files as $file ) {
			$hash = is_file( $file ) ? hash_file( 'sha256', $file ) : false;
			if ( false === $hash ) {
				$error = new InvalidArgumentException( 'Cannot read the SCSS file ' . $file . '.' );
				throw $error;
			}
			$hashes[] = $hash;
		}
		sort( $hashes, SORT_STRING );
		ksort( $versions, SORT_STRING );

		$values = array();
		foreach ( $variables as $name => $value ) {
			$values[ $name ] = $value->type . ':' . $value->value;
		}
		ksort( $values, SORT_STRING );

		$sorted_directions = array_values( array_unique( $directions ) );
		sort( $sorted_directions, SORT_STRING );

		$data = self::field( 'line' ) . self::field( (string) $line );
		foreach ( $versions as $component => $version ) {
			$data .= self::field( 'version' ) . self::field( $component ) . self::field( $version );
		}
		foreach ( $hashes as $hash ) {
			$data .= self::field( 'source' ) . self::field( $hash );
		}
		foreach ( $values as $name => $value ) {
			$data .= self::field( 'variable' ) . self::field( (string) $name ) . self::field( $value );
		}
		foreach ( $sorted_directions as $direction ) {
			$data .= self::field( 'direction' ) . self::field( $direction );
		}
		$data .= self::field( 'theme' ) . self::field( $theme_version );
		$data .= null === $child_version ? self::field( 'no-child' ) : self::field( 'child' ) . self::field( $child_version );
		return hash( 'sha256', $data );
	}

	/**
	 * Lists the SCSS files below folders.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $dirs Absolute folders; missing folders are skipped.
	 * @return list<string> Absolute paths of the .scss files, sorted, each once.
	 */
	public static function scss_files( array $dirs ): array {
		$files = array();
		foreach ( $dirs as $dir ) {
			$base = rtrim( $dir, '/' );
			if ( ! is_dir( $base ) ) {
				continue;
			}
			$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS ) );
			foreach ( $iterator as $file ) {
				if ( $file instanceof SplFileInfo && $file->isFile() && 'scss' === $file->getExtension() ) {
					$files[] = $file->getPathname();
				}
			}
		}
		$files = array_values( array_unique( $files ) );
		sort( $files, SORT_STRING );
		return $files;
	}

	/**
	 * Encodes one field with its length, so fields cannot shift into each other.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Field.
	 * @return string Encoded field.
	 */
	private static function field( string $value ): string {
		return strlen( $value ) . ':' . $value . ';';
	}
}
