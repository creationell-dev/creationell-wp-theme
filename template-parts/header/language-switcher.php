<?php
/**
 * Template part for the language switcher in the header actions.
 *
 * Prints the dropdown of creationell_wp_theme_language_switcher() in the
 * context "navbar"; without WPML or with fewer than two languages it prints
 * nothing.
 *
 * @package Creationell\WpTheme
 */

use Creationell\WpTheme\Wpml\Language_Switcher;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_language_switcher = creationell_wp_theme_language_switcher(
	array(
		'context' => 'navbar',
		'variant' => 'dropdown',
	)
);
if ( '' === $creationell_wp_theme_language_switcher ) {
	return;
}

/**
 * Filters the CSS classes that set the spacing of an element in the header actions.
 *
 * @since 1.0.0
 *
 * @param string $classes Space-separated CSS classes.
 * @param string $element Header element, for example search-toggler.
 */
$creationell_wp_theme_class_header_action_spacer_language = apply_filters( 'creationell_wp_theme_class_header_action_spacer', 'ms-1 ms-md-2', 'language-switcher' );
?>
<div class="<?php echo esc_attr( $creationell_wp_theme_class_header_action_spacer_language ); ?>">
	<?php echo wp_kses( $creationell_wp_theme_language_switcher, Language_Switcher::allowed_html() ); ?>
</div>
