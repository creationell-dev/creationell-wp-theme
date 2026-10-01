<?php
/**
 * One row of an import plan or report.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

use Creationell\WpTheme\Modules\Switch_Result;
use InvalidArgumentException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Tells what the import does with one key in one language, or with one module.
 *
 * "current" is the backend value on this site (null: no deviation, the lower
 * layers apply); "incoming" is the value the import writes: the file value
 * with its references resolved to IDs and URLs of this site. A module row keeps
 * the result of the switcher's dry run in $switch_result.
 *
 * @since 1.0.0
 */
final class Plan_Row {

	/**
	 * Keeps the fields.
	 *
	 * @since 1.0.0
	 *
	 * @param string             $section  Section ID, e.g. "settings".
	 * @param string             $key      Setting key or module switch key.
	 * @param string|null        $lang     Language code of a translated value; null for values that are the same in every language.
	 * @param mixed              $current  Backend value on this site, or null.
	 * @param mixed              $incoming Value to write, or the file value when the row is skipped or rejected.
	 * @param Row_Status         $status   Status.
	 * @param string             $message  Translated message; empty for a plain write.
	 * @param Switch_Result|null $switch_result Result of the module switcher for module rows.
	 */
	public function __construct(
		public readonly string $section,
		public readonly string $key,
		public readonly ?string $lang,
		public readonly mixed $current,
		public readonly mixed $incoming,
		public readonly Row_Status $status,
		public readonly string $message = '',
		public readonly ?Switch_Result $switch_result = null,
	) {
	}

	/**
	 * Returns a copy with another status and message.
	 *
	 * @since 1.0.0
	 *
	 * @param Row_Status         $status  Status.
	 * @param string             $message Message.
	 * @param Switch_Result|null $switch_result Result of the module switcher; null keeps the one of this row.
	 * @return self Row.
	 */
	public function with_status( Row_Status $status, string $message = '', ?Switch_Result $switch_result = null ): self {
		return new self( $this->section, $this->key, $this->lang, $this->current, $this->incoming, $status, $message, $switch_result ?? $this->switch_result );
	}

	/**
	 * Returns the group of the status: "write", "skip" or "reject".
	 *
	 * @since 1.0.0
	 *
	 * @return string Group.
	 */
	public function group(): string {
		return $this->status->group();
	}

	/**
	 * Returns the row as array, without the switch result.
	 *
	 * @since 1.0.0
	 *
	 * @return array{section: string, key: string, lang: string|null, current: mixed, incoming: mixed, status: string, message: string} Row.
	 */
	public function to_array(): array {
		return array(
			'section'  => $this->section,
			'key'      => $this->key,
			'lang'     => $this->lang,
			'current'  => $this->current,
			'incoming' => $this->incoming,
			'status'   => $this->status->value,
			'message'  => $this->message,
		);
	}

	/**
	 * Builds a row from to_array().
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $data Row as array.
	 * @return self Row.
	 * @throws InvalidArgumentException When a field is missing or has the wrong type.
	 */
	public static function from_array( array $data ): self {
		$status = is_string( $data['status'] ?? null ) ? Row_Status::tryFrom( $data['status'] ) : null;
		$lang   = $data['lang'] ?? null;
		if ( ! is_string( $data['section'] ?? null ) || ! is_string( $data['key'] ?? null ) || ( null !== $lang && ! is_string( $lang ) )
			|| null === $status || ! is_string( $data['message'] ?? null )
			|| ! array_key_exists( 'current', $data ) || ! array_key_exists( 'incoming', $data ) ) {
			throw new InvalidArgumentException( 'The plan row is incomplete.' );
		}
		return new self( $data['section'], $data['key'], $lang, $data['current'], $data['incoming'], $status, $data['message'] );
	}
}
