<?php
/**
 * Template of the block creationell-theme/consent-link: the button that opens the cookie settings.
 *
 * Blockstudio includes this file with the attributes in $a (also $attributes)
 * and $isEditor. The attributes of WordPress (additional CSS classes, anchor)
 * are not among them: Blockstudio adds them to the element with useBlockProps.
 * The block renders nothing while another or no consent
 * provider is effective. On the page the button stays hidden until
 * CookieConsent runs; in the block editor it is visible.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Modules\Consent\Links;
use Creationell\WpTheme\Modules\Consent\Provider;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( Provider::class ) || ! Provider::is_effective() ) {
	return;
}

$creationell_wp_theme_vars       = get_defined_vars();
$creationell_wp_theme_attributes = $creationell_wp_theme_vars['a'] ?? ( $creationell_wp_theme_vars['attributes'] ?? array() );
$creationell_wp_theme_attributes = is_array( $creationell_wp_theme_attributes ) ? $creationell_wp_theme_attributes : array();
$creationell_wp_theme_label      = is_string( $creationell_wp_theme_attributes['label'] ?? null ) ? $creationell_wp_theme_attributes['label'] : '';
$creationell_wp_theme_appearance = $creationell_wp_theme_attributes['appearance'] ?? 'link';
$creationell_wp_theme_appearance = is_array( $creationell_wp_theme_appearance ) ? ( $creationell_wp_theme_appearance['value'] ?? 'link' ) : $creationell_wp_theme_appearance;
$creationell_wp_theme_appearance = is_string( $creationell_wp_theme_appearance ) ? $creationell_wp_theme_appearance : 'link';

$creationell_wp_theme_editor = $creationell_wp_theme_vars['isEditor'] ?? false;

// Blockstudio replaces useBlockProps with the block wrapper attributes: additional CSS classes and the anchor as id.
echo '<div useBlockProps class="wp-block-creationell-theme-consent-link">';
echo wp_kses( Links::button( $creationell_wp_theme_label, $creationell_wp_theme_appearance, true !== $creationell_wp_theme_editor ), Links::ALLOWED_HTML );
echo '</div>';
