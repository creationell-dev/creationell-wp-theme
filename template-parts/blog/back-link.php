<?php
/**
 * Template part: the link back to the blog page in the header of a single post.
 *
 * The arrow is decoration and hidden from screen readers; the stylesheet of
 * the module mirrors it for right-to-left languages. The module blog prints
 * this part on the action creationell_wp_theme_entry_header_open.
 *
 * Arguments (raw values, escaped here): url (URL of the blog page; empty
 * prints nothing), text (plain text of the link, for example "Back to News";
 * empty gives "Back to the blog"), context (template, for example single).
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_args = isset( $args ) && is_array( $args ) ? $args : array();
$creationell_wp_theme_url  = is_string( $creationell_wp_theme_args['url'] ?? null ) ? $creationell_wp_theme_args['url'] : '';
$creationell_wp_theme_text = is_string( $creationell_wp_theme_args['text'] ?? null ) ? trim( $creationell_wp_theme_args['text'] ) : '';
$creationell_wp_theme_text = '' === $creationell_wp_theme_text ? __( 'Back to the blog', 'creationell-wp-theme' ) : $creationell_wp_theme_text;
if ( '' === $creationell_wp_theme_url ) {
	return;
}
?>
<p class="back-link"><a class="icon-link" href="<?php echo esc_url( $creationell_wp_theme_url ); ?>"><?php creationell_wp_theme_icon( 'arrow-left' ); ?><?php echo esc_html( $creationell_wp_theme_text ); ?></a></p>
