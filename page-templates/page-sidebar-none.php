<?php
/**
 * Template Name: No Sidebar
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

	<?php
	/**
	 * Filters the CSS classes that set the vertical spacing of the content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 * @param string $context Template, for example page or archive.
	 */
	$creationell_wp_theme_class_content_spacer = apply_filters( 'creationell_wp_theme_class_content_spacer', 'pt-4 pb-5', 'page-sidebar-none' );
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
	$creationell_wp_theme_class_container = apply_filters( 'creationell_wp_theme_class_container', 'container', 'page-sidebar-none' );
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
		do_action( 'creationell_wp_theme_after_primary_open', 'page-sidebar-none' );
		?>

		<main id="main" class="site-main">

		<div class="entry-header">
			<?php the_post(); ?>
			<?php
			/**
			 * Fires before the page title.
			 *
			 * @since 1.0.0
			 *
			 * @param string $context Template, for example page or archive.
			 */
			do_action( 'creationell_wp_theme_before_title', 'page-sidebar-none' );
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
			the_title( '<h1 class="entry-title ' . esc_attr( apply_filters( 'creationell_wp_theme_class_entry_title', '', 'page-sidebar-none' ) ) . '">', '</h1>' );
			?>
			<?php
			/**
			 * Fires after the page title.
			 *
			 * @since 1.0.0
			 *
			 * @param string $context Template, for example page or archive.
			 */
			do_action( 'creationell_wp_theme_after_title', 'page-sidebar-none' );
			?>
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
		do_action( 'creationell_wp_theme_after_featured_image', 'page-sidebar-none' );
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
		do_action( 'creationell_wp_theme_before_entry_footer', 'page-sidebar-none' );
		?>

		<div class="entry-footer">
			<?php comments_template(); ?>
		</div>

		</main>

	</div>
	</div>

<?php
get_footer();
