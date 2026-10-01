<?php
/**
 * Returns the stylesheet compiler of a Bootstrap line.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Compiler;

use Creationell\WpTheme\Compiler\Line5\Scss_Php_Compiler;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The only way to the compiler classes of a line and the scoped libraries in lib/.
 *
 * The theme loader never loads them: a normal request does not compile, and the
 * line classes extend classes of the scoped libraries.
 *
 * @since 1.0.0
 */
final class Compiler_Factory {

	/**
	 * Loads the compiler of a Bootstrap line and returns a new instance.
	 *
	 * @since 1.0.0
	 *
	 * @param int $line Bootstrap line.
	 * @return Stylesheet_Compiler_Interface Compiler of the line.
	 * @throws Line_Unavailable_Exception When this theme version has no compiler for the line.
	 */
	public static function for_line( int $line ): Stylesheet_Compiler_Interface {
		if ( 5 !== $line ) {
			$error = new Line_Unavailable_Exception( $line );
			throw $error;
		}
		require_once dirname( __DIR__, 2 ) . '/lib/autoload.php';
		require_once __DIR__ . '/line-5/class-scss-logger.php';
		require_once __DIR__ . '/line-5/class-rtl-preprocessor.php';
		require_once __DIR__ . '/line-5/class-rtl-postprocessor.php';
		require_once __DIR__ . '/line-5/class-rtl-port.php';
		require_once __DIR__ . '/line-5/class-scss-php-compiler.php';
		return new Scss_Php_Compiler();
	}
}
