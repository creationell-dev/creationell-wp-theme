<?php
/**
 * Values of the theme settings.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings;

use Closure;
use Creationell\WpTheme\Modules\Module_State;
use InvalidArgumentException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Resolves the value of a registry key and tells where it comes from.
 *
 * Precedence: registry default < child default < backend < child lock
 * < site constant CREATIONELL_WP_THEME_<KEY>. The child layer comes from the
 * filter creationell_wp_theme_settings, the backend layer from the autoloaded
 * snapshot only: the getter never reads the rows or calls ACF, so it works
 * without ACF from after_setup_theme on. A translated key reads the snapshot of
 * its language, then the one of the default language.
 *
 * Module switches follow Module_State, the Bootstrap line follows Bootstrap_Line
 * (the constant only holds the active line). An invalid value of a
 * PHP layer is skipped, reported with _doing_it_wrong() and kept as a finding.
 * Results are cached per request and dropped when the snapshot is rebuilt.
 *
 * @since 1.0.0
 */
final class Settings {

	/**
	 * Origin: the default of the definition.
	 *
	 * @since 1.0.0
	 */
	public const ORIGIN_DEFAULT = 'default';

	/**
	 * Origin: a default set by the child theme.
	 *
	 * @since 1.0.0
	 */
	public const ORIGIN_CHILD = 'child';

	/**
	 * Origin: a value saved in the backend.
	 *
	 * @since 1.0.0
	 */
	public const ORIGIN_BACKEND = 'backend';

	/**
	 * Origin: a value locked by the child theme.
	 *
	 * @since 1.0.0
	 */
	public const ORIGIN_LOCKED = 'locked';

	/**
	 * Origin: a site constant.
	 *
	 * @since 1.0.0
	 */
	public const ORIGIN_CONSTANT = 'constant';

	/**
	 * Prefix of the site constants; the key follows in upper case.
	 *
	 * @since 1.0.0
	 */
	public const CONSTANT_PREFIX = 'CREATIONELL_WP_THEME_';

	/**
	 * Filter for child defaults and locks.
	 *
	 * @since 1.0.0
	 */
	public const FILTER = 'creationell_wp_theme_settings';

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Reads a constant by name; returns null for an undefined one.
	 *
	 * @var Closure
	 * @phpstan-var Closure(string): mixed
	 */
	private Closure $constants;

	/**
	 * Snapshot; null reads the shared one.
	 *
	 * @var Snapshot|null
	 */
	private ?Snapshot $snapshot;

	/**
	 * Checks the values of the PHP layers.
	 *
	 * @var Sanitizer
	 */
	private Sanitizer $sanitizer;

	/**
	 * Child layers by key, read once per instance.
	 *
	 * @var array<string, array<string, mixed>>|null
	 */
	private ?array $child = null;

	/**
	 * Resolved results by "<key>|<lang>".
	 *
	 * @var array<string, array{value: mixed, origin: string, locked: bool}>
	 */
	private array $cache = array();

	/**
	 * Snapshot instance and revision the cache belongs to, as "<object id>:<revision>".
	 *
	 * @var string
	 */
	private string $revision = '';

	/**
	 * Invalid values of the PHP layers, by "<key>|<layer>".
	 *
	 * @var array<string, array{key: string, layer: string, message: string}>
	 */
	private array $findings = array();

	/**
	 * Takes the constant reader and the snapshot; without them it reads the real constants and the shared snapshot.
	 *
	 * The registry, the language, the module state and the Bootstrap line come
	 * from their shared instances when a value is read.
	 *
	 * @since 1.0.0
	 *
	 * @param Closure|null  $constants Returns the value of a constant by name, null when undefined.
	 * @param Snapshot|null $snapshot  Snapshot; null for the shared one.
	 * @phpstan-param (Closure(string): mixed)|null $constants
	 */
	public function __construct( ?Closure $constants = null, ?Snapshot $snapshot = null ) {
		$this->constants = $constants ?? static fn( string $name ): mixed => defined( $name ) ? constant( $name ) : null;
		$this->snapshot  = $snapshot;
		$this->sanitizer = new Sanitizer();
	}

	/**
	 * Returns the shared instance, which reads the real constants.
	 *
	 * @since 1.0.0
	 *
	 * @return self Instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Replaces the shared instance; null builds a new one on the next call of instance().
	 *
	 * @since 1.0.0
	 *
	 * @param self|null $settings Instance.
	 * @return void
	 */
	public static function set_instance( ?self $settings ): void {
		self::$instance = $settings;
	}

	/**
	 * Returns the name of the site constant of a key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Key.
	 * @return string Constant name, e.g. CREATIONELL_WP_THEME_BOOTSTRAP_LINE.
	 */
	public static function constant_name( string $key ): string {
		return self::CONSTANT_PREFIX . strtoupper( $key );
	}

	/**
	 * Returns the value of a key.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $key  Key.
	 * @param string|null $lang Language code; null for the language of the request.
	 * @return mixed Value.
	 * @throws InvalidArgumentException When the key is unknown.
	 */
	public function get( string $key, ?string $lang = null ): mixed {
		return $this->effective( $key, $lang )['value'];
	}

	/**
	 * Returns where the value of a key comes from.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $key  Key.
	 * @param string|null $lang Language code; null for the language of the request.
	 * @return string One of the ORIGIN_* constants.
	 * @throws InvalidArgumentException When the key is unknown.
	 */
	public function origin( string $key, ?string $lang = null ): string {
		return $this->effective( $key, $lang )['origin'];
	}

	/**
	 * Tells whether a child lock or a constant holds the value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Key.
	 * @return bool True when locked.
	 * @throws InvalidArgumentException When the key is unknown.
	 */
	public function is_locked( string $key ): bool {
		return $this->effective( $key )['locked'];
	}

	/**
	 * Returns the value of a key with its origin and whether it is locked.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $key  Key.
	 * @param string|null $lang Language code; null for the language of the request, "all" reads the default language.
	 * @return array{value: mixed, origin: string, locked: bool} Value, origin and lock.
	 * @throws InvalidArgumentException When the key is unknown.
	 */
	public function effective( string $key, ?string $lang = null ): array {
		$definition = Registry::instance()->get( $key );
		$secondary  = $definition->translatable ? $this->secondary( $lang ) : null;
		$snapshot   = $this->snapshot();
		$revision   = spl_object_id( $snapshot ) . ':' . $snapshot->revision();
		if ( $revision !== $this->revision ) {
			$this->cache    = array();
			$this->revision = $revision;
		}
		$cache_key = $key . '|' . ( $secondary ?? '' );
		if ( ! isset( $this->cache[ $cache_key ] ) ) {
			$this->cache[ $cache_key ] = $this->resolve( $definition, $secondary );
		}
		return $this->cache[ $cache_key ];
	}

	/**
	 * Returns the value a key has without a backend row in a language: the next lower layer.
	 *
	 * In the default language that is the child default or the registry default;
	 * in a secondary language it is the value of the default language.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $key  Key.
	 * @param string|null $lang Language code; null for the language of the request.
	 * @return mixed Value.
	 * @throws InvalidArgumentException When the key is unknown.
	 */
	public function below_backend( string $key, ?string $lang = null ): mixed {
		$definition = Registry::instance()->get( $key );
		if ( $definition->translatable && null !== $this->secondary( $lang ) ) {
			return $this->effective( $key, Language::instance()->default() )['value'];
		}
		$child = $this->child_value( $definition, 'default' );
		return null !== $child ? $child['value'] : $definition->default;
	}

	/**
	 * Returns the value of the backend row of a key in a language, without fallback.
	 *
	 * Reads the row itself, not the snapshot; for the admin, WP-CLI and the import.
	 * A key that is the same in every language has one global row, so its value
	 * does not depend on the language, not even on the language of an admin
	 * request (ACFML appends the language to the option pages). A
	 * translated key in a secondary language reads that language only.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $key  Key.
	 * @param string|null $lang Language code; null for the language of the request; ignored for untranslated keys.
	 * @return mixed Clean value, or null without a valid row.
	 * @throws InvalidArgumentException When the key is unknown.
	 */
	public function backend_value( string $key, ?string $lang = null ): mixed {
		$definition = Registry::instance()->get( $key );
		$secondary  = $definition->translatable ? $this->secondary( $lang ) : null;
		$raw        = ( new Option_Store() )->read( $definition, $secondary );
		if ( null === $raw ) {
			return null;
		}
		$clean = $this->sanitizer->clean( $definition, $raw );
		return $clean['valid'] ? $clean['value'] : null;
	}

	/**
	 * Returns the invalid values of the PHP layers found so far.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{key: string, layer: string, message: string}> Findings: key, layer ("child", "locked" or "constant") and message.
	 */
	public function findings(): array {
		return array_values( $this->findings );
	}

	/**
	 * Resolves a key through the layers.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition  $definition Definition.
	 * @param string|null $secondary  Code of a secondary language for a translated key, otherwise null.
	 * @return array{value: mixed, origin: string, locked: bool} Result.
	 */
	private function resolve( Definition $definition, ?string $secondary ): array {
		$key = $definition->key;
		if ( 'bootstrap_line' === $key ) {
			$line      = Bootstrap_Line::instance();
			$requested = $line->requested_by_constant();
			$held      = null !== $requested && $requested === $line->active();
			return self::result( $line->active(), $held ? self::ORIGIN_CONSTANT : self::ORIGIN_DEFAULT );
		}

		$slug = $this->module_slug( $key );
		if ( null !== $slug ) {
			$state  = Module_State::instance();
			$origin = $state->origin( $slug );
			return self::result( $state->state( $slug ), $origin );
		}

		$name     = self::constant_name( $key );
		$constant = ( $this->constants )( $name );
		if ( null !== $constant ) {
			if ( $this->sanitizer->is_clean( $definition, $constant ) ) {
				return self::result( $constant, self::ORIGIN_CONSTANT );
			}
			$this->report( $key, 'constant', sprintf( 'The constant %1$s holds no valid value for the setting %2$s; it is ignored.', $name, $key ) );
		}

		$locked = $this->child_value( $definition, 'locked' );
		if ( null !== $locked ) {
			return self::result( $locked['value'], self::ORIGIN_LOCKED );
		}

		$backend = $this->backend( $definition, $secondary );
		if ( null !== $backend ) {
			return self::result( $backend['value'], self::ORIGIN_BACKEND );
		}

		$child = $this->child_value( $definition, 'default' );
		if ( null !== $child ) {
			return self::result( $child['value'], self::ORIGIN_CHILD );
		}
		return self::result( $definition->default, self::ORIGIN_DEFAULT );
	}

	/**
	 * Returns the backend value from the snapshots: the language snapshot, then the default one.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition  $definition Definition.
	 * @param string|null $secondary  Code of a secondary language, or null.
	 * @return array{value: mixed}|null Value, or null without a valid backend value.
	 */
	private function backend( Definition $definition, ?string $secondary ): ?array {
		$sources = null === $secondary ? array( null ) : array( $secondary, null );
		foreach ( $sources as $source ) {
			$values = $this->snapshot()->values( $source );
			if ( array_key_exists( $definition->key, $values ) && $definition->accepts( $values[ $definition->key ] ) ) {
				return array( 'value' => $values[ $definition->key ] );
			}
		}
		return null;
	}

	/**
	 * Returns a valid value of the child layer.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition $definition Definition.
	 * @param string     $layer      "default" or "locked".
	 * @return array{value: mixed}|null Value, or null when the child sets none or an invalid one.
	 */
	private function child_value( Definition $definition, string $layer ): ?array {
		$layers = $this->child_layers()[ $definition->key ] ?? array();
		if ( ! array_key_exists( $layer, $layers ) ) {
			return null;
		}
		if ( $this->sanitizer->is_clean( $definition, $layers[ $layer ] ) ) {
			return array( 'value' => $layers[ $layer ] );
		}
		$this->report(
			$definition->key,
			'locked' === $layer ? 'locked' : 'child',
			sprintf( 'The filter %1$s gives no valid "%2$s" value for the setting %3$s; it is ignored.', self::FILTER, $layer, $definition->key )
		);
		return null;
	}

	/**
	 * Reads the child layers once.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Layers by key.
	 */
	private function child_layers(): array {
		if ( null !== $this->child ) {
			return $this->child;
		}
		/**
		 * Filters the child defaults and locks of the theme settings.
		 *
		 * A child theme sets a default ("default") or locks a value ("locked") per
		 * key. A default counts until an editor saves another value in the backend;
		 * a lock always wins, only a site constant is stronger. Values must be valid
		 * for the setting (e.g. colors as "#rrggbb" in lower case); invalid ones are
		 * ignored and reported. Module switches use creationell_wp_theme_modules.
		 *
		 * Example:
		 *
		 *     add_filter(
		 *         'creationell_wp_theme_settings',
		 *         static function ( mixed $settings ): mixed {
		 *             $settings = is_array( $settings ) ? $settings : array();
		 *             $settings['color_primary'] = array( 'default' => '#6f2da8' );
		 *             $settings['font_body']     = array( 'locked' => 'system-serif' );
		 *             return $settings;
		 *         }
		 *     );
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, array{default?: mixed, locked?: mixed}> $settings Layers by setting key; empty in the parent theme.
		 */
		$settings    = apply_filters( 'creationell_wp_theme_settings', array() );
		$this->child = array();
		if ( ! is_array( $settings ) ) {
			_doing_it_wrong( __METHOD__, esc_html( sprintf( 'The filter %s must return an array; it is ignored.', self::FILTER ) ), '1.0.0' );
			return $this->child;
		}
		foreach ( $settings as $key => $layer ) {
			if ( is_string( $key ) && is_array( $layer ) && null === $this->module_slug( $key ) ) {
				$this->child[ $key ] = $layer;
			}
		}
		return $this->child;
	}

	/**
	 * Returns the code of a secondary language for a request language.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $lang Language code; null for the language of the request.
	 * @return string|null Code of a secondary language, null for the default language and "all".
	 */
	private function secondary( ?string $lang ): ?string {
		$language = Language::instance();
		$lang     = $lang ?? $language->current();
		return Language::ALL === $lang ? null : $language->secondary( $lang );
	}

	/**
	 * Returns the snapshot.
	 *
	 * @since 1.0.0
	 *
	 * @return Snapshot Snapshot.
	 */
	private function snapshot(): Snapshot {
		return $this->snapshot ?? Snapshot::instance();
	}

	/**
	 * Records and reports an invalid value of a PHP layer once.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key     Key.
	 * @param string $layer   Layer.
	 * @param string $message Message in English; not translated, it may come before init.
	 * @return void
	 */
	private function report( string $key, string $layer, string $message ): void {
		if ( isset( $this->findings[ $key . '|' . $layer ] ) ) {
			return;
		}
		$this->findings[ $key . '|' . $layer ] = array(
			'key'     => $key,
			'layer'   => $layer,
			'message' => $message,
		);
		_doing_it_wrong( __CLASS__ . '::effective', esc_html( $message ), '1.0.0' );
	}

	/**
	 * Builds a result; locks and constants mark it locked.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $value  Value.
	 * @param string $origin Origin.
	 * @return array{value: mixed, origin: string, locked: bool} Result.
	 */
	private static function result( mixed $value, string $origin ): array {
		return array(
			'value'  => $value,
			'origin' => $origin,
			'locked' => self::ORIGIN_LOCKED === $origin || self::ORIGIN_CONSTANT === $origin,
		);
	}

	/**
	 * Returns the module slug of a module switch key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Key.
	 * @return string|null Slug, or null when the key is no module switch.
	 */
	private function module_slug( string $key ): ?string {
		if ( ! str_starts_with( $key, 'module_' ) ) {
			return null;
		}
		foreach ( Module_State::instance()->catalog()->slugs() as $slug ) {
			if ( Registry::module_key( $slug ) === $key ) {
				return $slug;
			}
		}
		return null;
	}
}
