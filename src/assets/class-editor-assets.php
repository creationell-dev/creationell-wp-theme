<?php
/**
 * Styles of the block editor.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Assets;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the theme styles into the editor canvas and the color variables into the block editor screens.
 *
 * The theme sets the support editor-styles but never calls add_editor_style:
 * for right-to-left languages WordPress would request a second -rtl.css file
 * next to each editor style. The canvas gets the main stylesheet, the icon font
 * under the same condition as in the front end and assets/css/editor-canvas.css
 * on enqueue_block_assets instead; the writing direction follows the language
 * of the signed-in user. The canvas gets no script.
 *
 * - creationell-wp-theme-editor-canvas: assets/css/editor-canvas.css, after the main stylesheet.
 * - creationell-wp-theme-editor-color-vars: the root custom properties of the main stylesheet
 *   on block editor screens, so the var(--bs-*) colors of theme.json resolve in swatches and
 *   color pickers outside the canvas.
 *
 * @since 1.0.0
 */
final class Editor_Assets {

	/**
	 * Handle of the canvas corrections.
	 *
	 * @since 1.0.0
	 */
	public const HANDLE_CANVAS = 'creationell-wp-theme-editor-canvas';

	/**
	 * Handle of the root custom properties on the block editor screens.
	 *
	 * @since 1.0.0
	 */
	public const HANDLE_COLOR_VARS = 'creationell-wp-theme-editor-color-vars';

	/**
	 * Hand-written canvas corrections, relative to the theme folder.
	 *
	 * @since 1.0.0
	 */
	public const CANVAS_STYLESHEET = 'assets/css/editor-canvas.css';

	/**
	 * Enqueues the theme styles into the editor canvas; runs on enqueue_block_assets with priority 5.
	 *
	 * The hook also fires in the front end, which has its own callback, and inside
	 * admin_enqueue_scripts for the editor document around the canvas; the theme
	 * stylesheet would restyle the editor interface there, so both cases add nothing.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function enqueue_canvas(): void {
		if ( ! is_admin() || doing_action( 'admin_enqueue_scripts' ) ) {
			return;
		}
		Assets::enqueue_main_style();
		Assets::enqueue_icon_font();
		wp_enqueue_style( self::HANDLE_CANVAS, get_template_directory_uri() . '/' . self::CANVAS_STYLESHEET, array( Assets::HANDLE_MAIN ), Stylesheet_Locator::theme_version() );
	}

	/**
	 * Enqueues the root custom properties on block editor screens; runs on admin_enqueue_scripts with priority 10.
	 *
	 * Adds nothing on other screens or while the build has not written
	 * root-vars.min.css yet.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $hook_suffix Suffix of the admin screen, unused.
	 * @return void
	 */
	public static function enqueue_color_vars( mixed $hook_suffix = '' ): void {
		unset( $hook_suffix );
		$screen = get_current_screen();
		if ( null === $screen || ! $screen->is_block_editor() ) {
			return;
		}
		$vars = Stylesheet_Locator::root_vars();
		if ( '' === $vars['version'] ) {
			return;
		}
		wp_enqueue_style( self::HANDLE_COLOR_VARS, $vars['url'], array(), $vars['version'] );
	}
}
