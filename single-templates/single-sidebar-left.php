<?php
/**
 * Template Name: Sidebar left
 * Template Post Type: post
 * Template Version: 7.0.0
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();
?>

	<?php
	/**
	 * Filters the CSS classes that set the vertical spacing of the content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 * @param string $context Template, for example page or archive.
	 */
	$creationell_wp_theme_class_content_spacer = apply_filters( 'creationell_wp_theme_class_content_spacer', 'pt-3 pb-5', 'single-sidebar-left' );
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
	$creationell_wp_theme_class_container = apply_filters( 'creationell_wp_theme_class_container', 'container', 'single-sidebar-left' );
	?>
	<div id="content" class="site-content <?php echo esc_attr( $creationell_wp_theme_class_container ); ?> <?php echo esc_attr( $creationell_wp_theme_class_content_spacer ); ?>">
	<div id="primary" class="content-area">
	  
		<?php
		/**
		 * Fires right after the opening tag of the main content area.
		 *
		 * @since 1.0.0
		 *
		 * @param string $context Template, for example page or archive.
		 */
		do_action( 'creationell_wp_theme_after_primary_open', 'single-sidebar-left' );
		?>

		<?php creationell_wp_theme_breadcrumb(); ?>

		<div class="row">
		<?php get_sidebar(); ?>
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
		<div class="<?php echo esc_attr( $creationell_wp_theme_class_main_col ); ?> order-first order-md-last">

			<main id="main" class="site-main">

			<div class="entry-header">
				<?php the_post(); ?>
				<?php creationell_wp_theme_category_badge(); ?>
				<?php
				/**
				 * Fires before the page title.
				 *
				 * @since 1.0.0
				 *
				 * @param string $context Template, for example page or archive.
				 */
				do_action( 'creationell_wp_theme_before_title', 'single-sidebar-left' );
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
				the_title( '<h1 class="entry-title ' . esc_attr( apply_filters( 'creationell_wp_theme_class_entry_title', '', 'single-sidebar-left' ) ) . '">', '</h1>' );
				?>
				<?php
				/**
				 * Fires after the page title.
				 *
				 * @since 1.0.0
				 *
				 * @param string $context Template, for example page or archive.
				 */
				do_action( 'creationell_wp_theme_after_title', 'single-sidebar-left' );
				?>
				<p class="entry-meta">
				<small class="text-body-secondary">
					<?php
					creationell_wp_theme_date();
					creationell_wp_theme_author();
					creationell_wp_theme_comment_count();
					?>
				</small>
				</p>
				<?php creationell_wp_theme_post_thumbnail(); ?>
			</div>
			
			<?php
			/**
			 * Fires after the featured image of a page or post.
			 *
			 * @since 1.0.0
			 *
			 * @param string $context Template, for example page or archive.
			 */
			do_action( 'creationell_wp_theme_after_featured_image', 'single-sidebar-left' );
			?>

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
			do_action( 'creationell_wp_theme_before_entry_footer', 'single-sidebar-left' );
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
				do_action( 'creationell_wp_theme_before_single_pagination', 'single-sidebar-left' );
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

			</main>

		</div>
		</div>

	</div>
	</div>

<?php
get_footer();
