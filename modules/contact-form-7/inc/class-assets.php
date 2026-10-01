<?php
/**
 * Stylesheet and script of the module contact-form-7.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\ContactForm7;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the form stylesheet on every front-end page and the focus script together with the script of Contact Form 7.
 *
 * Both use the handle creationell-wp-theme-contact-form-7 and the theme version.
 * The stylesheet uses logical properties only, so it needs no right-to-left
 * copy. The script carries no texts and needs no translations.
 *
 * @since 1.0.0
 */
final class Assets {

	/**
	 * Handle of the stylesheet and of the script.
	 *
	 * @since 1.0.0
	 */
	public const HANDLE = 'creationell-wp-theme-contact-form-7';

	/**
	 * Stylesheet, relative to the module folder.
	 *
	 * @since 1.0.0
	 */
	public const STYLESHEET = 'assets/css/contact-form-7.css';

	/**
	 * Script, relative to the module folder.
	 *
	 * @since 1.0.0
	 */
	public const SCRIPT = 'assets/js/contact-form-7.js';

	/**
	 * Handle of the compiled theme stylesheet, loaded before the module stylesheet.
	 *
	 * @since 1.0.0
	 */
	public const THEME_STYLE_HANDLE = 'creationell-wp-theme-main';

	/**
	 * Handle of the script of Contact Form 7, loaded before the module script.
	 *
	 * @since 1.0.0
	 */
	public const CF7_SCRIPT_HANDLE = 'contact-form-7';

	/**
	 * URL of the module folder, without trailing slash.
	 *
	 * @var string
	 */
	private string $url;

	/**
	 * Takes the URL of the module folder.
	 *
	 * @since 1.0.0
	 *
	 * @param string $module_url URL of modules/contact-form-7.
	 */
	public function __construct( string $module_url ) {
		$this->url = rtrim( $module_url, '/' );
	}

	/**
	 * Returns the URL of the module folder.
	 *
	 * @since 1.0.0
	 *
	 * @return string URL without trailing slash.
	 */
	public function url(): string {
		return $this->url;
	}

	/**
	 * Enqueues the stylesheet after the theme stylesheet; runs on wp_enqueue_scripts, never in the admin.
	 *
	 * Forms can sit in any template part, widget or pattern, so the stylesheet
	 * loads on every front-end page while the module is active.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_style(): void {
		if ( is_admin() ) {
			return;
		}
		wp_enqueue_style( self::HANDLE, $this->url . '/' . self::STYLESHEET, array( self::THEME_STYLE_HANDLE ), CREATIONELL_WP_THEME_VERSION );
	}

	/**
	 * Enqueues the script deferred in the footer after the script of Contact Form 7; runs on wpcf7_enqueue_scripts.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_script(): void {
		wp_enqueue_script(
			self::HANDLE,
			$this->url . '/' . self::SCRIPT,
			array( self::CF7_SCRIPT_HANDLE ),
			CREATIONELL_WP_THEME_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}
}
