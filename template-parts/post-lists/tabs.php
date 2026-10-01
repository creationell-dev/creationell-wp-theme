<?php
/**
 * Template part: the posts of the block creationell-theme/post-accordion as Bootstrap tabs.
 *
 * A tab list with one button per post of $creationell_wp_theme_args['query'] (role tab,
 * aria-controls, aria-selected; only the selected tab is in the tab order, the
 * arrow keys of Bootstrap move between tabs), then one panel per post (role
 * tabpanel, aria-labelledby, focusable) with panel.php. The first tab is
 * selected. Arguments: query (WP_Query), display (options of Display_Options),
 * content_mode (excerpt or content), root (class and id of the root element).
 * IDs come from wp_unique_id(). The caller resets the post data.
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
$creationell_wp_theme_display = Display_Options::normalize( $creationell_wp_theme_args['display'] ?? null );
$creationell_wp_theme_root    = is_array( $creationell_wp_theme_args['root'] ?? null ) ? $creationell_wp_theme_args['root'] : array();
$creationell_wp_theme_class   = is_string( $creationell_wp_theme_root['class'] ?? null ) ? $creationell_wp_theme_root['class'] : 'creationell-theme-post-accordion';
$creationell_wp_theme_anchor  = is_string( $creationell_wp_theme_root['id'] ?? null ) ? $creationell_wp_theme_root['id'] : '';
$creationell_wp_theme_id      = wp_unique_id( 'creationell-theme-tabs-' );
$creationell_wp_theme_index   = 0;
?>
<div class="<?php echo esc_attr( $creationell_wp_theme_class ); ?>"<?php echo '' === $creationell_wp_theme_anchor ? '' : ' id="' . esc_attr( $creationell_wp_theme_anchor ) . '"'; ?>>
	<ul class="nav nav-tabs" role="tablist">
		<?php
		while ( $creationell_wp_theme_query->have_posts() ) :
			$creationell_wp_theme_query->the_post();
			$creationell_wp_theme_selected = 0 === $creationell_wp_theme_index;
			?>
			<li class="nav-item" role="presentation"><button class="<?php echo esc_attr( $creationell_wp_theme_selected ? 'nav-link active' : 'nav-link' ); ?>" id="<?php echo esc_attr( $creationell_wp_theme_id . '-tab-' . $creationell_wp_theme_index ); ?>" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#<?php echo esc_attr( $creationell_wp_theme_id . '-pane-' . $creationell_wp_theme_index ); ?>" aria-controls="<?php echo esc_attr( $creationell_wp_theme_id . '-pane-' . $creationell_wp_theme_index ); ?>" aria-selected="<?php echo esc_attr( $creationell_wp_theme_selected ? 'true' : 'false' ); ?>"<?php echo $creationell_wp_theme_selected ? '' : ' tabindex="-1"'; ?>><?php echo esc_html( get_the_title() ); ?></button></li>
			<?php
			++$creationell_wp_theme_index;
		endwhile;
		?>
	</ul>
	<div class="tab-content pt-3">
		<?php
		$creationell_wp_theme_query->rewind_posts();
		$creationell_wp_theme_index = 0;
		while ( $creationell_wp_theme_query->have_posts() ) :
			$creationell_wp_theme_query->the_post();
			?>
			<div class="<?php echo esc_attr( 0 === $creationell_wp_theme_index ? 'tab-pane fade show active' : 'tab-pane fade' ); ?>" id="<?php echo esc_attr( $creationell_wp_theme_id . '-pane-' . $creationell_wp_theme_index ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $creationell_wp_theme_id . '-tab-' . $creationell_wp_theme_index ); ?>" tabindex="0">
				<?php
				get_template_part(
					'template-parts/post-lists/panel',
					null,
					array(
						'display'      => $creationell_wp_theme_display,
						'content_mode' => $creationell_wp_theme_args['content_mode'] ?? 'excerpt',
					)
				);
				?>
			</div>
			<?php
			++$creationell_wp_theme_index;
		endwhile;
		?>
	</div>
</div>
