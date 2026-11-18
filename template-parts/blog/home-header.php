<?php
/**
 * Template part: the header of the blog page with its title and the excerpt of the blog page.
 *
 * Prints the only H1 of the blog page. Arguments: title (plain text, the
 * title of the blog page or the site name), excerpt (excerpt of the blog page;
 * empty prints no lead), context (home). The H1 takes the classes of the
 * filter creationell_wp_theme_class_entry_title with the context index, like
 * index.php.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_args    = isset( $args ) && is_array( $args ) ? $args : array();
$creationell_wp_theme_title   = is_string( $creationell_wp_theme_args['title'] ?? null ) ? $creationell_wp_theme_args['title'] : '';
$creationell_wp_theme_excerpt = is_string( $creationell_wp_theme_args['excerpt'] ?? null ) ? trim( $creationell_wp_theme_args['excerpt'] ) : '';

/**
 * Filters the CSS classes of the page title.
 *
 * @since 1.0.0
 *
 * @param string $classes Space-separated CSS classes.
 * @param string $context Template, for example page or archive.
 */
$creationell_wp_theme_class_entry_title = apply_filters( 'creationell_wp_theme_class_entry_title', '', 'index' );
?>
<div class="p-5 text-center bg-body-tertiary rounded mb-4">
	<h1 class="entry-title <?php echo esc_attr( $creationell_wp_theme_class_entry_title ); ?>"><?php echo esc_html( $creationell_wp_theme_title ); ?></h1>
	<?php if ( '' !== $creationell_wp_theme_excerpt ) : ?>
	<p class="lead mb-0"><?php echo wp_kses_post( $creationell_wp_theme_excerpt ); ?></p>
	<?php endif; ?>
</div>
