<?php
/**
 * Hooks of the module contact-form-7.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\ContactForm7;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Connects the module to Contact Form 7.
 *
 * Hooks: own stylesheet instead of the plugin stylesheet, no autop in forms,
 * the filter of the form markup and the focus script.
 *
 * The module guards itself: it adds no hook without Contact Form 7 6.0 or later
 * and none while the plugin bs Contact Form 7 is still active. Contact Form 7
 * then keeps its own look.
 *
 * @since 1.0.0
 */
final class Module {

	/**
	 * Lowest version of Contact Form 7 the module supports.
	 *
	 * @since 1.0.0
	 */
	public const MIN_CF7_VERSION = '6.0';

	/**
	 * Script function of the plugin bs Contact Form 7; its presence keeps the module out.
	 *
	 * @since 1.0.0
	 */
	public const BS_CF7_FUNCTION = 'bootscore_cf7_scripts';

	/**
	 * Filter that keeps the autop of Contact Form 7 on in forms.
	 *
	 * @since 1.0.0
	 */
	public const FILTER_AUTOP = 'creationell_wp_theme_cf7_autop';

	/**
	 * Absolute path of the module folder, without trailing slash.
	 *
	 * @var string
	 */
	private string $dir;

	/**
	 * URL of the module folder, without trailing slash.
	 *
	 * @var string
	 */
	private string $url;

	/**
	 * Stylesheet and script of the module.
	 *
	 * @var Assets
	 */
	private Assets $assets;

	/**
	 * Takes the folder and the URL of the module.
	 *
	 * @since 1.0.0
	 *
	 * @param string $module_dir Absolute path of modules/contact-form-7.
	 * @param string $module_url URL of modules/contact-form-7.
	 */
	public function __construct( string $module_dir, string $module_url ) {
		$this->dir    = rtrim( $module_dir, '/' );
		$this->url    = rtrim( $module_url, '/' );
		$this->assets = new Assets( $this->url );
	}

	/**
	 * Adds the hooks of the module when Contact Form 7 6.0 or later runs without bs Contact Form 7.
	 *
	 * Hooks: wpcf7_load_css (off), wpcf7_autop_or_not (off in forms),
	 * wpcf7_form_elements with priority 20 (form markup, after other plugins
	 * on 10), wp_enqueue_scripts (stylesheet) and wpcf7_enqueue_scripts (script,
	 * only when Contact Form 7 loads its own script).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! self::supported() ) {
			return;
		}
		add_filter( 'wpcf7_load_css', '__return_false', 10, 0 );
		add_filter( 'wpcf7_autop_or_not', array( self::class, 'autop' ), 10, 2 );
		add_filter( 'wpcf7_form_elements', array( Form_Markup::class, 'filter' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this->assets, 'enqueue_style' ), 10, 0 );
		add_action( 'wpcf7_enqueue_scripts', array( $this->assets, 'enqueue_script' ), 10, 0 );
	}

	/**
	 * Tells whether the module may hook into Contact Form 7.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True with Contact Form 7 6.0 or later and without bs Contact Form 7.
	 */
	public static function supported(): bool {
		$version = defined( 'WPCF7_VERSION' ) ? constant( 'WPCF7_VERSION' ) : null;
		if ( ! is_string( $version ) || version_compare( $version, self::MIN_CF7_VERSION, '<' ) ) {
			return false;
		}
		return ! function_exists( self::BS_CF7_FUNCTION );
	}

	/**
	 * Decides whether Contact Form 7 applies autop; runs on wpcf7_autop_or_not.
	 *
	 * Mails keep the value of Contact Form 7. Forms follow the filter
	 * creationell_wp_theme_cf7_autop, false by default, because the form
	 * templates bring their own paragraphs.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $autop   Value of Contact Form 7, WPCF7_AUTOP by default.
	 * @param mixed $options Options of Contact Form 7; the key "for" is "form" or "mail".
	 * @return bool True when autop applies.
	 */
	public static function autop( mixed $autop, mixed $options ): bool {
		if ( is_array( $options ) && 'mail' === ( $options['for'] ?? null ) ) {
			return (bool) $autop;
		}

		/**
		 * Filters whether Contact Form 7 adds paragraphs and line breaks (autop) to the form markup.
		 *
		 * The theme switches autop off in forms, because the form templates bring
		 * their own paragraphs; mails are not affected. Values other than true or
		 * false give false and a notice.
		 *
		 * Example, in the functions.php of a child theme:
		 *
		 *     add_filter( 'creationell_wp_theme_cf7_autop', '__return_true' );
		 *
		 * @since 1.0.0
		 *
		 * @param bool $autop Whether autop applies in forms; false by default.
		 */
		$filtered = apply_filters( 'creationell_wp_theme_cf7_autop', false );
		if ( ! is_bool( $filtered ) ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html( sprintf( 'The filter %s must return true or false; false is used.', self::FILTER_AUTOP ) ),
				'1.0.0'
			);
			return false;
		}
		return $filtered;
	}

	/**
	 * Returns the module folder.
	 *
	 * @since 1.0.0
	 *
	 * @return string Absolute path without trailing slash.
	 */
	public function dir(): string {
		return $this->dir;
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
	 * Returns the stylesheet and script loader of the module; its methods are the hook callbacks.
	 *
	 * @since 1.0.0
	 *
	 * @return Assets Asset loader.
	 */
	public function assets(): Assets {
		return $this->assets;
	}
}
