<?php
/**
 * Request of a settings export.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

use InvalidArgumentException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Names the sections and languages to export, the user who exports and the channel.
 *
 * Example:
 *
 *     $request = new Export_Request( array( 'settings', 'modules' ), array( 'de', 'en' ), get_current_user_id(), 'cli' );
 *     $file    = Exporter::instance()->export( $request );
 *     echo Json_Codec::encode( $file->to_array() );
 *
 * @since 1.0.0
 */
final class Export_Request {

	/**
	 * Checks the fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>      $sections Section IDs, e.g. array( 'settings', 'modules' ).
	 * @param array<int, string>|null $langs    Language codes; null for every active language.
	 * @param int                     $user_id  User who exports; the capability is checked for this user.
	 * @param string                  $channel  Channel of the log entry: "admin" or "cli".
	 * @phpstan-param list<string> $sections
	 * @phpstan-param list<string>|null $langs
	 * @throws InvalidArgumentException When a field has no valid value.
	 */
	public function __construct(
		public readonly array $sections,
		public readonly ?array $langs,
		public readonly int $user_id,
		public readonly string $channel,
	) {
		self::check_codes( $sections, 'section' );
		if ( null !== $langs ) {
			self::check_codes( $langs, 'language' );
		}
		if ( $user_id < 0 ) {
			throw new InvalidArgumentException( 'The user ID of an export must not be negative.' );
		}
		if ( ! in_array( $channel, Transfer_Log::CHANNELS, true ) ) {
			throw new InvalidArgumentException( esc_html( sprintf( 'Export channel %s is unknown.', $channel ) ) );
		}
	}

	/**
	 * Checks that a value is a list of strings.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $codes Value.
	 * @param string       $name  Name of the field for the message.
	 * @return void
	 * @throws InvalidArgumentException When the value is no list of strings.
	 */
	public static function check_codes( array $codes, string $name ): void {
		if ( ! array_is_list( $codes ) ) {
			throw new InvalidArgumentException( esc_html( sprintf( 'The %s codes must be a list.', $name ) ) );
		}
		foreach ( $codes as $code ) {
			if ( ! is_string( $code ) || '' === $code ) {
				throw new InvalidArgumentException( esc_html( sprintf( 'Every %s code must be a non-empty string.', $name ) ) );
			}
		}
	}
}
