<?php
/**
 * Template part: the blog page without posts.
 *
 * A note without role="alert" and without a heading, so the H1 of the blog
 * page stays the only heading, and a search form with a name of its own.
 * Arguments: context (home).
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<p><?php esc_html_e( 'There are no posts yet.', 'creationell-wp-theme' ); ?></p>
<?php
get_search_form( array( 'aria_label' => __( 'Search the blog', 'creationell-wp-theme' ) ) );
