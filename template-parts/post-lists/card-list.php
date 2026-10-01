<?php
/**
 * Template part: post card of the post lists, layout list (horizontal card).
 *
 * Prints the current post of the loop as horizontal Bootstrap card: image in
 * the start column (col-md-4, medium_large, empty alt, no link), then category
 * badges, the title with the only link to the post (stretched over the card),
 * date and author for posts, the excerpt (none while the post wants its
 * password), "Read more" and the tags.
 * $args['display'] holds the options of Display_Options; the classes pass
 * creationell_wp_theme_post_card_classes.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Posts\Display_Options;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_display = Display_Options::normalize( $args['display'] ?? null );
$creationell_wp_theme_classes = Display_Options::card_classes(
	array(
		'card'      => 'creationell-theme-post-card card h-100',
		'row'       => 'row g-0 h-100',
		'media'     => 'col-md-4',
		'image'     => 'img-fluid rounded-start w-100 h-100 object-fit-cover',
		'content'   => 'col',
		'body'      => 'card-body d-flex flex-column h-100',
		'badges'    => 'position-relative z-2',
		'title'     => 'card-title h5',
		'link'      => 'stretched-link text-body text-decoration-none',
		'meta'      => 'meta small mb-2 text-body-secondary position-relative z-2',
		'read_more' => 'card-text mt-auto mb-0 link-primary',
		'tags'      => 'position-relative z-2',
	),
	'card-list',
	$creationell_wp_theme_display['context']
);
$creationell_wp_theme_level   = 'h' . $creationell_wp_theme_display['heading_level'];
$creationell_wp_theme_title   = get_the_title();
?>
<article class="<?php echo esc_attr( $creationell_wp_theme_classes['card'] ); ?>">
	<div class="<?php echo esc_attr( $creationell_wp_theme_classes['row'] ); ?>">
		<?php if ( $creationell_wp_theme_display['show_image'] && has_post_thumbnail() ) : ?>
			<div class="<?php echo esc_attr( $creationell_wp_theme_classes['media'] ); ?>">
			<?php
			the_post_thumbnail(
				'medium_large',
				array(
					'class' => $creationell_wp_theme_classes['image'],
					'alt'   => '',
				)
			);
			?>
			</div>
		<?php endif; ?>
		<div class="<?php echo esc_attr( $creationell_wp_theme_classes['content'] ); ?>">
			<div class="<?php echo esc_attr( $creationell_wp_theme_classes['body'] ); ?>">
				<?php if ( $creationell_wp_theme_display['show_categories'] && 'post' === get_post_type() ) : ?>
					<div class="<?php echo esc_attr( $creationell_wp_theme_classes['badges'] ); ?>"><?php creationell_wp_theme_category_badge(); ?></div>
				<?php endif; ?>
				<<?php echo esc_attr( $creationell_wp_theme_level ); ?> class="<?php echo esc_attr( $creationell_wp_theme_classes['title'] ); ?>"><a class="<?php echo esc_attr( $creationell_wp_theme_classes['link'] ); ?>" href="<?php echo esc_url( (string) get_permalink() ); ?>"><?php echo esc_html( $creationell_wp_theme_title ); ?></a></<?php echo esc_attr( $creationell_wp_theme_level ); ?>>
				<?php if ( $creationell_wp_theme_display['show_meta'] && 'post' === get_post_type() ) : ?>
					<p class="<?php echo esc_attr( $creationell_wp_theme_classes['meta'] ); ?>"><?php creationell_wp_theme_date(); ?><?php creationell_wp_theme_author(); ?></p>
				<?php endif; ?>
				<?php
				if ( $creationell_wp_theme_display['show_excerpt'] && ! post_password_required() ) {
					creationell_wp_theme_card_excerpt( 'card-list' );
				}
				?>
				<?php if ( $creationell_wp_theme_display['show_read_more'] ) : ?>
					<p class="<?php echo esc_attr( $creationell_wp_theme_classes['read_more'] ); ?>"><?php esc_html_e( 'Read more', 'creationell-wp-theme' ); ?><span class="visually-hidden">: <?php echo esc_html( $creationell_wp_theme_title ); ?></span></p>
				<?php endif; ?>
				<?php if ( $creationell_wp_theme_display['show_tags'] && has_tag() ) : ?>
					<div class="<?php echo esc_attr( $creationell_wp_theme_classes['tags'] ); ?>"><?php creationell_wp_theme_tags(); ?></div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</article>
