<?php
/**
 * Writes a file in one step.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Compiler;

use RuntimeException;
use SplFileObject;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Writes a temporary file in the target folder and renames it over the target.
 *
 * Readers see the old file or the new one, never a partial file. Plain PHP without
 * WordPress, so the command line build and the theme share it; the rename replaces
 * the file in one step, which WP_Filesystem cannot promise for every transport.
 *
 * @since 1.0.0
 */
final class Atomic_File_Writer {

	/**
	 * Writes contents to a path, creating missing folders.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path     Absolute path of the target file.
	 * @param string $contents File contents.
	 * @return void
	 * @throws RuntimeException When the folder or the file cannot be written; the old file stays and no temporary file remains.
	 */
	public static function write( string $path, string $contents ): void {
		$dir  = dirname( $path );
		$temp = $dir . '/.' . basename( $path ) . '.' . bin2hex( random_bytes( 6 ) ) . '.tmp';
		try {
			if ( file_exists( $dir ) && ! is_dir( $dir ) ) {
				throw new RuntimeException( 'A file is in place of the folder of ' . $path . '.' );
			}
			if ( ! is_dir( $dir ) && ! mkdir( $dir, 0755, true ) && ! is_dir( $dir ) ) {
				throw new RuntimeException( 'Cannot create the folder of ' . $path . '.' );
			}
			if ( ! is_writable( $dir ) || is_dir( $path ) ) {
				throw new RuntimeException( 'Cannot write ' . $path . '.' );
			}
			$file    = new SplFileObject( $temp, 'xb' );
			$written = $file->fwrite( $contents );
			unset( $file );
			if ( strlen( $contents ) !== $written || ! rename( $temp, $path ) ) {
				throw new RuntimeException( 'Cannot write ' . $path . '.' );
			}
		} catch ( RuntimeException $exception ) {
			if ( is_file( $temp ) ) {
				unlink( $temp );
			}
			throw $exception;
		}
	}
}
