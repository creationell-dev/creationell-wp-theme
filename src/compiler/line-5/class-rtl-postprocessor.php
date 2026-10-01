<?php
/**
 * Text step of the line 5 chain after the CSS parser.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Compiler\Line5;

use Creationell\WpTheme\Compiler\License_Banner;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Finishes a rendered stylesheet of either direction.
 *
 * The placeholder of the preprocessor becomes the empty value " " again (as in
 * bootstrap.min.css; older browsers drop a value that is entirely empty), and
 * the charset rule and the license banners go in front, because the parser
 * removes every comment.
 *
 * Example:
 *
 *     $css = Rtl_Postprocessor::finish( $document->render( OutputFormat::createCompact() ), 5 );
 *
 * @since 1.0.0
 */
final class Rtl_Postprocessor {

	/**
	 * Restores the empty values and puts the charset rule and the banners of a line in front.
	 *
	 * @since 1.0.0
	 *
	 * @param string $css  Rendered stylesheet with placeholders.
	 * @param int    $line Bootstrap line of the banners.
	 * @return string Finished stylesheet.
	 * @throws \RuntimeException When the Bootstrap banner of the line cannot be read.
	 */
	public static function finish( string $css, int $line ): string {
		return License_Banner::for_line( $line ) . self::restore_empty( $css );
	}

	/**
	 * Turns the placeholder back into the empty value " ".
	 *
	 * @since 1.0.0
	 *
	 * @param string $css Stylesheet with placeholders.
	 * @return string Stylesheet.
	 */
	public static function restore_empty( string $css ): string {
		return str_replace( Rtl_Preprocessor::EMPTY_VALUE, ' ', $css );
	}
}
