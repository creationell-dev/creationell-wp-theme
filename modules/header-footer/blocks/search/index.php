<?php
/**
 * Render template of the block creationell-theme/search; Blockstudio includes it when the block renders.
 *
 * Prints the search form of the theme.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Modules\HeaderFooter\Header_Blocks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

Header_Blocks::search();
