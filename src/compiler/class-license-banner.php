<?php
/**
 * Charset rule and license banners of the compiled stylesheets.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Compiler;

use RuntimeException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Head of every compiled stylesheet: charset rule, theme banner, Bootstrap banner.
 *
 * The compiler writes no charset rule and the CSS parser drops comments, so the
 * compiler puts this head in front of each direction. The Bootstrap banner comes
 * from the vendored Bootstrap source and names its version and MIT license.
 *
 * @since 1.0.0
 */
final class License_Banner {

	/**
	 * First statement of every stylesheet; the stylesheets contain non-ASCII characters.
	 *
	 * @since 1.0.0
	 */
	public const CHARSET = '@charset "UTF-8";';

	/**
	 * License banner of the theme.
	 *
	 * @since 1.0.0
	 */
	public const THEME_BANNER = "/*!\n * creationell Theme (https://github.com/creationell-dev/creationell-wp-theme)\n * Licensed under GPL-2.0-or-later (https://www.gnu.org/licenses/gpl-2.0.html)\n */";

	/**
	 * Returns the head of the stylesheets of a Bootstrap line.
	 *
	 * @since 1.0.0
	 *
	 * @param int $line Bootstrap line.
	 * @return non-empty-string Charset rule, theme banner and Bootstrap banner, each on its own lines, ending with a newline.
	 * @throws Line_Unavailable_Exception When the line is not available.
	 * @throws RuntimeException When the Bootstrap banner cannot be read.
	 */
	public static function for_line( int $line ): string {
		if ( 5 !== $line ) {
			$error = new Line_Unavailable_Exception( $line );
			throw $error;
		}
		return self::CHARSET . "\n" . self::THEME_BANNER . "\n" . self::bootstrap_banner( dirname( __DIR__, 2 ) . '/assets/vendor/bootstrap-5/scss/mixins/_banner.scss' ) . "\n";
	}

	/**
	 * Reads the banner comment of the Bootstrap mixin "bsBanner" with an empty file name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $mixin Path of scss/mixins/_banner.scss.
	 * @return string Banner comment.
	 * @throws RuntimeException When the file or the comment is missing.
	 */
	private static function bootstrap_banner( string $mixin ): string {
		$lines = is_file( $mixin ) ? file( $mixin, FILE_IGNORE_NEW_LINES ) : false;
		if ( false === $lines ) {
			$error = new RuntimeException( 'The Bootstrap banner mixin is missing.' );
			throw $error;
		}
		$banner = array();
		foreach ( $lines as $text ) {
			$text = trim( $text );
			if ( array() === $banner && '/*!' !== $text ) {
				continue;
			}
			$banner[] = ( '/*!' === $text ? '' : ' ' ) . str_replace( '#{$file}', '', $text );
			if ( '*/' === $text ) {
				return implode( "\n", $banner );
			}
		}
		$error = new RuntimeException( 'The Bootstrap banner mixin has no banner comment.' );
		throw $error;
	}
}
