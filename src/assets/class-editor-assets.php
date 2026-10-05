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
 * The widget editors (Widgets screen, Customizer) render without iframe, so
 * enqueue_block_assets reaches only the document around the blocks there. They
 * get main stylesheet and icon font as editor styles, which WordPress scopes to
 * the block area (widget_editor_styles()), and the canvas corrections, scoped
 * to .editor-styles-wrapper already, as a stylesheet (enqueue_widget_canvas()).
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
	 * Editor contexts that render the blocks without iframe.
	 *
	 * @since 1.0.0
	 */
	public const WIDGET_CONTEXTS = array( 'core/edit-widgets', 'core/customize-widgets' );

	/**
	 * Adds the theme styles to the editor styles of the widget editors; runs on block_editor_settings_all.
	 *
	 * The widget editors render the blocks in the admin document, where the theme
	 * stylesheet would restyle the interface. WordPress scopes editor styles to the
	 * block area with :where(.editor-styles-wrapper) in front of every selector;
	 * without them its reset set the blocks to font-family serif. Main stylesheet
	 * (right-to-left for such languages) and the icon font under the same condition
	 * as in the front end follow the given styles, each with its folder as base URL
	 * for fonts and images. The canvas corrections start with .editor-styles-wrapper
	 * and would match nothing behind that prefix; enqueue_widget_canvas() loads them.
	 * Other editors keep their settings: their iframe gets the styles through
	 * enqueue_canvas().
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $settings Block editor settings.
	 * @param mixed $context  Block editor context.
	 * @return mixed The settings with the theme styles in the widget editors, otherwise unchanged.
	 */
	public static function widget_editor_styles( mixed $settings, mixed $context ): mixed {
		if ( ! is_array( $settings ) || ! $context instanceof \WP_Block_Editor_Context || ! in_array( $context->name, self::WIDGET_CONTEXTS, true ) ) {
			return $settings;
		}
		$main  = Stylesheet_Locator::main();
		$files = array( array( $main['path'], $main['url'] ) );
		if ( is_rtl() && is_readable( Stylesheet_Locator::rtl_path( $main['path'] ) ) ) {
			$files = array( array( Stylesheet_Locator::rtl_path( $main['path'] ), Stylesheet_Locator::rtl_path( $main['url'] ) ) );
		}
		if ( Assets::load_icon_font() ) {
			$files[] = array( get_template_directory() . '/' . Assets::ICONS_STYLESHEET, get_template_directory_uri() . '/' . Assets::ICONS_STYLESHEET );
		}

		$styles = is_array( $settings['styles'] ?? null ) ? $settings['styles'] : array();
		foreach ( $files as $file ) {
			$css = self::read( $file[0] );
			if ( '' === $css ) {
				continue;
			}
			$styles[] = array(
				'css'            => $css,
				'baseURL'        => dirname( $file[1] ) . '/',
				'__unstableType' => 'theme',
			);
		}
		$settings['styles'] = $styles;
		return $settings;
	}

	/**
	 * Enqueues the canvas corrections on the Widgets screen and in the Customizer; runs on admin_enqueue_scripts and customize_controls_enqueue_scripts.
	 *
	 * Every rule of the file starts with .editor-styles-wrapper, so it styles only
	 * the block area of the widget editors there; without the block editor (plugin
	 * Classic Widgets) nothing matches.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $hook_suffix Suffix of the admin screen; none in the Customizer.
	 * @return void
	 */
	public static function enqueue_widget_canvas( mixed $hook_suffix = '' ): void {
		if ( 'widgets.php' !== $hook_suffix && ! doing_action( 'customize_controls_enqueue_scripts' ) ) {
			return;
		}
		wp_enqueue_style( self::HANDLE_CANVAS, get_template_directory_uri() . '/' . self::CANVAS_STYLESHEET, array(), Stylesheet_Locator::theme_version() );
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

	/**
	 * Reads a local file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file Absolute path.
	 * @return string Content; empty when the file cannot be read.
	 */
	private static function read( string $file ): string {
		$lines = is_file( $file ) ? file( $file ) : false;
		return false === $lines ? '' : implode( '', $lines );
	}
}
