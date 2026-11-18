<?php
/**
 * Reading time of a post.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Blog;

use WP_Post;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Estimates the minutes a post takes to read.
 *
 * The content of every block counts, lists, captions and the text inside
 * containers of other plugins too. The content loses its shortcodes and the
 * script and style elements with what they hold; every other tag and every
 * block comment becomes a space, so markup alone splits words; character
 * references are decoded last. Han, Hiragana and Katakana count as
 * characters, about 500 a minute, and are removed before the words are
 * counted. A word is a run of letters and digits; an apostrophe or hyphen
 * between two runs keeps them one word. The result is rounded up and at
 * least 1. The minutes are stored in the post meta
 * _creationell_wp_theme_reading_time with the modification time and the words
 * per minute, and reused while both match. A preview neither reads nor
 * stores them, as it may show an autosave. The class registers no hooks.
 *
 * @since 1.0.0
 */
final class Reading_Time {

	/**
	 * Post meta key of the stored minutes.
	 *
	 * @since 1.0.0
	 */
	public const META_KEY = '_creationell_wp_theme_reading_time';

	/**
	 * Lowest words per minute.
	 *
	 * @since 1.0.0
	 */
	public const MIN_WPM = 100;

	/**
	 * Highest words per minute.
	 *
	 * @since 1.0.0
	 */
	public const MAX_WPM = 1000;

	/**
	 * Han, Hiragana and Katakana characters read per minute.
	 *
	 * @since 1.0.0
	 */
	public const CJK_PER_MINUTE = 500;

	/**
	 * One Han, Hiragana or Katakana character.
	 *
	 * @since 1.0.0
	 */
	private const CJK_PATTERN = '~\p{Han}|\p{Hiragana}|\p{Katakana}~u';

	/**
	 * One word: letters and digits, joined by an apostrophe (straight or typographic) or a hyphen.
	 *
	 * @since 1.0.0
	 */
	private const WORD_PATTERN = '~[\p{L}\p{N}]+(?:[\'\x{2019}-][\p{L}\p{N}]+)*~u';

	/**
	 * Returns the minutes a post takes to read.
	 *
	 * Example:
	 *
	 *     $minutes = Reading_Time::minutes( get_post(), 200 ); // 4
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Post.
	 * @param int     $wpm  Words per minute; the filter creationell_wp_theme_reading_time_wpm may change it, the result is kept between 100 and 1000.
	 * @return int Minutes, at least 1.
	 */
	public static function minutes( WP_Post $post, int $wpm ): int {
		/**
		 * Filters the words per minute of the reading time of a post.
		 *
		 * The value is kept between 100 and 1000; an answer that is no number
		 * keeps the value passed in.
		 *
		 * Example:
		 *
		 *     add_filter(
		 *         'creationell_wp_theme_reading_time_wpm',
		 *         static fn( int $wpm, WP_Post $post ): int => has_category( 'tutorials', $post ) ? 150 : $wpm,
		 *         10,
		 *         2
		 *     );
		 *
		 * @since 1.0.0
		 *
		 * @param int     $wpm  Words per minute.
		 * @param WP_Post $post Post.
		 */
		$filtered = apply_filters( 'creationell_wp_theme_reading_time_wpm', $wpm, $post );
		$wpm      = is_numeric( $filtered ) ? (int) $filtered : $wpm;
		$wpm      = max( self::MIN_WPM, min( self::MAX_WPM, $wpm ) );

		$id       = (int) $post->ID;
		$modified = (string) $post->post_modified_gmt;
		$cache    = $id > 0 && ! is_preview();
		if ( $cache ) {
			$stored = get_post_meta( $id, self::META_KEY, true );
			if ( is_array( $stored ) && ( $stored['m'] ?? null ) === $modified && ( $stored['w'] ?? null ) === $wpm && is_int( $stored['n'] ?? null ) && $stored['n'] > 0 ) {
				return $stored['n'];
			}
		}

		$units   = self::count_units( (string) $post->post_content );
		$minutes = max( 1, (int) ceil( $units['words'] / $wpm + $units['cjk'] / self::CJK_PER_MINUTE ) );
		if ( $cache ) {
			update_post_meta(
				$id,
				self::META_KEY,
				array(
					'm' => $modified,
					'w' => $wpm,
					'n' => $minutes,
				)
			);
		}
		return $minutes;
	}

	/**
	 * Counts the words and the Han, Hiragana and Katakana characters of a post content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Post content with blocks, shortcodes and HTML.
	 * @return array{words: int, cjk: int} Words, and CJK characters.
	 */
	public static function count_units( string $content ): array {
		// A space before each tag and block comment leaves a space where it was: <p>x</p><p>y</p> is two words.
		// wp_strip_all_tags() drops script and style elements with their content before the other tags.
		$text = html_entity_decode( wp_strip_all_tags( str_replace( '<', ' <', strip_shortcodes( $content ) ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		$cjk  = (int) preg_match_all( self::CJK_PATTERN, $text );
		$text = (string) preg_replace( self::CJK_PATTERN, ' ', $text );

		return array(
			'words' => (int) preg_match_all( self::WORD_PATTERN, $text ),
			'cjk'   => $cjk,
		);
	}
}
