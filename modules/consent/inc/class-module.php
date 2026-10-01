<?php
/**
 * Hooks of the module consent.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\Consent;

use Creationell\WpTheme\Consent\Consent_Gate;
use Creationell\WpTheme\Modules\Block_I18n;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Connects CookieConsent to the consent interface of the theme core.
 *
 * Hooks: the provider on creationell_wp_theme_consent_provider, the library,
 * the module script and stylesheet with the configuration on
 * wp_enqueue_scripts, the root element of CookieConsent on wp_footer and the
 * consent link on creationell_wp_theme_footer_meta. Library and module assets
 * load only on front-end pages while CookieConsent is the consent provider.
 *
 * @since 1.0.0
 */
final class Module {

	/**
	 * Handle of the module script and stylesheet.
	 *
	 * @since 1.0.0
	 */
	public const HANDLE = 'creationell-wp-theme-consent-banner';

	/**
	 * Handle of the CookieConsent library, registered by Vendor_Scripts.
	 *
	 * @since 1.0.0
	 */
	public const VENDOR_HANDLE = 'creationell-wp-theme-cookieconsent';

	/**
	 * Handle of the compiled theme stylesheet, loaded before the module stylesheet.
	 *
	 * @since 1.0.0
	 */
	public const THEME_STYLE_HANDLE = 'creationell-wp-theme-main';

	/**
	 * Script, relative to the module folder.
	 *
	 * @since 1.0.0
	 */
	public const SCRIPT = 'assets/js/consent.js';

	/**
	 * Stylesheet, relative to the module folder.
	 *
	 * @since 1.0.0
	 */
	public const STYLESHEET = 'assets/css/consent.css';

	/**
	 * ID of the root element of CookieConsent.
	 *
	 * @since 1.0.0
	 */
	public const ROOT_ID = 'creationell-theme-consent';

	/**
	 * Global JavaScript variable that holds the configuration.
	 *
	 * @since 1.0.0
	 */
	public const CONFIG_GLOBAL = 'creationellWpThemeConsentConfig';

	/**
	 * JSON flags of the inline configuration: no "<", ">" or "&", readable non-ASCII text.
	 *
	 * @since 1.0.0
	 */
	public const JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;

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
	 * Builder of the configuration.
	 *
	 * @var Config_Builder
	 */
	private Config_Builder $builder;

	/**
	 * Takes the folder and the URL of the module and, for tests, a configuration builder.
	 *
	 * @since 1.0.0
	 *
	 * @param string              $module_dir Absolute path of modules/consent.
	 * @param string              $module_url URL of modules/consent.
	 * @param Config_Builder|null $builder    Builder of the configuration; null for the default.
	 */
	public function __construct( string $module_dir, string $module_url, ?Config_Builder $builder = null ) {
		$this->dir     = rtrim( $module_dir, '/' );
		$this->url     = rtrim( $module_url, '/' );
		$this->builder = $builder ?? new Config_Builder();
	}

	/**
	 * Adds the hooks of the module and registers the block namespace for translation.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( Consent_Gate::FILTER_PROVIDER, array( Provider::class, 'filter' ), 10, 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 20, 0 );
		add_action( 'wp_footer', array( $this, 'render_root' ), 5, 0 );
		add_action( 'creationell_wp_theme_footer_meta', array( Links::class, 'render_footer_link' ), 10, 0 );
		Block_I18n::register_namespace( 'creationell-theme' );
	}

	/**
	 * Enqueues library, module stylesheet and script with the configuration; runs on wp_enqueue_scripts with priority 20.
	 *
	 * Nothing loads in the admin, in embeds or while another or no consent
	 * provider is effective. The configuration is printed inline before the
	 * module script, which keeps its strategy defer.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue(): void {
		if ( is_admin() || is_embed() || ! Provider::is_effective() ) {
			return;
		}
		wp_enqueue_style( self::VENDOR_HANDLE );
		wp_enqueue_style( self::HANDLE, $this->url . '/' . self::STYLESHEET, array( self::VENDOR_HANDLE, self::THEME_STYLE_HANDLE ), CREATIONELL_WP_THEME_VERSION );
		wp_enqueue_script( self::VENDOR_HANDLE );
		wp_enqueue_script(
			self::HANDLE,
			$this->url . '/' . self::SCRIPT,
			array( self::VENDOR_HANDLE ),
			CREATIONELL_WP_THEME_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		$json = wp_json_encode( $this->builder->build( determine_locale(), is_rtl() ), self::JSON_FLAGS );
		if ( is_string( $json ) ) {
			wp_add_inline_script( self::HANDLE, 'window.' . self::CONFIG_GLOBAL . '=' . $json . ';', 'before' );
		}
	}

	/**
	 * Prints the root element of CookieConsent; runs on wp_footer with priority 5, only after enqueue().
	 *
	 * The class btn-primary sets the --bs-btn-* variables that consent.css maps
	 * to the buttons of CookieConsent.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_root(): void {
		if ( ! wp_script_is( self::HANDLE, 'enqueued' ) ) {
			return;
		}
		echo '<div id="' . esc_attr( self::ROOT_ID ) . '" class="btn-primary"></div>';
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
	 * Returns the builder of the configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return Config_Builder Builder.
	 */
	public function builder(): Config_Builder {
		return $this->builder;
	}
}
