<?php
/**
 * Swiper options of the post slider.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Posts;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Builds the options that the post slider prints as JSON in data-creationell-slider.
 *
 * The slider script passes them to Swiper; it holds no texts of its own, so
 * the a11y messages of Swiper and the labels of the pause button come from
 * here, translated. Columns show 1, 2, 3 and the chosen number of slides from
 * the breakpoints 0, 768, 992 and 1400 px on; heroes show one slide and may
 * fade. The class registers no hooks; the filter
 * creationell_wp_theme_swiper_options runs last.
 *
 * @since 1.0.0
 */
final class Slider_Config {

	/**
	 * Filter of the Swiper options.
	 *
	 * @since 1.0.0
	 */
	public const FILTER = 'creationell_wp_theme_swiper_options';

	/**
	 * Transition time in milliseconds (the Swiper default).
	 *
	 * @since 1.0.0
	 */
	public const SPEED = 300;

	/**
	 * Default and bounds of the autoplay delay in milliseconds.
	 *
	 * @since 1.0.0
	 */
	public const DELAY = array(
		'default' => 6000,
		'min'     => 3000,
		'max'     => 15000,
	);

	/**
	 * Flags for wp_json_encode() of the options in data-creationell-slider.
	 *
	 * The JSON then holds no "<", ">", "&", "'" or quote inside a string, so
	 * esc_attr() only encodes the quotes around the keys and values, and the
	 * browser reads back exactly the JSON that was encoded.
	 *
	 * @since 1.0.0
	 */
	public const JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

	/**
	 * Most rounds of decoding character references in a text of the options.
	 *
	 * @since 1.0.0
	 */
	private const DECODE_ROUNDS = 5;

	/**
	 * Number of columns when the attribute is missing or outside 1 to 4.
	 *
	 * @since 1.0.0
	 */
	public const COLUMNS = 3;

	/**
	 * Breakpoints in px and the most slides each shows.
	 *
	 * @since 1.0.0
	 */
	public const BREAKPOINTS = array(
		0    => 1,
		768  => 2,
		992  => 3,
		1400 => 4,
	);

	/**
	 * Gap between column slides in px; heroes have none.
	 *
	 * @since 1.0.0
	 */
	public const SPACE_BETWEEN = 24;

	/**
	 * Returns the Swiper options for the attributes of a slider.
	 *
	 * Example:
	 *
	 *     $options = Slider_Config::from_attributes( $a, (int) creationell_wp_theme_setting( 'post_slider_autoplay_delay' ) );
	 *     printf( '<section data-creationell-slider="%s">', esc_attr( (string) wp_json_encode( $options, Slider_Config::JSON_FLAGS ) ) );
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $attributes     Attributes layout, columns, effect, loop, autoplay, navigation, paginationDots.
	 * @param int                  $autoplay_delay Delay in ms (setting post_slider_autoplay_delay); 0 or less gives 6000, else kept within 3000 to 15000.
	 * @param string               $context        Context for the filter: post-slider or related-posts.
	 * @return array<string, mixed> Options: layout, effect, loop, speed, autoplay, navigation, pagination, breakpoints, spaceBetween, a11y, labels.
	 */
	public static function from_attributes( array $attributes, int $autoplay_delay, string $context = 'post-slider' ): array {
		$heroes  = 'heroes' === Query_Args::choice( $attributes['layout'] ?? '' );
		$columns = is_numeric( $attributes['columns'] ?? null ) ? (int) $attributes['columns'] : 0;
		$columns = $columns < 1 || $columns > max( self::BREAKPOINTS ) ? self::COLUMNS : $columns;
		$effect  = $heroes && 'fade' === Query_Args::choice( $attributes['effect'] ?? '' ) ? 'fade' : 'slide';

		$breakpoints = array();
		foreach ( self::BREAKPOINTS as $width => $most ) {
			$breakpoints[ $width ] = array( 'slidesPerView' => $heroes ? 1 : min( $most, $columns ) );
		}

		$options = array(
			'layout'       => $heroes ? 'heroes' : 'columns',
			'effect'       => $effect,
			'loop'         => Query_Args::flag( $attributes['loop'] ?? null, false ),
			'speed'        => self::SPEED,
			'autoplay'     => Query_Args::flag( $attributes['autoplay'] ?? null, false ) ? array( 'delay' => self::delay( $autoplay_delay ) ) : false,
			'navigation'   => Query_Args::flag( $attributes['navigation'] ?? null, true ),
			'pagination'   => Query_Args::flag( $attributes['paginationDots'] ?? null, true ),
			'breakpoints'  => $breakpoints,
			'spaceBetween' => $heroes ? 0 : self::SPACE_BETWEEN,
			'a11y'         => self::messages(),
			'labels'       => array(
				'pause' => __( 'Pause slideshow', 'creationell-wp-theme' ),
				'play'  => __( 'Play slideshow', 'creationell-wp-theme' ),
			),
		);

		/**
		 * Filters the Swiper options of a post slider before they are printed as JSON.
		 *
		 * Example:
		 *
		 *     add_filter(
		 *         'creationell_wp_theme_swiper_options',
		 *         static function ( array $options ): array {
		 *             $options['speed'] = 600;
		 *             return $options;
		 *         }
		 *     );
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $options Swiper options and the labels of the pause button.
		 * @param string               $context post-slider or related-posts.
		 */
		$filtered = apply_filters( 'creationell_wp_theme_swiper_options', $options, $context );
		if ( ! is_array( $filtered ) ) {
			return self::plain_texts( $options );
		}
		$result = array();
		foreach ( $filtered as $key => $value ) {
			$result[ (string) $key ] = $value;
		}
		return self::plain_texts( $result );
	}

	/**
	 * Keeps only plain text in the a11y messages and the labels of the options.
	 *
	 * Swiper writes some a11y messages with innerHTML into its live region, so
	 * markup from a translation or a filter must not reach it, not even as
	 * character references such as &lt;img&gt;. Values that are no string are
	 * dropped; Swiper and the template then use their defaults.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $options Options.
	 * @return array<string, mixed> Options with plain a11y messages and labels.
	 */
	private static function plain_texts( array $options ): array {
		foreach ( array( 'a11y', 'labels' ) as $group ) {
			if ( ! is_array( $options[ $group ] ?? null ) ) {
				continue;
			}
			$texts = array();
			foreach ( $options[ $group ] as $key => $text ) {
				if ( is_string( $text ) ) {
					$texts[ (string) $key ] = self::plain_text( $text );
				}
			}
			$options[ $group ] = $texts;
		}
		return $options;
	}

	/**
	 * Returns a text without markup: character references decoded, tags removed, no "<" or ">" left.
	 *
	 * Decoding comes first and repeats (at most DECODE_ROUNDS times) so that
	 * encoded markup such as &lt;img&gt; or &amp;lt;img&amp;gt; becomes a tag
	 * that wp_strip_all_tags() removes. An ampersand stays as text.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Text from a translation or a filter.
	 * @return string Plain text.
	 */
	private static function plain_text( string $text ): string {
		for ( $round = 0; $round < self::DECODE_ROUNDS; $round++ ) {
			$decoded = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			if ( $decoded === $text ) {
				break;
			}
			$text = $decoded;
		}
		return trim( str_replace( array( '<', '>' ), '', wp_strip_all_tags( $text ) ) );
	}

	/**
	 * Returns the autoplay delay: the default for a missing value (0 or less), else the value within the bounds.
	 *
	 * @since 1.0.0
	 *
	 * @param int $delay Delay in ms.
	 * @return int Delay.
	 */
	private static function delay( int $delay ): int {
		if ( $delay < 1 ) {
			return self::DELAY['default'];
		}
		return max( self::DELAY['min'], min( $delay, self::DELAY['max'] ) );
	}

	/**
	 * Returns the eight a11y messages of Swiper, translated.
	 *
	 * The Swiper parameter containerMessage stays unset: the section of the slider carries the name.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Messages by Swiper parameter.
	 */
	private static function messages(): array {
		return array(
			'prevSlideMessage'                => __( 'Previous slide', 'creationell-wp-theme' ),
			'nextSlideMessage'                => __( 'Next slide', 'creationell-wp-theme' ),
			'firstSlideMessage'               => __( 'This is the first slide', 'creationell-wp-theme' ),
			'lastSlideMessage'                => __( 'This is the last slide', 'creationell-wp-theme' ),
			/* translators: Keep {{index}}: Swiper replaces it with the number of the slide. */
			'paginationBulletMessage'         => __( 'Go to slide {{index}}', 'creationell-wp-theme' ),
			/* translators: Keep {{index}} and {{slidesLength}}: Swiper replaces them with the number of the slide and the number of slides. */
			'slideLabelMessage'               => __( '{{index}} / {{slidesLength}}', 'creationell-wp-theme' ),
			'containerRoleDescriptionMessage' => _x( 'carousel', 'role description of the post slider', 'creationell-wp-theme' ),
			'itemRoleDescriptionMessage'      => _x( 'slide', 'role description of one slide of the post slider', 'creationell-wp-theme' ),
		);
	}
}
