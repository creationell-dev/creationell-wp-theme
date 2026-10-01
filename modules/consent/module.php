<?php
/**
 * Manifest of the module consent: cookie banner and preferences dialog based on CookieConsent.
 *
 * Returns the manifest array that Module_Manifest::from_file() validates. The
 * module registers CookieConsent as the consent provider of the theme core,
 * brings the block creationell-theme/consent-link and replaces the plugin
 * bs Cookie Settings. Without Blockstudio only the block is missing. Title,
 * description and off warning stay closures, so they are translated only when
 * they are shown, after init.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return array(
	'slug'            => 'consent',
	'title'           => static fn(): string => __( 'Cookie consent', 'creationell-wp-theme' ),
	'description'     => static fn(): string => __( 'Cookie banner and preferences dialog based on CookieConsent. Not legally reviewed.', 'creationell-wp-theme' ),
	'type'            => 'block',
	'states'          => array( 'active', 'off' ),
	'default'         => 'off',
	'requires'        => array(
		'plugins'     => array(),
		'modules'     => array(),
		'blockstudio' => false,
	),
	'legacy_plugins'  => array( 'bs-cookie-settings/main.php' ),
	'warn_before_off' => true,
	'off_warning'     => static fn(): string => __( 'The cookie banner, the preferences dialog and the withdrawal link disappear immediately. Marked scripts stay blocked.', 'creationell-wp-theme' ),
	'blocks'          => array( 'creationell-theme/consent-link' ),
	'outposts'        => array(),
	'since'           => '1.0.0',
);
