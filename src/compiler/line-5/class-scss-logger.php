<?php
/**
 * Logger of the scoped scssphp for the line 5 compiler.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Compiler\Line5;

use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Deprecation;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Logger\LoggerInterface;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\StackTrace\Trace;
use Creationell\WpTheme\Vendor\SourceSpan\FileSpan;
use Creationell\WpTheme\Vendor\SourceSpan\SourceSpan;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Keeps warnings and deprecations as "warning: <text> (<file>:<line>)" and "deprecation: ..."; drops debug output.
 *
 * @since 1.0.0
 */
final class Scss_Logger implements LoggerInterface {

	/**
	 * Collected messages.
	 *
	 * @var list<string>
	 */
	private array $collected = array();

	/**
	 * Collects a warning or a deprecation.
	 *
	 * @since 1.0.0
	 *
	 * @param string           $message     Message.
	 * @param Deprecation|null $deprecation Deprecation, null for a warning.
	 * @param FileSpan|null    $span        Location.
	 * @param Trace|null       $trace       Stack trace.
	 * @return void
	 */
	public function warn( string $message, ?Deprecation $deprecation = null, ?FileSpan $span = null, ?Trace $trace = null ): void {
		unset( $trace );
		$where = '';
		if ( null !== $span ) {
			$url   = $span->getSourceUrl();
			$where = ' (' . ( null === $url ? 'input' : basename( $url->getPath() ) ) . ':' . ( $span->getStart()->getLine() + 1 ) . ')';
		}
		$this->collected[] = ( null === $deprecation ? 'warning: ' : 'deprecation: ' ) . $message . $where;
	}

	/**
	 * Drops a debug message.
	 *
	 * @since 1.0.0
	 *
	 * @param string     $message Message.
	 * @param SourceSpan $span    Location.
	 * @return void
	 */
	public function debug( string $message, SourceSpan $span ): void {
		unset( $message, $span );
	}

	/**
	 * Returns the collected messages.
	 *
	 * @since 1.0.0
	 *
	 * @return list<string> Messages in order.
	 */
	public function messages(): array {
		return $this->collected;
	}
}
