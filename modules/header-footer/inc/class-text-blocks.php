<?php
/**
 * Render code of the blocks footer-text, contact, social-links and language-switcher.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\HeaderFooter;

use Creationell\WpTheme\Core\Capabilities;
use Creationell\WpTheme\Settings\Acf\Options_Pages;
use Creationell\WpTheme\Wpml\Language_Switcher;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Prints the text blocks of the module from the theme settings through the partials of the theme.
 *
 * The texts live in one place, the page Texts of the theme settings (level 1);
 * the parts hold no text of their own. Each block reads the values in the
 * language of the request and hands them to a partial that a child theme may
 * override: contact to template-parts/header-footer/contact.php, social links
 * to template-parts/header-footer/social-links.php, the footer text to
 * template-parts/footer/footer-info.php of the PHP footer. The language
 * switcher is a wrapper around creationell_wp_theme_language_switcher() of the
 * theme core, with the context "block".
 *
 * A block without values prints nothing in the front end; in the editor it
 * prints a placeholder that points to the theme settings, as a link for users
 * who may change the texts.
 *
 * @since 1.0.0
 */
final class Text_Blocks {

	/**
	 * Heading levels of the contact block; the first one is the default.
	 *
	 * @since 1.0.0
	 */
	public const HEADING_LEVELS = array( 2, 3, 4, 5, 6 );

	/**
	 * Displays of the language switcher block, which become the variant of the switcher; the first one is the default.
	 *
	 * @since 1.0.0
	 */
	public const DISPLAYS = array( 'list', 'dropdown' );

	/**
	 * Returns the arguments of the contact partial for the attributes of a contact block.
	 *
	 * Reads contact_address, contact_phone and contact_email of the settings for
	 * the parts the block shows; a hidden part stays empty. An email address
	 * that is_email() rejects stays out, a phone number without a link target
	 * shows as text.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $attributes Block attributes: heading, headingLevel, showAddress, showPhone, showEmail.
	 * @return array{heading: string, level: int, address: string, phone: string, phone_href: string, email: string} Arguments.
	 */
	public static function contact_args( array $attributes ): array {
		$address = Header_Blocks::flag( $attributes['showAddress'] ?? null, true ) ? self::setting( 'contact_address' ) : '';
		$phone   = Header_Blocks::flag( $attributes['showPhone'] ?? null, true ) ? self::setting( 'contact_phone' ) : '';
		$email   = Header_Blocks::flag( $attributes['showEmail'] ?? null, true ) ? self::setting( 'contact_email' ) : '';

		return array(
			'heading'    => self::text( $attributes['heading'] ?? null, __( 'Contact', 'creationell-wp-theme' ) ),
			'level'      => self::level( $attributes['headingLevel'] ?? null ),
			'address'    => $address,
			'phone'      => $phone,
			'phone_href' => '' === $phone ? '' : (string) Contact_Formatter::tel_href( $phone ),
			'email'      => '' !== $email && false !== is_email( $email ) ? $email : '',
		);
	}

	/**
	 * Prints the contact block: the contact partial, or nothing without contact data.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param bool         $is_editor  Whether the block renders in the editor.
	 * @return void
	 */
	public static function contact( array $attributes, bool $is_editor ): void {
		$args = self::contact_args( $attributes );
		if ( '' === $args['address'] && '' === $args['phone'] && '' === $args['email'] ) {
			self::settings_placeholder( $is_editor );
			return;
		}
		get_template_part( 'template-parts/header-footer/contact', null, $args );
	}

	/**
	 * Prints the social links block: the social links partial, or nothing without links.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $attributes Block attributes: label.
	 * @param bool         $is_editor  Whether the block renders in the editor.
	 * @return void
	 */
	public static function social_links( array $attributes, bool $is_editor ): void {
		$links = Social_Networks::links();
		if ( array() === $links ) {
			self::settings_placeholder( $is_editor );
			return;
		}
		get_template_part(
			'template-parts/header-footer/social-links',
			null,
			array(
				'label' => self::text( $attributes['label'] ?? null, __( 'Social media', 'creationell-wp-theme' ) ),
				'links' => $links,
			)
		);
	}

	/**
	 * Prints the footer text block: the footer info partial of the PHP footer.
	 *
	 * The partial prints the footer text of the settings or the copyright line,
	 * then the action creationell_wp_theme_footer_meta, so the block is never
	 * empty and needs no placeholder.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function footer_text(): void {
		get_template_part( 'template-parts/footer/footer-info' );
	}

	/**
	 * Prints the language switcher block: the switcher of the theme core in the context "block".
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $attributes Block attributes: display, list or dropdown.
	 * @param bool         $is_editor  Whether the block renders in the editor.
	 * @return void
	 */
	public static function language_switcher( array $attributes, bool $is_editor ): void {
		$html = creationell_wp_theme_language_switcher(
			array(
				'context' => 'block',
				'variant' => Header_Blocks::choice( $attributes['display'] ?? null, self::DISPLAYS, self::DISPLAYS[0] ),
			)
		);
		if ( '' === $html ) {
			if ( $is_editor ) {
				self::placeholder( __( 'Language switcher (WPML)', 'creationell-wp-theme' ), '' );
			}
			return;
		}
		echo wp_kses( $html, Language_Switcher::allowed_html() );
	}

	/**
	 * Returns a heading level of HEADING_LEVELS; Blockstudio passes a select as its value or as an option array.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Attribute value.
	 * @return int Level, 2 outside the list.
	 */
	public static function level( mixed $value ): int {
		if ( is_array( $value ) ) {
			$value = $value['value'] ?? null;
		}
		if ( is_string( $value ) && 1 === preg_match( '~^[0-9]$~', $value ) ) {
			$value = (int) $value;
		}
		return is_int( $value ) && in_array( $value, self::HEADING_LEVELS, true ) ? $value : self::HEADING_LEVELS[0];
	}

	/**
	 * Returns a text attribute without surrounding white space, or the fallback for an empty or non-text value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $value    Attribute value.
	 * @param string $fallback Default text.
	 * @return string Text.
	 */
	private static function text( mixed $value, string $fallback ): string {
		$value = is_string( $value ) ? trim( $value ) : '';
		return '' === $value ? $fallback : $value;
	}

	/**
	 * Returns a text setting without surrounding white space; anything but a string gives an empty string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Setting key.
	 * @return string Value.
	 */
	private static function setting( string $key ): string {
		$value = creationell_wp_theme_setting( $key );
		return is_string( $value ) ? trim( $value ) : '';
	}

	/**
	 * Prints the placeholder of an empty text block in the editor; a link to the page Texts for users with the right to change the texts.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $is_editor Whether the block renders in the editor; outside it prints nothing.
	 * @return void
	 */
	private static function settings_placeholder( bool $is_editor ): void {
		if ( ! $is_editor ) {
			return;
		}
		$url = current_user_can( Capabilities::MANAGE_BASIC ) ? admin_url( 'admin.php?page=' . Options_Pages::TEXTS ) : '';
		self::placeholder( __( 'Set in Theme settings → Texts', 'creationell-wp-theme' ), $url );
	}

	/**
	 * Prints a placeholder paragraph, linked when a URL is given.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Text.
	 * @param string $url  Link target, or empty.
	 * @return void
	 */
	private static function placeholder( string $text, string $url ): void {
		if ( '' === $url ) {
			printf( '<p class="creationell-theme-placeholder">%s</p>', esc_html( $text ) );
			return;
		}
		printf( '<p class="creationell-theme-placeholder"><a href="%1$s">%2$s</a></p>', esc_url( $url ), esc_html( $text ) );
	}
}
