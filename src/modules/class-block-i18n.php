<?php
/**
 * Translation of the block texts of Blockstudio blocks.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules;

use WP_Block_Type;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Translates the texts of the blocks of registered namespaces with the theme text domain.
 *
 * Blockstudio registers its blocks without the block.json translation of
 * WordPress, so the texts of block.json stay untranslated. For the blocks of a
 * registered namespace this class translates title, description and keywords
 * on blockstudio/blocks/meta, and label, help and the option labels of each
 * field on blockstudio/blocks/attributes, also for the fields inside tabs, which
 * Blockstudio does not pass to the filter on their own. Option values stay:
 * they are stored in the content. The texts are variables at run time; the
 * literals for the extraction live in modules/<slug>/i18n-strings.php, a file
 * the theme never loads.
 *
 * Blockstudio runs the attributes filter only on a cold build and keeps the
 * result in its runtime cache, whose key has no locale. While a namespace is
 * registered, the class therefore adds the locale of the request to the
 * identity of that cache (blockstudio/runtime/identity, scope "runtime"), so
 * each locale gets a cache of its own and the field texts never freeze in the
 * language of the request that built the cache.
 *
 * @since 1.0.0
 */
final class Block_I18n {

	/**
	 * Text domain of the block texts.
	 *
	 * @since 1.0.0
	 */
	public const DOMAIN = 'creationell-wp-theme';

	/**
	 * Priority of the filters.
	 *
	 * @since 1.0.0
	 */
	public const PRIORITY = 20;

	/**
	 * Blockstudio filter for the metadata of a block.
	 *
	 * @since 1.0.0
	 */
	public const META_FILTER = 'blockstudio/blocks/meta';

	/**
	 * Blockstudio filter for one field definition of a block.
	 *
	 * @since 1.0.0
	 */
	public const ATTRIBUTES_FILTER = 'blockstudio/blocks/attributes';

	/**
	 * Blockstudio filter for the identity of a runtime cache scope.
	 *
	 * @since 1.0.0
	 */
	public const IDENTITY_FILTER = 'blockstudio/runtime/identity';

	/**
	 * Blockstudio cache scope that holds the built blocks with their filtered fields.
	 *
	 * @since 1.0.0
	 */
	public const CACHE_SCOPE = 'runtime';

	/**
	 * Key of the locale in the runtime cache identity.
	 *
	 * @since 1.0.0
	 */
	public const IDENTITY_KEY = 'creationell-wp-theme/locale';

	/**
	 * Pattern of a block namespace.
	 *
	 * @since 1.0.0
	 */
	public const NAMESPACE_PATTERN = '~^[a-z][a-z0-9-]*$~';

	/**
	 * Registered namespaces as keys, in the order of the first call.
	 *
	 * @var array<string, true>
	 */
	private static array $namespaces = array();

	/**
	 * Translates the block texts of a namespace from now on; call it before init.
	 *
	 * @since 1.0.0
	 *
	 * @param string $block_namespace Block namespace without slash, e.g. "creationell-theme".
	 * @return void
	 */
	public static function register_namespace( string $block_namespace ): void {
		if ( 1 !== preg_match( self::NAMESPACE_PATTERN, $block_namespace ) ) {
			_doing_it_wrong( __METHOD__, esc_html( sprintf( 'The block namespace "%s" is invalid: use lower case letters, digits and hyphens.', $block_namespace ) ), '1.0.0' );
			return;
		}
		self::$namespaces[ $block_namespace ] = true;
		if ( false === has_filter( self::META_FILTER, array( self::class, 'filter_meta' ) ) ) {
			add_filter( self::META_FILTER, array( self::class, 'filter_meta' ), self::PRIORITY, 1 );
			add_filter( self::ATTRIBUTES_FILTER, array( self::class, 'filter_attribute' ), self::PRIORITY, 2 );
			add_filter( self::IDENTITY_FILTER, array( self::class, 'filter_identity' ), self::PRIORITY, 2 );
		}
	}

	/**
	 * Returns the registered namespaces.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Namespaces in the order of the first call.
	 */
	public static function namespaces(): array {
		return array_keys( self::$namespaces );
	}

	/**
	 * Forgets the registered namespaces; for tests.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$namespaces = array();
	}

	/**
	 * Translates title, description and keywords of a block; runs on blockstudio/blocks/meta.
	 *
	 * The filter is global: blocks of other namespaces and values of other types pass unchanged.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $block Block type object or block metadata array.
	 * @return mixed The same value, translated for blocks of a registered namespace.
	 */
	public static function filter_meta( mixed $block ): mixed {
		if ( $block instanceof WP_Block_Type ) {
			if ( self::is_own( $block->name ) ) {
				$block->title       = is_string( $block->title ) ? self::translated( $block->title ) : $block->title;
				$block->description = is_string( $block->description ) ? self::translated( $block->description ) : $block->description;
				foreach ( is_array( $block->keywords ) ? $block->keywords : array() as $index => $keyword ) {
					if ( is_string( $keyword ) ) {
						$block->keywords[ $index ] = self::translated( $keyword );
					}
				}
			}
			return $block;
		}
		if ( ! is_array( $block ) || ! self::is_own( $block['name'] ?? null ) ) {
			return $block;
		}
		foreach ( array( 'title', 'description' ) as $key ) {
			if ( isset( $block[ $key ] ) ) {
				$block[ $key ] = self::text( $block[ $key ] );
			}
		}
		if ( isset( $block['keywords'] ) ) {
			$block['keywords'] = self::texts( $block['keywords'] );
		}
		return $block;
	}

	/**
	 * Translates label, help and option labels of one field; runs on blockstudio/blocks/attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $attribute Field definition.
	 * @param mixed $block     Metadata array of the block that owns the field.
	 * @return mixed The same field, translated for blocks of a registered namespace.
	 */
	public static function filter_attribute( mixed $attribute, mixed $block = null ): mixed {
		if ( ! is_array( $attribute ) || ! is_array( $block ) || ! self::is_own( $block['name'] ?? null ) ) {
			return $attribute;
		}
		return self::field( $attribute );
	}

	/**
	 * Adds the locale of the request to the runtime cache identity; runs on blockstudio/runtime/identity.
	 *
	 * Only the scope "runtime" carries the built blocks and so the translated
	 * field texts; the other scopes (generated assets, editor assets, Tailwind,
	 * static output) keep their identity, so their caches and output paths do
	 * not multiply per locale. Without a registered namespace nothing changes.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $identity Runtime identity.
	 * @param mixed $scope    Cache scope.
	 * @return mixed The identity, with the locale for the scope "runtime".
	 */
	public static function filter_identity( mixed $identity, mixed $scope = null ): mixed {
		if ( ! is_array( $identity ) || self::CACHE_SCOPE !== $scope || array() === self::$namespaces ) {
			return $identity;
		}
		$identity[ self::IDENTITY_KEY ] = determine_locale();
		return $identity;
	}

	/**
	 * Tells whether a block name belongs to a registered namespace.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $name Block name.
	 * @return bool True for "<namespace>/<block>" of a registered namespace.
	 */
	private static function is_own( mixed $name ): bool {
		if ( ! is_string( $name ) || ! str_contains( $name, '/' ) ) {
			return false;
		}
		return isset( self::$namespaces[ strstr( $name, '/', true ) ] );
	}

	/**
	 * Translates the texts of one field and of the fields inside its tabs.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $field Field definition.
	 * @return array<mixed> Translated field.
	 */
	private static function field( array $field ): array {
		foreach ( array( 'label', 'help' ) as $key ) {
			if ( isset( $field[ $key ] ) ) {
				$field[ $key ] = self::text( $field[ $key ] );
			}
		}
		if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
			foreach ( $field['options'] as $index => $option ) {
				if ( is_array( $option ) && isset( $option['label'] ) ) {
					$field['options'][ $index ]['label'] = self::text( $option['label'] );
				}
			}
		}
		if ( 'tabs' === ( $field['type'] ?? null ) && isset( $field['tabs'] ) && is_array( $field['tabs'] ) ) {
			foreach ( $field['tabs'] as $index => $tab ) {
				if ( ! is_array( $tab ) || ! isset( $tab['attributes'] ) || ! is_array( $tab['attributes'] ) ) {
					continue;
				}
				foreach ( $tab['attributes'] as $child => $child_field ) {
					if ( is_array( $child_field ) ) {
						$field['tabs'][ $index ]['attributes'][ $child ] = self::field( $child_field );
					}
				}
			}
		}
		return $field;
	}

	/**
	 * Translates one value when it is a string.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $text Text.
	 * @return mixed Translation for a string, otherwise the value unchanged.
	 */
	private static function text( mixed $text ): mixed {
		return is_string( $text ) ? self::translated( $text ) : $text;
	}

	/**
	 * Translates one text with the theme text domain.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Text.
	 * @return string Translation; an empty text stays empty.
	 */
	private static function translated( string $text ): string {
		return '' === $text ? $text : translate( $text, 'creationell-wp-theme' );
	}

	/**
	 * Translates each string of a list.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $texts List of texts.
	 * @return mixed Translated list; other values unchanged.
	 */
	private static function texts( mixed $texts ): mixed {
		if ( ! is_array( $texts ) ) {
			return $texts;
		}
		foreach ( $texts as $index => $text ) {
			$texts[ $index ] = self::text( $text );
		}
		return $texts;
	}
}
