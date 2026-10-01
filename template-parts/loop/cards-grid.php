<?php
/**
 * Template part for displaying loop items in cards
 * Template Version: 7.0.0
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_context = 'cards-grid';
?>


<?php
/**
 * Fires before a loop item.
 *
 * @since 1.0.0
 *
 * @param string $context Template part of the loop item, for example cards-grid.
 */
do_action( 'creationell_wp_theme_before_loop_item', 'cards-grid' );
?>

<!-- Default Post/CPT Card -->
<?php
/**
 * Filters the CSS classes of a loop item card.
 *
 * @since 1.0.0
 *
 * @param string $classes Space-separated CSS classes.
 * @param string $context Template part of the loop item, for example cards-grid.
 */
$creationell_wp_theme_class_loop_card = apply_filters( 'creationell_wp_theme_class_loop_card', 'card h-100', 'cards-grid' );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( esc_attr( $creationell_wp_theme_class_loop_card ) ); ?>>

	<?php
	/**
	 * Fires before the thumbnail of a loop item.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Template part of the loop item, for example cards-grid.
	 */
	do_action( 'creationell_wp_theme_before_loop_thumbnail', 'cards-grid' );
	?>
	
	<?php if ( has_post_thumbnail() ) : ?>
	<a href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
		<?php
		/**
		 * Filters the CSS classes of the image of a loop item.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $context Template part of the loop item, for example cards-grid.
		 */
		the_post_thumbnail( 'medium', array( 'class' => esc_attr( apply_filters( 'creationell_wp_theme_class_loop_card_image', 'card-img-top', 'cards-grid' ) ) ) );
		?>
	</a>
	<?php endif; ?>

	<?php
	/**
	 * Fires after the thumbnail of a loop item.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Template part of the loop item, for example cards-grid.
	 */
	do_action( 'creationell_wp_theme_after_loop_thumbnail', 'cards-grid' );
	?>

	<?php
	/**
	 * Filters the CSS classes of the card body of a loop item.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 * @param string $context Template part of the loop item, for example cards-grid.
	 */
	$creationell_wp_theme_class_loop_card_body = apply_filters( 'creationell_wp_theme_class_loop_card_body', 'card-body h-100 d-flex flex-column', 'cards-grid' );
	?>
	<div class="<?php echo esc_attr( $creationell_wp_theme_class_loop_card_body ); ?>">

	<?php
	/**
	 * Filters the CSS classes of the wrapper around the category and sticky badges of a loop item.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 * @param string $context Template part of the loop item, for example cards-grid.
	 */
	$creationell_wp_theme_class_loop_card_content_meta_wrapper = apply_filters( 'creationell_wp_theme_class_loop_card_content_meta_wrapper', 'd-flex justify-content-between gap-3', 'cards-grid' );
	?>
	<div class="<?php echo esc_attr( $creationell_wp_theme_class_loop_card_content_meta_wrapper ); ?>">

		<?php
		/**
		 * Filters whether a loop item shows its categories.
		 *
		 * @since 1.0.0
		 *
		 * @param bool   $show    Whether to show the categories.
		 * @param string $context Template part of the loop item, for example cards-grid.
		 */
		if ( apply_filters( 'creationell_wp_theme_loop_category', true, 'cards-grid' ) ) :
			?>
			<?php creationell_wp_theme_category_badge(); ?>
		<?php endif; ?>

		<?php if ( is_sticky() ) : ?>
			<?php creationell_wp_theme_sticky_badge( 'cards-grid' ); ?>
		<?php endif; ?>

	</div>

	<?php
	/**
	 * Fires before the title of a loop item.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Template part of the loop item, for example cards-grid.
	 */
	do_action( 'creationell_wp_theme_before_loop_title', 'cards-grid' );
	?>
	
	<?php
	/**
	 * Filters the CSS classes of the title link of a loop item.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 * @param string $context Template part of the loop item, for example cards-grid.
	 */
	$creationell_wp_theme_class_loop_card_title_link = apply_filters( 'creationell_wp_theme_class_loop_card_title_link', 'text-body text-decoration-none', 'cards-grid' );
	?>
	<a class="<?php echo esc_attr( $creationell_wp_theme_class_loop_card_title_link ); ?>" href="<?php the_permalink(); ?>">
		<?php
		/**
		 * Filters the CSS classes of the title of a loop item.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $context Template part of the loop item, for example cards-grid.
		 */
		the_title( '<h2 class="' . esc_attr( apply_filters( 'creationell_wp_theme_class_loop_card_title', 'h5', 'cards-grid' ) ) . '">', '</h2>' );
		?>
	</a>
   
	<?php
	/**
	 * Fires after the title of a loop item.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Template part of the loop item, for example cards-grid.
	 */
	do_action( 'creationell_wp_theme_after_loop_title', 'cards-grid' );
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
	if ( apply_filters( 'creationell_wp_theme_loop_meta', true, 'cards-grid' ) ) :
		?>
		<?php if ( 'post' === get_post_type() ) : ?>
		<p class="meta small mb-2 text-body-secondary">
			<?php
			creationell_wp_theme_date();
			creationell_wp_theme_author();
			creationell_wp_theme_comments();
			creationell_wp_theme_edit();
			?>
		</p>
		<?php endif; ?>
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
	if ( apply_filters( 'creationell_wp_theme_loop_excerpt', true, 'cards-grid' ) ) :
		creationell_wp_theme_card_excerpt( 'cards-grid' );
	endif;
	?>

	<?php
	/**
	 * Filters whether a loop item shows the read more link.
	 *
	 * @since 1.0.0
	 *
	 * @param bool   $show    Whether to show the read more link.
	 * @param string $context Template part of the loop item, for example cards-grid.
	 */
	if ( apply_filters( 'creationell_wp_theme_loop_read_more', true, 'cards-grid' ) ) :
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
		$creationell_wp_theme_class_loop_card_text_read_more = apply_filters( 'creationell_wp_theme_class_loop_card_text_read_more', 'card-text mt-auto', 'cards-grid' );
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
		$creationell_wp_theme_class_loop_read_more = apply_filters( 'creationell_wp_theme_class_loop_read_more', 'read-more', 'cards-grid' );
		?>
		<a class="<?php echo esc_attr( $creationell_wp_theme_class_loop_read_more ); ?>" href="<?php the_permalink(); ?>">
			<?php
			/**
			 * Filters the text of the read more link of a loop item.
			 *
			 * @since 1.0.0
			 *
			 * @param string $text    Link text, may contain HTML.
			 * @param string $context Template part of the loop item, for example cards-grid.
			 */
			echo wp_kses_post( apply_filters( 'creationell_wp_theme_loop_read_more_text', __( 'Read more »', 'creationell-wp-theme' ), 'cards-grid' ) );
			?>
		</a>
		</p>
	<?php endif; ?>

	<?php
	/**
	 * Filters whether a loop item shows its tags.
	 *
	 * @since 1.0.0
	 *
	 * @param bool   $show    Whether to show the tags.
	 * @param string $context Template part of the loop item, for example cards-grid.
	 */
	if ( apply_filters( 'creationell_wp_theme_loop_tags', true, 'cards-grid' ) && has_tag() ) :
		?>
		<?php creationell_wp_theme_tags(); ?>
	<?php endif; ?>

	<?php
	/**
	 * Fires after the tags of a loop item.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Template part of the loop item, for example cards-grid.
	 */
	do_action( 'creationell_wp_theme_after_loop_tags', 'cards-grid' );
	?>

	</div>

	<?php
	/**
	 * Fires after the card body of a loop item.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Template part of the loop item, for example cards-grid.
	 */
	do_action( 'creationell_wp_theme_loop_item_after_card_body', 'cards-grid' );
	?>

</article>

<?php
/**
 * Fires after a loop item.
 *
 * @since 1.0.0
 *
 * @param string $context Template part of the loop item, for example cards-grid.
 */
do_action( 'creationell_wp_theme_after_loop_item', 'cards-grid' );
?>