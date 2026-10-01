<?php
/**
 * Manifest of the module header-footer: header and footer as template parts of the Site Editor.
 *
 * Returns the manifest array that Module_Manifest::from_file() validates. The
 * module brings the blocks of header and footer, the part files, their patterns
 * and the rights of editors in the Site Editor; it has no settings of its own.
 * It needs Blockstudio for its blocks; without Blockstudio it stays off and the
 * PHP header and footer of the theme take over. Title, description and
 * warning stay closures, so they are translated only when they are shown,
 * after init.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return array(
	'slug'            => 'header-footer',
	'title'           => static fn(): string => __( 'Header and footer in the Site Editor', 'creationell-wp-theme' ),
	'description'     => static fn(): string => __( 'Header and footer as template parts that editors change in the Site Editor. While the module is off, the theme shows its own header and footer.', 'creationell-wp-theme' ),
	'type'            => 'block',
	'states'          => array( 'active', 'off' ),
	'default'         => 'off',
	'requires'        => array(
		'plugins'     => array(),
		'modules'     => array(),
		'blockstudio' => '7.6',
	),
	'blocks'          => array(
		'creationell-theme/navbar',
		'creationell-theme/logo',
		'creationell-theme/search',
		'creationell-theme/footer-menu',
		'creationell-theme/footer-text',
		'creationell-theme/contact',
		'creationell-theme/social-links',
		'creationell-theme/language-switcher',
	),
	'outposts'        => array(
		'parts/header.html',
		'parts/footer.html',
		'patterns/header-footer/',
		'template-parts/header-footer/',
	),
	'warn_before_off' => true,
	'off_warning'     => static fn(): string => __( 'Customised header and footer parts stay in the database but are no longer shown.', 'creationell-wp-theme' ),
	'since'           => '1.0.0',
);
