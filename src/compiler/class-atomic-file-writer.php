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

	/**
	 * Writes several files as one change: all temporary files first, then the renames.
	 *
	 * Before the first rename every existing target gets a copy in its folder;
	 * a target that cannot be read stops the change before any rename, so it is
	 * never lost. When a temporary file or a copy cannot be made, no target is
	 * touched. When
	 * a rename fails, the targets already replaced get their old file back (or are
	 * removed when they did not exist), so either every target is new or none.
	 * Should that undo fail as well, the copy of each such target stays in its
	 * folder, the exception names it ("old contents kept in …") and keeps the
	 * first error as previous.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $files Contents by absolute target path.
	 * @return void
	 * @throws RuntimeException When a file cannot be written; no target is replaced and no temporary file remains.
	 */
	public static function write_all( array $files ): void {
		$temps   = array();
		$backups = array();
		try {
			foreach ( $files as $path => $contents ) {
				$temps[ $path ]   = self::temp( (string) $path, $contents );
				$backups[ $path ] = self::backup( (string) $path );
			}
		} catch ( RuntimeException $exception ) {
			self::remove( array_merge( array_values( $temps ), array_values( array_filter( $backups ) ) ) );
			throw $exception;
		}
		$done = array();
		foreach ( $temps as $path => $temp ) {
			if ( rename( $temp, $path ) ) {
				$done[] = $path;
				continue;
			}
			$error  = new RuntimeException( 'Cannot write ' . $path . '.' );
			$failed = self::undo( $done, $backups );
			$kept   = array();
			foreach ( $failed as $target ) {
				$kept[] = (string) $backups[ $target ];
				unset( $backups[ $target ] );
			}
			if ( array() !== $failed ) {
				$error = new RuntimeException( 'Cannot put back the old file of ' . implode( ', ', $failed ) . '; the change is not fully undone; old contents kept in ' . implode( ', ', $kept ) . '.', 0, $error );
			}
			self::remove( array_merge( array_values( $temps ), array_values( array_filter( $backups ) ) ) );
			throw $error;
		}
		self::remove( array_values( array_filter( $backups ) ) );
	}

	/**
	 * Copies an existing target in its folder, to undo the change.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Absolute path of the target file.
	 * @return string|null Path of the copy; null when the target does not exist.
	 * @throws RuntimeException When the target cannot be read or copied.
	 */
	private static function backup( string $path ): ?string {
		if ( ! file_exists( $path ) ) {
			return null;
		}
		$backup = dirname( $path ) . '/.' . basename( $path ) . '.' . bin2hex( random_bytes( 6 ) ) . '.old';
		if ( ! is_file( $path ) || ! is_readable( $path ) || ! copy( $path, $backup ) ) {
			$error = new RuntimeException( 'Cannot keep the old file of ' . $path . ' to undo the change; nothing was replaced.' );
			throw $error;
		}
		return $backup;
	}

	/**
	 * Puts the old files of replaced targets back, or removes targets that did not exist.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>         $done    Targets already replaced.
	 * @param array<string, string|null> $backups Copies by target.
	 * @return list<string> Targets whose old file could not be put back; their copies stay.
	 */
	private static function undo( array $done, array $backups ): array {
		$failed = array();
		foreach ( $done as $path ) {
			$backup = $backups[ $path ] ?? null;
			$ok     = null === $backup ? unlink( $path ) : rename( $backup, $path );
			if ( ! $ok && null !== $backup ) {
				$failed[] = $path;
			}
		}
		return $failed;
	}

	/**
	 * Writes the temporary file of a target in its folder.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path     Absolute path of the target file.
	 * @param string $contents File contents.
	 * @return string Path of the temporary file.
	 * @throws RuntimeException When the folder or the file cannot be written; no temporary file remains.
	 */
	private static function temp( string $path, string $contents ): string {
		$dir  = dirname( $path );
		$temp = $dir . '/.' . basename( $path ) . '.' . bin2hex( random_bytes( 6 ) ) . '.tmp';
		if ( file_exists( $dir ) && ! is_dir( $dir ) ) {
			$error = new RuntimeException( 'A file is in place of the folder of ' . $path . '.' );
			throw $error;
		}
		if ( ! is_dir( $dir ) && ! mkdir( $dir, 0755, true ) && ! is_dir( $dir ) ) {
			$error = new RuntimeException( 'Cannot create the folder of ' . $path . '.' );
			throw $error;
		}
		if ( ! is_writable( $dir ) || is_dir( $path ) ) {
			$error = new RuntimeException( 'Cannot write ' . $path . '.' );
			throw $error;
		}
		$file    = new SplFileObject( $temp, 'xb' );
		$written = $file->fwrite( $contents );
		unset( $file );
		if ( strlen( $contents ) !== $written ) {
			unlink( $temp );
			$error = new RuntimeException( 'Cannot write ' . $path . '.' );
			throw $error;
		}
		return $temp;
	}

	/**
	 * Removes temporary files that still exist.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $temps Temporary files and copies.
	 * @return void
	 */
	private static function remove( array $temps ): void {
		foreach ( $temps as $temp ) {
			if ( is_file( $temp ) ) {
				unlink( $temp );
			}
		}
	}
}
