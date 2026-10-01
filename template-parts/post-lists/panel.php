<?php
/**
 * Template part: the panel of one post in the accordion or the tabs of creationell-theme/post-accordion.
 *
 * Prints for the current post of the loop, each as the options allow: the
 * image (medium_large, empty alt, no link), the category badges and date and
 * author for posts, then the full content (content_mode "content") or the
 * excerpt, the link "Read more" with the title for screen readers, and the tags.
 * The content prints only while Content_Guard lets it (never the queried post
 * itself, never a second level); otherwise the excerpt shows. A password
 * protected post shows neither. Arguments: display (options of
 * Display_Options), content_mode (excerpt or content).
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Posts\Content_Guard;
use Creationell\WpTheme\Posts\Display_Options;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_args    = isset( $args ) && is_array( $args ) ? $args : array();
$creationell_wp_theme_display = Display_Options::normalize( $creationell_wp_theme_args['display'] ?? null );
$creationell_wp_theme_post_id = (int) get_the_ID();
$creationell_wp_theme_is_post = 'post' === get_post_type();
$creationell_wp_theme_content = 'content' === ( $creationell_wp_theme_args['content_mode'] ?? 'excerpt' ) && ! post_password_required() && Content_Guard::enter( $creationell_wp_theme_post_id );
?>
<?php if ( $creationell_wp_theme_display['show_image'] && has_post_thumbnail() ) : ?>
	<?php
	the_post_thumbnail(
		'medium_large',
		array(
			'class' => 'img-fluid rounded mb-3',
			'alt'   => '',
		)
	);
	?>
<?php endif; ?>
<?php if ( $creationell_wp_theme_display['show_categories'] && $creationell_wp_theme_is_post ) : ?>
	<div class="mb-2"><?php creationell_wp_theme_category_badge(); ?></div>
<?php endif; ?>
<?php if ( $creationell_wp_theme_display['show_meta'] && $creationell_wp_theme_is_post ) : ?>
	<p class="meta small mb-2 text-body-secondary"><?php creationell_wp_theme_date(); ?><?php creationell_wp_theme_author(); ?></p>
<?php endif; ?>
<?php
if ( $creationell_wp_theme_content ) {
	echo '<div class="creationell-theme-post-list__content">';
	the_content();
	echo '</div>';
	Content_Guard::leave( $creationell_wp_theme_post_id );
} elseif ( $creationell_wp_theme_display['show_excerpt'] && ! post_password_required() ) {
	creationell_wp_theme_card_excerpt( 'post-accordion' );
}
?>
<?php if ( $creationell_wp_theme_display['show_read_more'] ) : ?>
	<p class="mb-0"><a href="<?php echo esc_url( (string) get_permalink() ); ?>"><?php esc_html_e( 'Read more', 'creationell-wp-theme' ); ?><span class="visually-hidden">: <?php echo esc_html( get_the_title() ); ?></span></a></p>
<?php endif; ?>
<?php if ( $creationell_wp_theme_display['show_tags'] && has_tag() ) : ?>
	<div class="mt-2"><?php creationell_wp_theme_tags(); ?></div>
<?php endif; ?>
