<?php
/**
 * Render template of the block creationell-theme/navbar; Blockstudio includes it with the block attributes in $attributes.
 *
 * Prints the navbar partial of the theme with the settings of the block, once per request.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Modules\HeaderFooter\Header_Blocks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

Header_Blocks::navbar( isset( $attributes ) && is_array( $attributes ) ? $attributes : array() );
