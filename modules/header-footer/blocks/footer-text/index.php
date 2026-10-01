<?php
/**
 * Render template of the block creationell-theme/footer-text; Blockstudio includes it when the block renders.
 *
 * Prints the footer info partial of the theme: the footer text of the settings or the copyright line.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Modules\HeaderFooter\Text_Blocks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

Text_Blocks::footer_text();
