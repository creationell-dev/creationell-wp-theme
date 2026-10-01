<?php
/**
 * Registry of the theme settings.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings;

use Creationell\WpTheme\Modules\Module_Catalog;
use InvalidArgumentException;
use Throwable;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Holds the definitions of all settings: the core keys and the settings of installed modules.
 *
 * The core keys are the Bootstrap line, one switch module_<slug> per catalog
 * module and the design and text keys of definitions/design.php and
 * definitions/texts.php. On init, boot() adds a switch per installed module and loads
 * modules/<slug>/settings.php of every installed module, whatever its state.
 * The registry reads no options; values come from Settings.
 *
 * @since 1.0.0
 */
final class Registry {

	/**
	 * Capability needed to switch the Bootstrap line; never granted in this theme version.
	 *
	 * @since 1.0.0
	 */
	public const CAP_BOOTSTRAP_LINE = 'creationell_wp_theme_switch_bootstrap_line';

	/**
	 * Capability needed to switch modules.
	 *
	 * @since 1.0.0
	 */
	public const CAP_MODULES = 'creationell_wp_theme_manage_modules';

	/**
	 * Capability needed to change the design settings: colors, fonts and the design settings of modules.
	 *
	 * @since 1.0.0
	 */
	public const CAP_DESIGN = 'creationell_wp_theme_manage_design';

	/**
	 * Capability needed to change the texts: footer, contact, social links and the texts of modules.
	 *
	 * @since 1.0.0
	 */
	public const CAP_BASIC = 'creationell_wp_theme_manage_basic';

	/**
	 * Shared registry.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Definitions by key, in the order they were added.
	 *
	 * @var array<string, Definition>
	 */
	private array $definitions = array();

	/**
	 * Whether boot() has loaded the modules into this registry.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Adds the given definitions.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, Definition> $definitions Definitions.
	 * @throws InvalidArgumentException When a key comes twice.
	 */
	public function __construct( array $definitions = array() ) {
		foreach ( $definitions as $definition ) {
			$this->add( $definition );
		}
	}

	/**
	 * Returns the shared registry with the core definitions.
	 *
	 * @since 1.0.0
	 *
	 * @return self Registry.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self( self::core_definitions() );
		}
		return self::$instance;
	}

	/**
	 * Replaces the shared registry; null builds a new one on the next call of instance().
	 *
	 * @since 1.0.0
	 *
	 * @param self|null $registry Registry.
	 * @return void
	 */
	public static function set_instance( ?self $registry ): void {
		self::$instance = $registry;
	}

	/**
	 * Loads the settings of the installed modules into the shared registry; runs on init with priority 10.
	 *
	 * Runs once per registry. Queues the notice about an unavailable Bootstrap
	 * line. Translates nothing: labels stay closures.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function boot(): void {
		$registry = self::instance();
		if ( $registry->booted ) {
			return;
		}
		$registry->booted = true;
		$registry->load_modules( Module_Catalog::instance() );
		Bootstrap_Line::instance()->queue_notice();
	}

	/**
	 * Returns the core definitions: the Bootstrap line, the switches of the seven catalog modules, the design and the text keys.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, Definition> Definitions in registry order.
	 */
	public static function core_definitions(): array {
		$definitions = array(
			new Definition(
				key: 'bootstrap_line',
				type: 'enum',
				default_value: 5,
				choices: array(
					5 => true,
					6 => false,
				),
				level: 'advanced',
				capability: self::CAP_BOOTSTRAP_LINE,
				label: static fn(): string => __( 'Bootstrap line', 'creationell-wp-theme' ),
				description: static fn(): string => __( 'Major Bootstrap version the theme styles and scripts are built on.', 'creationell-wp-theme' ),
			),
		);
		foreach ( Module_Catalog::RESERVED as $slug => $states ) {
			$definitions[] = self::switch_definition( $slug, $states, Module_Catalog::reserved_title( $slug ) );
		}
		foreach ( array( 'design', 'texts' ) as $file ) {
			$definitions = array_merge( $definitions, self::definitions_file( __DIR__ . '/definitions/' . $file . '.php' ) );
		}
		return $definitions;
	}

	/**
	 * Loads a file of core definitions.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file Absolute path of a file that returns a list of Definition objects.
	 * @return array<int, Definition> Definitions.
	 */
	private static function definitions_file( string $file ): array {
		$loaded = ( static fn( string $definitions_file ): mixed => require $definitions_file )( $file );
		return is_array( $loaded ) ? array_values( array_filter( $loaded, static fn( mixed $entry ): bool => $entry instanceof Definition ) ) : array();
	}

	/**
	 * Returns the key of a module switch: "module_" plus the slug with underscores.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Module slug.
	 * @return string Key.
	 */
	public static function module_key( string $slug ): string {
		return 'module_' . str_replace( '-', '_', $slug );
	}

	/**
	 * Adds a definition.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition $definition Definition.
	 * @return void
	 * @throws InvalidArgumentException When the key exists.
	 */
	public function add( Definition $definition ): void {
		if ( isset( $this->definitions[ $definition->key ] ) ) {
			throw new InvalidArgumentException( esc_html( sprintf( 'Setting key %s is already registered.', $definition->key ) ) );
		}
		$this->definitions[ $definition->key ] = $definition;
	}

	/**
	 * Returns the definition of a key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Key.
	 * @return Definition Definition.
	 * @throws InvalidArgumentException When the key is unknown.
	 */
	public function get( string $key ): Definition {
		if ( ! isset( $this->definitions[ $key ] ) ) {
			throw new InvalidArgumentException( esc_html( sprintf( 'Setting key %s is unknown.', $key ) ) );
		}
		return $this->definitions[ $key ];
	}

	/**
	 * Tells whether a key is registered.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Key.
	 * @return bool True when registered.
	 */
	public function has( string $key ): bool {
		return isset( $this->definitions[ $key ] );
	}

	/**
	 * Returns all definitions.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, Definition> Definitions by key, in registry order.
	 */
	public function all(): array {
		return $this->definitions;
	}

	/**
	 * Returns the keys whose values differ per language.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Keys in registry order.
	 */
	public function translatable_keys(): array {
		return array_keys( array_filter( $this->definitions, static fn( Definition $definition ): bool => $definition->translatable ) );
	}

	/**
	 * Returns all keys.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Keys in registry order.
	 */
	public function keys(): array {
		return array_keys( $this->definitions );
	}

	/**
	 * Adds a switch per installed module and the definitions of its settings.php.
	 *
	 * A settings.php returns a list of Definition objects. It is loaded for every
	 * installed module, whatever its state, and may run more than once per request
	 * (one run per registry), so it declares no functions or classes. A file
	 * that returns no list, entries that are no Definition, keys that exist and
	 * entries in the section "modules" are reported with _doing_it_wrong() and
	 * skipped; the other entries stay.
	 *
	 * @since 1.0.0
	 *
	 * @param Module_Catalog $catalog Module catalog.
	 * @return void
	 */
	public function load_modules( Module_Catalog $catalog ): void {
		foreach ( $catalog->installed() as $slug => $manifest ) {
			$key = self::module_key( $slug );
			if ( ! $this->has( $key ) ) {
				$this->add( self::switch_definition( $slug, $manifest->states, $manifest->title ) );
			}
			$file = $manifest->dir . '/settings.php';
			if ( is_file( $file ) ) {
				$this->load_settings_file( $file, 'modules/' . $slug . '/settings.php' );
			}
		}
	}

	/**
	 * Loads one settings.php and adds its definitions.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file  Absolute path.
	 * @param string $shown Path relative to the theme, for messages.
	 * @return void
	 */
	private function load_settings_file( string $file, string $shown ): void {
		try {
			$definitions = ( static fn( string $settings_file ): mixed => include $settings_file )( $file );
		} catch ( Throwable $error ) {
			self::report( sprintf( '%1$s failed: %2$s', $shown, $error->getMessage() ) );
			return;
		}
		if ( ! is_array( $definitions ) ) {
			self::report( sprintf( '%s must return a list of Definition objects.', $shown ) );
			return;
		}
		foreach ( $definitions as $index => $definition ) {
			if ( ! $definition instanceof Definition ) {
				self::report( sprintf( '%1$s: entry %2$s is no Definition object.', $shown, (string) $index ) );
				continue;
			}
			if ( 'modules' === $definition->section ) {
				self::report( sprintf( '%1$s: setting %2$s uses the section "modules", which holds only the module switches.', $shown, $definition->key ) );
				continue;
			}
			if ( $this->has( $definition->key ) ) {
				self::report( sprintf( '%1$s: setting %2$s is already registered.', $shown, $definition->key ) );
				continue;
			}
			$this->add( $definition );
		}
	}

	/**
	 * Builds the switch of a module.
	 *
	 * @since 1.0.0
	 *
	 * @param string             $slug   Module slug.
	 * @param array<int, string> $states States of the module.
	 * @param \Closure           $title  Returns the translated module title.
	 * @phpstan-param \Closure(): string $title
	 * @return Definition Definition of module_<slug>.
	 */
	private static function switch_definition( string $slug, array $states, \Closure $title ): Definition {
		return new Definition(
			key: self::module_key( $slug ),
			type: 'enum',
			default_value: 'off',
			choices: array_fill_keys( $states, true ),
			level: 'advanced',
			capability: self::CAP_MODULES,
			label: $title,
		);
	}

	/**
	 * Reports a problem with the settings of a module.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Message in English; not translated, it may come before init.
	 * @return void
	 */
	private static function report( string $message ): void {
		_doing_it_wrong( __CLASS__ . '::load_modules', esc_html( $message ), '1.0.0' );
	}
}
