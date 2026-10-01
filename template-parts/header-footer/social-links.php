<?php
/**
 * Template part for the social links of the theme settings, printed by the block creationell-theme/social-links.
 *
 * Prints a named list of links, one per network: an icon hidden from
 * assistive technology and the name of the network for screen readers only.
 * The links open in the same tab and carry rel="me", which lets profiles
 * verify the site. A child theme overrides this part at the same path.
 *
 * Arguments ($args):
 * - label: name of the list, read out by screen readers.
 * - links: list of arrays with name, url and icon (a Bootstrap Icons name);
 *   Social_Networks::links() builds them from the settings.
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_social_label = isset( $args['label'] ) && is_string( $args['label'] ) ? $args['label'] : '';
$creationell_wp_theme_social_links = isset( $args['links'] ) && is_array( $args['links'] ) ? $args['links'] : array();
if ( array() === $creationell_wp_theme_social_links ) {
	return;
}
?>
<ul class="creationell-theme-social-links list-inline mb-0" aria-label="<?php echo esc_attr( $creationell_wp_theme_social_label ); ?>">
	<?php foreach ( $creationell_wp_theme_social_links as $creationell_wp_theme_social_link ) : ?>
		<?php
		if ( ! is_array( $creationell_wp_theme_social_link ) || ! isset( $creationell_wp_theme_social_link['url'], $creationell_wp_theme_social_link['name'] ) || ! is_string( $creationell_wp_theme_social_link['url'] ) || ! is_string( $creationell_wp_theme_social_link['name'] ) ) {
			continue;
		}
		?>
	<li class="list-inline-item"><a class="d-inline-block p-1" href="<?php echo esc_url( $creationell_wp_theme_social_link['url'] ); ?>" rel="me noopener"><?php creationell_wp_theme_icon( isset( $creationell_wp_theme_social_link['icon'] ) && is_string( $creationell_wp_theme_social_link['icon'] ) ? $creationell_wp_theme_social_link['icon'] : 'link-45deg' ); ?><span class="visually-hidden"><?php echo esc_html( $creationell_wp_theme_social_link['name'] ); ?></span></a></li>
	<?php endforeach; ?>
</ul>
