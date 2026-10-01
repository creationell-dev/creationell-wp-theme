<?php
/**
 * Admin notices of the theme.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Admin;

use Closure;
use InvalidArgumentException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Collects notices of the theme and prints them on admin_notices for the users allowed to see them.
 *
 * A notice holds a closure that builds its message when the notice renders, so
 * translations load after init and never before. Messages are plain text; an
 * empty message prints nothing. By default only users with manage_options see
 * a notice; a notice may name another capability, such as the template parts
 * capability of the module header-footer.
 *
 * @since 1.0.0
 */
final class Notices {

	/**
	 * Notice type for a problem that switches a function off.
	 *
	 * @since 1.0.0
	 */
	public const ERROR = 'error';

	/**
	 * Notice type for a setting that differs from the default.
	 *
	 * @since 1.0.0
	 */
	public const WARNING = 'warning';

	/**
	 * Notice type for information.
	 *
	 * @since 1.0.0
	 */
	public const INFO = 'info';

	/**
	 * Capability needed to see a notice that names no other one.
	 *
	 * @since 1.0.0
	 */
	public const CAPABILITY = 'manage_options';

	/**
	 * Queued notices by ID, in the order they were first added.
	 *
	 * @var array<string, array{type: string, message: Closure(): string, capability: string}>
	 */
	private static array $notices = array();

	/**
	 * Queues a notice; a later notice with the same ID replaces it.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $id         Notice ID: lower case letters, digits and hyphens.
	 * @param Closure $message    Builds the plain text message when the notice renders.
	 * @param string  $type       One of ERROR, WARNING, INFO; other values give WARNING.
	 * @param string  $capability Capability needed to see the notice; default manage_options.
	 * @phpstan-param Closure(): string $message
	 * @return void
	 * @throws InvalidArgumentException When the ID has other characters.
	 */
	public static function add( string $id, Closure $message, string $type = self::WARNING, string $capability = self::CAPABILITY ): void {
		if ( 1 !== preg_match( '~^[a-z0-9]+(?:-[a-z0-9]+)*$~', $id ) ) {
			throw new InvalidArgumentException( 'Invalid notice ID.' );
		}
		self::$notices[ $id ] = array(
			'type'       => in_array( $type, array( self::ERROR, self::WARNING, self::INFO ), true ) ? $type : self::WARNING,
			'message'    => $message,
			'capability' => '' === $capability ? self::CAPABILITY : $capability,
		);
	}

	/**
	 * Tells whether a notice is queued.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Notice ID.
	 * @return bool True when queued.
	 */
	public static function has( string $id ): bool {
		return isset( self::$notices[ $id ] );
	}

	/**
	 * Removes a queued notice.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Notice ID.
	 * @return void
	 */
	public static function remove( string $id ): void {
		unset( self::$notices[ $id ] );
	}

	/**
	 * Removes all queued notices.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function clear(): void {
		self::$notices = array();
	}

	/**
	 * Prints each queued notice the current user may see; runs on admin_notices.
	 *
	 * The message of a notice is built only for users with its capability.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function render(): void {
		foreach ( self::$notices as $id => $notice ) {
			if ( ! current_user_can( $notice['capability'] ) ) {
				continue;
			}
			$message = ( $notice['message'] )();
			if ( '' === $message ) {
				continue;
			}
			printf(
				'<div id="%1$s" class="notice notice-%2$s"><p>%3$s</p></div>' . "\n",
				esc_attr( 'creationell-wp-theme-notice-' . $id ),
				esc_attr( $notice['type'] ),
				esc_html( $message )
			);
		}
	}
}
