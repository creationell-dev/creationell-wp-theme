<?php
/**
 * Template Name: Full width image
 * Template Post Type: post
 * Template Version: 7.0.0
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
		do_action( 'creationell_wp_theme_after_primary_open', 'single-full-width-image' );
		?>

		<main id="main" class="site-main">

		<?php the_post(); ?>
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
		$creationell_wp_theme_class_featured_full_width_img = apply_filters( 'creationell_wp_theme_class_featured_full_width_img', 'featured-full-width-img height-75 bg-dark text-light mb-4', 'single-full-width-image' );
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
			$creationell_wp_theme_class_featured_full_width_img_container = apply_filters( 'creationell_wp_theme_class_featured_full_width_img_container', 'h-100 d-flex align-items-end pb-3', 'single-full-width-image' );
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
			$creationell_wp_theme_class_container_2 = apply_filters( 'creationell_wp_theme_class_container', 'container', 'single-full-width-image' );
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
			$creationell_wp_theme_class_full_width_img_title_wrapper = apply_filters( 'creationell_wp_theme_class_full_width_img_title_wrapper', 'full-width-img-title-wrapper', 'single-full-width-image' );
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
				do_action( 'creationell_wp_theme_before_title', 'single-full-width-image' );
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
				the_title( '<h1 class="entry-title ' . esc_attr( apply_filters( 'creationell_wp_theme_class_entry_title', '', 'single-full-width-image' ) ) . '">', '</h1>' );
				?>
				<?php
				/**
				 * Fires after the page title.
				 *
				 * @since 1.0.0
				 *
				 * @param string $context Template, for example page or archive.
				 */
				do_action( 'creationell_wp_theme_after_title', 'single-full-width-image' );
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
		$creationell_wp_theme_class_content_spacer = apply_filters( 'creationell_wp_theme_class_content_spacer', 'pt-3 pb-5', 'single-full-width-image' );
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
		$creationell_wp_theme_class_container = apply_filters( 'creationell_wp_theme_class_container', 'container', 'single-full-width-image' );
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
			do_action( 'creationell_wp_theme_after_featured_image', 'single-full-width-image' );
			?>
		  
			<?php creationell_wp_theme_breadcrumb(); ?>

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
				<?php creationell_wp_theme_category_badge(); ?>
				<p class="entry-meta">
					<small class="text-body-secondary">
					<?php
					creationell_wp_theme_date();
					creationell_wp_theme_author();
					creationell_wp_theme_comment_count();
					?>
					</small>
				</p>
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
				do_action( 'creationell_wp_theme_before_entry_footer', 'single-full-width-image' );
				?>

				<div class="entry-footer clear-both">
				<div class="mb-4">
					<?php creationell_wp_theme_tags(); ?>
				</div>

				<?php
					// Related posts using bs Swiper plugin
					// Deprecated, new action creationell_wp_theme_before_pagination will be used for related posts.
				if ( function_exists( 'creationell_wp_theme_related_posts' ) ) {
					creationell_wp_theme_related_posts();
				}
				?>
				
				<?php
				/**
				 * Fires before the links to the previous and next post.
				 *
				 * @since 1.0.0
				 *
				 * @param string $context Template, for example page or archive.
				 */
				do_action( 'creationell_wp_theme_before_single_pagination', 'single-full-width-image' );
				?>
				
				<nav aria-label="<?php /* translators: Name of the navigation to the previous and the next post; not the same as "Posts navigation" (pages of a post archive). */ esc_attr_e( 'Post navigation', 'creationell-wp-theme' ); ?>">
					<ul class="pagination justify-content-center">
					<li class="page-item">
						<?php previous_post_link( '%link' ); ?>
					</li>
					<li class="page-item">
						<?php next_post_link( '%link' ); ?>
					</li>
					</ul>
				</nav>
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
