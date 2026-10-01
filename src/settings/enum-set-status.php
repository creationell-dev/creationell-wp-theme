<?php
/**
 * Outcome of one write through the setter.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Status of a Set_Result, in the order the setter checks.
 *
 * SAVED, REMOVED and UNCHANGED are successes: the value is in place. The others
 * refuse the write and leave the stored value alone.
 *
 * Example:
 *
 *     $result = Setter::instance()->set( 'footer_text', 'Hello', $ctx );
 *     $exit   = $result->status->succeeded() ? 0 : 1;
 *
 * @api
 * @since 1.0.0
 */
enum Set_Status: string {

	case UNKNOWN_KEY              = 'unknown_key';
	case MODULE_SWITCH_ONLY       = 'module_switch_only';
	case FORBIDDEN                = 'forbidden';
	case LINE_SWITCH_UNAVAILABLE  = 'line_switch_unavailable';
	case LOCKED                   = 'locked';
	case NOT_TRANSLATABLE         = 'not_translatable';
	case LANGUAGE_ALL_UNSUPPORTED = 'language_all_unsupported';
	case INVALID                  = 'invalid';
	case CONTRAST                 = 'contrast';
	case SAVED                    = 'saved';
	case REMOVED                  = 'removed';
	case UNCHANGED                = 'unchanged';

	/**
	 * Tells whether the value is in place.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True for SAVED, REMOVED and UNCHANGED.
	 */
	public function succeeded(): bool {
		return in_array( $this, array( self::SAVED, self::REMOVED, self::UNCHANGED ), true );
	}
}
