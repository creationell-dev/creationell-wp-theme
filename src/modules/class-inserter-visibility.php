<?php
/**
 * Blocks that render but are missing from the block inserter.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules;

use WP_Block_Type;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Collects block names and sets supports.inserter to false for them.
 *
 * The module gate hides the blocks of hidden modules; a module hides blocks of
 * other plugins it replaces, e.g. creabb/post-grid. Existing content keeps
 * rendering, only the inserter no longer offers the block. Blockstudio blocks
 * change on blockstudio/blocks/meta; blocks that WordPress registers without
 * Blockstudio change on register_block_type_args. Call hide() before init, when
 * the blocks are registered.
 *
 * Blockstudio keeps the filtered block objects in its runtime cache. The hidden
 * names therefore join the identity of that cache (blockstudio/runtime/identity,
 * scope "runtime"): a cache built while blocks were hidden never serves a
 * request in which they are offered again, and the other way round. The
 * identity filter runs from the boot of the theme on, also without hidden
 * blocks, and carries a format mark: a cache built by older code, which did
 * not add the names, never matches and is left unused.
 *
 * @since 1.0.0
 */
final class Inserter_Visibility {

	/**
	 * Priority of both filters: late, so the change wins over earlier callbacks.
	 *
	 * @since 1.0.0
	 */
	public const PRIORITY = 100;

	/**
	 * Key of the hidden block names in the runtime cache identity.
	 *
	 * @since 1.0.0
	 */
	public const IDENTITY_KEY = 'creationell-wp-theme/hidden-blocks';

	/**
	 * Format mark of the identity entry; raise it when the filtered block objects change in another way.
	 *
	 * @since 1.0.0
	 */
	public const IDENTITY_FORMAT = 1;

	/**
	 * Hidden block names as keys, in the order of the first call.
	 *
	 * @var array<string, true>
	 */
	private static array $names = array();

	/**
	 * Adds the identity filter of the runtime cache; Module_Gate::register_hooks() calls it.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		if ( false === has_filter( Block_I18n::IDENTITY_FILTER, array( self::class, 'filter_identity' ) ) ) {
			add_filter( Block_I18n::IDENTITY_FILTER, array( self::class, 'filter_identity' ), self::PRIORITY, 2 );
		}
	}

	/**
	 * Hides a block from the block inserter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $block_name Full block name, e.g. "creationell-theme/card".
	 * @return void
	 */
	public static function hide( string $block_name ): void {
		if ( '' === $block_name ) {
			return;
		}
		self::$names[ $block_name ] = true;
		if ( false === has_filter( Block_I18n::META_FILTER, array( self::class, 'filter_meta' ) ) ) {
			add_filter( Block_I18n::META_FILTER, array( self::class, 'filter_meta' ), self::PRIORITY, 1 );
			add_filter( 'register_block_type_args', array( self::class, 'filter_args' ), self::PRIORITY, 2 );
		}
		self::register_hooks();
	}

	/**
	 * Adds the format mark and the hidden block names to the runtime cache identity; runs on blockstudio/runtime/identity.
	 *
	 * Only the scope "runtime" holds the built blocks. The entry is there also
	 * without hidden blocks, so the identity differs from the one of older code;
	 * the names are sorted, so the order of the hide() calls does not split the
	 * cache.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $identity Runtime identity.
	 * @param mixed $scope    Cache scope.
	 * @return mixed The identity, with format mark and hidden names for the scope "runtime".
	 */
	public static function filter_identity( mixed $identity, mixed $scope = null ): mixed {
		if ( ! is_array( $identity ) || Block_I18n::CACHE_SCOPE !== $scope ) {
			return $identity;
		}
		$names = array_keys( self::$names );
		sort( $names, SORT_STRING );
		$identity[ self::IDENTITY_KEY ] = array(
			'format' => self::IDENTITY_FORMAT,
			'hidden' => $names,
		);
		return $identity;
	}

	/**
	 * Tells whether a block is hidden from the inserter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $block_name Full block name.
	 * @return bool True when hidden.
	 */
	public static function is_hidden( string $block_name ): bool {
		return isset( self::$names[ $block_name ] );
	}

	/**
	 * Returns the hidden block names.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Names in the order of the first call.
	 */
	public static function names(): array {
		return array_keys( self::$names );
	}

	/**
	 * Forgets the hidden names; for tests.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$names = array();
	}

	/**
	 * Sets supports.inserter to false on a hidden Blockstudio block; runs on blockstudio/blocks/meta.
	 *
	 * The filter is global, so the value may be any block of any plugin or a
	 * value of another type; everything else passes unchanged.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $block Block type object or block metadata array.
	 * @return mixed The same value, changed for hidden blocks.
	 */
	public static function filter_meta( mixed $block ): mixed {
		if ( $block instanceof WP_Block_Type ) {
			if ( self::is_hidden( $block->name ) ) {
				$block->supports = self::without_inserter( $block->supports );
			}
			return $block;
		}
		if ( is_array( $block ) && is_string( $block['name'] ?? null ) && self::is_hidden( $block['name'] ) ) {
			$block['supports'] = self::without_inserter( $block['supports'] ?? null );
		}
		return $block;
	}

	/**
	 * Sets supports.inserter to false in the arguments of a hidden block; runs on register_block_type_args.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $args       Arguments of the block type.
	 * @param mixed $block_name Block name.
	 * @return mixed The same arguments, changed for hidden blocks.
	 */
	public static function filter_args( mixed $args, mixed $block_name ): mixed {
		if ( ! is_array( $args ) || ! is_string( $block_name ) || ! self::is_hidden( $block_name ) ) {
			return $args;
		}
		$args['supports'] = self::without_inserter( $args['supports'] ?? null );
		return $args;
	}

	/**
	 * Returns the supports with the inserter switched off.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $supports Supports; anything but an array counts as none.
	 * @return array<mixed> Supports with "inserter" false.
	 */
	private static function without_inserter( mixed $supports ): array {
		$supports             = is_array( $supports ) ? $supports : array();
		$supports['inserter'] = false;
		return $supports;
	}
}
