<?php
/**
 * The template for displaying the footer
 * Template Version: 7.0.0
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>


<?php
/**
 * Fires before the site footer.
 *
 * @since 1.0.0
 */
do_action( 'creationell_wp_theme_before_footer' );
?>

<?php
// The footer part of the Site Editor while the module header-footer is active, else the PHP footer.
if ( ! creationell_wp_theme_render_template_part( 'footer' ) ) :
	?>

<footer id="footer" class="creationell-theme-footer">

	<?php if ( is_active_sidebar( 'footer-top' ) ) : ?>
		<?php
		/**
		 * Filters the CSS classes of the footer top area.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 */
		$creationell_wp_theme_class_footer_top = apply_filters( 'creationell_wp_theme_class_footer_top', 'bg-body-tertiary border-bottom py-5' );
		?>
	<div class="<?php echo esc_attr( $creationell_wp_theme_class_footer_top ); ?> creationell-theme-footer-top">
		<?php
		/**
		 * Filters the CSS classes of a layout container.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $context Template or template part, for example header or page.
		 */
		$creationell_wp_theme_class_container_3 = apply_filters( 'creationell_wp_theme_class_container', 'container', 'footer-top' );
		?>
		<div class="<?php echo esc_attr( $creationell_wp_theme_class_container_3 ); ?>">  
		<?php dynamic_sidebar( 'footer-top' ); ?>
		</div>
	</div>
	<?php endif; ?>
  
	<?php
	/**
	 * Filters the CSS classes of the footer columns area.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 */
	$creationell_wp_theme_class_footer_columns = apply_filters( 'creationell_wp_theme_class_footer_columns', 'bg-body-tertiary pt-5 pb-4' );
	?>
	<div class="<?php echo esc_attr( $creationell_wp_theme_class_footer_columns ); ?> creationell-theme-footer-columns">
	
	<?php
	/**
	 * Fires before the container of the footer columns.
	 *
	 * @since 1.0.0
	 */
	do_action( 'creationell_wp_theme_footer_columns_before_container' );
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
	$creationell_wp_theme_class_container_2 = apply_filters( 'creationell_wp_theme_class_container', 'container', 'footer-columns' );
	?>
	<div class="<?php echo esc_attr( $creationell_wp_theme_class_container_2 ); ?>">  
	  
		<?php
		/**
		 * Fires right after the opening tag of the container of the footer columns.
		 *
		 * @since 1.0.0
		 */
		do_action( 'creationell_wp_theme_footer_columns_after_container_open' );
		?>

		<div class="row">

		<?php
		/**
		 * Filters the CSS classes of a footer column.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $sidebar Footer widget area, footer-1 to footer-4.
		 */
		$creationell_wp_theme_class_footer_col_4 = apply_filters( 'creationell_wp_theme_class_footer_col', 'col-6 col-lg-3', 'footer-1' );
		?>
		<div class="<?php echo esc_attr( $creationell_wp_theme_class_footer_col_4 ); ?>">
			<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
				<?php dynamic_sidebar( 'footer-1' ); ?>
			<?php endif; ?>
		</div>

		<?php
		/**
		 * Filters the CSS classes of a footer column.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $sidebar Footer widget area, footer-1 to footer-4.
		 */
		$creationell_wp_theme_class_footer_col_3 = apply_filters( 'creationell_wp_theme_class_footer_col', 'col-6 col-lg-3', 'footer-2' );
		?>
		<div class="<?php echo esc_attr( $creationell_wp_theme_class_footer_col_3 ); ?>">
			<?php if ( is_active_sidebar( 'footer-2' ) ) : ?>
				<?php dynamic_sidebar( 'footer-2' ); ?>
			<?php endif; ?>
		</div>
		
		<?php
		/**
		 * Filters the CSS classes of a footer column.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $sidebar Footer widget area, footer-1 to footer-4.
		 */
		$creationell_wp_theme_class_footer_col_2 = apply_filters( 'creationell_wp_theme_class_footer_col', 'col-6 col-lg-3', 'footer-3' );
		?>
		<div class="<?php echo esc_attr( $creationell_wp_theme_class_footer_col_2 ); ?>">
			<?php if ( is_active_sidebar( 'footer-3' ) ) : ?>
				<?php dynamic_sidebar( 'footer-3' ); ?>
			<?php endif; ?>
		</div>
		
		<?php
		/**
		 * Filters the CSS classes of a footer column.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $sidebar Footer widget area, footer-1 to footer-4.
		 */
		$creationell_wp_theme_class_footer_col = apply_filters( 'creationell_wp_theme_class_footer_col', 'col-6 col-lg-3', 'footer-4' );
		?>
		<div class="<?php echo esc_attr( $creationell_wp_theme_class_footer_col ); ?>">
			<?php if ( is_active_sidebar( 'footer-4' ) ) : ?>
				<?php dynamic_sidebar( 'footer-4' ); ?>
			<?php endif; ?>
		</div>

		</div>
	  
		<?php
		/**
		 * Fires before the footer menu.
		 *
		 * @since 1.0.0
		 */
		do_action( 'creationell_wp_theme_footer_columns_before_footer_menu' );
		?>

		<!-- Bootstrap 5 Nav Walker Footer Menu -->
		<?php get_template_part( 'template-parts/footer/footer-menu' ); ?>

		<?php
		/**
		 * Fires right before the closing tag of the container of the footer columns.
		 *
		 * @since 1.0.0
		 */
		do_action( 'creationell_wp_theme_footer_columns_before_container_close' );
		?>
	  
	</div>
	
	<?php
	/**
	 * Fires after the container of the footer columns.
	 *
	 * @since 1.0.0
	 */
	do_action( 'creationell_wp_theme_footer_columns_after_container' );
	?>
	
	</div>

	<?php
	/**
	 * Filters the CSS classes of the footer info area.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 */
	$creationell_wp_theme_class_footer_info = apply_filters( 'creationell_wp_theme_class_footer_info', 'bg-body-tertiary text-body-secondary border-top py-2 text-center' );
	?>
	<div class="<?php echo esc_attr( $creationell_wp_theme_class_footer_info ); ?> creationell-theme-footer-info">
	<?php
	/**
	 * Filters the CSS classes of a layout container.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 * @param string $context Template or template part, for example header or page.
	 */
	$creationell_wp_theme_class_container = apply_filters( 'creationell_wp_theme_class_container', 'container', 'footer-info' );
	?>
	<div class="<?php echo esc_attr( $creationell_wp_theme_class_container ); ?>">
	  
		<?php
		/**
		 * Fires right after the opening tag of the container of the footer info.
		 *
		 * @since 1.0.0
		 */
		do_action( 'creationell_wp_theme_footer_info_after_container_open' );
		?>
	  
		<?php if ( is_active_sidebar( 'footer-info' ) ) : ?>
			<?php dynamic_sidebar( 'footer-info' ); ?>
		<?php endif; ?>
		<?php get_template_part( 'template-parts/footer/footer-info' ); ?>
	</div>
	</div>

</footer>

<?php endif; ?>

<!-- To top button -->
<?php
/**
 * Filters the CSS classes of the button that scrolls to the top.
 *
 * @since 1.0.0
 *
 * @param string $classes Space-separated CSS classes.
 */
$creationell_wp_theme_class_footer_to_top_button = apply_filters( 'creationell_wp_theme_class_footer_to_top_button', 'btn btn-primary shadow' );
?>
<button type="button" class="<?php echo esc_attr( $creationell_wp_theme_class_footer_to_top_button ); ?> position-fixed zi-1000 top-button" aria-label="<?php esc_attr_e( 'Return to top', 'creationell-wp-theme' ); ?>"><?php creationell_wp_theme_icon( 'chevron-up' ); ?></button>

</div><!-- #page -->

<?php wp_footer(); ?>

</body>

</html>
