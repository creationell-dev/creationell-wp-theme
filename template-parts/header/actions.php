<?php
/**
 * Template part for displaying the header-actions
 * Template Version: 7.0.0
 *
 * Arguments ($args): show_search false leaves out both searches, show_language_switcher
 * false leaves out the language switcher (both default true).
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_actions_search = ! isset( $args['show_search'] ) || false !== $args['show_search'];
$creationell_wp_theme_actions_switch = ! isset( $args['show_language_switcher'] ) || false !== $args['show_language_switcher'];
?>


<!-- Searchform large -->
<?php if ( $creationell_wp_theme_actions_search && is_active_sidebar( 'top-nav-search' ) ) : ?>
	<?php
	/**
	 * Filters the CSS classes that set the spacing of an element in the header actions.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 * @param string $element Header element, for example search-toggler.
	 */
	$creationell_wp_theme_class_header_action_spacer_2 = apply_filters( 'creationell_wp_theme_class_header_action_spacer', 'ms-1 ms-md-2', 'searchform' );
	?>
	<?php
	/**
	 * Filters the breakpoint at which the search moves into the navbar.
	 *
	 * @since 1.0.0
	 *
	 * @param string $breakpoint Bootstrap breakpoint, for example lg.
	 */
	$creationell_wp_theme_class_header_search_breakpoint_2 = apply_filters( 'creationell_wp_theme_class_header_search_breakpoint', 'lg' );
	?>
	<div class="d-none d-<?php echo esc_attr( $creationell_wp_theme_class_header_search_breakpoint_2 ); ?>-block <?php echo esc_attr( $creationell_wp_theme_class_header_action_spacer_2 ); ?> nav-search-lg">
	<?php dynamic_sidebar( 'top-nav-search' ); ?>
	</div>
<?php endif; ?>

<?php
if ( $creationell_wp_theme_actions_switch ) :
	get_template_part( 'template-parts/header/language-switcher' );
endif;
?>

<!-- Search toggler mobile -->
<?php
/**
 * Filters whether a widget area counts as filled.
 *
 * @since 1.0.0
 *
 * @param bool   $has_widgets Whether the area has widgets.
 * @param string $sidebar     Widget area ID.
 */
if ( $creationell_wp_theme_actions_search && apply_filters( 'creationell_wp_theme_sidebar_has_widgets', is_active_sidebar( 'top-nav-search' ), 'top-nav-search' ) ) :
	?>
	<?php
	/**
	 * Filters the CSS classes that set the spacing of an element in the header actions.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 * @param string $element Header element, for example search-toggler.
	 */
	$creationell_wp_theme_class_header_action_spacer = apply_filters( 'creationell_wp_theme_class_header_action_spacer', 'ms-1 ms-md-2', 'search-toggler' );
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
	 * Filters the CSS classes of a button in the header actions.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 * @param string $element Header element, for example search-toggler.
	 */
	$creationell_wp_theme_class_header_button = apply_filters( 'creationell_wp_theme_class_header_button', 'btn btn-outline-secondary', 'search-toggler' );
	?>
	<button class="<?php echo esc_attr( $creationell_wp_theme_class_header_button ); ?> d-<?php echo esc_attr( $creationell_wp_theme_class_header_search_breakpoint ); ?>-none <?php echo esc_attr( $creationell_wp_theme_class_header_action_spacer ); ?> search-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-search" aria-expanded="false" aria-controls="collapse-search" aria-label="<?php esc_attr_e( 'Open search', 'creationell-wp-theme' ); ?>">
	<?php creationell_wp_theme_icon( 'search' ); ?>
	</button>
<?php endif; ?>
