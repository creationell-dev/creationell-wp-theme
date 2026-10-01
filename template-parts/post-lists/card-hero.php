<?php
/**
 * Template part: post card of the post lists and the slider, layout hero.
 *
 * Prints the current post of the loop as image card: the image (large, empty
 * alt, no link) fills the card, the text sits on a dark box at 75 % opacity so
 * that its contrast does not depend on the image (FS-8). The box holds the
 * category badges, the title with the only link to the post (stretched over
 * the card), date and author for posts, the excerpt (none while the post wants
 * its password), "Read more" and the tags.
 * Without image the box grows to fill the card. The box is a dark color mode island
 * (data-bs-theme="dark"), so the badges take their dark colors, and its links
 * (the author) are light: the dark link color of Bootstrap alone stays under
 * 4.5:1 over a light image. $args['display'] holds the options of
 * Display_Options; the classes pass creationell_wp_theme_post_card_classes.
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
		'card'      => 'creationell-theme-post-card creationell-theme-post-hero card h-100 border-0 overflow-hidden',
		'image'     => 'card-img w-100 h-100 object-fit-cover',
		'overlay'   => 'card-img-overlay d-flex flex-column justify-content-end p-0',
		'plain'     => 'd-flex flex-column h-100',
		'body'      => 'bg-dark bg-opacity-75 text-white p-3 p-lg-4 d-flex flex-column',
		'badges'    => 'position-relative z-2',
		'title'     => 'card-title h5',
		'link'      => 'stretched-link text-white text-decoration-none',
		'meta'      => 'meta small mb-2 position-relative z-2',
		'read_more' => 'card-text mt-auto mb-0 text-decoration-underline',
		'tags'      => 'position-relative z-2',
	),
	'card-hero',
	$creationell_wp_theme_display['context']
);
$creationell_wp_theme_level   = 'h' . $creationell_wp_theme_display['heading_level'];
$creationell_wp_theme_title   = get_the_title();
$creationell_wp_theme_image   = $creationell_wp_theme_display['show_image'] && has_post_thumbnail();
?>
<article class="<?php echo esc_attr( $creationell_wp_theme_classes['card'] ); ?>">
	<?php if ( $creationell_wp_theme_image ) : ?>
		<?php
		the_post_thumbnail(
			'large',
			array(
				'class' => $creationell_wp_theme_classes['image'],
				'alt'   => '',
			)
		);
		?>
	<?php endif; ?>
	<div class="<?php echo esc_attr( $creationell_wp_theme_image ? $creationell_wp_theme_classes['overlay'] : $creationell_wp_theme_classes['plain'] ); ?>">
		<div class="<?php echo esc_attr( $creationell_wp_theme_classes['body'] . ( $creationell_wp_theme_image ? '' : ' flex-grow-1' ) ); ?>" data-bs-theme="dark" style="--bs-link-color-rgb: var(--bs-light-rgb); --bs-link-hover-color-rgb: var(--bs-white-rgb);">
			<?php if ( $creationell_wp_theme_display['show_categories'] && 'post' === get_post_type() ) : ?>
				<div class="<?php echo esc_attr( $creationell_wp_theme_classes['badges'] ); ?>"><?php creationell_wp_theme_category_badge(); ?></div>
			<?php endif; ?>
			<<?php echo esc_attr( $creationell_wp_theme_level ); ?> class="<?php echo esc_attr( $creationell_wp_theme_classes['title'] ); ?>"><a class="<?php echo esc_attr( $creationell_wp_theme_classes['link'] ); ?>" href="<?php echo esc_url( (string) get_permalink() ); ?>"><?php echo esc_html( $creationell_wp_theme_title ); ?></a></<?php echo esc_attr( $creationell_wp_theme_level ); ?>>
			<?php if ( $creationell_wp_theme_display['show_meta'] && 'post' === get_post_type() ) : ?>
				<p class="<?php echo esc_attr( $creationell_wp_theme_classes['meta'] ); ?>"><?php creationell_wp_theme_date(); ?><?php creationell_wp_theme_author(); ?></p>
			<?php endif; ?>
			<?php
			if ( $creationell_wp_theme_display['show_excerpt'] && ! post_password_required() ) {
				creationell_wp_theme_card_excerpt( 'card-hero' );
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
</article>
