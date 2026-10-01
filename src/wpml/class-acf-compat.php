<?php
/**
 * Compatibility of the settings pages with ACF Extended under WPML.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Wpml;

use Creationell\WpTheme\Settings\Language;
use Creationell\WpTheme\Settings\Option_Store;
use Creationell\WpTheme\Settings\Registry;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Keeps the global settings page out of the multilang module of ACF Extended and finds global rows with a language suffix.
 *
 * ACF Extended appends "_<lang>" to the post_id of every options page when its
 * multilang module runs with WPML, so global settings would get rows per
 * language. The filter acfe/modules/multilang/exclude_options takes the page
 * with the post_id creationell_wp_theme_global out. ACFML 5 appends the suffix
 * as well; the ACF adapter of the settings removes it on acf/validate_post_id.
 * Rows creationell_wp_theme_global_<lang>_<key> that exist
 * anyway are reported by doctor (finding wpml.acfe_multilang); the theme
 * never reads them.
 *
 * @since 1.0.0
 */
final class Acf_Compat {

	/**
	 * Filter of ACF Extended with the post_ids its multilang module leaves alone.
	 *
	 * @since 1.0.0
	 */
	public const EXCLUDE_FILTER = 'acfe/modules/multilang/exclude_options';

	/**
	 * Adds the global settings page to the pages ACF Extended does not translate; filter acfe/modules/multilang/exclude_options.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $exclude Post_ids of the pages; ACF Extended passes a list of strings.
	 * @return array<int, string> The strings of the input followed by creationell_wp_theme_global, each once.
	 */
	public static function exclude_options( mixed $exclude ): array {
		$list = array();
		foreach ( is_array( $exclude ) ? $exclude : array() as $post_id ) {
			if ( is_string( $post_id ) && ! in_array( $post_id, $list, true ) ) {
				$list[] = $post_id;
			}
		}
		if ( ! in_array( Option_Store::GLOBAL_POST_ID, $list, true ) ) {
			$list[] = Option_Store::GLOBAL_POST_ID;
		}
		return $list;
	}

	/**
	 * Returns the value rows of global settings that carry the suffix of an active secondary language.
	 *
	 * Checks creationell_wp_theme_global_<lang>_<key> for every untranslated
	 * key and every active secondary language, with one priming call; call it
	 * in the admin or from WP-CLI only.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>|null $languages Language codes; null for Snapshot_Sync::languages(), hidden languages included.
	 * @return array<int, string> Option names in language and registry order.
	 */
	public static function suffixed_rows( ?array $languages = null ): array {
		$language = Language::instance();
		$names    = array();
		foreach ( $languages ?? Snapshot_Sync::instance()->languages() as $code ) {
			$secondary = $language->secondary( $code );
			if ( null === $secondary || ! Option_Store::valid_lang( $secondary ) ) {
				continue;
			}
			foreach ( Registry::instance()->all() as $definition ) {
				if ( ! $definition->translatable ) {
					$names[] = Option_Store::GLOBAL_POST_ID . '_' . $secondary . '_' . Option_Store::field_name( $definition );
				}
			}
		}
		if ( array() === $names ) {
			return array();
		}
		wp_prime_option_caches( $names );

		$missing = new \stdClass();
		$found   = array();
		foreach ( $names as $name ) {
			if ( get_option( $name, $missing ) !== $missing ) {
				$found[] = $name;
			}
		}
		return $found;
	}
}
