<?php
/**
 * Template part: empty state of the post blocks, shown in the block editor only.
 *
 * On the page a block without posts prints nothing; in the editor this
 * paragraph tells the author why the block looks empty.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<p class="creationell-theme-post-list__empty text-body-secondary"><?php esc_html_e( 'No posts found.', 'creationell-wp-theme' ); ?></p>
