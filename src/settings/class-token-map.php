<?php
/**
 * Map of the design settings to the SCSS variables and theme.json presets of a Bootstrap line.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Tells which SCSS variable and which theme.json preset a design setting feeds, per Bootstrap line.
 *
 * The defaults of these settings are the !default values of the variables
 * (checked by a test); a translatable setting never appears here.
 * The individual stylesheet and the theme.json tokens read the map.
 *
 * @since 1.0.0
 */
final class Token_Map {

	/**
	 * Map of line 5: setting key => SCSS variable without "$" and preset slug (null without preset).
	 *
	 * @since 1.0.0
	 */
	private const LINE_5 = array(
		'color_primary'   => array( 'primary', 'primary' ),
		'color_secondary' => array( 'secondary', 'secondary' ),
		'color_success'   => array( 'success', 'success' ),
		'color_info'      => array( 'info', 'info' ),
		'color_warning'   => array( 'warning', 'warning' ),
		'color_danger'    => array( 'danger', 'danger' ),
		'color_light'     => array( 'light', 'light' ),
		'color_dark'      => array( 'dark', 'dark' ),
		'color_body_text' => array( 'body-color', null ),
		'color_body_bg'   => array( 'body-bg', null ),
		'font_body'       => array( 'font-family-sans-serif', 'body' ),
		'font_headings'   => array( 'headings-font-family', 'headings' ),
	);

	/**
	 * Returns the map of a line.
	 *
	 * @since 1.0.0
	 *
	 * @param int $line Bootstrap line.
	 * @return array<string, array{variable: string, preset: string|null}> Variable and preset by setting key; empty for a line this version does not map.
	 */
	public static function for_line( int $line ): array {
		if ( 5 !== $line ) {
			return array();
		}
		$map = array();
		foreach ( self::LINE_5 as $key => $token ) {
			$map[ $key ] = array(
				'variable' => $token[0],
				'preset'   => $token[1],
			);
		}
		return $map;
	}
}
