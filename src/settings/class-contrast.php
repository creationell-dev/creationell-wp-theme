<?php
/**
 * Contrast ratio of two colors.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings;

use InvalidArgumentException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Computes the relative luminance and the contrast ratio of WCAG 2.2 for "#rrggbb" colors.
 *
 * @since 1.0.0
 */
final class Contrast {

	/**
	 * Returns the contrast ratio of two colors, from 1 to 21; the order does not matter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $a Color "#rrggbb".
	 * @param string $b Color "#rrggbb".
	 * @return float Ratio, e.g. 4.5 for 4.5:1.
	 * @throws InvalidArgumentException When a color is no "#rrggbb".
	 */
	public static function ratio( string $a, string $b ): float {
		$first  = self::luminance( $a );
		$second = self::luminance( $b );
		return ( max( $first, $second ) + 0.05 ) / ( min( $first, $second ) + 0.05 );
	}

	/**
	 * Returns the relative luminance of a color.
	 *
	 * @since 1.0.0
	 *
	 * @param string $color Color "#rrggbb", any case.
	 * @return float Luminance from 0 (black) to 1 (white).
	 * @throws InvalidArgumentException When the color is no "#rrggbb".
	 */
	public static function luminance( string $color ): float {
		if ( 1 !== preg_match( '~^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$~iD', $color, $match ) ) {
			throw new InvalidArgumentException( esc_html( sprintf( 'Color %s is no #rrggbb value.', $color ) ) );
		}
		$weights   = array( 0.2126, 0.7152, 0.0722 );
		$luminance = 0.0;
		foreach ( $weights as $index => $weight ) {
			$channel    = hexdec( $match[ $index + 1 ] ) / 255;
			$linear     = $channel <= 0.04045 ? $channel / 12.92 : ( ( $channel + 0.055 ) / 1.055 ) ** 2.4;
			$luminance += $weight * $linear;
		}
		return $luminance;
	}
}
