<?php
/**
 * The sidebar containing the main widget area
 * Template Version: 7.0.0
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Filters whether a widget area counts as filled.
 *
 * @since 1.0.0
 *
 * @param bool   $has_widgets Whether the area has widgets.
 * @param string $sidebar     Widget area ID.
 */
if ( ! apply_filters( 'creationell_wp_theme_sidebar_has_widgets', is_active_sidebar( 'sidebar-1' ), 'sidebar-1' ) ) {
	return;
}
?>
<?php
/**
 * Filters the CSS classes of the sidebar column.
 *
 * @since 1.0.0
 *
 * @param string $classes Space-separated CSS classes.
 */
$creationell_wp_theme_class_sidebar_col = apply_filters( 'creationell_wp_theme_class_sidebar_col', 'col-lg-3 order-lg-2' );
?>
<div class="<?php echo esc_attr( $creationell_wp_theme_class_sidebar_col ); ?>">
	<aside id="secondary" class="widget-area">

	<?php
	/**
	 * Filters the CSS classes of the button that opens the sidebar.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 */
	$creationell_wp_theme_class_sidebar_button = apply_filters( 'creationell_wp_theme_class_sidebar_button', 'd-lg-none btn btn-outline-primary w-100 mb-4 d-flex justify-content-between align-items-center' );
	?>
	<button class="<?php echo esc_attr( $creationell_wp_theme_class_sidebar_button ); ?>" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
		<?php
		/**
		 * Filters the text of the button that opens the sidebar.
		 *
		 * @since 1.0.0
		 *
		 * @param string $text Button text.
		 */
		$creationell_wp_theme_offcanvas_sidebar_button_text = apply_filters( 'creationell_wp_theme_offcanvas_sidebar_button_text', __( 'Open side menu', 'creationell-wp-theme' ) );
		?>
		<?php echo esc_html( $creationell_wp_theme_offcanvas_sidebar_button_text ); ?> <?php creationell_wp_theme_icon( 'three-dots-vertical' ); ?>
	</button>

	<?php
	/**
	 * Filters the CSS classes of the offcanvas sidebar.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 */
	$creationell_wp_theme_class_sidebar_offcanvas = apply_filters( 'creationell_wp_theme_class_sidebar_offcanvas', 'offcanvas-lg offcanvas-end' );
	?>
	<div class="<?php echo esc_attr( $creationell_wp_theme_class_sidebar_offcanvas ); ?>" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
		<?php
		/**
		 * Filters the CSS classes of the header of an offcanvas.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $context Offcanvas, menu or sidebar.
		 */
		$creationell_wp_theme_class_offcanvas_header = apply_filters( 'creationell_wp_theme_class_offcanvas_header', '', 'sidebar' );
		?>
		<div class="offcanvas-header <?php echo esc_attr( $creationell_wp_theme_class_offcanvas_header ); ?>">
		<?php
		/**
		 * Filters the title of the offcanvas sidebar.
		 *
		 * @since 1.0.0
		 *
		 * @param string $title Title.
		 */
		$creationell_wp_theme_offcanvas_sidebar_title = apply_filters( 'creationell_wp_theme_offcanvas_sidebar_title', __( 'Sidebar', 'creationell-wp-theme' ) );
		?>
		<span class="h5 offcanvas-title" id="sidebarLabel"><?php echo esc_html( $creationell_wp_theme_offcanvas_sidebar_title ); ?></span>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="<?php esc_attr_e( 'Close', 'creationell-wp-theme' ); ?>"></button>
		</div>
		<?php
		/**
		 * Filters the CSS classes of the body of an offcanvas.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $context Offcanvas, menu or sidebar.
		 */
		$creationell_wp_theme_class_offcanvas_body = apply_filters( 'creationell_wp_theme_class_offcanvas_body', '', 'sidebar' );
		?>
		<div class="offcanvas-body flex-column <?php echo esc_attr( $creationell_wp_theme_class_offcanvas_body ); ?>">
		
		<?php
		/**
		 * Fires before the widgets of the sidebar.
		 *
		 * @since 1.0.0
		 */
		do_action( 'creationell_wp_theme_before_sidebar_widgets' );
		?>
		
		<?php dynamic_sidebar( 'sidebar-1' ); ?>
		
		<?php
		/**
		 * Fires after the widgets of the sidebar.
		 *
		 * @since 1.0.0
		 */
		do_action( 'creationell_wp_theme_after_sidebar_widgets' );
		?>
		
		</div>
	</div>

	</aside><!-- #secondary -->
</div>
