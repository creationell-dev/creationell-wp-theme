<?php
/**
 * Exception of the settings transfer.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

use RuntimeException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Stops an import or export with a Transfer_Error; nothing has been written when it is thrown.
 *
 * The exception message is technical English for logs and WP-CLI, escaped for
 * HTML: the error code, then " at <path>" and ": <detail>" when the context has
 * them. People see $error->message() instead. Theme code throws it with raise().
 *
 * @since 1.0.0
 */
final class Transfer_Exception extends RuntimeException {

	/**
	 * Builds the message from the code and the context.
	 *
	 * @since 1.0.0
	 *
	 * @param Transfer_Error            $error   Error.
	 * @param array<string, int|string> $context Details: "path" (JSON path, e.g. "$.bootstrap_line"), "detail" (English text) and others by error.
	 */
	public function __construct( public readonly Transfer_Error $error, public readonly array $context = array() ) {
		$message = $error->value;
		if ( isset( $context['path'] ) ) {
			$message .= ' at ' . $context['path'];
		}
		if ( isset( $context['detail'] ) ) {
			$message .= ': ' . $context['detail'];
		}
		parent::__construct( esc_html( $message ) );
	}

	/**
	 * Throws the exception for an error.
	 *
	 * @since 1.0.0
	 *
	 * @param Transfer_Error            $error   Error.
	 * @param array<string, int|string> $context Details, see the constructor.
	 * @return never
	 * @throws Transfer_Exception Always.
	 */
	public static function raise( Transfer_Error $error, array $context = array() ): never {
		$exception = new self( $error, $context );
		throw $exception;
	}
}
