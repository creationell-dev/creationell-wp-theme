<?php
/**
 * Result of one write through the setter.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Key, status, translated message and details of one write.
 *
 * Details: "failures" for CONTRAST (the broken rules of Contrast_Rules::check()),
 * "lang" for writes in a secondary language.
 *
 * Example:
 *
 *     $result = Setter::instance()->set( 'color_primary', '#ffff00', $ctx );
 *     if ( Set_Status::CONTRAST === $result->status ) {
 *         echo esc_html( $result->message ); // "... at least 4.5:1 is needed."
 *     }
 *
 * @api
 * @since 1.0.0
 */
final class Set_Result {

	/**
	 * Keeps the fields.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $key     Setting key.
	 * @param Set_Status           $status  Status.
	 * @param string               $message Translated message, empty for a plain success.
	 * @param array<string, mixed> $data    Details.
	 */
	public function __construct(
		public readonly string $key,
		public readonly Set_Status $status,
		public readonly string $message = '',
		public readonly array $data = array(),
	) {
	}

	/**
	 * Tells whether the value is in place.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True for saved, removed and unchanged.
	 */
	public function succeeded(): bool {
		return $this->status->succeeded();
	}
}
