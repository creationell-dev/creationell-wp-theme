<?php
/**
 * The template for displaying 404 pages (not found)
 * Template Version: 6.3.1
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
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
	$creationell_wp_theme_class_content_spacer = apply_filters( 'creationell_wp_theme_class_content_spacer', 'pt-4 pb-5', '404' );
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
	$creationell_wp_theme_class_container = apply_filters( 'creationell_wp_theme_class_container', 'container', '404' );
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
		do_action( 'creationell_wp_theme_after_primary_open', '404' );
		?>

		<main id="main" class="site-main">

		<section class="error-404 not-found">
			<div class="page-404">

			<div class="entry-header">
				<?php
				/**
				 * Fires before the page title.
				 *
				 * @since 1.0.0
				 *
				 * @param string $context Template, for example page or archive.
				 */
				do_action( 'creationell_wp_theme_before_title', '404' );
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
				$creationell_wp_theme_class_entry_title = apply_filters( 'creationell_wp_theme_class_entry_title', '', '404' );
				?>
				<h1 class="entry-title <?php echo esc_attr( $creationell_wp_theme_class_entry_title ); ?>"><?php esc_html_e( 'Page not found', 'creationell-wp-theme' ); ?></h1>
				<?php
				/**
				 * Fires after the page title.
				 *
				 * @since 1.0.0
				 *
				 * @param string $context Template, for example page or archive.
				 */
				do_action( 'creationell_wp_theme_after_title', '404' );
				?>
			</div>
			<!-- Remove this line and place some widgets -->
			<p class="alert alert-info mb-4"><?php esc_html_e( 'The page you are looking for does not exist or has moved.', 'creationell-wp-theme' ); ?></p>
			<!-- 404 Widget -->
			<?php if ( is_active_sidebar( '404-page' ) ) : ?>
				<div><?php dynamic_sidebar( '404-page' ); ?></div>
			<?php endif; ?>
			<a class="btn btn-outline-primary" href="<?php echo esc_url( home_url() ); ?>"><?php esc_html_e( 'Back Home &raquo;', 'creationell-wp-theme' ); ?></a>
			</div>
		</section><!-- .error-404 -->

		</main><!-- #main -->

	</div><!-- #primary -->
	</div><!-- #content -->

<?php
get_footer();
