<?php
/**
 * Warning about the font coverage on the design page.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Admin;

use Creationell\WpTheme\Settings\Acf\Options_Pages;
use Creationell\WpTheme\Settings\Font_Catalog;
use Creationell\WpTheme\Settings\Font_Coverage;
use Creationell\WpTheme\Settings\Language;
use Creationell\WpTheme\Settings\Registry;
use Creationell\WpTheme\Settings\Settings;
use Locale;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Tells on the design page which active languages the chosen fonts do not cover.
 *
 * One line per font and language: the missing script, or the missing
 * characters (at most MAX_CHARACTERS, the rest as a count) in a bdi element
 * with the language of the characters. A font chosen for body and headings is
 * named once; inheriting headings are skipped. Findings of the font catalog
 * about the chosen fonts, and those without a font, follow in English like in
 * doctor. The page exists only with ACF, so Acf_Adapter hooks the notice.
 *
 * @since 1.0.0
 */
final class Font_Coverage_Notice {

	/**
	 * Characters a line names before it counts the rest.
	 *
	 * @since 1.0.0
	 */
	public const MAX_CHARACTERS = 20;

	/**
	 * Prints the notice on the design page; runs on admin_notices.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function render(): void {
		self::print( Options_Pages::current() );
	}

	/**
	 * Prints the notice when the page is the design page and a chosen font has a gap or a finding.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $page Menu slug of the current settings page; null prints nothing.
	 * @return void
	 */
	public static function print( ?string $page ): void {
		if ( Options_Pages::DESIGN !== $page ) {
			return;
		}
		$catalog = Font_Catalog::instance();
		$locales = Font_Coverage::site_locales();
		$lines   = array();
		$seen    = array();
		$named   = array();
		foreach ( Registry::instance()->all() as $key => $definition ) {
			if ( 'font' !== $definition->type ) {
				continue;
			}
			$stored = Settings::instance()->get( $key );
			if ( is_string( $stored ) ) {
				$named[ $stored ] = true;
			}
			$family = $catalog->for_setting( $key );
			if ( null === $family || isset( $seen[ $family->slug ] ) ) {
				continue;
			}
			$seen[ $family->slug ]  = true;
			$named[ $family->slug ] = true;
			foreach ( Font_Coverage::gaps( $family, $locales ) as $locale => $gap ) {
				$lines[] = self::line( $family->name(), $definition->label_text(), $locale, $gap, $locales );
			}
		}
		foreach ( $catalog->findings() as $finding ) {
			if ( '' === $finding['slug'] || isset( $named[ $finding['slug'] ] ) ) {
				$lines[] = esc_html( $finding['message'] );
			}
		}
		if ( array() === $lines ) {
			return;
		}
		$paragraphs = '';
		foreach ( $lines as $line ) {
			$paragraphs .= '<p>' . $line . '</p>';
		}
		printf(
			'<div class="notice notice-warning">%s</div>' . "\n",
			wp_kses(
				$paragraphs,
				array(
					'p'   => array(),
					'bdi' => array( 'lang' => true ),
				)
			)
		);
	}

	/**
	 * Returns one escaped line about a gap.
	 *
	 * @since 1.0.0
	 *
	 * @param string                                                 $font   Name of the font.
	 * @param string                                                 $label  Label of the setting.
	 * @param string                                                 $locale Locale.
	 * @param array{scripts: list<string>, characters: list<string>} $gap    Gap from Font_Coverage::gaps().
	 * @param array<int, string>                                     $locales Active locales, to tell variants of a language apart.
	 * @return string HTML.
	 */
	private static function line( string $font, string $label, string $locale, array $gap, array $locales ): string {
		$language = self::language_name( $locale, $locales );
		if ( array() !== $gap['scripts'] ) {
			return esc_html(
				sprintf(
					/* translators: 1: font name, 2: label of the font setting, 3: language, 4: comma-separated script names. */
					_n( 'The font "%1$s" (%2$s) does not cover %3$s: the script %4$s is missing.', 'The font "%1$s" (%2$s) does not cover %3$s: the scripts %4$s are missing.', count( $gap['scripts'] ), 'creationell-wp-theme' ),
					$font,
					$label,
					$language,
					implode( ', ', array_map( array( self::class, 'script_name' ), $gap['scripts'] ) )
				)
			);
		}
		$shown      = array_map( array( self::class, 'with_base' ), array_slice( $gap['characters'], 0, self::MAX_CHARACTERS ) );
		$rest       = count( $gap['characters'] ) - count( $shown );
		$characters = '<bdi lang="' . esc_attr( Language::bcp47( $locale ) ) . '">' . esc_html( implode( ' ', $shown ) ) . '</bdi>';
		if ( $rest > 0 ) {
			/* translators: %d: number of further missing characters. */
			$characters .= ' ' . esc_html( sprintf( _n( '(and %d more)', '(and %d more)', $rest, 'creationell-wp-theme' ), $rest ) );
		}
		return sprintf(
			/* translators: 1: font name, 2: label of the font setting, 3: language, 4: missing characters. */
			esc_html__( 'The font "%1$s" (%2$s) does not cover %3$s: these characters are missing: %4$s', 'creationell-wp-theme' ),
			esc_html( $font ),
			esc_html( $label ),
			esc_html( $language ),
			$characters
		);
	}

	/**
	 * Returns a combining mark on a dotted circle, so it does not float on the space before it; other characters as they are.
	 *
	 * @since 1.0.0
	 *
	 * @param string $character Character.
	 * @return string Character to show.
	 */
	private static function with_base( string $character ): string {
		return 1 === preg_match( '~^\\p{M}$~u', $character ) ? "\u{25CC}" . $character : $character;
	}

	/**
	 * Returns the name of a language in the language of the user; the locale without the intl extension.
	 *
	 * With another active variant of the same language the name holds the
	 * region, e.g. Portuguese (Brazil) next to Portuguese (Portugal).
	 *
	 * @since 1.0.0
	 *
	 * @param string             $locale  Locale.
	 * @param array<int, string> $locales Active locales.
	 * @return string Name.
	 */
	private static function language_name( string $locale, array $locales ): string {
		if ( ! class_exists( Locale::class ) ) {
			return $locale;
		}
		$code     = strtolower( explode( '_', str_replace( '-', '_', $locale ) )[0] );
		$variants = 0;
		foreach ( $locales as $other ) {
			if ( strtolower( explode( '_', str_replace( '-', '_', $other ) )[0] ) === $code ) {
				++$variants;
			}
		}
		$display = function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		// WordPress marks address forms as a variant (de_DE_formal); ICU would print it as FORMAL.
		$plain = (string) preg_replace( '~_(?:in)?formal$~i', '', $locale );
		$name  = $variants > 1 ? Locale::getDisplayName( $plain, $display ) : Locale::getDisplayLanguage( $plain, $display );
		return is_string( $name ) && '' !== $name ? $name : $locale;
	}

	/**
	 * Returns the translated name of a script of the font catalog.
	 *
	 * @since 1.0.0
	 *
	 * @param string $script Script, see Font_Catalog::SCRIPTS.
	 * @return string Name.
	 */
	private static function script_name( string $script ): string {
		return match ( $script ) {
			'latin'      => __( 'Latin', 'creationell-wp-theme' ),
			'latin-ext'  => __( 'Latin Extended', 'creationell-wp-theme' ),
			'vietnamese' => __( 'Vietnamese', 'creationell-wp-theme' ),
			'cyrillic'   => __( 'Cyrillic', 'creationell-wp-theme' ),
			'arabic'     => __( 'Arabic', 'creationell-wp-theme' ),
			'cjk'        => __( 'CJK (Chinese and Japanese)', 'creationell-wp-theme' ),
			default      => $script,
		};
	}
}
