<?php
/**
 * Text settings of the theme: footer text, contact data and social links.
 *
 * Loaded by Registry::core_definitions(); the file returns a list of Definition
 * objects and declares nothing, so it may run more than once per request.
 * The footer text and the address differ per language; phone, email and the
 * social links are the same in every language.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

use Creationell\WpTheme\Settings\Definition;
use Creationell\WpTheme\Settings\Registry;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_social = array(
	'facebook'  => static fn(): string => __( 'Facebook', 'creationell-wp-theme' ),
	'instagram' => static fn(): string => __( 'Instagram', 'creationell-wp-theme' ),
	'linkedin'  => static fn(): string => __( 'LinkedIn', 'creationell-wp-theme' ),
	'xing'      => static fn(): string => __( 'XING', 'creationell-wp-theme' ),
	'youtube'   => static fn(): string => __( 'YouTube', 'creationell-wp-theme' ),
	'mastodon'  => static fn(): string => __( 'Mastodon', 'creationell-wp-theme' ),
);

$creationell_wp_theme_texts = array(
	new Definition(
		key: 'footer_text',
		type: 'html_inline',
		default_value: '',
		capability: Registry::CAP_BASIC,
		translatable: true,
		section: 'texts',
		group: 'footer',
		max_length: 500,
		label: static fn(): string => __( 'Footer text', 'creationell-wp-theme' ),
		description: static fn(): string => __( 'Links, bold, italic and line breaks are allowed. Empty shows the copyright line.', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'contact_address',
		type: 'text',
		default_value: '',
		capability: Registry::CAP_BASIC,
		translatable: true,
		section: 'texts',
		group: 'contact',
		max_length: 300,
		label: static fn(): string => __( 'Address', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'contact_phone',
		type: 'tel',
		default_value: '',
		capability: Registry::CAP_BASIC,
		section: 'texts',
		group: 'contact',
		max_length: 50,
		label: static fn(): string => __( 'Phone', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'contact_email',
		type: 'email',
		default_value: '',
		capability: Registry::CAP_BASIC,
		section: 'texts',
		group: 'contact',
		max_length: 254,
		label: static fn(): string => __( 'Email', 'creationell-wp-theme' ),
	),
);

foreach ( $creationell_wp_theme_social as $creationell_wp_theme_network => $creationell_wp_theme_label ) {
	$creationell_wp_theme_texts[] = new Definition(
		key: 'social_' . $creationell_wp_theme_network,
		type: 'url',
		default_value: '',
		capability: Registry::CAP_BASIC,
		section: 'texts',
		group: 'social',
		max_length: 2048,
		label: $creationell_wp_theme_label,
	);
}

return $creationell_wp_theme_texts;
