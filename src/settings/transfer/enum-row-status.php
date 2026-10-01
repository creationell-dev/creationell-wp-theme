<?php
/**
 * Outcome of one row of an import plan.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

use BackedEnum;
use InvalidArgumentException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Tells what the import does with one key or module: write it, skip it or reject it.
 *
 * Skipped rows are expected (unchanged, locked, unknown to this theme version);
 * rejected rows are values the site refuses (rights, validation, references).
 *
 * @since 1.0.0
 */
enum Row_Status: string {

	case SAVE                      = 'save';
	case RESET                     = 'reset';
	case MODULE_SWITCH             = 'module_switch';
	case UNCHANGED                 = 'unchanged';
	case ABSENT                    = 'absent';
	case LOCKED                    = 'locked';
	case NOT_TRANSLATABLE          = 'not_translatable';
	case UNKNOWN_KEY               = 'unknown_key';
	case REMOVED_KEY               = 'removed_key';
	case RENAMED                   = 'renamed';
	case SENSITIVE_SKIPPED         = 'sensitive_skipped';
	case LANGUAGE_INACTIVE         = 'language_inactive';
	case LINE_UNMAPPED             = 'line_unmapped';
	case LINE_NEVER_IMPORTED       = 'line_never_imported';
	case SECTION_UNKNOWN           = 'section_unknown';
	case MODULE_UNCHANGED          = 'module_unchanged';
	case MODULE_LOCKED             = 'module_locked';
	case FORBIDDEN                 = 'forbidden';
	case INVALID                   = 'invalid';
	case CONTRAST                  = 'contrast';
	case REFERENCE_UNRESOLVED      = 'reference_unresolved';
	case MODULE_DENIED             = 'module_denied';
	case MODULE_INVALID            = 'module_invalid';
	case MODULE_NEEDS_CONFIRMATION = 'module_needs_confirmation';

	/**
	 * Returns the group of the status.
	 *
	 * @since 1.0.0
	 *
	 * @return string "write", "skip" or "reject".
	 */
	public function group(): string {
		return match ( $this ) {
			self::SAVE, self::RESET, self::MODULE_SWITCH => 'write',
			self::FORBIDDEN, self::INVALID, self::CONTRAST, self::REFERENCE_UNRESOLVED,
			self::MODULE_DENIED, self::MODULE_INVALID, self::MODULE_NEEDS_CONFIRMATION => 'reject',
			default => 'skip',
		};
	}

	/**
	 * Maps a status of the settings writer to a row status.
	 *
	 * "saved" becomes save, "removed" reset, "line_switch_unavailable"
	 * line_never_imported, "module_switch_only" section_unknown; statuses with the
	 * same name map one to one.
	 *
	 * @since 1.0.0
	 *
	 * @param BackedEnum|string $status Status of the settings writer, or its value.
	 * @return self Row status.
	 * @throws Transfer_Exception With language_all for "language_all_unsupported": the whole transfer stops.
	 * @throws InvalidArgumentException For a status without a row status.
	 */
	public static function from_set_status( BackedEnum|string $status ): self {
		$value = $status instanceof BackedEnum ? $status->value : $status;
		if ( ! is_string( $value ) ) {
			self::unknown( (string) $value );
		}
		$row = match ( $value ) {
			'saved'                    => self::SAVE,
			'removed'                  => self::RESET,
			'line_switch_unavailable'  => self::LINE_NEVER_IMPORTED,
			'module_switch_only'       => self::SECTION_UNKNOWN,
			'language_all_unsupported' => Transfer_Exception::raise( Transfer_Error::LANGUAGE_ALL ),
			'unchanged', 'unknown_key', 'forbidden', 'locked', 'not_translatable', 'invalid', 'contrast' => self::from( $value ),
			default                    => null,
		};
		if ( null === $row ) {
			self::unknown( $value );
		}
		return $row;
	}

	/**
	 * Throws for a setter status without a row status.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Setter status value.
	 * @return never
	 * @throws InvalidArgumentException Always.
	 */
	private static function unknown( string $value ): never {
		throw new InvalidArgumentException( esc_html( sprintf( 'Setter status %s has no row status.', $value ) ) );
	}
}
