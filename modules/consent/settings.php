<?php
/**
 * Settings of the module consent: texts of the banner and the dialog, the imprint page, the revision and the cookie lifetime.
 *
 * Loaded by Registry::load_modules() for the installed module, whatever its
 * state; the file returns a list of Definition objects and declares nothing, so
 * it may run more than once per request. Empty texts show the default texts of
 * the module. The three texts differ per language; the imprint page is one ID,
 * which WPML maps to the page of the language. Revision and cookie lifetime are
 * advanced settings of the agency: a higher revision asks every visitor again.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Settings\Definition;
use Creationell\WpTheme\Settings\Registry;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return array(
	new Definition(
		key: 'consent_banner_title',
		type: 'string',
		default_value: '',
		capability: Registry::CAP_BASIC,
		translatable: true,
		section: 'texts',
		group: 'consent',
		max_length: 120,
		label: static fn(): string => __( 'Cookie banner title', 'creationell-wp-theme' ),
		description: static fn(): string => __( 'Empty shows "We use cookies".', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'consent_banner_text',
		type: 'html_inline',
		default_value: '',
		capability: Registry::CAP_BASIC,
		translatable: true,
		section: 'texts',
		group: 'consent',
		max_length: 1000,
		label: static fn(): string => __( 'Cookie banner text', 'creationell-wp-theme' ),
		description: static fn(): string => __( 'Links, bold, italic and line breaks are allowed. Empty shows the default text.', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'consent_dialog_text',
		type: 'html_inline',
		default_value: '',
		capability: Registry::CAP_BASIC,
		translatable: true,
		section: 'texts',
		group: 'consent',
		max_length: 2000,
		label: static fn(): string => __( 'Cookie settings introduction', 'creationell-wp-theme' ),
		description: static fn(): string => __( 'First paragraph of the cookie settings dialog. Links, bold, italic and line breaks are allowed. Empty shows the default text.', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'consent_imprint_page',
		type: 'int',
		default_value: 0,
		capability: Registry::CAP_BASIC,
		reference_type: 'page',
		section: 'texts',
		group: 'consent',
		validate: static fn( mixed $value ): bool => is_int( $value ) && $value >= 0,
		label: static fn(): string => __( 'Imprint page', 'creationell-wp-theme' ),
		description: static fn(): string => __( 'Linked in the cookie banner next to the privacy policy page of the site. None hides the link.', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'consent_revision',
		type: 'int',
		default_value: 0,
		level: 'advanced',
		capability: Registry::CAP_DESIGN,
		section: 'design',
		group: 'consent',
		validate: static fn( mixed $value ): bool => is_int( $value ) && $value >= 0 && $value <= 9999,
		label: static fn(): string => __( 'Consent revision', 'creationell-wp-theme' ),
		description: static fn(): string => __( 'Raise the number after a change of the cookies: every visitor is asked again. From 0 to 9999.', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'consent_cookie_days',
		type: 'int',
		default_value: 182,
		level: 'advanced',
		capability: Registry::CAP_DESIGN,
		section: 'design',
		group: 'consent',
		validate: static fn( mixed $value ): bool => is_int( $value ) && $value >= 30 && $value <= 395,
		label: static fn(): string => __( 'Consent lifetime in days', 'creationell-wp-theme' ),
		description: static fn(): string => __( 'How long the choice of a visitor is kept, from 30 to 395 days.', 'creationell-wp-theme' ),
	),
);
