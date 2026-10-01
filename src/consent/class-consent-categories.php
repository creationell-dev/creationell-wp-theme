<?php
/**
 * Consent categories of the theme core.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Consent;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Returns the consent categories: the four defaults, changed by the filter creationell_wp_theme_consent_categories.
 *
 * A category is a name of lower case letters, digits, hyphens and underscores
 * that starts with a letter. Other values fall away; "necessary" is always the
 * first category. The labels of the categories belong to the consent provider.
 *
 * Example:
 *
 *     add_filter(
 *         'creationell_wp_theme_consent_categories',
 *         static fn( array $categories ): array => array_merge( $categories, array( 'video' ) )
 *     );
 *
 * @since 1.0.0
 */
final class Consent_Categories {

	/**
	 * Standard categories in their order.
	 *
	 * @since 1.0.0
	 */
	public const DEFAULTS = array( Consent_Gate::NECESSARY, 'functional', 'analytics', 'marketing' );

	/**
	 * Pattern of a category name.
	 *
	 * @since 1.0.0
	 */
	public const PATTERN = '~^[a-z][a-z0-9_-]*$~';

	/**
	 * Returns the categories after the filter.
	 *
	 * @since 1.0.0
	 *
	 * @return list<string> Categories, "necessary" first, without duplicates.
	 */
	public static function all(): array {
		/**
		 * Filters the consent categories.
		 *
		 * Values that are no category name fall away; "necessary" is always
		 * added. A value other than an array keeps the defaults.
		 *
		 * @since 1.0.0
		 *
		 * @param array<int, string> $categories Categories, by default necessary, functional, analytics, marketing.
		 */
		$filtered   = apply_filters( 'creationell_wp_theme_consent_categories', self::DEFAULTS );
		$filtered   = is_array( $filtered ) ? $filtered : self::DEFAULTS;
		$categories = array( Consent_Gate::NECESSARY );
		foreach ( $filtered as $category ) {
			if ( is_string( $category ) && 1 === preg_match( self::PATTERN, $category ) && ! in_array( $category, $categories, true ) ) {
				$categories[] = $category;
			}
		}
		return $categories;
	}

	/**
	 * Tells whether a name is one of the categories.
	 *
	 * @since 1.0.0
	 *
	 * @param string $category Name.
	 * @return bool True when listed.
	 */
	public static function is_known( string $category ): bool {
		return in_array( $category, self::all(), true );
	}
}
