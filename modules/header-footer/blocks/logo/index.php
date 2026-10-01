<?php
/**
 * Render template of the block creationell-theme/logo; Blockstudio includes it with the block attributes in $attributes.
 *
 * Prints the logo files of the theme, or the site name without them.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Modules\HeaderFooter\Header_Blocks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

Header_Blocks::logo( isset( $attributes ) && is_array( $attributes ) ? $attributes : array() );
