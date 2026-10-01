<?php
/**
 * Render template of the block creationell-theme/contact; Blockstudio includes it with $attributes and $isEditor in scope.
 *
 * Prints the contact data of the theme settings, or a placeholder in the editor without them.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Modules\HeaderFooter\Text_Blocks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

Text_Blocks::contact( isset( $attributes ) && is_array( $attributes ) ? $attributes : array(), true === ( get_defined_vars()['isEditor'] ?? null ) );
