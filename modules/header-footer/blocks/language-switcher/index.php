<?php
/**
 * Render template of the block creationell-theme/language-switcher; Blockstudio includes it with $attributes and $isEditor in scope.
 *
 * Prints the language switcher of the theme core, or a placeholder in the editor without WPML.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Modules\HeaderFooter\Text_Blocks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

Text_Blocks::language_switcher( isset( $attributes ) && is_array( $attributes ) ? $attributes : array(), true === ( get_defined_vars()['isEditor'] ?? null ) );
