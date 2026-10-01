<?php
/**
 * Template part: the posts of the block creationell-theme/post-accordion as a Bootstrap accordion.
 *
 * One item per post of $creationell_wp_theme_args['query']: the title is a button in a heading of
 * the chosen level that opens the panel through data-bs-target (no href="#",
 * which other accordion scripts would catch); the panel holds panel.php.
 * Arguments: query (WP_Query), display (options of Display_Options),
 * open_first (the first item starts open), always_open (items do not close each
 * other: no data-bs-parent), flush (accordion-flush), content_mode (excerpt or
 * content), root (class and id of the root element). IDs come from
 * wp_unique_id(), so several accordions on a page stay apart. The caller resets
 * the post data.
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
$creationell_wp_theme_display     = Display_Options::normalize( $creationell_wp_theme_args['display'] ?? null );
$creationell_wp_theme_open_first  = true === ( $creationell_wp_theme_args['open_first'] ?? true );
$creationell_wp_theme_always_open = true === ( $creationell_wp_theme_args['always_open'] ?? false );
$creationell_wp_theme_root        = is_array( $creationell_wp_theme_args['root'] ?? null ) ? $creationell_wp_theme_args['root'] : array();
$creationell_wp_theme_class       = is_string( $creationell_wp_theme_root['class'] ?? null ) ? $creationell_wp_theme_root['class'] : 'creationell-theme-post-accordion';
$creationell_wp_theme_anchor      = is_string( $creationell_wp_theme_root['id'] ?? null ) ? $creationell_wp_theme_root['id'] : '';
$creationell_wp_theme_id          = wp_unique_id( 'creationell-theme-accordion-' );
$creationell_wp_theme_level       = 'h' . $creationell_wp_theme_display['heading_level'];
$creationell_wp_theme_index       = 0;
?>
<div class="<?php echo esc_attr( $creationell_wp_theme_class ); ?>"<?php echo '' === $creationell_wp_theme_anchor ? '' : ' id="' . esc_attr( $creationell_wp_theme_anchor ) . '"'; ?>>
	<div class="<?php echo esc_attr( true === ( $creationell_wp_theme_args['flush'] ?? false ) ? 'accordion accordion-flush' : 'accordion' ); ?>" id="<?php echo esc_attr( $creationell_wp_theme_id ); ?>">
		<?php
		while ( $creationell_wp_theme_query->have_posts() ) :
			$creationell_wp_theme_query->the_post();
			$creationell_wp_theme_panel = $creationell_wp_theme_id . '-panel-' . $creationell_wp_theme_index;
			$creationell_wp_theme_open  = $creationell_wp_theme_open_first && 0 === $creationell_wp_theme_index;
			?>
			<div class="accordion-item">
				<<?php echo esc_attr( $creationell_wp_theme_level ); ?> class="accordion-header"><button class="<?php echo esc_attr( $creationell_wp_theme_open ? 'accordion-button' : 'accordion-button collapsed' ); ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo esc_attr( $creationell_wp_theme_panel ); ?>" aria-expanded="<?php echo esc_attr( $creationell_wp_theme_open ? 'true' : 'false' ); ?>" aria-controls="<?php echo esc_attr( $creationell_wp_theme_panel ); ?>"><?php echo esc_html( get_the_title() ); ?></button></<?php echo esc_attr( $creationell_wp_theme_level ); ?>>
				<div id="<?php echo esc_attr( $creationell_wp_theme_panel ); ?>" class="<?php echo esc_attr( $creationell_wp_theme_open ? 'accordion-collapse collapse show' : 'accordion-collapse collapse' ); ?>"<?php echo $creationell_wp_theme_always_open ? '' : ' data-bs-parent="#' . esc_attr( $creationell_wp_theme_id ) . '"'; ?>>
					<div class="accordion-body">
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
				</div>
			</div>
			<?php
			++$creationell_wp_theme_index;
		endwhile;
		?>
	</div>
</div>
