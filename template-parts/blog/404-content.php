<?php
/**
 * Template part: search, popular sections and the latest posts on the 404 page.
 *
 * In this order: a search form with the heading and the name "Search the
 * website"; the menu of the location 404-links as a navigation named by its
 * heading "Popular sections"; the latest posts as a section named by its
 * heading "Latest posts", each with its date, and below them the link "To
 * the blog". The module blog prints this part on the action
 * creationell_wp_theme_404_after_content, after the widget area and before
 * the link home.
 *
 * Arguments (raw values, escaped here): search (bool, whether the search
 * shows), menu (bool, whether the menu shows), posts (entries: title as
 * plain text, empty gives "(no title)", url, datetime, date), blog_url (URL
 * of the blog page; empty prints no link), heading_ids (IDs of the headings
 * by section, keys menu and posts; a missing ID comes from wp_unique_id()),
 * context (template, here 404).
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_args        = isset( $args ) && is_array( $args ) ? $args : array();
$creationell_wp_theme_search      = true === ( $creationell_wp_theme_args['search'] ?? false );
$creationell_wp_theme_menu        = true === ( $creationell_wp_theme_args['menu'] ?? false );
$creationell_wp_theme_posts       = array_values(
	array_filter(
		is_array( $creationell_wp_theme_args['posts'] ?? null ) ? $creationell_wp_theme_args['posts'] : array(),
		static fn( mixed $entry ): bool => is_array( $entry ) && is_string( $entry['url'] ?? null ) && '' !== $entry['url']
	)
);
$creationell_wp_theme_blog_url    = is_string( $creationell_wp_theme_args['blog_url'] ?? null ) ? $creationell_wp_theme_args['blog_url'] : '';
$creationell_wp_theme_heading_ids = is_array( $creationell_wp_theme_args['heading_ids'] ?? null ) ? $creationell_wp_theme_args['heading_ids'] : array();
if ( ! $creationell_wp_theme_search && ! $creationell_wp_theme_menu && array() === $creationell_wp_theme_posts ) {
	return;
}

if ( $creationell_wp_theme_search ) :
	$creationell_wp_theme_label = __( 'Search the website', 'creationell-wp-theme' );
	?>
<div class="widget not-found-search mb-4">
	<h2 class="widget-title h4"><?php echo esc_html( $creationell_wp_theme_label ); ?></h2>
	<?php get_search_form( array( 'aria_label' => $creationell_wp_theme_label ) ); ?>
</div>
	<?php
endif;

if ( $creationell_wp_theme_menu ) :
	$creationell_wp_theme_heading_id = is_string( $creationell_wp_theme_heading_ids['menu'] ?? null ) && '' !== $creationell_wp_theme_heading_ids['menu'] ? $creationell_wp_theme_heading_ids['menu'] : wp_unique_id( 'creationell-theme-404-links-' );
	?>
<div class="widget not-found-links mb-4">
	<h2 id="<?php echo esc_attr( $creationell_wp_theme_heading_id ); ?>" class="widget-title h4"><?php esc_html_e( 'Popular sections', 'creationell-wp-theme' ); ?></h2>
	<nav aria-labelledby="<?php echo esc_attr( $creationell_wp_theme_heading_id ); ?>">
		<?php
		wp_nav_menu(
			array(
				'theme_location' => '404-links',
				'container'      => false,
				'depth'          => 1,
				'fallback_cb'    => false,
			)
		);
		?>
	</nav>
</div>
	<?php
endif;

if ( array() !== $creationell_wp_theme_posts ) :
	$creationell_wp_theme_heading_id = is_string( $creationell_wp_theme_heading_ids['posts'] ?? null ) && '' !== $creationell_wp_theme_heading_ids['posts'] ? $creationell_wp_theme_heading_ids['posts'] : wp_unique_id( 'creationell-theme-404-posts-' );
	?>
<section class="widget not-found-posts mb-4" aria-labelledby="<?php echo esc_attr( $creationell_wp_theme_heading_id ); ?>">
	<h2 id="<?php echo esc_attr( $creationell_wp_theme_heading_id ); ?>" class="widget-title h4"><?php esc_html_e( 'Latest posts', 'creationell-wp-theme' ); ?></h2>
	<ul>
	<?php
	foreach ( $creationell_wp_theme_posts as $creationell_wp_theme_entry ) :
		$creationell_wp_theme_title    = is_string( $creationell_wp_theme_entry['title'] ?? null ) ? trim( $creationell_wp_theme_entry['title'] ) : '';
		$creationell_wp_theme_title    = '' === $creationell_wp_theme_title ? __( '(no title)', 'creationell-wp-theme' ) : $creationell_wp_theme_title;
		$creationell_wp_theme_datetime = is_string( $creationell_wp_theme_entry['datetime'] ?? null ) ? $creationell_wp_theme_entry['datetime'] : '';
		$creationell_wp_theme_date     = is_string( $creationell_wp_theme_entry['date'] ?? null ) ? $creationell_wp_theme_entry['date'] : '';
		?>
		<li><a href="<?php echo esc_url( $creationell_wp_theme_entry['url'] ); ?>"><?php echo esc_html( $creationell_wp_theme_title ); ?></a><?php echo '' === $creationell_wp_theme_datetime || '' === $creationell_wp_theme_date ? '' : ' <time class="small text-body-secondary" datetime="' . esc_attr( $creationell_wp_theme_datetime ) . '">' . esc_html( $creationell_wp_theme_date ) . '</time>'; ?></li>
		<?php
	endforeach;
	?>
	</ul>
	<?php if ( '' !== $creationell_wp_theme_blog_url ) : ?>
	<p><a class="btn btn-outline-primary" href="<?php echo esc_url( $creationell_wp_theme_blog_url ); ?>"><?php esc_html_e( 'To the blog', 'creationell-wp-theme' ); ?></a></p>
	<?php endif; ?>
</section>
	<?php
endif;
