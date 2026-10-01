<?php
/**
 * Language switcher of the theme core.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Wpml;

use Creationell\WpTheme\Settings\Language;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Renders the language switcher from the languages WPML gives; the one markup for the classic header and the header/footer block.
 *
 * The markup follows Bootstrap: a dropdown (button and menu) or an inline
 * list of links, without flags and without WPML styles. The first output of
 * a request is a navigation landmark, every further one a group, so a page
 * with a switcher in the header and the footer has one landmark "Language".
 * Every link carries lang and hreflang in BCP 47 form; the current language
 * has aria-current="page", a language without translation of the page says
 * that its link leads to the home page.
 *
 * Nothing renders without WPML, with fewer than two languages or before init;
 * reading the languages translates nothing.
 *
 * @since 1.0.0
 */
final class Language_Switcher {

	/**
	 * Filter for the arguments of a switcher.
	 *
	 * @since 1.0.0
	 */
	public const FILTER_ARGS = 'creationell_wp_theme_language_switcher_args';

	/**
	 * Filter for the list of languages.
	 *
	 * @since 1.0.0
	 */
	public const FILTER_LANGUAGES = 'creationell_wp_theme_language_switcher_languages';

	/**
	 * CSS class of the outer element.
	 *
	 * @since 1.0.0
	 */
	public const CSS_CLASS = 'creationell-theme-language-switcher';

	/**
	 * Prefix of the ID of a dropdown button; the number of the output follows.
	 *
	 * @since 1.0.0
	 */
	public const ID_PREFIX = 'creationell-theme-lang-';

	/**
	 * Variants with the display each one uses by default.
	 *
	 * @since 1.0.0
	 */
	public const VARIANTS = array(
		'dropdown' => 'code',
		'list'     => 'native',
	);

	/**
	 * Displays of the language: the code in upper case or the native name.
	 *
	 * @since 1.0.0
	 */
	public const DISPLAYS = array( 'code', 'native' );

	/**
	 * Places a switcher renders in; the first one is the default.
	 *
	 * @since 1.0.0
	 */
	public const CONTEXTS = array( 'navbar', 'footer', 'block' );

	/**
	 * Arguments WPML gets for wpml_active_languages: all languages, in the order of the WPML settings.
	 *
	 * @since 1.0.0
	 */
	public const WPML_ARGS = array(
		'skip_missing' => 0,
		'orderby'      => 'custom',
	);

	/**
	 * Name the notices give to the caller.
	 */
	private const PUBLIC_NAME = 'creationell_wp_theme_language_switcher';

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Languages of the request, null until read.
	 *
	 * @var array<int, array{code: string, name: string, locale: string, bcp47: string, url: string, current: bool, missing: bool}>|null
	 */
	private ?array $languages = null;

	/**
	 * Number of switchers rendered in this request.
	 *
	 * @var int
	 */
	private int $outputs = 0;

	/**
	 * Returns the shared instance of the request.
	 *
	 * @since 1.0.0
	 *
	 * @return self Instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Replaces the shared instance; null builds a new one on the next call of instance().
	 *
	 * @since 1.0.0
	 *
	 * @param self|null $switcher Instance.
	 * @return void
	 */
	public static function set_instance( ?self $switcher ): void {
		self::$instance = $switcher;
	}

	/**
	 * Returns the switcher as escaped HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $args Arguments, see arguments().
	 * @return string HTML; empty without WPML, before init, when disabled or with fewer than two languages.
	 */
	public function render( array $args ): string {
		if ( ! Wpml_Integration::is_active() ) {
			return '';
		}
		if ( 0 === did_action( 'init' ) ) {
			// Not translated: this runs before the translations may load.
			_doing_it_wrong( esc_html( self::PUBLIC_NAME ), esc_html( 'The language switcher renders from the init action on; before it, it stays empty.' ), '1.0.0' );
			return '';
		}
		$args = $this->arguments( $args );
		if ( ! $args['enabled'] ) {
			return '';
		}
		$languages = $this->languages();
		if ( count( $languages ) < 2 ) {
			return '';
		}

		++$this->outputs;
		$current = $languages[0];
		foreach ( $languages as $language ) {
			if ( $language['current'] ) {
				$current = $language;
				break;
			}
		}
		$inner = 'list' === $args['variant']
			? self::list_markup( $languages, $args['display'], $current['bcp47'] )
			: self::dropdown_markup( $languages, $args['display'], $current, self::ID_PREFIX . $this->outputs );
		$class = self::CSS_CLASS . ( 'dropdown' === $args['variant'] ? ' dropdown' : '' );
		$label = __( 'Language', 'creationell-wp-theme' );

		if ( 1 === $this->outputs ) {
			return '<nav class="' . esc_attr( $class ) . '" aria-label="' . esc_attr( $label ) . '">' . $inner . '</nav>';
		}
		return '<div class="' . esc_attr( $class ) . '" role="group" aria-label="' . esc_attr( $label ) . '">' . $inner . '</div>';
	}

	/**
	 * Returns the arguments of a switcher after defaults and the filter.
	 *
	 * Keys: enabled (bool, default true), variant ("dropdown" or "list",
	 * default "dropdown"), display ("code" or "native"; default "code" for the
	 * dropdown, "native" for the list), context ("navbar", "footer" or "block",
	 * default "navbar"). Unknown values fall back to the default, unknown keys
	 * are dropped.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $args Arguments of the caller.
	 * @return array{enabled: bool, variant: string, display: string, context: string} Arguments.
	 */
	public function arguments( array $args ): array {
		$args = self::normalize( $args );

		/**
		 * Filters the arguments of a language switcher.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $args    Arguments: enabled (bool), variant ("dropdown" or "list"), display ("code", "native", or null for the default of the variant), context ("navbar", "footer" or "block").
		 * @param string $context Place of the switcher, the same as $args['context'].
		 */
		$filtered = apply_filters( 'creationell_wp_theme_language_switcher_args', $args, $args['context'] );
		$args     = is_array( $filtered ) ? self::normalize( $filtered ) : $args;

		return array(
			'enabled' => $args['enabled'],
			'variant' => $args['variant'],
			'display' => $args['display'] ?? self::VARIANTS[ $args['variant'] ],
			'context' => $args['context'],
		);
	}

	/**
	 * Returns the languages of the current page, read once per request.
	 *
	 * Comes from wpml_active_languages with WPML_ARGS, then passes the filter
	 * creationell_wp_theme_language_switcher_languages. Entries without code,
	 * name or a URL that esc_url() keeps fall out. Without WPML and before init
	 * the list is empty.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{code: string, name: string, locale: string, bcp47: string, url: string, current: bool, missing: bool}> Languages in the order of the WPML settings.
	 */
	public function languages(): array {
		if ( null !== $this->languages ) {
			return $this->languages;
		}
		if ( ! Wpml_Integration::is_active() || 0 === did_action( 'init' ) ) {
			return array();
		}

		$wpml      = apply_filters( 'wpml_active_languages', null, self::WPML_ARGS );
		$languages = array();
		foreach ( is_array( $wpml ) ? $wpml : array() as $key => $entry ) {
			$language = is_array( $entry ) ? self::from_wpml( $entry, is_string( $key ) ? $key : '' ) : null;
			if ( null !== $language ) {
				$languages[] = $language;
			}
		}

		/**
		 * Filters the languages of the language switcher.
		 *
		 * Each entry has the keys code, name (native name), locale (WordPress
		 * locale), bcp47 (tag for lang and hreflang), url, current (bool) and
		 * missing (bool, true when the page has no translation and the URL
		 * leads to the home page of the language). Entries without code, name
		 * or a valid URL are dropped after the filter.
		 *
		 * @since 1.0.0
		 *
		 * @param array $languages Languages in the order of the WPML settings.
		 */
		$filtered        = apply_filters( 'creationell_wp_theme_language_switcher_languages', $languages );
		$this->languages = self::valid( is_array( $filtered ) ? $filtered : array() );
		return $this->languages;
	}

	/**
	 * Returns a WordPress locale as BCP 47 tag, without the suffix _formal or _informal.
	 *
	 * Same as Settings\Language::bcp47().
	 *
	 * @since 1.0.0
	 *
	 * @param string $locale WordPress locale, e.g. "de_DE_formal".
	 * @return string Tag, e.g. "de-DE"; empty for an empty locale.
	 */
	public static function bcp47( string $locale ): string {
		return Language::bcp47( $locale );
	}

	/**
	 * Returns the tags and attributes of the switcher markup for wp_kses().
	 *
	 * Templates print the switcher with wp_kses( $html, Language_Switcher::allowed_html() ).
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, bool>> Allowed attributes by tag.
	 */
	public static function allowed_html(): array {
		return array(
			'nav'    => array(
				'class'      => true,
				'aria-label' => true,
			),
			'div'    => array(
				'class'      => true,
				'role'       => true,
				'aria-label' => true,
			),
			'button' => array(
				'type'           => true,
				'class'          => true,
				'data-bs-toggle' => true,
				'aria-expanded'  => true,
				'id'             => true,
			),
			'span'   => array(
				'class' => true,
				'lang'  => true,
			),
			'ul'     => array(
				'class'           => true,
				'aria-labelledby' => true,
			),
			'li'     => array(
				'class' => true,
			),
			'a'      => array(
				'class'        => true,
				'href'         => true,
				'lang'         => true,
				'hreflang'     => true,
				'aria-current' => true,
			),
		);
	}

	/**
	 * Returns the arguments with known keys and values; display stays null when not chosen.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $args Arguments.
	 * @return array{enabled: bool, variant: string, display: string|null, context: string} Arguments.
	 */
	private static function normalize( array $args ): array {
		$variant = $args['variant'] ?? null;
		$display = $args['display'] ?? null;
		$context = $args['context'] ?? null;
		return array(
			'enabled' => (bool) ( $args['enabled'] ?? true ),
			'variant' => is_string( $variant ) && isset( self::VARIANTS[ $variant ] ) ? $variant : 'dropdown',
			'display' => is_string( $display ) && in_array( $display, self::DISPLAYS, true ) ? $display : null,
			'context' => is_string( $context ) && in_array( $context, self::CONTEXTS, true ) ? $context : self::CONTEXTS[0],
		);
	}

	/**
	 * Maps one entry of wpml_active_languages.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $entry Entry.
	 * @param string       $key   Array key, the language code.
	 * @return array{code: string, name: string, locale: string, bcp47: string, url: string, current: bool, missing: bool}|null Language, or null without code.
	 */
	private static function from_wpml( array $entry, string $key ): ?array {
		$code = self::first_string( $entry, array( 'code', 'language_code' ), $key );
		if ( '' === $code ) {
			return null;
		}
		$locale = self::first_string( $entry, array( 'default_locale' ), '' );
		$bcp47  = self::bcp47( $locale );
		return array(
			'code'    => $code,
			'name'    => self::first_string( $entry, array( 'native_name', 'translated_name' ), $code ),
			'locale'  => $locale,
			'bcp47'   => '' === $bcp47 ? $code : $bcp47,
			'url'     => self::first_string( $entry, array( 'url' ), '' ),
			'current' => ! empty( $entry['active'] ),
			'missing' => ! empty( $entry['missing'] ),
		);
	}

	/**
	 * Keeps the filtered entries with code, name and a URL that esc_url() keeps, in list form.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $languages Filtered languages.
	 * @return array<int, array{code: string, name: string, locale: string, bcp47: string, url: string, current: bool, missing: bool}> Languages.
	 */
	private static function valid( array $languages ): array {
		$valid = array();
		foreach ( $languages as $language ) {
			if ( ! is_array( $language ) ) {
				continue;
			}
			$code = self::first_string( $language, array( 'code' ), '' );
			$name = self::first_string( $language, array( 'name' ), '' );
			$url  = self::first_string( $language, array( 'url' ), '' );
			if ( '' === $code || '' === $name || '' === esc_url( $url ) ) {
				continue;
			}
			$locale  = self::first_string( $language, array( 'locale' ), '' );
			$bcp47   = self::first_string( $language, array( 'bcp47' ), self::bcp47( $locale ) );
			$valid[] = array(
				'code'    => $code,
				'name'    => $name,
				'locale'  => $locale,
				'bcp47'   => '' === $bcp47 ? $code : $bcp47,
				'url'     => $url,
				'current' => ! empty( $language['current'] ),
				'missing' => ! empty( $language['missing'] ),
			);
		}
		return $valid;
	}

	/**
	 * Returns the first non-empty string among the keys.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed>       $entry    Entry.
	 * @param array<int, string> $keys     Keys in order.
	 * @param string             $fallback Value when none is a non-empty string.
	 * @return string Value.
	 */
	private static function first_string( array $entry, array $keys, string $fallback ): string {
		foreach ( $keys as $key ) {
			if ( isset( $entry[ $key ] ) && is_string( $entry[ $key ] ) && '' !== $entry[ $key ] ) {
				return $entry[ $key ];
			}
		}
		return $fallback;
	}

	/**
	 * Returns the dropdown: a toggle button with the current language and a menu with all languages.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array{code: string, name: string, locale: string, bcp47: string, url: string, current: bool, missing: bool}> $languages Languages.
	 * @param string                                                                                                                  $display   "code" or "native".
	 * @param array{code: string, name: string, locale: string, bcp47: string, url: string, current: bool, missing: bool}             $current   Current language.
	 * @param string                                                                                                                  $id        ID of the button.
	 * @return string HTML.
	 */
	private static function dropdown_markup( array $languages, string $display, array $current, string $id ): string {
		$shown  = 'code' === $display ? strtoupper( $current['code'] ) : $current['name'];
		$hidden = sprintf(
			/* translators: %s: native name of the current language, e.g. "Deutsch". */
			__( 'Language: %s. Change language', 'creationell-wp-theme' ),
			$current['name']
		);
		$html  = '<button type="button" class="btn btn-link nav-link dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="' . esc_attr( $id ) . '">';
		$html .= esc_html( $shown ) . ' <span class="visually-hidden">' . esc_html( $hidden ) . '</span></button>';
		$html .= '<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="' . esc_attr( $id ) . '">';
		foreach ( $languages as $language ) {
			$html .= '<li>' . self::link( $language, 'dropdown-item', 'native', $current['bcp47'] ) . '</li>';
		}
		return $html . '</ul>';
	}

	/**
	 * Returns the inline list of links.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array{code: string, name: string, locale: string, bcp47: string, url: string, current: bool, missing: bool}> $languages Languages.
	 * @param string                                                                                                                  $display   "code" or "native".
	 * @param string                                                                                                                  $page_lang BCP 47 tag of the page language.
	 * @return string HTML.
	 */
	private static function list_markup( array $languages, string $display, string $page_lang ): string {
		$html = '<ul class="list-inline mb-0">';
		foreach ( $languages as $language ) {
			$html .= '<li class="list-inline-item">' . self::link( $language, '', $display, $page_lang ) . '</li>';
		}
		return $html . '</ul>';
	}

	/**
	 * Returns the link to one language.
	 *
	 * The visible text is the native name, or the code with the native name for
	 * screen readers. The hint for a missing translation is in the page language.
	 *
	 * @since 1.0.0
	 *
	 * @param array{code: string, name: string, locale: string, bcp47: string, url: string, current: bool, missing: bool} $language  Language.
	 * @param string                                                                                                      $classes   Base CSS classes, may be empty.
	 * @param string                                                                                                      $display   "code" or "native".
	 * @param string                                                                                                      $page_lang BCP 47 tag of the page language.
	 * @return string HTML.
	 */
	private static function link( array $language, string $classes, string $display, string $page_lang ): string {
		$classes = trim( $classes . ( $language['current'] ? ' active' : '' ) );
		$html    = '<a' . ( '' === $classes ? '' : ' class="' . esc_attr( $classes ) . '"' )
			. ' href="' . esc_url( $language['url'] ) . '"'
			. ' lang="' . esc_attr( $language['bcp47'] ) . '"'
			. ' hreflang="' . esc_attr( $language['bcp47'] ) . '"'
			. ( $language['current'] ? ' aria-current="page"' : '' ) . '>';
		$html   .= 'code' === $display
			? esc_html( strtoupper( $language['code'] ) ) . ' <span class="visually-hidden">' . esc_html( $language['name'] ) . '</span>'
			: esc_html( $language['name'] );
		if ( $language['missing'] ) {
			$html .= ' <span class="visually-hidden" lang="' . esc_attr( $page_lang ) . '">' . esc_html__( '(home page)', 'creationell-wp-theme' ) . '</span>';
		}
		return $html . '</a>';
	}
}
