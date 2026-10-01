<?php
/**
 * Template part: the post slider of the block creationell-theme/post-slider.
 *
 * Prints a named section (or, without name, a div) with the Swiper options as JSON in
 * data-creationell-slider; with autoplay a pause button with visible text comes
 * first (2.2.2). div.swiper carries the writing direction, each post of
 * $creationell_wp_theme_args['query'] becomes one slide with the card part of
 * template-parts/post-lists/ (card-grid for columns, card-hero for heroes).
 * Arrows are buttons with a label and an icon that points the other way in
 * right-to-left languages; the dots are filled by the slider script. The
 * script post-slider.js starts Swiper; without it the stylesheet shows the
 * slides as a grid and hides the controls. In the block editor the part
 * prints a static grid of the first posts instead (FS-11): no Swiper markup,
 * no JSON, no buttons.
 *
 * Arguments: query (WP_Query), display (options of Display_Options), options
 * (Swiper options of Slider_Config), layout (columns, heroes), columns (1 to
 * 4), editor (bool), root (class and id of the root element), label (name of
 * the section; empty for a div without name inside a named section). The
 * caller resets the post data.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Posts\Display_Options;
use Creationell\WpTheme\Posts\Slider_Config;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_args  = isset( $args ) && is_array( $args ) ? $args : array();
$creationell_wp_theme_query = $creationell_wp_theme_args['query'] ?? null;
if ( ! $creationell_wp_theme_query instanceof WP_Query ) {
	return;
}
$creationell_wp_theme_display = Display_Options::normalize( $creationell_wp_theme_args['display'] ?? null );
$creationell_wp_theme_options = is_array( $creationell_wp_theme_args['options'] ?? null ) ? $creationell_wp_theme_args['options'] : array();
$creationell_wp_theme_layout  = 'heroes' === ( $creationell_wp_theme_args['layout'] ?? null ) ? 'heroes' : 'columns';
$creationell_wp_theme_card    = 'template-parts/post-lists/' . ( 'heroes' === $creationell_wp_theme_layout ? 'card-hero' : 'card-grid' );
$creationell_wp_theme_columns = is_int( $creationell_wp_theme_args['columns'] ?? null ) && $creationell_wp_theme_args['columns'] >= 1 && $creationell_wp_theme_args['columns'] <= 4 ? $creationell_wp_theme_args['columns'] : 3;
$creationell_wp_theme_root    = is_array( $creationell_wp_theme_args['root'] ?? null ) ? $creationell_wp_theme_args['root'] : array();
$creationell_wp_theme_class   = is_string( $creationell_wp_theme_root['class'] ?? null ) ? $creationell_wp_theme_root['class'] : 'creationell-theme-post-slider';
$creationell_wp_theme_id      = is_string( $creationell_wp_theme_root['id'] ?? null ) ? $creationell_wp_theme_root['id'] : '';
$creationell_wp_theme_label   = is_string( $creationell_wp_theme_args['label'] ?? null ) ? $creationell_wp_theme_args['label'] : '';
$creationell_wp_theme_element = '' === $creationell_wp_theme_label ? 'div' : 'section';

if ( true === ( $creationell_wp_theme_args['editor'] ?? false ) ) :
	$creationell_wp_theme_shown = 0;
	?>
<<?php echo esc_attr( $creationell_wp_theme_element ); ?> class="<?php echo esc_attr( $creationell_wp_theme_class . ' creationell-theme-post-slider--canvas' ); ?>"<?php echo '' === $creationell_wp_theme_id ? '' : ' id="' . esc_attr( $creationell_wp_theme_id ) . '"'; ?><?php echo '' === $creationell_wp_theme_label ? '' : ' aria-label="' . esc_attr( $creationell_wp_theme_label ) . '"'; ?>>
	<div class="<?php echo esc_attr( sprintf( 'row row-cols-1 row-cols-md-%1$d row-cols-lg-%2$d g-4', min( 2, $creationell_wp_theme_columns ), $creationell_wp_theme_columns ) ); ?>">
		<?php
		while ( $creationell_wp_theme_shown < $creationell_wp_theme_columns && $creationell_wp_theme_query->have_posts() ) :
			$creationell_wp_theme_query->the_post();
			++$creationell_wp_theme_shown;
			?>
			<div class="col">
				<?php get_template_part( $creationell_wp_theme_card, null, array( 'display' => $creationell_wp_theme_display ) ); ?>
			</div>
		<?php endwhile; ?>
	</div>
</<?php echo esc_attr( $creationell_wp_theme_element ); ?>>
	<?php
	return;
endif;

$creationell_wp_theme_rtl      = is_rtl();
$creationell_wp_theme_a11y     = is_array( $creationell_wp_theme_options['a11y'] ?? null ) ? $creationell_wp_theme_options['a11y'] : array();
$creationell_wp_theme_labels   = is_array( $creationell_wp_theme_options['labels'] ?? null ) ? $creationell_wp_theme_options['labels'] : array();
$creationell_wp_theme_prev     = is_string( $creationell_wp_theme_a11y['prevSlideMessage'] ?? null ) ? $creationell_wp_theme_a11y['prevSlideMessage'] : __( 'Previous slide', 'creationell-wp-theme' );
$creationell_wp_theme_next     = is_string( $creationell_wp_theme_a11y['nextSlideMessage'] ?? null ) ? $creationell_wp_theme_a11y['nextSlideMessage'] : __( 'Next slide', 'creationell-wp-theme' );
$creationell_wp_theme_pause    = is_string( $creationell_wp_theme_labels['pause'] ?? null ) ? $creationell_wp_theme_labels['pause'] : __( 'Pause slideshow', 'creationell-wp-theme' );
$creationell_wp_theme_autoplay = false !== ( $creationell_wp_theme_options['autoplay'] ?? false );
$creationell_wp_theme_arrows   = true === ( $creationell_wp_theme_options['navigation'] ?? false );
$creationell_wp_theme_dots     = true === ( $creationell_wp_theme_options['pagination'] ?? false );
$creationell_wp_theme_button   = 'btn btn-sm btn-outline-secondary';
?>
<<?php echo esc_attr( $creationell_wp_theme_element ); ?> class="<?php echo esc_attr( $creationell_wp_theme_class ); ?>"<?php echo '' === $creationell_wp_theme_id ? '' : ' id="' . esc_attr( $creationell_wp_theme_id ) . '"'; ?><?php echo '' === $creationell_wp_theme_label ? '' : ' aria-label="' . esc_attr( $creationell_wp_theme_label ) . '"'; ?> data-creationell-slider="<?php echo esc_attr( (string) wp_json_encode( $creationell_wp_theme_options, Slider_Config::JSON_FLAGS ) ); ?>">
	<?php if ( $creationell_wp_theme_autoplay ) : ?>
		<button type="button" class="creationell-theme-post-slider__pause <?php echo esc_attr( $creationell_wp_theme_button ); ?> mb-3"><?php echo esc_html( $creationell_wp_theme_pause ); ?></button>
	<?php endif; ?>
	<div class="swiper" dir="<?php echo esc_attr( $creationell_wp_theme_rtl ? 'rtl' : 'ltr' ); ?>">
		<div class="swiper-wrapper">
			<?php
			while ( $creationell_wp_theme_query->have_posts() ) :
				$creationell_wp_theme_query->the_post();
				?>
				<div class="swiper-slide">
					<?php get_template_part( $creationell_wp_theme_card, null, array( 'display' => $creationell_wp_theme_display ) ); ?>
				</div>
			<?php endwhile; ?>
		</div>
	</div>
	<?php if ( $creationell_wp_theme_arrows || $creationell_wp_theme_dots ) : ?>
		<div class="creationell-theme-post-slider__controls">
			<?php if ( $creationell_wp_theme_arrows ) : ?>
				<button type="button" class="creationell-theme-post-slider__prev <?php echo esc_attr( $creationell_wp_theme_button ); ?>" aria-label="<?php echo esc_attr( $creationell_wp_theme_prev ); ?>"><?php creationell_wp_theme_icon( $creationell_wp_theme_rtl ? 'chevron-right' : 'chevron-left' ); ?></button>
			<?php endif; ?>
			<?php if ( $creationell_wp_theme_dots ) : ?>
				<div class="swiper-pagination creationell-theme-post-slider__dots"></div>
			<?php endif; ?>
			<?php if ( $creationell_wp_theme_arrows ) : ?>
				<button type="button" class="creationell-theme-post-slider__next <?php echo esc_attr( $creationell_wp_theme_button ); ?>" aria-label="<?php echo esc_attr( $creationell_wp_theme_next ); ?>"><?php creationell_wp_theme_icon( $creationell_wp_theme_rtl ? 'chevron-left' : 'chevron-right' ); ?></button>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</<?php echo esc_attr( $creationell_wp_theme_element ); ?>>
