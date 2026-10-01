<?php
/**
 * Contrast rules of the theme colors.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The one source of the contrast rules (WCAG 2.2 AA) for the setter, the settings pages and the lint.
 *
 * Rules: the body text on the body background; primary (the link color),
 * secondary, success and danger on the body background, as they appear as text
 * there; every theme color with the better of white and black, as Bootstrap's
 * color-contrast() picks the text on a colored background. Each rule needs 4.5:1.
 * With black and white every color reaches at least 4.58:1, so that rule
 * guards against a higher minimum or other contrast colors later.
 *
 * @since 1.0.0
 */
final class Contrast_Rules {

	/**
	 * Minimum ratio for text (WCAG 2.2 AA, 1.4.3).
	 *
	 * @since 1.0.0
	 */
	public const MIN_TEXT = 4.5;

	/**
	 * Background of a rule that takes the better of white and black.
	 *
	 * @since 1.0.0
	 */
	public const BLACK_OR_WHITE = 'black_or_white';

	/**
	 * Theme colors of Bootstrap, as setting keys.
	 *
	 * @since 1.0.0
	 */
	public const THEME_COLORS = array( 'color_primary', 'color_secondary', 'color_success', 'color_info', 'color_warning', 'color_danger', 'color_light', 'color_dark' );

	/**
	 * Colors that appear as text on the body background.
	 *
	 * @since 1.0.0
	 */
	public const TEXT_ON_BODY = array( 'color_primary', 'color_secondary', 'color_success', 'color_danger' );

	/**
	 * Returns the rules.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{fg: string, bg: string, min: float, reason: string}> Rules: foreground key, background key or BLACK_OR_WHITE, minimum ratio, reason.
	 */
	public static function pairs(): array {
		$pairs = array(
			array(
				'fg'     => 'color_body_text',
				'bg'     => 'color_body_bg',
				'min'    => self::MIN_TEXT,
				'reason' => 'body-text',
			),
		);
		foreach ( self::TEXT_ON_BODY as $key ) {
			$pairs[] = array(
				'fg'     => $key,
				'bg'     => 'color_body_bg',
				'min'    => self::MIN_TEXT,
				'reason' => 'text-on-body',
			);
		}
		foreach ( self::THEME_COLORS as $key ) {
			$pairs[] = array(
				'fg'     => $key,
				'bg'     => self::BLACK_OR_WHITE,
				'min'    => self::MIN_TEXT,
				'reason' => 'color-contrast',
			);
		}
		return $pairs;
	}

	/**
	 * Returns the rules a set of colors breaks.
	 *
	 * A rule whose colors are missing from the set, or are no "#rrggbb", is skipped.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $colors Colors "#rrggbb" by setting key.
	 * @return array<int, array{pair: array{fg: string, bg: string}, ratio: float, min: float, reason: string}> Broken rules in rule order.
	 */
	public static function check( array $colors ): array {
		$failures = array();
		foreach ( self::pairs() as $pair ) {
			$ratio = self::ratio_of( $pair['fg'], $pair['bg'], $colors );
			if ( null === $ratio || $ratio >= $pair['min'] ) {
				continue;
			}
			$failures[] = array(
				'pair'   => array(
					'fg' => $pair['fg'],
					'bg' => $pair['bg'],
				),
				'ratio'  => $ratio,
				'min'    => $pair['min'],
				'reason' => $pair['reason'],
			);
		}
		return $failures;
	}

	/**
	 * Returns the ratio of one rule.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $fg     Foreground key.
	 * @param string               $bg     Background key or BLACK_OR_WHITE.
	 * @param array<string, mixed> $colors Colors by key.
	 * @return float|null Ratio, or null when a color is missing.
	 */
	private static function ratio_of( string $fg, string $bg, array $colors ): ?float {
		$foreground = self::color( $colors[ $fg ] ?? null );
		if ( null === $foreground ) {
			return null;
		}
		if ( self::BLACK_OR_WHITE === $bg ) {
			return max( Contrast::ratio( $foreground, '#ffffff' ), Contrast::ratio( $foreground, '#000000' ) );
		}
		$background = self::color( $colors[ $bg ] ?? null );
		return null === $background ? null : Contrast::ratio( $foreground, $background );
	}

	/**
	 * Returns a color when it is "#rrggbb".
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return string|null Color or null.
	 */
	private static function color( mixed $value ): ?string {
		return is_string( $value ) && 1 === preg_match( '~^#[0-9a-f]{6}$~iD', $value ) ? $value : null;
	}
}
