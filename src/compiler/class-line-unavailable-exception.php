<?php
/**
 * Exception for a Bootstrap line without a compiler.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Compiler;

use RuntimeException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Thrown by Compiler_Factory and License_Banner for a Bootstrap line this theme version cannot build.
 *
 * @since 1.0.0
 */
final class Line_Unavailable_Exception extends RuntimeException {

	/**
	 * Sets the message "Bootstrap line <n> is not available.".
	 *
	 * @since 1.0.0
	 *
	 * @param int $bootstrap_line Requested Bootstrap line.
	 */
	public function __construct( public readonly int $bootstrap_line ) {
		parent::__construct( sprintf( 'Bootstrap line %d is not available.', $bootstrap_line ) );
	}
}
