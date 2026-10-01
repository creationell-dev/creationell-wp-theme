<?php
/**
 * Request to switch a module.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules;

use InvalidArgumentException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Names the module, the target state, the channel and what the person confirmed.
 *
 * Channels: "admin" (module page), "cli" (WP-CLI), "import" (settings import).
 * A confirmation counts only for the live contents the person saw: $seen_live is
 * the number of live contents shown with the question; null skips that check,
 * for channels that confirm what the switcher counts at the moment of the switch.
 *
 * Example:
 *
 *     $result = Module_Switcher::instance()->switch( new Switch_Request( 'post-lists', 'off', 'cli', true ) );
 *
 * @since 1.0.0
 */
final class Switch_Request {

	/**
	 * Channels a switch may come from.
	 *
	 * @since 1.0.0
	 */
	public const CHANNELS = array( 'admin', 'cli', 'import' );

	/**
	 * Keeps the fields.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $slug      Module slug.
	 * @param string   $target    Target state; the switcher checks it against the states of the module.
	 * @param string   $channel   Channel: admin, cli or import.
	 * @param bool     $confirmed Whether the person confirmed the consequences of switching off.
	 * @param bool     $dry_run   Whether to return the outcome without changing anything.
	 * @param int|null $seen_live Live contents the person saw with the question; null for no check.
	 * @throws InvalidArgumentException When the channel is unknown.
	 */
	public function __construct(
		public readonly string $slug,
		public readonly string $target,
		public readonly string $channel,
		public readonly bool $confirmed = false,
		public readonly bool $dry_run = false,
		public readonly ?int $seen_live = null,
	) {
		if ( ! in_array( $channel, self::CHANNELS, true ) ) {
			throw new InvalidArgumentException( esc_html( sprintf( 'Unknown switch channel %s.', $channel ) ) );
		}
	}
}
