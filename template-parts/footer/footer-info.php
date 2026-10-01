<?php
/**
 * Template part for the footer info: the footer text of the theme settings or the copyright line.
 *
 * Prints the setting footer_text in the language of the request, with links,
 * bold, italic and line breaks only; an empty text prints the copyright line
 * with the year and the site name. The action creationell_wp_theme_footer_meta
 * follows, for further links such as the cookie settings. footer.php includes
 * this part; a child theme overrides it at the same path.
 *
 * @package Creationell\WpTheme
 */

use Creationell\WpTheme\Settings\Sanitizer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_footer_text = creationell_wp_theme_setting( 'footer_text' );
$creationell_wp_theme_footer_text = is_string( $creationell_wp_theme_footer_text ) ? trim( $creationell_wp_theme_footer_text ) : '';
?>
<?php if ( '' !== $creationell_wp_theme_footer_text ) : ?>
<div class="small creationell-theme-footer-text"><?php echo wp_kses( $creationell_wp_theme_footer_text, Sanitizer::INLINE_HTML, Sanitizer::INLINE_PROTOCOLS ); ?></div>
<?php else : ?>
<div class="small creationell-theme-copyright"><span class="cr-symbol">&copy;</span>&nbsp;<?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></div>
<?php endif; ?>
<?php
/**
 * Fires after the footer text or the copyright line in the footer info.
 *
 * Prints further links of the footer info, for example the link that opens
 * the cookie settings again.
 *
 * @since 1.0.0
 */
do_action( 'creationell_wp_theme_footer_meta' );
