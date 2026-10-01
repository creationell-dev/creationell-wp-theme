<?php
/**
 * Template part for displaying taxonomy TERM loop items in cards-overlay (hero template).
 * Counterpart to cards-overlay.php (posts), used by creationell-theme-loop when 'type=""'
 * is a taxonomy rather than a post type (e.g. type="category" layout="overlay").
 * Template Version: 6.5.0
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * CAUTION: this layout's card height comes entirely from the image
 * (.card-img-overlay is absolutely positioned). A term without a thumbnail
 * (e.g. plain "category"/"post_tag" with no thumbnail_id meta set) will render
 * with no image and the card can collapse to zero height. See README.
 *
 * Expects $args['term'] to be a WP_Term object (passed by creationell-theme-loop).
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_context = 'cards-overlay';

$creationell_wp_theme_term = ( ! empty( $args['term'] ) && $args['term'] instanceof WP_Term ) ? $args['term'] : null;
if ( ! $creationell_wp_theme_term ) {
	return;
}

$creationell_wp_theme_term_link = get_term_link( $creationell_wp_theme_term );
$creationell_wp_theme_term_link = is_wp_error( $creationell_wp_theme_term_link ) ? '' : $creationell_wp_theme_term_link;

// Reuses the 'thumbnail_id' term meta key (the same one WooCommerce uses for
// product category images), so any plugin that sets a term thumbnail this way will show up here.
// Core WordPress categories/tags have no built-in term thumbnail, so $term_thumbnail_id
// will be empty for them by default. Unlike cards-horizontal/cards-grid, this layout's
// height comes entirely from the image — see the CAUTION note above the file header.
$creationell_wp_theme_term_thumbnail_id = get_term_meta( $creationell_wp_theme_term->term_id, 'thumbnail_id', true );

/**
 * Filters the label after the number of posts in a term card.
 *
 * @since 1.0.0
 *
 * @param string  $label   Translated label, singular or plural.
 * @param WP_Term $term    Term of the card.
 * @param string  $context Template part of the loop item, for example cards-grid.
 */
$creationell_wp_theme_term_count_label = apply_filters(
	'creationell_wp_theme_bs_loop_term_count_label',
	_n( 'item', 'items', $creationell_wp_theme_term->count, 'creationell-wp-theme' ),
	$creationell_wp_theme_term,
	'cards-overlay'
);
?>

<?php
/**
 * Fires before a loop item.
 *
 * @since 1.0.0
 *
 * @param string $context Template part of the loop item, for example cards-grid.
 */
do_action( 'creationell_wp_theme_before_loop_item', 'cards-overlay' );
?>

<?php
/**
 * Filters the CSS classes of a loop item card.
 *
 * @since 1.0.0
 *
 * @param string $classes Space-separated CSS classes.
 * @param string $context Template part of the loop item, for example cards-grid.
 */
$creationell_wp_theme_class_loop_card = apply_filters( 'creationell_wp_theme_class_loop_card', 'card rounded-4 overflow-hidden', 'cards-overlay' );
?>
<div id="term-<?php echo esc_attr( (string) $creationell_wp_theme_term->term_id ); ?>" class="<?php echo esc_attr( $creationell_wp_theme_class_loop_card ); ?>">

	<?php
	/**
	 * Fires before the thumbnail of a loop item.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Template part of the loop item, for example cards-grid.
	 */
	do_action( 'creationell_wp_theme_before_loop_thumbnail', 'cards-overlay' );
	?>

	<?php if ( $creationell_wp_theme_term_thumbnail_id ) : ?>
		<?php
		if ( $creationell_wp_theme_term_link ) :
			?>
			<a href="<?php echo esc_url( $creationell_wp_theme_term_link ); ?>" aria-hidden="true" tabindex="-1"><?php endif; ?>
		<?php
		/**
		 * Filters the CSS classes of the image of a loop item.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $context Template part of the loop item, for example cards-grid.
		 */
		echo wp_get_attachment_image( $creationell_wp_theme_term_thumbnail_id, 'full', false, array( 'class' => esc_attr( apply_filters( 'creationell_wp_theme_class_loop_card_image', 'card-img', 'cards-overlay' ) ) ) );
		?>
		<?php
		if ( $creationell_wp_theme_term_link ) :
			?>
			</a><?php endif; ?>
	<?php endif; ?>

	<?php
	/**
	 * Fires after the thumbnail of a loop item.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Template part of the loop item, for example cards-grid.
	 */
	do_action( 'creationell_wp_theme_after_loop_thumbnail', 'cards-overlay' );
	?>

	<?php
	/**
	 * Filters the CSS classes of the image overlay of a loop item card.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 * @param string $context Template part of the loop item, for example cards-grid.
	 */
	$creationell_wp_theme_class_loop_card_img_overlay = apply_filters( 'creationell_wp_theme_class_loop_card_img_overlay', 'card-img-overlay d-flex flex-column', 'cards-overlay' );
	?>
	<div class="<?php echo esc_attr( $creationell_wp_theme_class_loop_card_img_overlay ); ?>">

	<?php
	/**
	 * Filters the CSS classes of the content wrapper in the image overlay of a loop item card.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 * @param string $context Template part of the loop item, for example cards-grid.
	 */
	$creationell_wp_theme_class_loop_card_img_overlay_content_wrapper = apply_filters( 'creationell_wp_theme_class_loop_card_img_overlay_content_wrapper', 'bg-body bg-opacity-50 backdrop-blur-10 rounded-3 p-3 mt-auto', 'cards-overlay' );
	?>
	<div class="<?php echo esc_attr( $creationell_wp_theme_class_loop_card_img_overlay_content_wrapper ); ?>">

		<?php
		/**
		 * Fires before the title of a loop item.
		 *
		 * @since 1.0.0
		 *
		 * @param string $context Template part of the loop item, for example cards-grid.
		 */
		do_action( 'creationell_wp_theme_before_loop_title', 'cards-overlay' );
		?>

		<?php if ( $creationell_wp_theme_term_link ) : ?>
			<?php
			/**
			 * Filters the CSS classes of the title link of a loop item.
			 *
			 * @since 1.0.0
			 *
			 * @param string $classes Space-separated CSS classes.
			 * @param string $context Template part of the loop item, for example cards-grid.
			 */
			$creationell_wp_theme_class_loop_card_title_link = apply_filters( 'creationell_wp_theme_class_loop_card_title_link', 'text-body text-decoration-none', 'cards-overlay' );
			?>
		<a class="<?php echo esc_attr( $creationell_wp_theme_class_loop_card_title_link ); ?>" href="<?php echo esc_url( $creationell_wp_theme_term_link ); ?>">
			<?php
			/**
			 * Filters the CSS classes of the title of a loop item.
			 *
			 * @since 1.0.0
			 *
			 * @param string $classes Space-separated CSS classes.
			 * @param string $context Template part of the loop item, for example cards-grid.
			 */
			$creationell_wp_theme_class_loop_card_title_2 = apply_filters( 'creationell_wp_theme_class_loop_card_title', 'h5', 'cards-overlay' );
			?>
			<h2 class="<?php echo esc_attr( $creationell_wp_theme_class_loop_card_title_2 ); ?>"><?php echo esc_html( $creationell_wp_theme_term->name ); ?></h2>
		</a>
		<?php else : ?>
			<?php
			/**
			 * Filters the CSS classes of the title of a loop item.
			 *
			 * @since 1.0.0
			 *
			 * @param string $classes Space-separated CSS classes.
			 * @param string $context Template part of the loop item, for example cards-grid.
			 */
			$creationell_wp_theme_class_loop_card_title = apply_filters( 'creationell_wp_theme_class_loop_card_title', 'h5', 'cards-overlay' );
			?>
		<h2 class="<?php echo esc_attr( $creationell_wp_theme_class_loop_card_title ); ?>"><?php echo esc_html( $creationell_wp_theme_term->name ); ?></h2>
		<?php endif; ?>

		<?php
		/**
		 * Fires after the title of a loop item.
		 *
		 * @since 1.0.0
		 *
		 * @param string $context Template part of the loop item, for example cards-grid.
		 */
		do_action( 'creationell_wp_theme_after_loop_title', 'cards-overlay' );
		?>

		<?php
		/**
		 * Filters whether a loop item shows the post meta.
		 *
		 * @since 1.0.0
		 *
		 * @param bool   $show    Whether to show the post meta.
		 * @param string $context Template part of the loop item, for example cards-grid.
		 */
		if ( apply_filters( 'creationell_wp_theme_loop_meta', true, 'cards-overlay' ) && $creationell_wp_theme_term->count > 0 ) :
			?>
		<p class="meta small mb-2 text-body-secondary">
			<?php echo esc_html( $creationell_wp_theme_term->count . ' ' . $creationell_wp_theme_term_count_label ); ?>
		</p>
		<?php endif; ?>

		<?php
		/**
		 * Filters whether a loop item shows the excerpt.
		 *
		 * @since 1.0.0
		 *
		 * @param bool   $show    Whether to show the excerpt.
		 * @param string $context Template part of the loop item, for example cards-grid.
		 */
		if ( apply_filters( 'creationell_wp_theme_loop_excerpt', true, 'cards-overlay' ) && ! empty( $creationell_wp_theme_term->description ) ) :
			?>
			<?php
			/**
			 * Filters the CSS classes of the excerpt of a loop item.
			 *
			 * @since 1.0.0
			 *
			 * @param string $classes Space-separated CSS classes.
			 * @param string $context Template part of the loop item, for example cards-grid.
			 */
			$creationell_wp_theme_class_loop_card_text_excerpt = apply_filters( 'creationell_wp_theme_class_loop_card_text_excerpt', 'card-text', 'cards-overlay' );
			?>
		<p class="<?php echo esc_attr( $creationell_wp_theme_class_loop_card_text_excerpt ); ?>">
			<?php echo esc_html( wp_trim_words( wp_strip_all_tags( $creationell_wp_theme_term->description ), 20 ) ); ?>
		</p>
		<?php endif; ?>

		<?php
		/**
		 * Filters whether a loop item shows the read more link.
		 *
		 * @since 1.0.0
		 *
		 * @param bool   $show    Whether to show the read more link.
		 * @param string $context Template part of the loop item, for example cards-grid.
		 */
		if ( apply_filters( 'creationell_wp_theme_loop_read_more', true, 'cards-overlay' ) && $creationell_wp_theme_term_link ) :
			?>
			<?php
			/**
			 * Filters the CSS classes of the paragraph around the read more link of a loop item.
			 *
			 * @since 1.0.0
			 *
			 * @param string $classes Space-separated CSS classes.
			 * @param string $context Template part of the loop item, for example cards-grid.
			 */
			$creationell_wp_theme_class_loop_card_text_read_more = apply_filters( 'creationell_wp_theme_class_loop_card_text_read_more', 'card-text mt-auto', 'cards-overlay' );
			?>
		<p class="<?php echo esc_attr( $creationell_wp_theme_class_loop_card_text_read_more ); ?>">
			<?php
			/**
			 * Filters the CSS classes of the read more link of a loop item.
			 *
			 * @since 1.0.0
			 *
			 * @param string $classes Space-separated CSS classes.
			 * @param string $context Template part of the loop item, for example cards-grid.
			 */
			$creationell_wp_theme_class_loop_read_more = apply_filters( 'creationell_wp_theme_class_loop_read_more', 'read-more', 'cards-overlay' );
			?>
			<a class="<?php echo esc_attr( $creationell_wp_theme_class_loop_read_more ); ?>" href="<?php echo esc_url( $creationell_wp_theme_term_link ); ?>">
			<?php
			/**
			 * Filters the text of the read more link of a loop item.
			 *
			 * @since 1.0.0
			 *
			 * @param string $text    Link text, may contain HTML.
			 * @param string $context Template part of the loop item, for example cards-grid.
			 */
			echo wp_kses_post( apply_filters( 'creationell_wp_theme_loop_read_more_text', __( 'Read more »', 'creationell-wp-theme' ), 'cards-overlay' ) );
			?>
			</a>
		</p>
		<?php endif; ?>

		<?php
		/**
		 * Fires after the tags of a loop item.
		 *
		 * @since 1.0.0
		 *
		 * @param string $context Template part of the loop item, for example cards-grid.
		 */
		do_action( 'creationell_wp_theme_after_loop_tags', 'cards-overlay' );
		?>

	</div>

	</div>

	<?php
	/**
	 * Fires after the image overlay of a loop item card.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Template part of the loop item, for example cards-grid.
	 */
	do_action( 'creationell_wp_theme_loop_item_after_card_img_overlay', 'cards-overlay' );
	?>

</div>

<?php
/**
 * Fires after a loop item.
 *
 * @since 1.0.0
 *
 * @param string $context Template part of the loop item, for example cards-grid.
 */
do_action( 'creationell_wp_theme_after_loop_item', 'cards-overlay' );
?>