<?php
/**
 * Theme data of theme.json from the theme settings: palette, color switches and font presets.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings;

use Closure;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * First layer of the Global Styles lock: the theme origin of theme.json always carries the tokens of the theme.
 *
 * Runs on wp_theme_json_data_theme and merges into the theme data, after the
 * theme.json of an active child theme was read:
 *
 * - the palette of the eight Bootstrap theme colors, each on var(--bs-<slug>),
 *   so that the editor and the front end follow the compiled stylesheet; a
 *   palette or color of a child theme.json does not survive;
 * - the color switches custom, customGradient, customDuotone, defaultPalette,
 *   defaultGradients and defaultDuotone set to false;
 * - the font presets "body" and "headings" with the stack of the fonts chosen
 *   in the theme settings, and the font faces of a font that has files, so that
 *   WordPress prints their @font-face rules. Inheriting headings take the stack
 *   of the body font; the faces of a font print once.
 *
 * The values come from the settings (snapshot and PHP layers), never from ACF.
 * User Global Styles are emptied by Global_Styles_Lock.
 *
 * @since 1.0.0
 */
final class Theme_Json_Tokens {

	/**
	 * Hook of the theme origin of theme.json.
	 *
	 * @since 1.0.0
	 */
	public const HOOK = 'wp_theme_json_data_theme';

	/**
	 * Priority on HOOK.
	 *
	 * @since 1.0.0
	 */
	public const PRIORITY = 10;

	/**
	 * Slugs of the palette, in palette order; each color is var(--bs-<slug>).
	 *
	 * @since 1.0.0
	 */
	public const COLOR_SLUGS = array( 'primary', 'secondary', 'success', 'info', 'warning', 'danger', 'light', 'dark' );

	/**
	 * Color switches of theme.json that stay off.
	 *
	 * @since 1.0.0
	 */
	public const COLOR_SWITCHES = array( 'custom', 'customGradient', 'customDuotone', 'defaultPalette', 'defaultGradients', 'defaultDuotone' );

	/**
	 * Setting key of each font preset, by preset slug.
	 *
	 * @since 1.0.0
	 */
	public const FONT_PRESETS = array(
		'body'     => 'font_body',
		'headings' => 'font_headings',
	);

	/**
	 * Fonts known without a font catalog: stack and font faces by font slug.
	 *
	 * The sans-serif stack is the one of Bootstrap ($font-family-sans-serif).
	 *
	 * @since 1.0.0
	 */
	public const SYSTEM_FONTS = array(
		'system-sans'  => array(
			'stack' => 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", "Noto Sans", "Liberation Sans", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"',
			'faces' => array(),
		),
		'system-serif' => array(
			'stack' => 'Georgia, "Times New Roman", Times, serif',
			'faces' => array(),
		),
	);

	/**
	 * Font slug used when the chosen font is unknown.
	 *
	 * @since 1.0.0
	 */
	public const DEFAULT_FONT = 'system-sans';

	/**
	 * Returns the fonts: stack and font faces by font slug; null for SYSTEM_FONTS.
	 *
	 * @var Closure|null
	 * @phpstan-var (Closure(): array<string, array{stack: string, faces: array<int, array<string, mixed>>}>)|null
	 */
	private static ?Closure $fonts = null;

	/**
	 * Hooks the filter on wp_theme_json_data_theme.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_filter( self::HOOK, array( self::class, 'filter' ), self::PRIORITY, 1 );
	}

	/**
	 * Replaces the source of the fonts; null restores SYSTEM_FONTS.
	 *
	 * The font catalog passes its fonts here.
	 *
	 * @since 1.0.0
	 *
	 * @param Closure|null $fonts Returns stack and font faces (theme.json fontFace entries) by font slug.
	 * @phpstan-param (Closure(): array<string, array{stack: string, faces: array<int, array<string, mixed>>}>)|null $fonts
	 * @return void
	 */
	public static function set_fonts( ?Closure $fonts ): void {
		self::$fonts = $fonts;
	}

	/**
	 * Merges the theme tokens into the theme data; runs on wp_theme_json_data_theme.
	 *
	 * The parameter is mixed: WordPress also accepts a compatible object
	 * that is no WP_Theme_JSON_Data (such as the one of the Gutenberg plugin)
	 * and reads it through get_data(). A value without update_with() passes
	 * unchanged and is reported, so that the filter never breaks a request.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $theme_json Theme data (WP_Theme_JSON_Data or a compatible object), of the child theme when one is active.
	 * @return mixed The same value; the tokens are merged in when it has update_with().
	 */
	public static function filter( mixed $theme_json ): mixed {
		if ( ! is_object( $theme_json ) || ! method_exists( $theme_json, 'update_with' ) ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html( sprintf( 'The theme data of %1$s is a %2$s without update_with(); the theme tokens are not merged in.', self::HOOK, get_debug_type( $theme_json ) ) ),
				'1.0.0'
			);
			return $theme_json;
		}
		$theme_json->update_with( self::data() );
		return $theme_json;
	}

	/**
	 * Returns the theme.json data of the tokens.
	 *
	 * @since 1.0.0
	 *
	 * @return array{version: int, settings: array{color: array<string, mixed>, typography: array{fontFamilies: list<array<string, mixed>>}}} Theme.json data, schema version 3.
	 */
	public static function data(): array {
		$color = array_fill_keys( self::COLOR_SWITCHES, false );

		$color['palette'] = array();
		foreach ( self::COLOR_SLUGS as $slug ) {
			$color['palette'][] = array(
				'slug'  => $slug,
				'color' => 'var(--bs-' . $slug . ')',
				'name'  => self::color_name( $slug ),
			);
		}

		return array(
			'version'  => 3,
			'settings' => array(
				'color'      => $color,
				'typography' => array( 'fontFamilies' => self::font_families() ),
			),
		);
	}

	/**
	 * Returns the font presets "body" and "headings".
	 *
	 * @since 1.0.0
	 *
	 * @return list<array<string, mixed>> Font family presets of theme.json.
	 */
	private static function font_families(): array {
		$fonts    = null === self::$fonts ? self::SYSTEM_FONTS : ( self::$fonts )();
		$settings = Settings::instance();
		$families = array();
		$printed  = array();
		$body     = self::DEFAULT_FONT;
		foreach ( self::FONT_PRESETS as $preset => $key ) {
			$slug = $settings->get( $key );
			$slug = Sanitizer::INHERIT === $slug ? $body : self::known_font( $fonts, $slug, $key );
			if ( 'body' === $preset ) {
				$body = $slug;
			}
			$font   = $fonts[ $slug ] ?? self::SYSTEM_FONTS[ self::DEFAULT_FONT ];
			$family = array(
				'name'       => self::font_name( $preset ),
				'slug'       => $preset,
				'fontFamily' => $font['stack'],
			);
			if ( array() !== $font['faces'] && ! isset( $printed[ $slug ] ) ) {
				$family['fontFace'] = $font['faces'];
				$printed[ $slug ]   = true;
			}
			$families[] = $family;
		}
		return $families;
	}

	/**
	 * Returns the chosen font slug when the fonts know it, otherwise DEFAULT_FONT and a report.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $fonts Fonts by slug.
	 * @param mixed                $slug  Chosen font slug.
	 * @param string               $key   Setting key, for the report.
	 * @return string Font slug.
	 */
	private static function known_font( array $fonts, mixed $slug, string $key ): string {
		if ( is_string( $slug ) && isset( $fonts[ $slug ] ) ) {
			return $slug;
		}
		_doing_it_wrong(
			__METHOD__,
			esc_html( sprintf( 'The font "%1$s" of %2$s is not in the font catalog; %3$s is used.', is_string( $slug ) ? $slug : gettype( $slug ), $key, self::DEFAULT_FONT ) ),
			'1.0.0'
		);
		return self::DEFAULT_FONT;
	}

	/**
	 * Returns the translated name of a palette color.
	 *
	 * The names and context equal those of theme.json, so they share one translation.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Color slug.
	 * @return string Name.
	 */
	private static function color_name( string $slug ): string {
		return match ( $slug ) {
			'primary'   => _x( 'Primary', 'Color name', 'creationell-wp-theme' ),
			'secondary' => _x( 'Secondary', 'Color name', 'creationell-wp-theme' ),
			'success'   => _x( 'Success', 'Color name', 'creationell-wp-theme' ),
			'info'      => _x( 'Info', 'Color name', 'creationell-wp-theme' ),
			'warning'   => _x( 'Warning', 'Color name', 'creationell-wp-theme' ),
			'danger'    => _x( 'Danger', 'Color name', 'creationell-wp-theme' ),
			'light'     => _x( 'Light', 'Color name', 'creationell-wp-theme' ),
			default     => _x( 'Dark', 'Color name', 'creationell-wp-theme' ),
		};
	}

	/**
	 * Returns the translated name of a font preset.
	 *
	 * @since 1.0.0
	 *
	 * @param string $preset Preset slug: body or headings.
	 * @return string Name.
	 */
	private static function font_name( string $preset ): string {
		return 'body' === $preset
			? _x( 'Body', 'Font family name', 'creationell-wp-theme' )
			: _x( 'Headings', 'Font family name', 'creationell-wp-theme' );
	}
}
