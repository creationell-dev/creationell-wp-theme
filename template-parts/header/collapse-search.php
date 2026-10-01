<?php
/**
 * Template part for displaying the top-nav searchform collapse widget
 * Template Version: 7.0.0
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>


<!-- Collapse Search Mobile -->
<?php
/**
 * Filters whether a widget area counts as filled.
 *
 * @since 1.0.0
 *
 * @param bool   $has_widgets Whether the area has widgets.
 * @param string $sidebar     Widget area ID.
 */
if ( apply_filters( 'creationell_wp_theme_sidebar_has_widgets', is_active_sidebar( 'top-nav-search' ), 'top-nav-search' ) ) :
	?>
	<?php
	/**
	 * Filters the breakpoint at which the search moves into the navbar.
	 *
	 * @since 1.0.0
	 *
	 * @param string $breakpoint Bootstrap breakpoint, for example lg.
	 */
	$creationell_wp_theme_class_header_search_breakpoint = apply_filters( 'creationell_wp_theme_class_header_search_breakpoint', 'lg' );
	?>
	<?php
	/**
	 * Filters the CSS classes of the collapsible search in the header.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 */
	$creationell_wp_theme_class_header_collapse = apply_filters( 'creationell_wp_theme_class_header_collapse', 'bg-body-tertiary position-absolute start-0 end-0' );
	?>
	<div class="collapse <?php echo esc_attr( $creationell_wp_theme_class_header_collapse ); ?> d-<?php echo esc_attr( $creationell_wp_theme_class_header_search_breakpoint ); ?>-none" id="collapse-search">
	<?php
	/**
	 * Filters the CSS classes of a layout container.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 * @param string $context Template or template part, for example header or page.
	 */
	$creationell_wp_theme_class_container = apply_filters( 'creationell_wp_theme_class_container', 'container', 'collapse-search' );
	?>
	<div class="<?php echo esc_attr( $creationell_wp_theme_class_container ); ?> pb-2">
		<?php
		// The search forms of this copy are named after its role, "Header search (menu)" (inc/blocks/block-widget-search.php).
		$creationell_wp_theme_header_search_context = function_exists( 'creationell_wp_theme_header_search_context' );
		if ( $creationell_wp_theme_header_search_context ) {
			creationell_wp_theme_header_search_context( 'menu' );
		}
		dynamic_sidebar( 'top-nav-search' );
		if ( $creationell_wp_theme_header_search_context ) {
			creationell_wp_theme_header_search_context( '' );
		}
		?>
	</div>
	</div>
<?php endif; ?>
