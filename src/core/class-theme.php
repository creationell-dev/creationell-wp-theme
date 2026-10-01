<?php
/**
 * Boot of the theme core.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Core;

use Creationell\WpTheme\Admin\Menu;
use Creationell\WpTheme\Admin\Module_Page;
use Creationell\WpTheme\Admin\Notices;
use Creationell\WpTheme\Admin\Theme_Switch_Notice;
use Creationell\WpTheme\Admin\Transfer_Page;
use Creationell\WpTheme\Assets\Assets;
use Creationell\WpTheme\Assets\Bootstrap_Bridge;
use Creationell\WpTheme\Assets\Editor_Assets;
use Creationell\WpTheme\Assets\Vendor_Scripts;
use Creationell\WpTheme\Child\Child_Theme;
use Creationell\WpTheme\Cli\Child_Command;
use Creationell\WpTheme\Cli\Css_Command;
use Creationell\WpTheme\Cli\Doctor_Command;
use Creationell\WpTheme\Cli\Module_Command;
use Creationell\WpTheme\Cli\Settings_Command;
use Creationell\WpTheme\Cli\Settings_Transfer_Command;
use Creationell\WpTheme\Cli\Update_Command;
use Creationell\WpTheme\Cli\Wpml_Command;
use Creationell\WpTheme\Compiler\Custom_Stylesheet;
use Creationell\WpTheme\Consent\Consent_Gate;
use Creationell\WpTheme\Modules\Module_Gate;
use Creationell\WpTheme\Settings\Acf\Acf_Adapter;
use Creationell\WpTheme\Settings\Global_Styles_Lock;
use Creationell\WpTheme\Settings\Registry;
use Creationell\WpTheme\Settings\Snapshot;
use Creationell\WpTheme\Settings\Theme_Json_Tokens;
use Creationell\WpTheme\Settings\Transfer\Transfer;
use Creationell\WpTheme\Update\Theme_Updater;
use Creationell\WpTheme\Wpml\Wpml_Integration;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Registers the hooks of the theme core once src/loader.php has loaded all files.
 *
 * @since 1.0.0
 */
final class Theme {

	/**
	 * Registers the hooks when PHP and WordPress are new enough, otherwise only a notice.
	 *
	 * Runs while functions.php loads, before after_setup_theme and init; it
	 * translates nothing. The notices hook is always registered, so
	 * administrators learn why the theme functions stay off. With the
	 * requirements met it switches the Bootstrap of CreaBootstrapBlocks off
	 * first; with unmet requirements the theme loads no Bootstrap, so the
	 * plugin keeps its own.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function boot(): void {
		add_action( 'admin_notices', array( Notices::class, 'render' ), 10, 0 );

		if ( ! Environment::meets_requirements() ) {
			Notices::add( 'requirements', self::requirements_message( ... ), Notices::ERROR );
			return;
		}

		Bootstrap_Bridge::define_cbb_constants();
		add_action( 'after_setup_theme', array( Theme_Support::class, 'setup' ), 2, 0 );
		add_action( 'init', array( Registry::class, 'boot' ), 10, 0 );
		add_action( 'init', array( Snapshot::class, 'boot' ), 10, 0 );
		Module_Gate::register_hooks();
		add_action( 'init', array( Vendor_Scripts::class, 'register' ), Vendor_Scripts::PRIORITY, 0 );
		Theme_Json_Tokens::register_hooks();
		Global_Styles_Lock::register_hooks();
		add_action( 'wp_enqueue_scripts', array( Assets::class, 'enqueue_frontend' ), 5, 0 );
		Consent_Gate::register_hooks();
		add_action( 'enqueue_block_assets', array( Editor_Assets::class, 'enqueue_canvas' ), 5, 0 );
		add_action( 'admin_enqueue_scripts', array( Editor_Assets::class, 'enqueue_color_vars' ), 10, 1 );
		Bootstrap_Bridge::register_order_hooks();
		add_action( 'cli_init', array( Bootstrap_Bridge::class, 'add_doctor_section' ), 10, 0 );
		add_action( 'cli_init', array( Doctor_Command::class, 'register' ), 10, 0 );
		add_action( 'cli_init', array( Module_Command::class, 'register' ), 10, 0 );
		add_filter( 'map_meta_cap', array( Capabilities::class, 'map_meta_cap' ), 10, 4 );
		Site_Editor_Lock::register();
		Template_Part_Gate::register();
		add_action( 'admin_menu', array( Menu::class, 'register' ), 9, 0 );
		add_action( 'admin_menu', array( Menu::class, 'collect' ), PHP_INT_MAX, 0 );
		add_action( 'admin_init', array( Menu::class, 'redirect' ), 10, 0 );
		Module_Page::register_hooks();
		Theme_Switch_Notice::register_hooks();
		add_action( 'after_setup_theme', array( Acf_Adapter::class, 'boot' ), 10, 0 );
		add_action( 'cli_init', array( Child_Command::class, 'register' ), 10, 0 );
		add_action( 'admin_init', array( Child_Theme::class, 'queue_notice' ), 10, 0 );
		add_action( 'cli_init', array( Update_Command::class, 'register' ), 10, 0 );
		add_action( 'after_setup_theme', array( Wpml_Integration::class, 'boot' ), 20, 0 );
		add_action( 'cli_init', array( Wpml_Command::class, 'register' ), 10, 0 );
		Theme_Updater::instance()->register_hooks();
		Custom_Stylesheet::register_hooks();
		add_action( 'cli_init', array( Settings_Command::class, 'register' ), 10, 0 );
		add_action( 'cli_init', array( Css_Command::class, 'register' ), 10, 0 );
		Transfer::register();
		Transfer_Page::register_hooks();
		add_action( 'cli_init', array( Settings_Transfer_Command::class, 'register' ), Settings_Transfer_Command::PRIORITY, 0 );
		self::queue_manifest_notice();

		/**
		 * Fires after the theme core has registered its hooks, while functions.php of the parent theme loads.
		 *
		 * A child theme hooks in from its own functions.php, which WordPress loads
		 * before the one of the parent theme.
		 *
		 * @since 1.0.0
		 */
		do_action( 'creationell_wp_theme_loaded' );
	}

	/**
	 * Returns the notice text about the unmet requirements.
	 *
	 * @since 1.0.0
	 *
	 * @return string Translated message.
	 */
	private static function requirements_message(): string {
		$unmet = Environment::unmet_requirements();
		return sprintf(
			/* translators: 1: required PHP version, 2: required WordPress version, 3: running PHP version, 4: running WordPress version. */
			__( 'The creationell Theme needs PHP %1$s and WordPress %2$s or newer; this site runs PHP %3$s and WordPress %4$s. The theme functions stay off until the server and WordPress are updated.', 'creationell-wp-theme' ),
			CREATIONELL_WP_THEME_MIN_PHP,
			CREATIONELL_WP_THEME_MIN_WP,
			$unmet['php']['current'] ?? PHP_VERSION,
			$unmet['wp']['current'] ?? Environment::wp_version()
		);
	}

	/**
	 * Queues a notice when the site predefines the update manifest URL.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function queue_manifest_notice(): void {
		$status = Environment::manifest_url_status();
		if ( Environment::MANIFEST_DEFAULT === $status ) {
			return;
		}
		$value = constant( 'CREATIONELL_WP_THEME_MANIFEST_URL' );
		$shown = is_string( $value ) ? $value : gettype( $value );
		if ( Environment::MANIFEST_OVERRIDE === $status ) {
			Notices::add(
				'manifest-url',
				static fn(): string => sprintf(
					/* translators: %s: URL of the update manifest. */
					__( 'The creationell Theme looks for updates at %s (set by CREATIONELL_WP_THEME_MANIFEST_URL) instead of the official manifest. Use this only for tests.', 'creationell-wp-theme' ),
					$shown
				),
				Notices::WARNING
			);
			return;
		}
		Notices::add(
			'manifest-url',
			static fn(): string => sprintf(
				/* translators: %s: value of the constant. */
				__( 'CREATIONELL_WP_THEME_MANIFEST_URL is set to %s, which is no https URL. The creationell Theme ignores it and uses the official update manifest.', 'creationell-wp-theme' ),
				$shown
			),
			Notices::ERROR
		);
	}
}
