<?php
/**
 * Design settings of the theme: the Bootstrap theme colors, the body colors and the two fonts.
 *
 * Loaded by Registry::core_definitions(); the file returns a list of Definition
 * objects and declares nothing, so it may run more than once per request.
 * Colors are "#rrggbb" in lower case; the defaults are the !default values of
 * the variables of the Bootstrap line (Token_Map), from _theme-variables.scss
 * where the theme sets an accessible value. Fonts are slugs of the font catalog;
 * "inherit" keeps the body font for the headings.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

use Creationell\WpTheme\Settings\Definition;
use Creationell\WpTheme\Settings\Registry;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return array(
	new Definition(
		key: 'color_primary',
		type: 'color',
		default_value: '#0b5ed7',
		level: 'advanced',
		capability: Registry::CAP_DESIGN,
		a11y_rule: 'contrast',
		section: 'design',
		group: 'colors',
		label: static fn(): string => __( 'Primary color', 'creationell-wp-theme' ),
		description: static fn(): string => __( 'Buttons, links and highlights.', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'color_secondary',
		type: 'color',
		default_value: '#5c636a',
		level: 'advanced',
		capability: Registry::CAP_DESIGN,
		a11y_rule: 'contrast',
		section: 'design',
		group: 'colors',
		label: static fn(): string => __( 'Secondary color', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'color_success',
		type: 'color',
		default_value: '#198754',
		level: 'advanced',
		capability: Registry::CAP_DESIGN,
		a11y_rule: 'contrast',
		section: 'design',
		group: 'colors',
		label: static fn(): string => __( 'Success color', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'color_info',
		type: 'color',
		default_value: '#0dcaf0',
		level: 'advanced',
		capability: Registry::CAP_DESIGN,
		a11y_rule: 'contrast',
		section: 'design',
		group: 'colors',
		label: static fn(): string => __( 'Info color', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'color_warning',
		type: 'color',
		default_value: '#ffc107',
		level: 'advanced',
		capability: Registry::CAP_DESIGN,
		a11y_rule: 'contrast',
		section: 'design',
		group: 'colors',
		label: static fn(): string => __( 'Warning color', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'color_danger',
		type: 'color',
		default_value: '#dc3545',
		level: 'advanced',
		capability: Registry::CAP_DESIGN,
		a11y_rule: 'contrast',
		section: 'design',
		group: 'colors',
		label: static fn(): string => __( 'Danger color', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'color_light',
		type: 'color',
		default_value: '#f8f9fa',
		level: 'advanced',
		capability: Registry::CAP_DESIGN,
		a11y_rule: 'contrast',
		section: 'design',
		group: 'colors',
		label: static fn(): string => __( 'Light color', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'color_dark',
		type: 'color',
		default_value: '#212529',
		level: 'advanced',
		capability: Registry::CAP_DESIGN,
		a11y_rule: 'contrast',
		section: 'design',
		group: 'colors',
		label: static fn(): string => __( 'Dark color', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'color_body_text',
		type: 'color',
		default_value: '#212529',
		level: 'advanced',
		capability: Registry::CAP_DESIGN,
		a11y_rule: 'contrast',
		section: 'design',
		group: 'colors',
		label: static fn(): string => __( 'Text color', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'color_body_bg',
		type: 'color',
		default_value: '#ffffff',
		level: 'advanced',
		capability: Registry::CAP_DESIGN,
		a11y_rule: 'contrast',
		section: 'design',
		group: 'colors',
		label: static fn(): string => __( 'Background color', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'font_body',
		type: 'font',
		default_value: 'system-sans',
		level: 'advanced',
		capability: Registry::CAP_DESIGN,
		section: 'design',
		group: 'fonts',
		label: static fn(): string => __( 'Body font', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'font_headings',
		type: 'font',
		default_value: 'inherit',
		level: 'advanced',
		capability: Registry::CAP_DESIGN,
		section: 'design',
		group: 'fonts',
		label: static fn(): string => __( 'Headings font', 'creationell-wp-theme' ),
		description: static fn(): string => __( 'Without an own font the headings use the body font.', 'creationell-wp-theme' ),
	),
);
