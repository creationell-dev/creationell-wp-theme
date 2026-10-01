<?php
/**
 * Template Name: Full Width Image
 * Template Version: 6.3.1
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();
?>

	<div id="content" class="site-content">
	<div id="primary" class="content-area">
	  
		<?php
		/**
		 * Fires right after the opening tag of the main content area.
		 *
		 * @since 1.0.0
		 *
		 * @param string $context Template, for example page or archive.
		 */
		do_action( 'creationell_wp_theme_after_primary_open', 'page-full-width-image' );
		?>

		<main id="main" class="site-main">

		<?php $creationell_wp_theme_thumb = wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' ); ?>
		<?php
		/**
		 * Filters the CSS classes of the full width featured image area.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $context Template, for example page or archive.
		 */
		$creationell_wp_theme_class_featured_full_width_img = apply_filters( 'creationell_wp_theme_class_featured_full_width_img', 'featured-full-width-img height-75 bg-dark text-light mb-4', 'page-full-width-image' );
		?>
		<div class="entry-header <?php echo esc_attr( $creationell_wp_theme_class_featured_full_width_img ); ?>" style="background-image: url('<?php echo esc_url( is_array( $creationell_wp_theme_thumb ) ? $creationell_wp_theme_thumb[0] : '' ); ?>')">
			<?php
			/**
			 * Filters the CSS classes of the container in the full width featured image area.
			 *
			 * @since 1.0.0
			 *
			 * @param string $classes Space-separated CSS classes.
			 * @param string $context Template, for example page or archive.
			 */
			$creationell_wp_theme_class_featured_full_width_img_container = apply_filters( 'creationell_wp_theme_class_featured_full_width_img_container', 'h-100 d-flex align-items-end pb-3', 'page-full-width-image' );
			?>
			<?php
			/**
			 * Filters the CSS classes of a layout container.
			 *
			 * @since 1.0.0
			 *
			 * @param string $classes Space-separated CSS classes.
			 * @param string $context Template or template part, for example header or page.
			 */
			$creationell_wp_theme_class_container_2 = apply_filters( 'creationell_wp_theme_class_container', 'container', 'page-full-width-image' );
			?>
			<div class="<?php echo esc_attr( $creationell_wp_theme_class_container_2 ); ?> <?php echo esc_attr( $creationell_wp_theme_class_featured_full_width_img_container ); ?>">
			<?php
			/**
			 * Filters the CSS classes of the title wrapper in the full width featured image area.
			 *
			 * @since 1.0.0
			 *
			 * @param string $classes Space-separated CSS classes.
			 * @param string $context Template, for example page or archive.
			 */
			$creationell_wp_theme_class_full_width_img_title_wrapper = apply_filters( 'creationell_wp_theme_class_full_width_img_title_wrapper', 'full-width-img-title-wrapper', 'page-full-width-image' );
			?>
			<div class="<?php echo esc_attr( $creationell_wp_theme_class_full_width_img_title_wrapper ); ?>">
				<?php
				/**
				 * Fires before the page title.
				 *
				 * @since 1.0.0
				 *
				 * @param string $context Template, for example page or archive.
				 */
				do_action( 'creationell_wp_theme_before_title', 'page-full-width-image' );
				?>
				<?php
				/**
				 * Filters the CSS classes of the page title.
				 *
				 * @since 1.0.0
				 *
				 * @param string $classes Space-separated CSS classes.
				 * @param string $context Template, for example page or archive.
				 */
				the_title( '<h1 class="entry-title ' . esc_attr( apply_filters( 'creationell_wp_theme_class_entry_title', '', 'page-full-width-image' ) ) . '">', '</h1>' );
				?>
				<?php
				/**
				 * Fires after the page title.
				 *
				 * @since 1.0.0
				 *
				 * @param string $context Template, for example page or archive.
				 */
				do_action( 'creationell_wp_theme_after_title', 'page-full-width-image' );
				?>
			</div>
			</div>
		</div>

		<?php
		/**
		 * Filters the CSS classes that set the vertical spacing of the content.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $context Template, for example page or archive.
		 */
		$creationell_wp_theme_class_content_spacer = apply_filters( 'creationell_wp_theme_class_content_spacer', 'pb-5', 'page-full-width-image' );
		?>
		<?php
		/**
		 * Filters the CSS classes of a layout container.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $context Template or template part, for example header or page.
		 */
		$creationell_wp_theme_class_container = apply_filters( 'creationell_wp_theme_class_container', 'container', 'page-full-width-image' );
		?>
		<div class="<?php echo esc_attr( $creationell_wp_theme_class_container ); ?> <?php echo esc_attr( $creationell_wp_theme_class_content_spacer ); ?>">
		  
			<?php
			/**
			 * Fires after the featured image of a page or post.
			 *
			 * @since 1.0.0
			 *
			 * @param string $context Template, for example page or archive.
			 */
			do_action( 'creationell_wp_theme_after_featured_image', 'page-full-width-image' );
			?>
		  
			<div class="row">
			<?php
			/**
			 * Filters the CSS classes of the main column.
			 *
			 * @since 1.0.0
			 *
			 * @param string $classes Space-separated CSS classes.
			 */
			$creationell_wp_theme_class_main_col = apply_filters( 'creationell_wp_theme_class_main_col', 'col' );
			?>
			<div class="<?php echo esc_attr( $creationell_wp_theme_class_main_col ); ?>">

				<div class="entry-content">
				<?php the_content(); ?>
				</div>
			  
				<?php
				/**
				 * Fires before the footer of a page or post.
				 *
				 * @since 1.0.0
				 *
				 * @param string $context Template, for example page or archive.
				 */
				do_action( 'creationell_wp_theme_before_entry_footer', 'page-full-width-image' );
				?>

				<div class="entry-footer">
				<?php comments_template(); ?>
				</div>

			</div>
			<?php get_sidebar(); ?>
			</div>
		</div>

		</main>

	</div>
	</div>

<?php
get_footer();
