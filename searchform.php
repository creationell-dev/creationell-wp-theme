<?php
/**
 * Template to show classic searchform widget
 * Used is content-none.php as well
 *
 * Template Version: 7.0.0
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>


<?php
$creationell_wp_theme_search_field_id = wp_unique_id( 'search-field-' );

// get_search_form() passes aria_label; the default name keeps the landmark apart from other search forms.
// The form is named like the search block in the same place (inc/blocks/block-widget-search.php): in the
// header widget area after the header search, the copy in the collapse after its role; elsewhere
// "Site search", from the second form on with a number, also for two search widgets in one widget area.
// The search form of a page without results passes a name of its own.
if ( isset( $args['aria_label'] ) && is_string( $args['aria_label'] ) && '' !== $args['aria_label'] ) {
	$creationell_wp_theme_search_label = $args['aria_label'];
} elseif ( function_exists( 'creationell_wp_theme_widget_area' ) && function_exists( 'creationell_wp_theme_header_search_label' ) && 'top-nav-search' === creationell_wp_theme_widget_area() ) {
	$creationell_wp_theme_search_label = creationell_wp_theme_header_search_label();
} elseif ( function_exists( 'creationell_wp_theme_site_search_label' ) ) {
	$creationell_wp_theme_search_label = creationell_wp_theme_site_search_label();
} else {
	$creationell_wp_theme_search_label = __( 'Site search', 'creationell-wp-theme' );
}
?>
<form class="searchform input-group" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" role="search" aria-label="<?php echo esc_attr( $creationell_wp_theme_search_label ); ?>">
	<label class="visually-hidden" for="<?php echo esc_attr( $creationell_wp_theme_search_field_id ); ?>"><?php esc_html_e( 'Search for:', 'creationell-wp-theme' ); ?></label>
	<input type="search" id="<?php echo esc_attr( $creationell_wp_theme_search_field_id ); ?>" name="s" class="form-control" placeholder="<?php esc_attr_e( 'Search', 'creationell-wp-theme' ); ?>" value="<?php echo esc_attr( get_search_query( false ) ); ?>">
	<?php
	/**
	 * Filters the CSS classes of the search button.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 */
	$creationell_wp_theme_class_widget_search_button = apply_filters( 'creationell_wp_theme_class_widget_search_button', 'btn btn-outline-secondary' );
	?>
	<button type="submit" class="input-group-text <?php echo esc_attr( $creationell_wp_theme_class_widget_search_button ); ?>"><?php creationell_wp_theme_icon( 'search' ); ?><span class="visually-hidden"><?php esc_html_e( 'Search', 'creationell-wp-theme' ); ?></span></button>
</form>
