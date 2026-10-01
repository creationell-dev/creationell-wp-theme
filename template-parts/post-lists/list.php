<?php
/**
 * Template part: the post list of the block creationell-theme/post-list.
 *
 * Prints the root element of the block and one list item per post of
 * $creationell_wp_theme_args['query'] with the card part of the layout: grid (1 column, 2 from md,
 * the chosen number from lg), list or hero (one card per row). A paginated list
 * ends with pagination.php. Arguments: query (WP_Query), display (options of
 * Display_Options), layout (grid, list, hero), columns (1 to 4), pagination
 * (instance number, 0 without pagination), root (class and id of the root
 * element). The caller resets the post data.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Posts\Display_Options;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_args  = isset( $args ) && is_array( $args ) ? $args : array();
$creationell_wp_theme_query = $creationell_wp_theme_args['query'] ?? null;
if ( ! $creationell_wp_theme_query instanceof WP_Query ) {
	return;
}
$creationell_wp_theme_display  = Display_Options::normalize( $creationell_wp_theme_args['display'] ?? null );
$creationell_wp_theme_layout   = in_array( $creationell_wp_theme_args['layout'] ?? null, array( 'grid', 'list', 'hero' ), true ) ? (string) $creationell_wp_theme_args['layout'] : 'grid';
$creationell_wp_theme_columns  = is_int( $creationell_wp_theme_args['columns'] ?? null ) && $creationell_wp_theme_args['columns'] >= 1 && $creationell_wp_theme_args['columns'] <= 4 ? $creationell_wp_theme_args['columns'] : 3;
$creationell_wp_theme_instance = is_int( $creationell_wp_theme_args['pagination'] ?? null ) ? $creationell_wp_theme_args['pagination'] : 0;
$creationell_wp_theme_root     = is_array( $creationell_wp_theme_args['root'] ?? null ) ? $creationell_wp_theme_args['root'] : array();
$creationell_wp_theme_class    = is_string( $creationell_wp_theme_root['class'] ?? null ) ? $creationell_wp_theme_root['class'] : 'creationell-theme-post-list';
$creationell_wp_theme_id       = is_string( $creationell_wp_theme_root['id'] ?? null ) ? $creationell_wp_theme_root['id'] : '';
$creationell_wp_theme_rows     = 'grid' === $creationell_wp_theme_layout
	? sprintf( 'row row-cols-1 row-cols-md-%1$d row-cols-lg-%2$d g-4 list-unstyled mb-0', min( 2, $creationell_wp_theme_columns ), $creationell_wp_theme_columns )
	: 'row row-cols-1 g-4 list-unstyled mb-0';
?>
<div class="<?php echo esc_attr( $creationell_wp_theme_class ); ?>"<?php echo '' === $creationell_wp_theme_id ? '' : ' id="' . esc_attr( $creationell_wp_theme_id ) . '"'; ?>>
	<ul class="<?php echo esc_attr( $creationell_wp_theme_rows ); ?>">
		<?php
		while ( $creationell_wp_theme_query->have_posts() ) :
			$creationell_wp_theme_query->the_post();
			?>
			<li class="col">
				<?php get_template_part( 'template-parts/post-lists/card-' . $creationell_wp_theme_layout, null, array( 'display' => $creationell_wp_theme_display ) ); ?>
			</li>
		<?php endwhile; ?>
	</ul>
	<?php
	if ( $creationell_wp_theme_instance > 0 ) {
		get_template_part(
			'template-parts/post-lists/pagination',
			null,
			array(
				'query'    => $creationell_wp_theme_query,
				'instance' => $creationell_wp_theme_instance,
			)
		);
	}
	?>
</div>
