<?php
/**
 * Context of a write through the setter.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings;

use InvalidArgumentException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Tells the setter who writes, through which channel, in which language and whether only to check.
 *
 * Example:
 *
 *     $ctx    = new Write_Context( channel: 'cli', user_id: get_current_user_id(), lang: null );
 *     $result = Setter::instance()->set( 'color_primary', '#6f2da8', $ctx );
 *     Setter::instance()->commit();
 *
 * @api
 * @since 1.0.0
 */
final class Write_Context {

	/**
	 * Channels: the settings pages, WP-CLI, the import and the abilities.
	 *
	 * @since 1.0.0
	 */
	public const CHANNELS = array( 'acf', 'cli', 'import', 'ability' );

	/**
	 * Checks the channel.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $channel One of CHANNELS.
	 * @param int         $user_id User whose capabilities count; 0 has none.
	 * @param string|null $lang    Language code; null for the default language, "all" is refused by the setter.
	 * @param bool        $dry_run Whether to check only and write nothing.
	 * @throws InvalidArgumentException When the channel is unknown.
	 */
	public function __construct(
		public readonly string $channel,
		public readonly int $user_id,
		public readonly ?string $lang = null,
		public readonly bool $dry_run = false,
	) {
		if ( ! in_array( $channel, self::CHANNELS, true ) ) {
			throw new InvalidArgumentException( esc_html( sprintf( 'Write channel %s is unknown.', $channel ) ) );
		}
	}
}
