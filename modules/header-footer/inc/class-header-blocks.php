<?php
/**
 * Render code of the blocks navbar, logo, search and footer-menu.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\HeaderFooter;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Prints the header blocks of the module through the partials of the theme.
 *
 * The index.php of each block hands its attributes over and prints nothing
 * itself, so the PHP header and the blocks share one markup: the navbar block
 * prints template-parts/header/navbar.php, the footer menu block
 * template-parts/footer/footer-menu.php, the search block the search form of
 * the theme. Attribute values pass positive lists; anything else falls back to
 * the defaults of the PHP header. The navbar carries fixed IDs, so it prints
 * once per request; a second navbar block prints nothing.
 *
 * The logo block shows assets/img/logo/logo.svg and logo-theme-dark.svg of the
 * child theme or else of the parent theme, and only files that exist. Without
 * a logo it prints the site name, so no image request fails.
 *
 * @since 1.0.0
 */
final class Header_Blocks {

	/**
	 * Breakpoints from which the navbar expands.
	 *
	 * @since 1.0.0
	 */
	public const EXPAND = array( 'sm', 'md', 'lg', 'xl', 'xxl' );

	/**
	 * Sides from which the offcanvas menu opens.
	 *
	 * @since 1.0.0
	 */
	public const PLACEMENTS = array( 'start', 'end' );

	/**
	 * Logo files by variant, relative to the theme folder.
	 *
	 * @since 1.0.0
	 */
	public const LOGOS = array(
		'default'    => 'assets/img/logo/logo.svg',
		'theme-dark' => 'assets/img/logo/logo-theme-dark.svg',
	);

	/**
	 * Whether a navbar block printed in this request.
	 *
	 * @var bool
	 */
	private static bool $navbar_printed = false;

	/**
	 * Returns the arguments of the navbar partial for the attributes of a navbar block.
	 *
	 * The navbar block leaves out the language switcher of the header actions:
	 * in the header part the switcher is a block of its own
	 * (creationell-theme/language-switcher), so a page shows one switcher.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $attributes Block attributes: expand, showLogo, showSearch, offcanvasPlacement.
	 * @return array{expand: string, show_logo: bool, show_search: bool, placement: string, show_language_switcher: false} Arguments.
	 */
	public static function navbar_args( array $attributes ): array {
		return array(
			'expand'                 => self::choice( $attributes['expand'] ?? null, self::EXPAND, 'lg' ),
			'show_logo'              => self::flag( $attributes['showLogo'] ?? null, true ),
			'show_search'            => self::flag( $attributes['showSearch'] ?? null, true ),
			'placement'              => self::choice( $attributes['offcanvasPlacement'] ?? null, self::PLACEMENTS, 'end' ),
			'show_language_switcher' => false,
		);
	}

	/**
	 * Prints the navbar block: the navbar partial with the arguments of the block, once per request.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @return void
	 */
	public static function navbar( array $attributes ): void {
		if ( self::$navbar_printed ) {
			return;
		}
		self::$navbar_printed = true;
		get_template_part( 'template-parts/header/navbar', null, self::navbar_args( $attributes ) );
	}

	/**
	 * Prints the logo block: the logo images of the theme, or the site name without logo files.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $attributes Block attributes: linkHome.
	 * @return void
	 */
	public static function logo( array $attributes ): void {
		$link    = self::flag( $attributes['linkHome'] ?? null, true );
		$name    = get_bloginfo( 'name' );
		$default = self::logo_url( 'default' );
		$dark    = '' === $default ? '' : self::logo_url( 'theme-dark' );
		if ( '' === $default && '' === $name ) {
			return;
		}
		if ( $link ) {
			printf( '<a class="creationell-theme-logo" href="%s" rel="home">', esc_url( home_url( '/' ) ) );
		} else {
			echo '<span class="creationell-theme-logo">';
		}
		if ( '' === $default ) {
			echo esc_html( $name );
		} else {
			/* translators: %s: site name */
			$alt = $link ? sprintf( __( '%s – Home', 'creationell-wp-theme' ), $name ) : $name;
			self::logo_image( $default, $alt, '' === $dark ? '' : 'd-td-none' );
			if ( '' !== $dark ) {
				self::logo_image( $dark, $alt, 'd-tl-none' );
			}
		}
		echo $link ? '</a>' : '</span>';
	}

	/**
	 * Prints the search block: the search form of the theme.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function search(): void {
		get_search_form();
	}

	/**
	 * Prints the footer menu block: the footer menu partial.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function footer_menu(): void {
		get_template_part( 'template-parts/footer/footer-menu' );
	}

	/**
	 * Forgets that a navbar printed; for a new render pass such as a test.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$navbar_printed = false;
	}

	/**
	 * Returns the URL of a logo variant: the file of the child or parent theme when it exists, then the logo filter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $variant Variant: default or theme-dark.
	 * @return string URL, or an empty string without a logo.
	 */
	private static function logo_url( string $variant ): string {
		$file = self::LOGOS[ $variant ] ?? '';
		$url  = '' !== $file && is_file( get_theme_file_path( $file ) ) ? get_theme_file_uri( $file ) : '';

		/**
		 * Filters the URL of the logo.
		 *
		 * @since 1.0.0
		 *
		 * @param string $url     Logo URL.
		 * @param string $variant Logo variant, default or theme-dark.
		 */
		$url = apply_filters( 'creationell_wp_theme_logo', $url, $variant );
		return is_string( $url ) ? $url : '';
	}

	/**
	 * Prints one logo image with the size of the header logo.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url   Image URL.
	 * @param string $alt   Alternative text.
	 * @param string $css_class CSS class that hides the image in one color scheme, or empty.
	 * @return void
	 */
	private static function logo_image( string $url, string $alt, string $css_class ): void {
		/**
		 * Filters the height attribute of the logo.
		 *
		 * @since 1.0.0
		 *
		 * @param string $height Height in pixels.
		 */
		$height = apply_filters( 'creationell_wp_theme_logo_height', '30' );

		/**
		 * Filters the width attribute of the logo.
		 *
		 * @since 1.0.0
		 *
		 * @param string $width Width in pixels.
		 */
		$width = apply_filters( 'creationell_wp_theme_logo_width', '30' );
		printf(
			'<img src="%1$s" alt="%2$s"%3$s width="%4$s" height="%5$s">',
			esc_url( $url ),
			esc_attr( $alt ),
			'' === $css_class ? '' : ' class="' . esc_attr( $css_class ) . '"',
			esc_attr( is_scalar( $width ) ? (string) $width : '30' ),
			esc_attr( is_scalar( $height ) ? (string) $height : '30' )
		);
	}

	/**
	 * Returns a value of a positive list; Blockstudio passes a select as its value or as an option array.
	 *
	 * Shared with the text blocks (Text_Blocks).
	 *
	 * @since 1.0.0
	 *
	 * @param mixed              $value    Attribute value.
	 * @param array<int, string> $allowed  Allowed values.
	 * @param string             $fallback Default.
	 * @return string Allowed value or the default.
	 */
	public static function choice( mixed $value, array $allowed, string $fallback ): string {
		if ( is_array( $value ) ) {
			$value = $value['value'] ?? null;
		}
		return is_string( $value ) && in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	/**
	 * Returns a boolean attribute; anything but a boolean gives the default.
	 *
	 * Shared with the text blocks (Text_Blocks).
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value    Attribute value.
	 * @param bool  $fallback Default.
	 * @return bool Value.
	 */
	public static function flag( mixed $value, bool $fallback ): bool {
		return is_bool( $value ) ? $value : $fallback;
	}
}
