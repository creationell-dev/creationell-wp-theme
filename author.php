<?php
/**
 * The template for displaying the author pages
 *
 * Renders archive.php instead while the module blog is not active, while the
 * setting blog_author_pages is off and while a child theme has its own
 * archive.php but no author.php. Otherwise the page of a public author: the
 * header part template-parts/blog/author-header.php with the profile, then the
 * posts of the author. The scaffold, the columns, the sidebar and every hook
 * and filter of archive.php keep the context archive.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_fallback = \Creationell\WpTheme\Blog\Blog_Templates::fallback( 'author' );
if ( '' !== $creationell_wp_theme_fallback ) {
	require $creationell_wp_theme_fallback;
	return;
}

// Author_Pages::guard() lets only public authors through and answers any other author address with the 404 page; without a user as queried object the archive template renders.
$creationell_wp_theme_author = get_queried_object();
if ( ! $creationell_wp_theme_author instanceof WP_User ) {
	$creationell_wp_theme_fallback = locate_template( 'archive.php' );
	if ( '' !== $creationell_wp_theme_fallback ) {
		require $creationell_wp_theme_fallback;
	}
	return;
}

// Set global template context for custom layout hooks.
$GLOBALS['creationell_wp_theme_template_context'] = 'archive';

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
$creationell_wp_theme_class_content_spacer = apply_filters( 'creationell_wp_theme_class_content_spacer', 'pt-4 pb-5', 'archive' );
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
$creationell_wp_theme_class_container = apply_filters( 'creationell_wp_theme_class_container', 'container', 'archive' );
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
	do_action( 'creationell_wp_theme_after_primary_open', 'archive' );
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
		$creationell_wp_theme_class_main_col = apply_filters( 'creationell_wp_theme_class_main_col', 'col', 'archive' );
		?>
		<div class="<?php echo esc_attr( $creationell_wp_theme_class_main_col ); ?>">

		<main id="main" class="site-main">

			<div class="entry-header">
			<?php
			/**
			 * Fires before the page title.
			 *
			 * @since 1.0.0
			 *
			 * @param string $context Template, for example page or archive.
			 */
			do_action( 'creationell_wp_theme_before_title', 'archive' );

			get_template_part(
				'template-parts/blog/author-header',
				null,
				\Creationell\WpTheme\Modules\Blog\Author_Pages::header_args( $creationell_wp_theme_author )
			);

			/**
			 * Fires after the page title.
			 *
			 * @since 1.0.0
			 *
			 * @param string $context Template, for example page or archive.
			 */
			do_action( 'creationell_wp_theme_after_title', 'archive' );
			?>
			</div>

			<?php
			/* translators: %s: display name of the author */
			$creationell_wp_theme_posts_by = sprintf( __( 'Posts by %s', 'creationell-wp-theme' ), $creationell_wp_theme_author->display_name );
			?>
			<h2 class="h4"><?php echo esc_html( $creationell_wp_theme_posts_by ); ?></h2>

			<?php
			/**
			 * Fires before the post loop of an archive template.
			 *
			 * @since 1.0.0
			 *
			 * @param string $context Template, for example page or archive.
			 */
			do_action( 'creationell_wp_theme_before_loop', 'archive' );
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
			$creationell_wp_theme_layout = apply_filters( 'creationell_wp_theme_loop_layout', 'horizontal', 'archive' ); // Layouts: horizontal, grid, overlay or custom.

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
				'archive'
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
				'archive'
			);
			?>

			<?php if ( have_posts() ) : ?>

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

			<?php else : ?>
			<!-- No posts found -->
				<?php get_template_part( 'template-parts/loop/loop-none' ); ?>
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
			do_action( 'creationell_wp_theme_after_loop', 'archive' );
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
			do_action( 'creationell_wp_theme_before_loop_pagination', 'archive' );
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

		</main>

		</div><!-- .col -->
		<?php get_sidebar(); ?>
	</div><!-- .row -->

	</div><!-- #primary -->
</div><!-- #content -->

<?php
get_footer();
