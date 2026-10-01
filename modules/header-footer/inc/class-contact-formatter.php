<?php
/**
 * Formats the contact data of the theme settings for links.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\HeaderFooter;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Turns the phone number of the settings into the target of a tel: link.
 *
 * Editors type numbers in many forms, such as "+49 (0) 30 123-45" or
 * "030 / 123 45". The link keeps a leading plus and the digits only; the
 * trunk zero in brackets of an international number goes. No phone library:
 * the text of the number stays as typed, only the link target is cleaned.
 *
 * @since 1.0.0
 */
final class Contact_Formatter {

	/**
	 * Fewest digits a phone number needs for a link.
	 *
	 * @since 1.0.0
	 */
	public const MIN_DIGITS = 3;

	/**
	 * Returns the tel: link of a phone number.
	 *
	 * Example: "+49 (0) 30 123-45" gives "tel:+493012345"; "abc" gives null.
	 *
	 * @since 1.0.0
	 *
	 * @param string $phone Phone number as typed.
	 * @return string|null Link target, or null for fewer than three digits.
	 */
	public static function tel_href( string $phone ): ?string {
		$phone  = trim( (string) preg_replace( '~\(\s*0\s*\)~', '', $phone ) );
		$digits = (string) preg_replace( '~\D~', '', $phone );
		if ( strlen( $digits ) < self::MIN_DIGITS ) {
			return null;
		}
		return 'tel:' . ( str_starts_with( $phone, '+' ) ? '+' : '' ) . $digits;
	}
}
