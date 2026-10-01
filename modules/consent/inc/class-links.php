<?php
/**
 * Links of the module consent: policy pages and the button that opens the preferences dialog.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\Consent;

use Closure;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Builds the policy links of the banner and the consent button of the footer and the block.
 *
 * The privacy policy page comes from the core (Settings > Privacy), the imprint
 * page from the setting consent_imprint_page. Both link only published pages,
 * in the language of the request. The button carries data-cc, which
 * CookieConsent binds when it starts, and stays hidden until then, so a visitor
 * without JavaScript never sees a button that does nothing.
 *
 * @since 1.0.0
 */
final class Links {

	/**
	 * CSS class of the consent button.
	 *
	 * @since 1.0.0
	 */
	public const BUTTON_CLASS = 'creationell-theme-consent-link';

	/**
	 * Bootstrap classes per appearance of the button.
	 *
	 * @since 1.0.0
	 */
	public const APPEARANCES = array(
		'link'   => 'btn btn-link p-0 align-baseline',
		'button' => 'btn btn-outline-secondary btn-sm',
	);

	/**
	 * Tags and attributes of the consent button markup, for wp_kses().
	 *
	 * @since 1.0.0
	 */
	public const ALLOWED_HTML = array(
		'div'    => array( 'class' => true ),
		'button' => array(
			'type'    => true,
			'class'   => true,
			'data-cc' => true,
			'hidden'  => true,
		),
	);

	/**
	 * Filter that shows the consent button in the footer.
	 *
	 * @since 1.0.0
	 */
	public const FILTER_FOOTER_LINK = 'creationell_wp_theme_consent_footer_link';

	/**
	 * Reads a theme setting.
	 *
	 * @var Closure
	 * @phpstan-var Closure(string): mixed
	 */
	private Closure $setting;

	/**
	 * Takes the settings reader; without one it reads creationell_wp_theme_setting().
	 *
	 * @since 1.0.0
	 *
	 * @param Closure|null $setting Returns the value of a setting key.
	 * @phpstan-param (Closure(string): mixed)|null $setting
	 */
	public function __construct( ?Closure $setting = null ) {
		$this->setting = $setting ?? static fn( string $key ): mixed => creationell_wp_theme_setting( $key );
	}

	/**
	 * Returns the links to the privacy policy page and the imprint page, where published.
	 *
	 * @since 1.0.0
	 *
	 * @return list<array{url: string, label: string}> Links in this order: privacy policy, imprint.
	 */
	public function policy_links(): array {
		$links   = array();
		$privacy = get_privacy_policy_url();
		if ( '' !== $privacy ) {
			$links[] = array(
				'url'   => esc_url_raw( $privacy ),
				'label' => __( 'Privacy policy', 'creationell-wp-theme' ),
			);
		}
		$imprint = $this->page_url( ( $this->setting )( 'consent_imprint_page' ) );
		if ( '' !== $imprint ) {
			$links[] = array(
				'url'   => $imprint,
				'label' => __( 'Imprint', 'creationell-wp-theme' ),
			);
		}
		return $links;
	}

	/**
	 * Returns the button that opens the preferences dialog.
	 *
	 * @since 1.0.0
	 *
	 * @param string $label      Label; empty gives "Cookie settings".
	 * @param string $appearance "link" or "button"; other values give "link".
	 * @param bool   $hidden     Whether the button stays hidden until CookieConsent runs; false in the block editor.
	 * @return string Markup.
	 */
	public static function button( string $label, string $appearance, bool $hidden = true ): string {
		$label   = '' === trim( $label ) ? __( 'Cookie settings', 'creationell-wp-theme' ) : $label;
		$classes = self::BUTTON_CLASS . ' ' . ( self::APPEARANCES[ $appearance ] ?? self::APPEARANCES['link'] );
		return '<button type="button" class="' . esc_attr( $classes ) . '" data-cc="show-preferencesModal"' . ( $hidden ? ' hidden' : '' ) . '>' . esc_html( $label ) . '</button>';
	}

	/**
	 * Prints the consent button in the footer; runs on creationell_wp_theme_footer_meta.
	 *
	 * Only while CookieConsent is the consent provider and the filter
	 * creationell_wp_theme_consent_footer_link is true.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function render_footer_link(): void {
		if ( ! Provider::is_effective() ) {
			return;
		}

		/**
		 * Filters whether the footer shows the link that opens the cookie settings.
		 *
		 * Switch it off when a page carries the block creationell-theme/consent-link
		 * at another place of every page. Values other than true or false keep the
		 * link and give a notice.
		 *
		 * Example, in the functions.php of a child theme:
		 *
		 *     add_filter( 'creationell_wp_theme_consent_footer_link', '__return_false' );
		 *
		 * @since 1.0.0
		 *
		 * @param bool $show Whether the footer shows the link; true by default.
		 */
		$show = apply_filters( 'creationell_wp_theme_consent_footer_link', true );
		if ( ! is_bool( $show ) ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html( sprintf( 'The filter %s must return true or false; true is used.', self::FILTER_FOOTER_LINK ) ),
				'1.0.0'
			);
			$show = true;
		}
		if ( $show ) {
			echo wp_kses( '<div class="small">' . self::button( '', 'link' ) . '</div>', self::ALLOWED_HTML );
		}
	}

	/**
	 * Returns the URL of a published page in the language of the request.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $page_id Page ID from the settings.
	 * @return string URL, empty for no page, an unpublished page or no ID.
	 */
	private function page_url( mixed $page_id ): string {
		if ( ! is_int( $page_id ) || $page_id <= 0 ) {
			return '';
		}
		// WPML maps the page to the page of the request language; without WPML the ID stays.
		$translated = apply_filters( 'wpml_object_id', $page_id, 'page', true );
		$translated = is_numeric( $translated ) ? (int) $translated : $page_id;
		if ( 'publish' !== get_post_status( $translated ) ) {
			return '';
		}
		$url = get_permalink( $translated );
		return is_string( $url ) ? esc_url_raw( $url ) : '';
	}
}
