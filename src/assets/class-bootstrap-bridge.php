<?php
/**
 * Bridge to the Bootstrap blocks plugin CreaBootstrapBlocks.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Assets;

use Creationell\WpTheme\Admin\Notices;
use Creationell\WpTheme\Cli\Doctor_Command;
use _WP_Dependency;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Lets CreaBootstrapBlocks run on the Bootstrap of the theme.
 *
 * The plugin reads the constants CREA_BOOTSTRAP_BLOCKS_BOOTSTRAP_CSS, _JS and
 * _ICONS before its own settings when it enqueues. The theme defines all three
 * as false while functions.php loads, so the plugin loads no second Bootstrap;
 * a constant that wp-config.php already set to true stays, and administrators
 * see a notice.
 *
 * Order strategy "prio-defer" (standard): the theme enqueues on
 * wp_enqueue_scripts and enqueue_block_assets with priority 5, the plugin on 10.
 * So the main stylesheet of the theme comes before the stylesheet of the
 * plugin, and the deferred Bootstrap bundle comes before the deferred front-end
 * script of the plugin in the document; deferred scripts run in document order,
 * so window.bootstrap exists when the plugin script runs. Order strategy
 * "deps" (fallback) adds the theme handles to the dependencies of the plugin
 * handles on priority 20 instead.
 *
 * When leaving the theme, switch the Bootstrap settings of the plugin on again.
 *
 * @since 1.0.0
 */
final class Bootstrap_Bridge {

	/**
	 * Constants of CreaBootstrapBlocks that switch its own Bootstrap stylesheet, script and icon font.
	 *
	 * @since 1.0.0
	 */
	public const SWITCHES = array(
		'CREA_BOOTSTRAP_BLOCKS_BOOTSTRAP_CSS',
		'CREA_BOOTSTRAP_BLOCKS_BOOTSTRAP_JS',
		'CREA_BOOTSTRAP_BLOCKS_BOOTSTRAP_ICONS',
	);

	/**
	 * Order strategy of the theme: "prio-defer" (priorities and defer) or "deps" (dependencies on priority 20).
	 *
	 * @since 1.0.0
	 */
	public const ORDER_STRATEGY = 'prio-defer';

	/**
	 * Order strategy of the fallback that adds dependencies to the plugin handles.
	 *
	 * @since 1.0.0
	 */
	public const STRATEGY_DEPS = 'deps';

	/**
	 * Stylesheet handle of CreaBootstrapBlocks.
	 *
	 * @since 1.0.0
	 */
	public const CBB_STYLE = 'crea-bootstrap-blocks';

	/**
	 * Front-end script handle of CreaBootstrapBlocks; it uses window.bootstrap.
	 *
	 * @since 1.0.0
	 */
	public const CBB_FRONTEND_SCRIPT = 'crea-bootstrap-blocks-frontend';

	/**
	 * File with the version of the Bootstrap vendored in CreaBootstrapBlocks, relative to its plugin folder.
	 *
	 * @since 1.0.0
	 */
	public const CBB_BOOTSTRAP_VERSION_FILE = 'assets/vendor/bootstrap/VERSION';

	/**
	 * Priority of the fallback callback, after the plugin registered its handles on 10.
	 *
	 * @since 1.0.0
	 */
	public const DEPS_PRIORITY = 20;

	/**
	 * Key of the section of the doctor command.
	 *
	 * @since 1.0.0
	 */
	public const DOCTOR_SECTION = 'cbb';

	/**
	 * Defines the Bootstrap switches of CreaBootstrapBlocks as false unless the site set them.
	 *
	 * Runs while functions.php loads, before any enqueue hook. Queues the notice
	 * "Bootstrap loads twice" when wp-config.php set a switch to true, and the
	 * version comparison when the plugin is active; both build their text only
	 * when they render.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function define_cbb_constants(): void {
		// The names stay literal: the plugin reads exactly these constants (see SWITCHES).
		if ( ! defined( 'CREA_BOOTSTRAP_BLOCKS_BOOTSTRAP_CSS' ) ) {
			define( 'CREA_BOOTSTRAP_BLOCKS_BOOTSTRAP_CSS', false );
		}
		if ( ! defined( 'CREA_BOOTSTRAP_BLOCKS_BOOTSTRAP_JS' ) ) {
			define( 'CREA_BOOTSTRAP_BLOCKS_BOOTSTRAP_JS', false );
		}
		if ( ! defined( 'CREA_BOOTSTRAP_BLOCKS_BOOTSTRAP_ICONS' ) ) {
			define( 'CREA_BOOTSTRAP_BLOCKS_BOOTSTRAP_ICONS', false );
		}

		$predefined_on = self::predefined_on();
		if ( array() !== $predefined_on ) {
			Notices::add(
				'cbb-bootstrap-twice',
				static fn(): string => sprintf(
					/* translators: %s: names of the constants, separated by commas. */
					__( 'Bootstrap loads twice: the creationell Theme loads Bootstrap, and CreaBootstrapBlocks loads its own copy because wp-config.php sets %s to true. Remove these constants or set them to false.', 'creationell-wp-theme' ),
					implode( ', ', $predefined_on )
				),
				Notices::WARNING
			);
		}

		if ( self::cbb_active() ) {
			Notices::add( 'cbb-bootstrap-version', self::version_message( ... ), Notices::WARNING );
		}
	}

	/**
	 * Tells whether CreaBootstrapBlocks is loaded.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when the plugin defined CREA_BOOTSTRAP_BLOCKS_DIR.
	 */
	public static function cbb_active(): bool {
		return defined( 'CREA_BOOTSTRAP_BLOCKS_DIR' );
	}

	/**
	 * Returns the state of the bridge for diagnostics such as the doctor command.
	 *
	 * @since 1.0.0
	 *
	 * @return array{active: bool, switches_off: bool, order_strategy: string, theme_bootstrap: string, plugin_bootstrap: string|null, version_match: bool|null, predefined_on: array<int, string>} State: plugin loaded, all three switches off, order strategy, Bootstrap versions of theme and plugin (null when unreadable), whether they match (null without the plugin), switches the site set to true.
	 */
	public static function status(): array {
		$active           = self::cbb_active();
		$theme_bootstrap  = Assets::vendor_version( Assets::BOOTSTRAP_VERSION_FILE );
		$plugin_bootstrap = $active ? self::plugin_bootstrap_version() : null;
		$switches_off     = true;
		foreach ( self::SWITCHES as $name ) {
			if ( ! defined( $name ) || (bool) constant( $name ) ) {
				$switches_off = false;
			}
		}
		return array(
			'active'           => $active,
			'switches_off'     => $switches_off,
			'order_strategy'   => self::ORDER_STRATEGY,
			'theme_bootstrap'  => $theme_bootstrap,
			'plugin_bootstrap' => $plugin_bootstrap,
			'version_match'    => $active ? ( null !== $plugin_bootstrap && '' !== $theme_bootstrap && $plugin_bootstrap === $theme_bootstrap ) : null,
			'predefined_on'    => self::predefined_on(),
		);
	}

	/**
	 * Adds the section "cbb" to "wp creationell-theme doctor"; runs on cli_init.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function add_doctor_section(): void {
		if ( in_array( self::DOCTOR_SECTION, Doctor_Command::section_keys(), true ) ) {
			return;
		}
		Doctor_Command::add_section( self::DOCTOR_SECTION, array( self::class, 'doctor_section' ) );
	}

	/**
	 * Collects the doctor section: the status of the bridge and a warning per problem.
	 *
	 * Example:
	 *
	 *     wp creationell-theme doctor --format=json | jq -e '.cbb.switches_off and .cbb.version_match'
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Values of status(); "warnings" when a switch is on or the Bootstrap versions differ.
	 */
	public static function doctor_section(): array {
		$values   = self::status();
		$warnings = array();
		if ( array() !== $values['predefined_on'] ) {
			$warnings[] = sprintf( 'Bootstrap loads twice: wp-config.php sets %s to true.', implode( ', ', $values['predefined_on'] ) );
		}
		if ( false === $values['version_match'] ) {
			$warnings[] = sprintf( 'CreaBootstrapBlocks ships Bootstrap %s, the theme %s.', $values['plugin_bootstrap'] ?? 'unknown', $values['theme_bootstrap'] );
		}
		if ( array() !== $warnings ) {
			$values['warnings'] = $warnings;
		}
		return $values;
	}

	/**
	 * Registers the fallback callback for the order strategy "deps"; the standard strategy needs no callback.
	 *
	 * @since 1.0.0
	 *
	 * @param string $strategy Order strategy, by default ORDER_STRATEGY.
	 * @return void
	 */
	public static function register_order_hooks( string $strategy = self::ORDER_STRATEGY ): void {
		if ( self::STRATEGY_DEPS !== $strategy ) {
			return;
		}
		add_action( 'wp_enqueue_scripts', array( self::class, 'order_after_theme' ), self::DEPS_PRIORITY, 0 );
		add_action( 'enqueue_block_assets', array( self::class, 'order_after_theme' ), self::DEPS_PRIORITY, 0 );
	}

	/**
	 * Fallback "deps": makes the plugin stylesheet depend on the main stylesheet and the plugin script on the Bootstrap bundle.
	 *
	 * Changes only registered plugin handles and adds each dependency once.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function order_after_theme(): void {
		self::add_dependency( wp_styles()->query( self::CBB_STYLE ), Assets::HANDLE_MAIN );
		self::add_dependency( wp_scripts()->query( self::CBB_FRONTEND_SCRIPT ), Assets::HANDLE_BOOTSTRAP );
	}

	/**
	 * Adds a dependency to a registered item once.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $item       Result of WP_Dependencies::query(): the item or false.
	 * @param string $dependency Handle to depend on.
	 * @return void
	 */
	private static function add_dependency( mixed $item, string $dependency ): void {
		if ( ! $item instanceof _WP_Dependency || in_array( $dependency, $item->deps, true ) ) {
			return;
		}
		$item->deps[] = $dependency;
	}

	/**
	 * Returns the switches that the site set to a true value before the theme loaded.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Constant names.
	 */
	private static function predefined_on(): array {
		$names = array();
		foreach ( self::SWITCHES as $name ) {
			if ( defined( $name ) && (bool) constant( $name ) ) {
				$names[] = $name;
			}
		}
		return $names;
	}

	/**
	 * Returns the version of the Bootstrap vendored in CreaBootstrapBlocks.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null Version, or null when the plugin folder or the file is not readable.
	 */
	private static function plugin_bootstrap_version(): ?string {
		$dir = defined( 'CREA_BOOTSTRAP_BLOCKS_DIR' ) ? constant( 'CREA_BOOTSTRAP_BLOCKS_DIR' ) : null;
		if ( ! is_string( $dir ) || '' === $dir ) {
			return null;
		}
		$file    = rtrim( $dir, '/' ) . '/' . self::CBB_BOOTSTRAP_VERSION_FILE;
		$lines   = is_readable( $file ) ? file( $file, FILE_IGNORE_NEW_LINES ) : false;
		$version = false === $lines ? '' : trim( $lines[0] ?? '' );
		return '' === $version ? null : $version;
	}

	/**
	 * Returns the notice text when the Bootstrap versions of theme and plugin differ.
	 *
	 * @since 1.0.0
	 *
	 * @return string Translated message, or an empty string when the versions match.
	 */
	private static function version_message(): string {
		$status = self::status();
		if ( false !== $status['version_match'] ) {
			return '';
		}
		return sprintf(
			/* translators: 1: Bootstrap version of the plugin, 2: Bootstrap version of the theme. */
			__( 'CreaBootstrapBlocks is built for Bootstrap %1$s, the creationell Theme loads Bootstrap %2$s. The blocks run on the Bootstrap of the theme; update the plugin or the theme so that both use the same version.', 'creationell-wp-theme' ),
			$status['plugin_bootstrap'] ?? __( 'unknown', 'creationell-wp-theme' ),
			'' === $status['theme_bootstrap'] ? __( 'unknown', 'creationell-wp-theme' ) : $status['theme_bootstrap']
		);
	}
}
