<?php
/**
 * Template part for the navbar of the site header
 *
 * Prints the main navigation with logo, offcanvas menu, header actions and the
 * collapsed search. The PHP header prints it inside <header id="masthead">, the
 * navbar block of the module header-footer inside the header part; both share
 * this markup. The IDs are fixed, so a page holds one navbar.
 *
 * Arguments ($args), each outside its positive list back to the default:
 * - expand: breakpoint from which the navbar expands, sm, md, lg, xl or xxl (lg).
 * - show_logo: whether the logo link shows (true).
 * - show_search: whether the searches of the header actions show (true).
 * - placement: side from which the offcanvas menu opens, start or end (end).
 * - show_language_switcher: whether the header actions show the language
 *   switcher (true); the navbar block passes false, because the header part
 *   holds the switcher as a block of its own.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_navbar_args   = isset( $args ) && is_array( $args ) ? $args : array();
$creationell_wp_theme_navbar_expand = isset( $creationell_wp_theme_navbar_args['expand'] ) && in_array( $creationell_wp_theme_navbar_args['expand'], array( 'sm', 'md', 'lg', 'xl', 'xxl' ), true ) ? $creationell_wp_theme_navbar_args['expand'] : 'lg';
$creationell_wp_theme_navbar_logo   = ! isset( $creationell_wp_theme_navbar_args['show_logo'] ) || false !== $creationell_wp_theme_navbar_args['show_logo'];
$creationell_wp_theme_navbar_search = ! isset( $creationell_wp_theme_navbar_args['show_search'] ) || false !== $creationell_wp_theme_navbar_args['show_search'];
$creationell_wp_theme_navbar_switch = ! isset( $creationell_wp_theme_navbar_args['show_language_switcher'] ) || false !== $creationell_wp_theme_navbar_args['show_language_switcher'];
$creationell_wp_theme_navbar_side   = isset( $creationell_wp_theme_navbar_args['placement'] ) && in_array( $creationell_wp_theme_navbar_args['placement'], array( 'start', 'end' ), true ) ? $creationell_wp_theme_navbar_args['placement'] : 'end';
?>
<?php
/**
 * Filters the CSS class that sets the breakpoint at which the navbar expands.
 *
 * @since 1.0.0
 *
 * @param string $classes Space-separated CSS classes.
 */
$creationell_wp_theme_class_header_navbar_breakpoint = apply_filters( 'creationell_wp_theme_class_header_navbar_breakpoint', 'navbar-expand-' . $creationell_wp_theme_navbar_expand );
?>
<nav id="nav-main" class="navbar <?php echo esc_attr( $creationell_wp_theme_class_header_navbar_breakpoint ); ?>" aria-label="<?php esc_attr_e( 'Main menu', 'creationell-wp-theme' ); ?>">

	<?php
	/**
	 * Filters the CSS classes of a layout container.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 * @param string $context Template or template part, for example header or page.
	 */
	$creationell_wp_theme_class_container = apply_filters( 'creationell_wp_theme_class_container', 'container', 'header' );
	?>
	<div class="<?php echo esc_attr( $creationell_wp_theme_class_container ); ?>">
	
	<?php
	/**
	 * Fires before the logo link in the navbar.
	 *
	 * @since 1.0.0
	 */
	do_action( 'creationell_wp_theme_before_navbar_brand' );
	?>
	
	<?php if ( $creationell_wp_theme_navbar_logo ) : ?>
		<?php
		/**
		 * Filters the CSS classes of the logo link in the navbar.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 */
		$creationell_wp_theme_class_header_navbar_brand = apply_filters( 'creationell_wp_theme_class_header_navbar_brand', 'navbar-brand' );
		?>
	<a class="<?php echo esc_attr( $creationell_wp_theme_class_header_navbar_brand ); ?>" href="<?php echo esc_url( home_url() ); ?>">
		<?php
		/**
		 * Filters the height attribute of the logo.
		 *
		 * @since 1.0.0
		 *
		 * @param string $height Height in pixels.
		 */
		$creationell_wp_theme_logo_height = apply_filters( 'creationell_wp_theme_logo_height', '30' );

		/**
		 * Filters the width attribute of the logo.
		 *
		 * @since 1.0.0
		 *
		 * @param string $width Width in pixels.
		 */
		$creationell_wp_theme_logo_width = apply_filters( 'creationell_wp_theme_logo_width', '30' );

		/**
		 * Filters the URL of the logo.
		 *
		 * @since 1.0.0
		 *
		 * @param string $url     Logo URL.
		 * @param string $variant Logo variant, default or theme-dark.
		 */
		$creationell_wp_theme_logo_default = apply_filters( 'creationell_wp_theme_logo', get_theme_file_uri( 'assets/img/logo/logo.svg' ), 'default' );

		/**
		 * Filters the URL of the logo.
		 *
		 * @since 1.0.0
		 *
		 * @param string $url     Logo URL.
		 * @param string $variant Logo variant, default or theme-dark.
		 */
		$creationell_wp_theme_logo_dark = apply_filters( 'creationell_wp_theme_logo', get_theme_file_uri( 'assets/img/logo/logo-theme-dark.svg' ), 'theme-dark' );

		// The logo is the only content of the link, so its text names the target of the link.
		/* translators: %s: site name */
		$creationell_wp_theme_logo_alt = sprintf( __( '%s – Home', 'creationell-wp-theme' ), get_bloginfo( 'name' ) );
		?>
		<img src="<?php echo esc_url( $creationell_wp_theme_logo_default ); ?>" alt="<?php echo esc_attr( $creationell_wp_theme_logo_alt ); ?>" class="d-td-none" width="<?php echo esc_attr( $creationell_wp_theme_logo_width ); ?>" height="<?php echo esc_attr( $creationell_wp_theme_logo_height ); ?>">
		<img src="<?php echo esc_url( $creationell_wp_theme_logo_dark ); ?>" alt="<?php echo esc_attr( $creationell_wp_theme_logo_alt ); ?>" class="d-tl-none" width="<?php echo esc_attr( $creationell_wp_theme_logo_width ); ?>" height="<?php echo esc_attr( $creationell_wp_theme_logo_height ); ?>">
	</a>  
	<?php endif; ?>
	
	<?php
	/**
	 * Fires after the logo link in the navbar.
	 *
	 * @since 1.0.0
	 */
	do_action( 'creationell_wp_theme_after_navbar_brand' );
	?>

	<!-- Offcanvas Navbar -->
	<?php
	/**
	 * Filters the side from which the offcanvas main menu opens.
	 *
	 * @since 1.0.0
	 *
	 * @param string $direction Offcanvas side, start or end.
	 * @param string $context   Offcanvas, menu or sidebar.
	 */
	$creationell_wp_theme_class_header_offcanvas_direction = apply_filters( 'creationell_wp_theme_class_header_offcanvas_direction', $creationell_wp_theme_navbar_side, 'menu' );
	?>
	<div class="offcanvas offcanvas-<?php echo esc_attr( $creationell_wp_theme_class_header_offcanvas_direction ); ?>" tabindex="-1" id="offcanvas-navbar" aria-labelledby="offcanvas-navbar-label">
		<?php
		/**
		 * Filters the CSS classes of the header of an offcanvas.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $context Offcanvas, menu or sidebar.
		 */
		$creationell_wp_theme_class_offcanvas_header = apply_filters( 'creationell_wp_theme_class_offcanvas_header', '', 'menu' );
		?>
		<div class="offcanvas-header <?php echo esc_attr( $creationell_wp_theme_class_offcanvas_header ); ?>">
		<?php
		/**
		 * Filters the title of the offcanvas main menu.
		 *
		 * @since 1.0.0
		 *
		 * @param string $title Title.
		 */
		$creationell_wp_theme_offcanvas_navbar_title = apply_filters( 'creationell_wp_theme_offcanvas_navbar_title', __( 'Menu', 'creationell-wp-theme' ) );
		?>
		<span class="h5 offcanvas-title" id="offcanvas-navbar-label"><?php echo esc_html( $creationell_wp_theme_offcanvas_navbar_title ); ?></span>
		<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="<?php esc_attr_e( 'Close', 'creationell-wp-theme' ); ?>"></button>
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
		$creationell_wp_theme_class_offcanvas_body = apply_filters( 'creationell_wp_theme_class_offcanvas_body', '', 'menu' );
		?>
		<div class="offcanvas-body <?php echo esc_attr( $creationell_wp_theme_class_offcanvas_body ); ?>">

		<!-- Bootstrap 5 Nav Walker Main Menu -->
		<?php get_template_part( 'template-parts/header/main-menu' ); ?>

		<!-- Top Nav 2 Widget -->
		<?php if ( is_active_sidebar( 'top-nav-2' ) ) : ?>
			<?php dynamic_sidebar( 'top-nav-2' ); ?>
		<?php endif; ?>

		</div>
	</div>

	<?php
	/**
	 * Filters the CSS classes of the header actions.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 */
	$creationell_wp_theme_class_header_actions = apply_filters( 'creationell_wp_theme_class_header_actions', 'd-flex align-items-center' );
	?>
	<div class="header-actions <?php echo esc_attr( $creationell_wp_theme_class_header_actions ); ?>">

		<!-- Top Nav Widget -->
		<?php if ( is_active_sidebar( 'top-nav' ) ) : ?>
			<?php dynamic_sidebar( 'top-nav' ); ?>
		<?php endif; ?>

		<?php
		if ( class_exists( 'WooCommerce' ) ) :
			get_template_part(
				'template-parts/header/actions',
				'woocommerce',
				array(
					'show_search'            => $creationell_wp_theme_navbar_search,
					'show_language_switcher' => $creationell_wp_theme_navbar_switch,
				)
			);
		else :
			get_template_part(
				'template-parts/header/actions',
				null,
				array(
					'show_search'            => $creationell_wp_theme_navbar_search,
					'show_language_switcher' => $creationell_wp_theme_navbar_switch,
				)
			);
		endif;
		?>

		<!-- Navbar Toggler -->
		<?php
		/**
		 * Filters the CSS classes that set the spacing of an element in the header actions.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $element Header element, for example search-toggler.
		 */
		$creationell_wp_theme_class_header_action_spacer = apply_filters( 'creationell_wp_theme_class_header_action_spacer', 'ms-1 ms-md-2', 'nav-toggler' );
		?>
		<?php
		/**
		 * Filters the CSS classes that hide the menu button above the navbar breakpoint.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 */
		$creationell_wp_theme_class_header_navbar_toggler_breakpoint = apply_filters( 'creationell_wp_theme_class_header_navbar_toggler_breakpoint', 'd-' . $creationell_wp_theme_navbar_expand . '-none' );
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
		$creationell_wp_theme_class_header_button = apply_filters( 'creationell_wp_theme_class_header_button', 'btn btn-outline-secondary', 'nav-toggler' );
		?>
		<button class="<?php echo esc_attr( $creationell_wp_theme_class_header_button ); ?> <?php echo esc_attr( $creationell_wp_theme_class_header_navbar_toggler_breakpoint ); ?> <?php echo esc_attr( $creationell_wp_theme_class_header_action_spacer ); ?> nav-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-navbar" aria-controls="offcanvas-navbar" aria-label="<?php esc_attr_e( 'Toggle main menu', 'creationell-wp-theme' ); ?>">
		<?php creationell_wp_theme_icon( 'list' ); ?>
		</button>
	  
		<?php
		/**
		 * Fires after the button that opens the main menu.
		 *
		 * @since 1.0.0
		 */
		do_action( 'creationell_wp_theme_after_nav_toggler' );
		?>

	</div><!-- .header-actions -->

	</div><!-- .container -->

</nav><!-- .navbar -->

<?php
// The collapsed search belongs to the search toggler of the header actions.
if ( $creationell_wp_theme_navbar_search && class_exists( 'WooCommerce' ) ) :
	get_template_part( 'template-parts/header/collapse-search', 'woocommerce' );
elseif ( $creationell_wp_theme_navbar_search ) :
	get_template_part( 'template-parts/header/collapse-search' );
endif;
?>

<!-- Offcanvas User and Cart -->
<?php
if ( class_exists( 'WooCommerce' ) ) :
	get_template_part( 'template-parts/header/offcanvas', 'woocommerce' );
endif;
?>
