<?php
/**
 * Boot of the module header-footer.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\HeaderFooter;

use Creationell\WpTheme\Modules\Module_Gate;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Registers the parts of the module that act on their own: the rights of editors, the menu scope, the part translations and the block category.
 *
 * The file bootstrap.php calls boot() once, while the module is active. The render
 * path of the parts and the part gate belong to the theme core
 * (Core\Template_Part_Gate); the blocks come from the blocks/ folder, which the
 * module gate hands to Blockstudio.
 *
 * @since 1.0.0
 */
final class Header_Footer_Module {

	/**
	 * Registers the hooks of the module.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function boot(): void {
		Access_Context::register();
		Template_Part_Access::register();
		Editor_Restrictions::register();
		Menu_Scope::register();
		Part_Translations::register();
		add_filter( 'block_categories_all', array( self::class, 'block_categories' ), 10, 1 );
	}

	/**
	 * Adds the block category of the theme modules once; runs on block_categories_all.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $categories Block categories.
	 * @return mixed Categories with creationell-theme at the end; values other than an array unchanged.
	 */
	public static function block_categories( mixed $categories ): mixed {
		if ( ! is_array( $categories ) ) {
			return $categories;
		}
		foreach ( $categories as $category ) {
			if ( is_array( $category ) && Module_Gate::BLOCK_NAMESPACE === ( $category['slug'] ?? null ) ) {
				return $categories;
			}
		}
		$categories[] = array(
			'slug'  => Module_Gate::BLOCK_NAMESPACE,
			'title' => __( 'creationell Theme', 'creationell-wp-theme' ),
			'icon'  => null,
		);
		return $categories;
	}
}
