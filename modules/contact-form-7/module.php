<?php
/**
 * Manifest of the module contact-form-7: Bootstrap styling and accessible behaviour for Contact Form 7.
 *
 * Returns the manifest array that Module_Manifest::from_file() validates. The
 * module brings no block and no settings; it needs the plugin Contact Form 7 and
 * replaces the plugin bs Contact Form 7. Title and description stay closures, so
 * they are translated only when they are shown, after init.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return array(
	'slug'           => 'contact-form-7',
	'title'          => static fn(): string => __( 'Contact Form 7 styling', 'creationell-wp-theme' ),
	'description'    => static fn(): string => __( 'Bootstrap styling and accessible behaviour for Contact Form 7 forms. Form templates come from the plugin "Contact Form 7: Accessible Defaults".', 'creationell-wp-theme' ),
	'type'           => 'integration',
	'states'         => array( 'active', 'off' ),
	'default'        => 'off',
	'requires'       => array(
		'plugins'     => array( 'contact-form-7/wp-contact-form-7.php' ),
		'modules'     => array(),
		'blockstudio' => false,
	),
	'legacy_plugins' => array( 'bs-contact-form-7/main.php' ),
	'blocks'         => array(),
	'outposts'       => array(),
	'since'          => '1.0.0',
);
