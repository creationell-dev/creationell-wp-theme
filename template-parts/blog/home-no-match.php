<?php
/**
 * Template part: the blog page when its posts exist but the main query is empty, for example after a filter that matches nothing.
 *
 * A note without role="alert", without a heading and without a search form,
 * so the H1 of the blog page stays the only heading and the filter stays the
 * way to change the selection. Arguments: context (home).
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<p><?php esc_html_e( 'No posts match these filters.', 'creationell-wp-theme' ); ?></p>
