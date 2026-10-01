<?php
/**
 * State of the theme modules.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules;

use Closure;
use Creationell\WpTheme\Settings\Settings_Module_Store;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Resolves the state of a module from its layers and tells why.
 *
 * Configured state, precedence P4, lowest first: default "off" < child default <
 * backend < child lock < constant CREATIONELL_WP_THEME_MODULE_<SLUG>. The child
 * layers come from the filter creationell_wp_theme_modules, the backend layer
 * from the module store (the settings snapshot for the shared instance). The highest layer that is set wins; when its value is no
 * state of the module, the module is "off" with the reason "invalid_state". A
 * backend state the module does not know is skipped with the reason
 * "invalid_backend", unless a higher layer wins.
 *
 * Effective state: a module that is not installed is always "off". A module
 * configured on is "off" when a check fails, in this order: an older plugin it
 * replaces is active ("legacy_plugin:<plugin file>"), a required plugin is not
 * active ("missing_plugin:<plugin file>"), Blockstudio is required but not
 * loaded ("missing_blockstudio"), a required module is effectively off
 * ("missing_module:<slug>"), the module requires itself through other modules
 * ("dependency_cycle"). Plugins count when active on the site or, in a network,
 * for the whole network.
 *
 * Reasons: "default", "child", "backend", "locked", "constant" (the winning
 * layer), "invalid_state", "invalid_backend", "not_installed",
 * "invalid_manifest", "unknown_module" and the reasons of the checks. Results are
 * cached per request; flush() forgets them. Resolving reads the store and the
 * active plugins, calls no ACF function and translates nothing, so it may run
 * from after_setup_theme on.
 *
 * @since 1.0.0
 */
final class Module_State {

	/**
	 * Filter for child defaults and locks.
	 *
	 * @since 1.0.0
	 */
	public const FILTER = 'creationell_wp_theme_modules';

	/**
	 * Prefix of the constants per module; the slug follows in upper case with underscores.
	 *
	 * @since 1.0.0
	 */
	public const CONSTANT_PREFIX = 'CREATIONELL_WP_THEME_MODULE_';

	/**
	 * Origins that lock the configured state against the backend.
	 *
	 * @since 1.0.0
	 */
	public const LOCKING_ORIGINS = array( 'locked', 'constant' );

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Module catalog.
	 *
	 * @var Module_Catalog
	 */
	private Module_Catalog $catalog;

	/**
	 * Reads a constant by name; returns null for an undefined one.
	 *
	 * @var Closure
	 * @phpstan-var Closure(string): mixed
	 */
	private Closure $constants;

	/**
	 * Store of the backend layer; null for none.
	 *
	 * @var Module_Store_Interface|null
	 */
	private ?Module_Store_Interface $store;

	/**
	 * Tells whether Blockstudio is loaded.
	 *
	 * @var Closure
	 * @phpstan-var Closure(): bool
	 */
	private Closure $blockstudio;

	/**
	 * Child layers by slug; null before the first read.
	 *
	 * @var array<string, array<string, mixed>>|null
	 */
	private ?array $layers = null;

	/**
	 * Active plugin files as keys; null before the first read.
	 *
	 * @var array<string, true>|null
	 */
	private ?array $plugins = null;

	/**
	 * Configured states by slug.
	 *
	 * @var array<string, array{value: string, reason: string, origin: string, base: string}>
	 */
	private array $configured = array();

	/**
	 * Resolved results by slug.
	 *
	 * @var array<string, array{configured: string, state: string, reason: string, origin: string}>
	 */
	private array $results = array();

	/**
	 * Takes the catalog, the constant reader, the backend store and the Blockstudio probe.
	 *
	 * @since 1.0.0
	 *
	 * @param Module_Catalog              $catalog     Module catalog.
	 * @param Closure|null                $constants   Returns the value of a constant by name, null when undefined; null reads the real constants.
	 * @param Module_Store_Interface|null $store       Store of the backend layer; null for none.
	 * @param Closure|null                $blockstudio Tells whether Blockstudio is loaded; null checks the class Blockstudio\Build.
	 * @phpstan-param (Closure(string): mixed)|null $constants
	 * @phpstan-param (Closure(): bool)|null $blockstudio
	 */
	public function __construct( Module_Catalog $catalog, ?Closure $constants = null, ?Module_Store_Interface $store = null, ?Closure $blockstudio = null ) {
		$this->catalog     = $catalog;
		$this->constants   = $constants ?? static fn( string $name ): mixed => defined( $name ) ? constant( $name ) : null;
		$this->store       = $store;
		$this->blockstudio = $blockstudio ?? static fn(): bool => class_exists( '\Blockstudio\Build' );
	}

	/**
	 * Returns the shared instance over the shared catalog, the real constants and the settings module store.
	 *
	 * The settings module store reads the autoloaded settings snapshot without
	 * the registry, so the states resolve on after_setup_theme, before the
	 * registry boots on init.
	 *
	 * @since 1.0.0
	 *
	 * @return self Instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self( Module_Catalog::instance(), null, new Settings_Module_Store() );
		}
		return self::$instance;
	}

	/**
	 * Replaces the shared instance; null builds a new one on the next call of instance().
	 *
	 * @since 1.0.0
	 *
	 * @param self|null $state Instance.
	 * @return void
	 */
	public static function set_instance( ?self $state ): void {
		self::$instance = $state;
	}

	/**
	 * Returns the name of the constant of a module.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Slug.
	 * @return string Constant name, e.g. CREATIONELL_WP_THEME_MODULE_HEADER_FOOTER.
	 */
	public static function constant_name( string $slug ): string {
		return self::CONSTANT_PREFIX . strtoupper( str_replace( '-', '_', $slug ) );
	}

	/**
	 * Returns the catalog the states are resolved against.
	 *
	 * @since 1.0.0
	 *
	 * @return Module_Catalog Catalog.
	 */
	public function catalog(): Module_Catalog {
		return $this->catalog;
	}

	/**
	 * Returns the store of the backend layer.
	 *
	 * @since 1.0.0
	 *
	 * @return Module_Store_Interface|null Store; null without backend layer.
	 */
	public function store(): ?Module_Store_Interface {
		return $this->store;
	}

	/**
	 * Returns the effective state of a module.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Slug.
	 * @return string "active", "hidden" or "off".
	 */
	public function state( string $slug ): string {
		return $this->resolve( $slug )['state'];
	}

	/**
	 * Returns why a module has its effective state.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Slug.
	 * @return string Reason.
	 */
	public function reason( string $slug ): string {
		return $this->resolve( $slug )['reason'];
	}

	/**
	 * Returns the layer that decides the configured state: default, child, backend, locked or constant.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Slug.
	 * @return string Origin.
	 */
	public function origin( string $slug ): string {
		return $this->configure( $slug )['origin'];
	}

	/**
	 * Returns the configured state of a module: P4 with the backend layer, before the checks.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Slug.
	 * @return string "active", "hidden" or "off".
	 */
	public function configured( string $slug ): string {
		return $this->configure( $slug )['value'];
	}

	/**
	 * Returns the base of a module: the state of the layers below the backend, registry default and child default.
	 *
	 * The module switcher stores a backend state only where it differs from the base.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Slug.
	 * @return string "active", "hidden" or "off"; "off" when the child default is no state of the module.
	 */
	public function base( string $slug ): string {
		return $this->configure( $slug )['base'];
	}

	/**
	 * Tells whether a child lock or a constant holds the configured state.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Slug.
	 * @return bool True when locked.
	 */
	public function is_locked( string $slug ): bool {
		return in_array( $this->origin( $slug ), self::LOCKING_ORIGINS, true );
	}

	/**
	 * Returns configured state, effective state, reason and origin of every known module.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array{configured: string, state: string, reason: string, origin: string}> Results by slug, in catalog order.
	 */
	public function all(): array {
		$all = array();
		foreach ( $this->catalog->slugs() as $slug ) {
			$all[ $slug ] = $this->resolve( $slug );
		}
		return $all;
	}

	/**
	 * Forgets the cached layers, plugins and results; the next call reads them again.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function flush(): void {
		$this->layers     = null;
		$this->plugins    = null;
		$this->configured = array();
		$this->results    = array();
	}

	/**
	 * Resolves the effective state of one module.
	 *
	 * Required modules that lead back to the module are skipped here; they make
	 * the module "dependency_cycle". Every other required module leads away from
	 * the module, so the recursion ends.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Slug.
	 * @return array{configured: string, state: string, reason: string, origin: string} Result.
	 */
	private function resolve( string $slug ): array {
		if ( isset( $this->results[ $slug ] ) ) {
			return $this->results[ $slug ];
		}
		$configured = $this->configure( $slug );
		$state      = $configured['value'];
		$reason     = $configured['reason'];
		$manifest   = $this->catalog->manifest( $slug );
		if ( null === $manifest ) {
			$state  = 'off';
			$reason = 'not_installed';
			if ( ! $this->catalog->is_known( $slug ) ) {
				$reason = 'unknown_module';
			} elseif ( isset( $this->catalog->errors()[ $slug ] ) ) {
				$reason = 'invalid_manifest';
			}
		} elseif ( 'off' !== $state ) {
			$failed = $this->check( $manifest );
			if ( null !== $failed ) {
				$state  = 'off';
				$reason = $failed;
			}
		}
		$this->results[ $slug ] = array(
			'configured' => $configured['value'],
			'state'      => $state,
			'reason'     => $reason,
			'origin'     => $configured['origin'],
		);
		return $this->results[ $slug ];
	}

	/**
	 * Runs the checks of an installed module that is configured on.
	 *
	 * @since 1.0.0
	 *
	 * @param Module_Manifest $manifest Manifest.
	 * @return string|null Reason of the first failed check, or null when all pass.
	 */
	private function check( Module_Manifest $manifest ): ?string {
		$plugins = $this->active_plugins();
		foreach ( $manifest->legacy_plugins as $plugin ) {
			if ( isset( $plugins[ $plugin ] ) ) {
				return 'legacy_plugin:' . $plugin;
			}
		}
		foreach ( $manifest->requires['plugins'] as $plugin ) {
			if ( ! isset( $plugins[ $plugin ] ) ) {
				return 'missing_plugin:' . $plugin;
			}
		}
		if ( false !== $manifest->requires['blockstudio'] && ! ( $this->blockstudio )() ) {
			return 'missing_blockstudio';
		}
		$cycle = false;
		foreach ( $manifest->requires['modules'] as $required ) {
			if ( $this->reaches( $required, $manifest->slug ) ) {
				$cycle = true;
				continue;
			}
			if ( 'off' === $this->resolve( $required )['state'] ) {
				return 'missing_module:' . $required;
			}
		}
		return $cycle ? 'dependency_cycle' : null;
	}

	/**
	 * Tells whether a module leads to another through the required modules of installed modules.
	 *
	 * @since 1.0.0
	 *
	 * @param string $from   Slug to start from.
	 * @param string $target Slug to reach.
	 * @return bool True when $from is $target or requires it directly or indirectly.
	 */
	private function reaches( string $from, string $target ): bool {
		$queue   = array( $from );
		$visited = array();
		while ( array() !== $queue ) {
			$slug = array_shift( $queue );
			if ( $slug === $target ) {
				return true;
			}
			if ( isset( $visited[ $slug ] ) ) {
				continue;
			}
			$visited[ $slug ] = true;
			$manifest         = $this->catalog->manifest( $slug );
			if ( null !== $manifest ) {
				array_push( $queue, ...$manifest->requires['modules'] );
			}
		}
		return false;
	}

	/**
	 * Resolves the configured state of one module over its layers.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Slug.
	 * @return array{value: string, reason: string, origin: string, base: string} Configured state, reason, origin and base.
	 */
	private function configure( string $slug ): array {
		if ( isset( $this->configured[ $slug ] ) ) {
			return $this->configured[ $slug ];
		}
		$states     = $this->catalog->states( $slug );
		$valid      = static fn( mixed $value ): bool => is_string( $value ) && ( 'off' === $value || in_array( $value, $states, true ) );
		$candidates = array( 'default' => 'off' );
		$child      = $this->layers()[ $slug ] ?? array();
		if ( array_key_exists( 'default', $child ) ) {
			$candidates['child'] = $child['default'];
		}
		$child_default   = $candidates['child'] ?? null;
		$base            = is_string( $child_default ) && $valid( $child_default ) ? $child_default : 'off';
		$invalid_backend = false;
		$backend         = null === $this->store ? null : $this->store->get( $slug );
		if ( null !== $backend ) {
			if ( $valid( $backend ) ) {
				$candidates['backend'] = $backend;
			} else {
				$invalid_backend = true;
			}
		}
		if ( array_key_exists( 'locked', $child ) ) {
			$candidates['locked'] = $child['locked'];
		}
		$constant = ( $this->constants )( self::constant_name( $slug ) );
		if ( null !== $constant ) {
			$candidates['constant'] = $constant;
		}

		$origin = array_key_last( $candidates );
		$value  = end( $candidates );
		$reason = $origin;
		if ( ! is_string( $value ) || ! $valid( $value ) ) {
			$value  = 'off';
			$reason = 'invalid_state';
		} elseif ( $invalid_backend && in_array( $origin, array( 'default', 'child' ), true ) ) {
			$reason = 'invalid_backend';
		}
		$this->configured[ $slug ] = array(
			'value'  => $value,
			'reason' => $reason,
			'origin' => $origin,
			'base'   => $base,
		);
		return $this->configured[ $slug ];
	}

	/**
	 * Returns the active plugins of the site and, in a network, of the network.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, true> Plugin files as keys.
	 */
	private function active_plugins(): array {
		if ( null !== $this->plugins ) {
			return $this->plugins;
		}
		$plugins = array();
		$site    = get_option( 'active_plugins', array() );
		foreach ( is_array( $site ) ? $site : array() as $plugin ) {
			if ( is_string( $plugin ) ) {
				$plugins[ $plugin ] = true;
			}
		}
		if ( is_multisite() ) {
			$network = get_site_option( 'active_sitewide_plugins', array() );
			foreach ( is_array( $network ) ? array_keys( $network ) : array() as $plugin ) {
				if ( is_string( $plugin ) ) {
					$plugins[ $plugin ] = true;
				}
			}
		}
		$this->plugins = $plugins;
		return $plugins;
	}

	/**
	 * Returns the child layers, read from the filter once until flush().
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Layers by slug with the keys default and locked.
	 */
	private function layers(): array {
		if ( null !== $this->layers ) {
			return $this->layers;
		}
		$this->layers = $this->read_layers();
		return $this->layers;
	}

	/**
	 * Reads the child layers from the filter.
	 *
	 * A filter value that is no array is ignored and reported; entries that are no arrays are skipped.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Layers by slug with the keys default and locked.
	 */
	private function read_layers(): array {
		/**
		 * Filters the child layers of the modules: a default and a lock per module slug.
		 *
		 * A child theme sets a default state, which a site constant overrides,
		 * or locks a state. Values outside the states of a module switch it off.
		 *
		 * Example, in the functions.php of a child theme:
		 *
		 *     add_filter(
		 *         'creationell_wp_theme_modules',
		 *         static function ( mixed $modules ): mixed {
		 *             $modules = is_array( $modules ) ? $modules : array();
		 *             $modules['header-footer'] = array( 'default' => 'active' );
		 *             return $modules;
		 *         }
		 *     );
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, array{default?: string, locked?: string}> $modules Layers by module slug; empty in the parent theme.
		 */
		$modules = apply_filters( 'creationell_wp_theme_modules', array() );
		if ( ! is_array( $modules ) ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html( sprintf( 'The filter %s must return an array; it is ignored.', self::FILTER ) ),
				'1.0.0'
			);
			return array();
		}
		$layers = array();
		foreach ( $modules as $slug => $layer ) {
			if ( is_string( $slug ) && is_array( $layer ) ) {
				$layers[ $slug ] = $layer;
			}
		}
		return $layers;
	}
}
