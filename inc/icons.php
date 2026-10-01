<?php
/**
 * Icons.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Returns the allowed HTML for inline SVG icons.
 *
 * Extends the given allowed tags (for example those of the context "post") with the
 * elements and attributes that Bootstrap Icons use: svg, path, circle and rect.
 *
 * @since 1.0.0
 *
 * @param mixed $tags Allowed tags and attributes as used by wp_kses().
 * @return array<string, array<string, bool>> Allowed tags including the SVG elements.
 */
function creationell_wp_theme_kses_allowed_svg( mixed $tags = array() ): array {

	$shape = array(
		'fill'      => true,
		'fill-rule' => true,
		'transform' => true,
	);

	$svg_tags = array(
		'svg'    => array(
			'class'       => true,
			'xmlns'       => true,
			'viewbox'     => true, // Matched case-insensitively, the output keeps the original casing.
			'width'       => true,
			'height'      => true,
			'fill'        => true,
			'aria-hidden' => true,
			'role'        => true,
			'focusable'   => true,
		),
		'path'   => array( 'd' => true ) + $shape,
		'circle' => array(
			'cx' => true,
			'cy' => true,
			'r'  => true,
		) + $shape,
		'rect'   => array(
			'x'      => true,
			'y'      => true,
			'width'  => true,
			'height' => true,
			'rx'     => true,
			'ry'     => true,
		) + $shape,
	);

	$allowed = array();
	foreach ( is_array( $tags ) ? $tags : array() as $tag => $attributes ) {
		if ( is_string( $tag ) && is_array( $attributes ) ) {
			$allowed[ $tag ] = array_map( 'boolval', $attributes );
		}
	}

	return array_merge( $allowed, $svg_tags );
}


if ( ! function_exists( 'creationell_wp_theme_icon_svg' ) ) {
	/**
	 * Returns the markup of a Bootstrap Icons icon from the icon files of the theme.
	 *
	 * The opening tag is rebuilt: the icon gets the classes creationell-theme-icon, bi and
	 * bi-{name}, is hidden from assistive technology and cannot take the focus. Results are
	 * kept per request.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Icon name of Bootstrap Icons, for example search or chevron-up.
	 * @return string SVG markup, or an empty string for an unknown name.
	 */
	function creationell_wp_theme_icon_svg( string $name ): string {
		static $cache = array();

		if ( 1 !== preg_match( '~^[a-z0-9]+(?:-[a-z0-9]+)*$~', $name ) ) {
			return '';
		}

		$file = get_template_directory() . '/assets/vendor/bootstrap-icons/icons/' . $name . '.svg';
		if ( isset( $cache[ $file ] ) ) {
			return $cache[ $file ];
		}

		$source = is_readable( $file ) ? file_get_contents( get_template_directory() . '/assets/vendor/bootstrap-icons/icons/' . $name . '.svg' ) : false;
		$svg    = '';
		if ( is_string( $source ) && 1 === preg_match( '~<svg\b([^>]*)>(.*)</svg>~s', $source, $parts ) ) {
			$view_box = 1 === preg_match( '~\bviewBox="([0-9. ]+)"~', $parts[1], $box ) ? $box[1] : '0 0 16 16';
			$inner    = trim( (string) preg_replace( '~>\s+<~', '><', $parts[2] ) );
			$svg      = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="' . $view_box . '" class="creationell-theme-icon bi bi-' . $name . '" fill="currentColor" aria-hidden="true" focusable="false">' . $inner . '</svg>';
		}

		$cache[ $file ] = $svg;
		return $svg;
	}
}


if ( ! function_exists( 'creationell_wp_theme_icon' ) ) {
	/**
	 * Outputs or returns a filterable, sanitized SVG icon.
	 *
	 * The icons are the ones of Bootstrap Icons (https://icons.getbootstrap.com/), read
	 * from the theme; the name is the file name without .svg.
	 *
	 * Example:
	 *
	 *     <button type="button" class="btn">
	 *         <?php creationell_wp_theme_icon( 'search' ); ?>
	 *         <span class="visually-hidden"><?php esc_html_e( 'Search', 'my-child' ); ?></span>
	 *     </button>
	 *
	 * @since 1.0.0
	 *
	 * @param string $name    Icon name of Bootstrap Icons and the suffix of the creationell_wp_theme_icon_{name} filter.
	 * @param bool   $display Whether to print the icon (default) in addition to returning it.
	 * @return string Sanitized icon markup, or an empty string for an unknown name.
	 */
	function creationell_wp_theme_icon( string $name, bool $display = true ): string {

		/**
		 * Filters the SVG markup of an icon; the hook name ends with the icon name.
		 *
		 * The markup should keep aria-hidden="true" and focusable="false": icons are
		 * decoration, the text next to them or a label names the control.
		 *
		 * @since 1.0.0
		 *
		 * @param string $svg Default SVG markup of the icon.
		 */
		$svg     = apply_filters( 'creationell_wp_theme_icon_' . $name, creationell_wp_theme_icon_svg( $name ) );
		$allowed = creationell_wp_theme_kses_allowed_svg( wp_kses_allowed_html( 'post' ) );
		$icon    = wp_kses( is_string( $svg ) ? $svg : '', $allowed );

		if ( $display ) {
			echo wp_kses( $icon, $allowed );
		}

		return $icon;
	}
}
