<?php
/**
 * The template for displaying search results pages
 * Template Version: 7.0.0
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#search-result
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Set global template context for custom layout hooks.
$GLOBALS['creationell_wp_theme_template_context'] = 'search';

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
$creationell_wp_theme_class_content_spacer = apply_filters( 'creationell_wp_theme_class_content_spacer', 'pt-4 pb-5', 'search' );
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
$creationell_wp_theme_class_container = apply_filters( 'creationell_wp_theme_class_container', 'container', 'search' );
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
	do_action( 'creationell_wp_theme_after_primary_open', 'search' );
	?>

	<div class="row">
		<?php
		/**
		 * Filters the CSS classes of the main column.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $context Template, for example page or archive.
		 */
		$creationell_wp_theme_class_main_col = apply_filters( 'creationell_wp_theme_class_main_col', 'col', 'search' );
		?>
		<div class="<?php echo esc_attr( $creationell_wp_theme_class_main_col ); ?>">

		<main id="main" class="site-main">

			<?php if ( have_posts() ) : ?>

			<div class="entry-header">
				<?php
				/**
				 * Fires before the page title.
				 *
				 * @since 1.0.0
				 *
				 * @param string $context Template, for example page or archive.
				 */
				do_action( 'creationell_wp_theme_before_title', 'search' );
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
				$creationell_wp_theme_class_entry_title = apply_filters( 'creationell_wp_theme_class_entry_title', '', 'search' );
				?>
				<h1 class="entry-title <?php echo esc_attr( $creationell_wp_theme_class_entry_title ); ?>">
				<?php
				/* translators: %s: search query. */
				printf( esc_html__( 'Search Results for: %s', 'creationell-wp-theme' ), '<span class="text-body-secondary">' . esc_html( get_search_query() ) . '</span>' );
				?>
				</h1>
				<?php
				/**
				 * Fires after the page title.
				 *
				 * @since 1.0.0
				 *
				 * @param string $context Template, for example page or archive.
				 */
				do_action( 'creationell_wp_theme_after_title', 'search' );
				?>
			</div>
		  
				<?php
				/**
				 * Fires before the post loop of an archive template.
				 *
				 * @since 1.0.0
				 *
				 * @param string $context Template, for example page or archive.
				 */
				do_action( 'creationell_wp_theme_before_loop', 'search' );
				?>

			<!-- Loop START -->
				<?php
				// Set layout via filter (can be overridden by plugins).
				/**
				 * Filters the layout of the post loop.
				 *
				 * @since 1.0.0
				 *
				 * @param string $layout  Layout, horizontal or grid.
				 * @param string $context Template, for example page or archive.
				 */
				$creationell_wp_theme_layout = apply_filters( 'creationell_wp_theme_loop_layout', 'horizontal', 'search' ); // Layouts: horizontal, grid, overlay or custom.

				// Default grid classes.
				/**
				 * Filters the CSS classes of the row of the grid loop layout.
				 *
				 * @since 1.0.0
				 *
				 * @param string $classes Space-separated CSS classes.
				 * @param string $context Template, for example page or archive.
				 */
				$creationell_wp_theme_grid_classes = apply_filters(
					'creationell_wp_theme_class_loop_grid_col',
					'row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4 mb-4 creationell-theme-loop-grid',
					'search'
				);

				// Default horizontal/overlay classes (both use same grid structure).
				/**
				 * Filters the CSS classes of the row of the horizontal loop layout.
				 *
				 * @since 1.0.0
				 *
				 * @param string $classes Space-separated CSS classes.
				 * @param string $context Template, for example page or archive.
				 */
				$creationell_wp_theme_horizontal_classes = apply_filters(
					'creationell_wp_theme_class_loop_horizontal_col',
					'row row-cols-1 g-4 mb-4 creationell-theme-loop-grid',
					'search'
				);
				?>

				<?php if ( 'custom' === $creationell_wp_theme_layout ) : ?>
				<!-- Custom layout - NO GRID WRAPPER, just loop through posts -->
					<?php
					while ( have_posts() ) :
						the_post();
						?>
						<?php get_template_part( 'template-parts/loop/custom' ); ?>
				<?php endwhile; ?>

			<?php else : ?>
				<!-- Grid or horizontal layout with wrapper -->
				<div class="<?php echo 'grid' === $creationell_wp_theme_layout ? esc_attr( $creationell_wp_theme_grid_classes ) : esc_attr( $creationell_wp_theme_horizontal_classes ); ?>">

				<?php
				while ( have_posts() ) :
					the_post();
					?>

					<!-- Column wrapper for ALL layouts -->
					<div class="col">

					<?php if ( 'grid' === $creationell_wp_theme_layout ) : ?>
						<!-- Grid card -->
						<?php get_template_part( 'template-parts/loop/cards-grid' ); ?>
					  
					<?php elseif ( 'overlay' === $creationell_wp_theme_layout ) : ?>
						<!-- Overlay card -->
						<?php get_template_part( 'template-parts/loop/cards-overlay' ); ?>
					  
					<?php else : ?>
						<!-- Horizontal card -->
						<?php get_template_part( 'template-parts/loop/cards-horizontal' ); ?>
					<?php endif; ?>

					</div><!-- .col -->

				<?php endwhile; ?>

				</div><!-- .row (loop row) -->
			<?php endif; ?>
			<!-- Loop END -->

				<?php
				/**
				 * Fires after the post loop of an archive template.
				 *
				 * @since 1.0.0
				 *
				 * @param string $context Template, for example page or archive.
				 */
				do_action( 'creationell_wp_theme_after_loop', 'search' );
				?>

			<div class="entry-footer">
				<?php
				/**
				 * Fires before the pagination of the post loop.
				 *
				 * @since 1.0.0
				 *
				 * @param string $context Template, for example page or archive.
				 */
				do_action( 'creationell_wp_theme_before_loop_pagination', 'search' );
				?>
				<?php
				/**
				 * Fires where the pagination of the post loop is rendered.
				 *
				 * @since 1.0.0
				 */
				do_action( 'creationell_wp_theme_loop_pagination' );
				?>
			</div>

			<?php else : ?>

				<?php get_template_part( 'template-parts/loop/content', 'none' ); ?>

			<?php endif; ?>
		  
		</main>

		</div><!-- .col -->
		<?php get_sidebar(); ?>
	</div><!-- .row -->

	</div><!-- #primary -->
</div><!-- #content -->

<?php
get_footer();
