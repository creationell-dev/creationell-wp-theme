<?php
/**
 * The header for our theme
 * Template Version: 7.0.0
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<?php wp_body_open(); ?>

<div id="page" class="site">

	<!-- Focus target of the button that scrolls back to the top -->
	<div id="to-top" tabindex="-1"></div>

	<!-- Skip Links -->
	<a class="skip-link visually-hidden-focusable" href="#primary"><?php esc_html_e( 'Skip to content', 'creationell-wp-theme' ); ?></a>
	<a class="skip-link visually-hidden-focusable" href="#footer"><?php esc_html_e( 'Skip to footer', 'creationell-wp-theme' ); ?></a>

	<!-- Top Bar Widget -->
	<?php if ( is_active_sidebar( 'top-bar' ) ) : ?>
		<?php dynamic_sidebar( 'top-bar' ); ?>
	<?php endif; ?>
  
	<?php
	/**
	 * Fires before the site header.
	 *
	 * @since 1.0.0
	 */
	do_action( 'creationell_wp_theme_before_masthead' );
	?>

	<?php
	// The header part of the Site Editor while the module header-footer is active, else the PHP header.
	if ( ! creationell_wp_theme_render_template_part( 'header' ) ) :
		?>

		<?php
		/**
		 * Filters the CSS classes of the site header.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 */
		$creationell_wp_theme_class_header = apply_filters( 'creationell_wp_theme_class_header', 'sticky-top bg-body-tertiary' );
		?>
	<header id="masthead" class="<?php echo esc_attr( $creationell_wp_theme_class_header ); ?> site-header">

		<?php
		/**
		 * Fires right after the opening tag of the site header.
		 *
		 * @since 1.0.0
		 */
		do_action( 'creationell_wp_theme_after_masthead_open' );
		?>
	
		<?php get_template_part( 'template-parts/header/navbar' ); ?>

		<?php
		/**
		 * Fires right before the closing tag of the site header.
		 *
		 * @since 1.0.0
		 */
		do_action( 'creationell_wp_theme_before_masthead_close' );
		?>
	
	</header><!-- #masthead -->

	<?php endif; ?>
  
	<?php
	/**
	 * Fires after the site header.
	 *
	 * @since 1.0.0
	 */
	do_action( 'creationell_wp_theme_after_masthead' );
	?>
