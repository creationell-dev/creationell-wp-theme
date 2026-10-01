<?php
/**
 * Render template of the block creationell-theme/footer-menu; Blockstudio includes it when the block renders.
 *
 * Prints the footer menu partial of the theme.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Modules\HeaderFooter\Header_Blocks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

Header_Blocks::footer_menu();
