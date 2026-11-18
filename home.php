<?php
/**
 * The template for displaying the blog page
 *
 * Renders index.php instead while the module blog is not active and while a
 * child theme has its own index.php but no home.php. The existing hooks and
 * filters keep the context index; the parts of the blog page get the context
 * home.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_fallback = \Creationell\WpTheme\Blog\Blog_Templates::fallback( 'home' );
if ( '' !== $creationell_wp_theme_fallback ) {
	require $creationell_wp_theme_fallback;
	return;
}

// Set global template context for custom layout hooks.
$GLOBALS['creationell_wp_theme_template_context'] = 'index';

// Title of the blog page, the site name with posts on the front page; the excerpt of the blog page as lead, the tagline with posts on the front page, like index.php.
$creationell_wp_theme_blog_title = creationell_wp_theme_blog_title();
$creationell_wp_theme_blog_page  = 'page' === get_option( 'show_on_front' ) ? (int) get_queried_object_id() : 0;
if ( 'posts' === get_option( 'show_on_front' ) ) {
	$creationell_wp_theme_excerpt = get_bloginfo( 'description' );
} else {
	$creationell_wp_theme_excerpt = $creationell_wp_theme_blog_page > 0 ? get_post_field( 'post_excerpt', $creationell_wp_theme_blog_page ) : '';
}

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
$creationell_wp_theme_class_content_spacer = apply_filters( 'creationell_wp_theme_class_content_spacer', 'pt-4 pb-5', 'index' );
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
$creationell_wp_theme_class_container = apply_filters( 'creationell_wp_theme_class_container', 'container', 'index' );
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
	do_action( 'creationell_wp_theme_after_primary_open', 'index' );
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
		$creationell_wp_theme_class_main_col = apply_filters( 'creationell_wp_theme_class_main_col', 'col', 'index' );
		?>
		<div class="<?php echo esc_attr( $creationell_wp_theme_class_main_col ); ?>">

		<main id="main" class="site-main">

			<?php
			/**
			 * Fires before the page title.
			 *
			 * @since 1.0.0
			 *
			 * @param string $context Template, for example page or archive.
			 */
			do_action( 'creationell_wp_theme_before_title', 'index' );

			get_template_part(
				'template-parts/blog/home-header',
				null,
				array(
					'title'   => '' !== $creationell_wp_theme_blog_title ? $creationell_wp_theme_blog_title : get_bloginfo( 'name' ),
					'excerpt' => is_string( $creationell_wp_theme_excerpt ) ? $creationell_wp_theme_excerpt : '',
					'context' => 'home',
				)
			);

			/**
			 * Fires after the page title.
			 *
			 * @since 1.0.0
			 *
			 * @param string $context Template, for example page or archive.
			 */
			do_action( 'creationell_wp_theme_after_title', 'index' );
			?>

			<?php
			/**
			 * Fires before the post loop of an archive template.
			 *
			 * @since 1.0.0
			 *
			 * @param string $context Template, for example page or archive.
			 */
			do_action( 'creationell_wp_theme_before_loop', 'index' );
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
			$creationell_wp_theme_layout = apply_filters( 'creationell_wp_theme_loop_layout', 'horizontal', 'index' ); // Layouts: horizontal, grid, overlay or custom.

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
				'index'
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
				'index'
			);
			?>

			<?php if ( have_posts() ) : ?>
				<?php
				// The row opens on loop_start and closes on loop_end, inside the filter bar of a plugin; the custom layout has none.
				if ( 'custom' === $creationell_wp_theme_layout ) {
					\Creationell\WpTheme\Modules\Blog\Blog_Home::begin( '' );
				} else {
					\Creationell\WpTheme\Modules\Blog\Blog_Home::begin( 'grid' === $creationell_wp_theme_layout ? (string) $creationell_wp_theme_grid_classes : (string) $creationell_wp_theme_horizontal_classes );
				}

				while ( have_posts() ) :
					the_post();
					?>

					<?php if ( 'custom' === $creationell_wp_theme_layout ) : ?>
						<?php get_template_part( 'template-parts/loop/custom' ); ?>
					<?php else : ?>
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
					<?php endif; ?>

					<?php
				endwhile;

				\Creationell\WpTheme\Modules\Blog\Blog_Home::end();
				?>

			<?php else : ?>
				<!-- No posts found -->
				<?php
				// The note "There are no posts yet." only on a blog without posts; else a filter matched nothing.
				if ( ! creationell_wp_theme_has_blog_posts() ) {
					get_template_part( 'template-parts/blog/home-none', null, array( 'context' => 'home' ) );
				} else {
					/**
					 * Filters whether the blog page prints the note "No posts match these filters." when its main query is empty although the blog has posts.
					 *
					 * The module post-filter answers false where the plugin JPKCom Post
					 * Filter prints its own zero results zone. Only true counts; any
					 * other answer prints nothing.
					 *
					 * Example:
					 *
					 *     add_filter( 'creationell_wp_theme_blog_home_empty', '__return_false' );
					 *
					 * @since 1.0.0
					 *
					 * @param bool $show Whether the note shows; true by default.
					 */
					$creationell_wp_theme_home_empty = apply_filters( 'creationell_wp_theme_blog_home_empty', true );
					if ( true === $creationell_wp_theme_home_empty ) {
						get_template_part( 'template-parts/blog/home-no-match', null, array( 'context' => 'home' ) );
					}
				}
				?>
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
			do_action( 'creationell_wp_theme_after_loop', 'index' );
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
			do_action( 'creationell_wp_theme_before_loop_pagination', 'index' );
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
