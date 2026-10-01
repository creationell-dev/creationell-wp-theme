<?php
/**
 * TinyMCE editor.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Keeps the body margin and font family of the TinyMCE editor that the Bootstrap editor styles override.
 *
 * See https://github.com/bootscore/bootscore/issues/442, https://github.com/bootscore/bootscore/pull/757
 * and https://github.com/bootscore/bootscore/issues/934.
 *
 * @since 1.0.0
 *
 * @param mixed $settings TinyMCE settings.
 * @return mixed Settings with the extra content style, other values unchanged.
 */
function creationell_wp_theme_tiny_mce_content_style( mixed $settings ): mixed {
	if ( ! is_array( $settings ) ) {
		return $settings;
	}

	$content_style = 'body.mce-content-body { margin: 9px 10px; font-family: inherit; height: auto; color: inherit; background: inherit; background-color: inherit; background-image: none; }';

	if ( isset( $settings['content_style'] ) && is_string( $settings['content_style'] ) ) {
		$settings['content_style'] .= ' ' . $content_style;
	} else {
		$settings['content_style'] = $content_style;
	}

	return $settings;
}
add_filter( 'tiny_mce_before_init', 'creationell_wp_theme_tiny_mce_content_style' );
