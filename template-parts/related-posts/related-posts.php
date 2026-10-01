<?php
/**
 * Template part: the related posts below a single post and of the block creationell-theme/related-posts.
 *
 * Prints a section named by its heading. The posts of
 * $creationell_wp_theme_args['query'] follow as a grid of the grid cards of
 * template-parts/post-lists/ (1 column, 2 from md, the given number from lg),
 * or, for the layout slider, as the post slider of the module post-slider in
 * the context related-posts; without that module the grid shows instead. In
 * the block editor the slider prints its static grid (FS-11).
 *
 * Arguments: query (WP_Query), display (card options of Display_Options),
 * layout (grid, slider), columns (1 to 3), heading (text), heading_level (2
 * to 6), heading_id (ID of the heading), editor (bool), root (class and id of
 * the root element), slider (attributes of the post slider). The caller resets
 * the post data.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Modules\PostSlider\Post_Slider_Module;
use Creationell\WpTheme\Posts\Display_Options;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_args  = isset( $args ) && is_array( $args ) ? $args : array();
$creationell_wp_theme_query = $creationell_wp_theme_args['query'] ?? null;
if ( ! $creationell_wp_theme_query instanceof WP_Query ) {
	return;
}
$creationell_wp_theme_display    = Display_Options::normalize( $creationell_wp_theme_args['display'] ?? null );
$creationell_wp_theme_slider     = 'slider' === ( $creationell_wp_theme_args['layout'] ?? null ) && class_exists( Post_Slider_Module::class );
$creationell_wp_theme_columns    = is_int( $creationell_wp_theme_args['columns'] ?? null ) && $creationell_wp_theme_args['columns'] >= 1 && $creationell_wp_theme_args['columns'] <= 3 ? $creationell_wp_theme_args['columns'] : 3;
$creationell_wp_theme_level      = is_int( $creationell_wp_theme_args['heading_level'] ?? null ) && $creationell_wp_theme_args['heading_level'] >= 2 && $creationell_wp_theme_args['heading_level'] <= 6 ? $creationell_wp_theme_args['heading_level'] : 2;
$creationell_wp_theme_tag        = 'h' . $creationell_wp_theme_level;
$creationell_wp_theme_heading    = is_string( $creationell_wp_theme_args['heading'] ?? null ) ? $creationell_wp_theme_args['heading'] : '';
$creationell_wp_theme_heading_id = is_string( $creationell_wp_theme_args['heading_id'] ?? null ) && '' !== $creationell_wp_theme_args['heading_id'] ? $creationell_wp_theme_args['heading_id'] : wp_unique_id( 'creationell-theme-related-posts-heading-' );
$creationell_wp_theme_root       = is_array( $creationell_wp_theme_args['root'] ?? null ) ? $creationell_wp_theme_args['root'] : array();
$creationell_wp_theme_class      = is_string( $creationell_wp_theme_root['class'] ?? null ) ? $creationell_wp_theme_root['class'] : 'creationell-theme-related-posts';
$creationell_wp_theme_id         = is_string( $creationell_wp_theme_root['id'] ?? null ) ? $creationell_wp_theme_root['id'] : '';
?>
<section class="<?php echo esc_attr( $creationell_wp_theme_class ); ?>"<?php echo '' === $creationell_wp_theme_id ? '' : ' id="' . esc_attr( $creationell_wp_theme_id ) . '"'; ?> aria-labelledby="<?php echo esc_attr( $creationell_wp_theme_heading_id ); ?>">
	<<?php echo esc_attr( $creationell_wp_theme_tag ); ?> id="<?php echo esc_attr( $creationell_wp_theme_heading_id ); ?>" class="creationell-theme-related-posts__heading mb-4"><?php echo esc_html( $creationell_wp_theme_heading ); ?></<?php echo esc_attr( $creationell_wp_theme_tag ); ?>>
	<?php
	if ( $creationell_wp_theme_slider ) :
		Post_Slider_Module::render_query(
			$creationell_wp_theme_query,
			is_array( $creationell_wp_theme_args['slider'] ?? null ) ? $creationell_wp_theme_args['slider'] : array(),
			true === ( $creationell_wp_theme_args['editor'] ?? false ),
			'related-posts'
		);
	else :
		?>
		<ul class="<?php echo esc_attr( sprintf( 'row row-cols-1 row-cols-md-%1$d row-cols-lg-%2$d g-4 list-unstyled mb-0', min( 2, $creationell_wp_theme_columns ), $creationell_wp_theme_columns ) ); ?>">
			<?php
			while ( $creationell_wp_theme_query->have_posts() ) :
				$creationell_wp_theme_query->the_post();
				?>
				<li class="col">
					<?php get_template_part( 'template-parts/post-lists/card-grid', null, array( 'display' => $creationell_wp_theme_display ) ); ?>
				</li>
			<?php endwhile; ?>
		</ul>
	<?php endif; ?>
</section>
