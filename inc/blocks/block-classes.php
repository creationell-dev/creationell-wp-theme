<?php
/**
 * Classes on single tags of block markup.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


if ( ! function_exists( 'creationell_wp_theme_tag_has_class' ) ) {
	/**
	 * Tells whether the tag a processor stands on has a class.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_HTML_Tag_Processor $tags Processor on the tag.
	 * @param string                $name Class name.
	 * @return bool True when the class attribute lists the class.
	 */
	function creationell_wp_theme_tag_has_class( WP_HTML_Tag_Processor $tags, string $name ): bool {
		return in_array( $name, creationell_wp_theme_tag_class_list( $tags ), true );
	}
}

if ( ! function_exists( 'creationell_wp_theme_tag_classes' ) ) {
	/**
	 * Adds and removes classes on the tag a processor stands on.
	 *
	 * The filters of the theme used to replace text in the whole block markup,
	 * which also hit longer class names (cat-item-1), names and links that had a
	 * class attribute already. This changes the class attribute of one tag only.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_HTML_Tag_Processor $tags   Processor on the tag.
	 * @param array<int, string>    $add    Classes to add, in this order, unless present.
	 * @param array<int, string>    $remove Classes to remove.
	 * @return void
	 */
	function creationell_wp_theme_tag_classes( WP_HTML_Tag_Processor $tags, array $add, array $remove = array() ): void {
		$classes = array_values( array_diff( creationell_wp_theme_tag_class_list( $tags ), $remove ) );
		foreach ( $add as $name ) {
			if ( ! in_array( $name, $classes, true ) ) {
				$classes[] = $name;
			}
		}
		$tags->set_attribute( 'class', implode( ' ', $classes ) );
	}
}

if ( ! function_exists( 'creationell_wp_theme_tag_class_list' ) ) {
	/**
	 * Returns the classes of the tag a processor stands on.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_HTML_Tag_Processor $tags Processor on the tag.
	 * @return list<string> Classes in their order.
	 */
	function creationell_wp_theme_tag_class_list( WP_HTML_Tag_Processor $tags ): array {
		$value   = $tags->get_attribute( 'class' );
		$classes = is_string( $value ) ? preg_split( '~\s+~', trim( $value ), -1, PREG_SPLIT_NO_EMPTY ) : array();
		return false === $classes ? array() : $classes;
	}
}
