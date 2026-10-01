<?php
/**
 * Template part for the contact data of the theme settings, printed by the block creationell-theme/contact.
 *
 * Prints a heading and an <address> with the postal address, the phone number
 * and the email address; a part without value stays out, without any value
 * the part prints nothing. The block reads the values from the settings; a
 * child theme overrides this part at the same path.
 *
 * Arguments ($args):
 * - heading: text of the heading.
 * - level: heading level, 2 to 6 (2).
 * - address: postal address, line breaks become <br>.
 * - phone: phone number as typed.
 * - phone_href: tel: link of the number; empty prints the number as text.
 * - email: email address, hidden from address harvesters; link and text share one encoding.
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_contact = array();
foreach ( array( 'heading', 'address', 'phone', 'phone_href', 'email' ) as $creationell_wp_theme_contact_key ) {
	$creationell_wp_theme_contact[ $creationell_wp_theme_contact_key ] = isset( $args[ $creationell_wp_theme_contact_key ] ) && is_string( $args[ $creationell_wp_theme_contact_key ] ) ? trim( $args[ $creationell_wp_theme_contact_key ] ) : '';
}
$creationell_wp_theme_contact_href  = esc_url( $creationell_wp_theme_contact['phone_href'] );
$creationell_wp_theme_contact_level = isset( $args['level'] ) && in_array( $args['level'], array( 2, 3, 4, 5, 6 ), true ) ? (int) $args['level'] : 2;
// One encoding for link and text: antispambot() encodes at random.
$creationell_wp_theme_contact_email = '' !== $creationell_wp_theme_contact['email'] ? antispambot( $creationell_wp_theme_contact['email'] ) : '';

if ( '' === $creationell_wp_theme_contact['address'] && '' === $creationell_wp_theme_contact['phone'] && '' === $creationell_wp_theme_contact['email'] ) {
	return;
}
?>
<div class="creationell-theme-contact">
	<?php if ( '' !== $creationell_wp_theme_contact['heading'] ) : ?>
		<?php printf( '<h%1$d class="h5">%2$s</h%1$d>', (int) $creationell_wp_theme_contact_level, esc_html( $creationell_wp_theme_contact['heading'] ) ); ?>
	<?php endif; ?>
	<address class="mb-0">
		<?php if ( '' !== $creationell_wp_theme_contact['address'] ) : ?>
		<p class="mb-2"><?php echo nl2br( esc_html( $creationell_wp_theme_contact['address'] ) ); ?></p>
		<?php endif; ?>
		<?php if ( '' !== $creationell_wp_theme_contact['phone'] && '' !== $creationell_wp_theme_contact_href ) : ?>
		<p class="mb-0"><a href="<?php echo esc_url( $creationell_wp_theme_contact['phone_href'] ); ?>"><?php echo esc_html( $creationell_wp_theme_contact['phone'] ); ?></a></p>
		<?php elseif ( '' !== $creationell_wp_theme_contact['phone'] ) : ?>
		<p class="mb-0"><?php echo esc_html( $creationell_wp_theme_contact['phone'] ); ?></p>
		<?php endif; ?>
		<?php if ( '' !== $creationell_wp_theme_contact['email'] ) : ?>
		<p class="mb-0"><a href="<?php echo esc_url( 'mailto:' . $creationell_wp_theme_contact_email ); ?>"><?php echo esc_html( $creationell_wp_theme_contact_email ); ?></a></p>
		<?php endif; ?>
	</address>
</div>
