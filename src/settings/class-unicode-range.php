<?php
/**
 * Unicode ranges of font faces.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Reads the unicode-range values of font faces (CSS Fonts 4) as code point intervals.
 *
 * Accepts U+XXXX, U+XXXX-YYYY and trailing wildcards such as U+4??, separated
 * by commas, in any case. Parts CSS does not allow (a wildcard with an
 * interval, a start above the end, a code point above U+10FFFF) are skipped,
 * so a value the font catalog let through never throws.
 *
 * @since 1.0.0
 */
final class Unicode_Range {

	/**
	 * Highest Unicode code point.
	 *
	 * @since 1.0.0
	 */
	public const MAX = 0x10FFFF;

	/**
	 * Returns the intervals of a unicode-range value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $range Value, e.g. "U+0000-00FF, U+0131".
	 * @return list<array{0: int, 1: int}> Intervals with first and last code point, in the given order.
	 */
	public static function parse( string $range ): array {
		$intervals = array();
		foreach ( explode( ',', $range ) as $part ) {
			$interval = self::part( $part );
			if ( null !== $interval ) {
				$intervals[] = $interval;
			}
		}
		return $intervals;
	}

	/**
	 * Returns the parts of a value that CSS does not allow, trimmed, as written.
	 *
	 * Browsers drop the whole unicode-range of a face with such a part and use
	 * the face for every character.
	 *
	 * @since 1.0.0
	 *
	 * @param string $range Value.
	 * @return list<string> Invalid parts, in the given order.
	 */
	public static function invalid_parts( string $range ): array {
		$invalid = array();
		foreach ( explode( ',', $range ) as $part ) {
			if ( null === self::part( $part ) ) {
				$invalid[] = trim( $part );
			}
		}
		return $invalid;
	}

	/**
	 * Returns the interval of one part, or null when CSS does not allow it.
	 *
	 * @since 1.0.0
	 *
	 * @param string $part Part, e.g. "U+0000-00FF" or "U+4??".
	 * @return array{0: int, 1: int}|null Interval.
	 */
	private static function part( string $part ): ?array {
		$part = strtoupper( trim( $part ) );
		if ( 1 !== preg_match( '~^U\+([0-9A-F]{0,6}\?{0,6})(?:-([0-9A-F]{1,6}))?$~D', $part, $matches ) || '' === $matches[1] || strlen( $matches[1] ) > 6 ) {
			return null;
		}
		$start = $matches[1];
		$end   = $matches[2] ?? '';
		if ( str_contains( $start, '?' ) ) {
			if ( '' !== $end ) {
				return null;
			}
			$low  = (int) hexdec( str_replace( '?', '0', $start ) );
			$high = (int) hexdec( str_replace( '?', 'F', $start ) );
		} else {
			$low  = (int) hexdec( $start );
			$high = '' === $end ? $low : (int) hexdec( $end );
		}
		if ( $low > $high || $high > self::MAX ) {
			return null;
		}
		return array( $low, $high );
	}

	/**
	 * Tells whether a code point lies in one of the intervals.
	 *
	 * @since 1.0.0
	 *
	 * @param list<array{0: int, 1: int}> $intervals  Intervals from parse().
	 * @param int                         $code_point Code point.
	 * @return bool True when an interval holds it.
	 */
	public static function contains( array $intervals, int $code_point ): bool {
		foreach ( $intervals as $interval ) {
			if ( $code_point >= $interval[0] && $code_point <= $interval[1] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns the intervals without the code points of other intervals.
	 *
	 * @since 1.0.0
	 *
	 * @param list<array{0: int, 1: int}> $intervals Intervals.
	 * @param list<array{0: int, 1: int}> $remove    Intervals to take out.
	 * @return list<array{0: int, 1: int}> What is left, in the given order.
	 */
	public static function subtract( array $intervals, array $remove ): array {
		foreach ( $remove as $cut ) {
			$left = array();
			foreach ( $intervals as $interval ) {
				if ( $cut[1] < $interval[0] || $cut[0] > $interval[1] ) {
					$left[] = $interval;
					continue;
				}
				if ( $interval[0] < $cut[0] ) {
					$left[] = array( $interval[0], $cut[0] - 1 );
				}
				if ( $interval[1] > $cut[1] ) {
					$left[] = array( $cut[1] + 1, $interval[1] );
				}
			}
			$intervals = $left;
		}
		return $intervals;
	}

	/**
	 * Tells whether two interval lists share a code point.
	 *
	 * @since 1.0.0
	 *
	 * @param list<array{0: int, 1: int}> $first  Intervals.
	 * @param list<array{0: int, 1: int}> $second Intervals.
	 * @return bool True on a shared code point.
	 */
	public static function intersects( array $first, array $second ): bool {
		foreach ( $first as $one ) {
			foreach ( $second as $other ) {
				if ( $one[0] <= $other[1] && $other[0] <= $one[1] ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Returns the intervals sorted by start and merged where they overlap or touch.
	 *
	 * @since 1.0.0
	 *
	 * @param list<array{0: int, 1: int}> $intervals Intervals.
	 * @return list<array{0: int, 1: int}> Sorted, disjoint intervals.
	 */
	public static function normalize( array $intervals ): array {
		usort( $intervals, static fn( array $one, array $other ): int => $one[0] <=> $other[0] );
		$merged = array();
		$start  = null;
		$end    = 0;
		foreach ( $intervals as $interval ) {
			if ( null !== $start && $interval[0] <= $end + 1 ) {
				$end = max( $end, $interval[1] );
				continue;
			}
			if ( null !== $start ) {
				$merged[] = array( $start, $end );
			}
			$start = $interval[0];
			$end   = $interval[1];
		}
		if ( null !== $start ) {
			$merged[] = array( $start, $end );
		}
		return $merged;
	}

	/**
	 * Tells whether a code point lies in normalized intervals, by binary search.
	 *
	 * @since 1.0.0
	 *
	 * @param list<array{0: int, 1: int}> $intervals  Intervals from normalize().
	 * @param int                         $code_point Code point.
	 * @return bool True when an interval holds it.
	 */
	public static function covers( array $intervals, int $code_point ): bool {
		$low  = 0;
		$high = count( $intervals ) - 1;
		while ( $low <= $high ) {
			$middle = intdiv( $low + $high, 2 );
			if ( $code_point < $intervals[ $middle ][0] ) {
				$high = $middle - 1;
			} elseif ( $code_point > $intervals[ $middle ][1] ) {
				$low = $middle + 1;
			} else {
				return true;
			}
		}
		return false;
	}
}
