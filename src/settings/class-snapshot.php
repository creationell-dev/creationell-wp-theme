<?php
/**
 * Autoloaded snapshot of the backend values.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings;

use Creationell\WpTheme\Modules\Module_Catalog;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Collects the backend rows of the settings into one autoloaded option per language, which the getter reads.
 *
 * Option "creationell_wp_theme_settings_snapshot" holds the rows of the default
 * language: every untranslated key and the translated keys in the default
 * language. "creationell_wp_theme_settings_snapshot_<lang>" holds the translated
 * rows of a secondary language only; the getter falls back to the default
 * snapshot. Form: array{schema: 1, lang: string, theme_version: string,
 * values: array<string, scalar>, hash: string, modules: string}. "modules" is
 * the fingerprint of the installed modules (module_fingerprint()): the
 * switches of the installed modules enter only when the registry knows them.
 * Only valid rows enter; the Bootstrap line (section "system") never does.
 *
 * Without any row there is no snapshot option: a fresh theme writes no option.
 * A rebuild writes only when the hash, the schema or the theme version changed.
 * Rows written by others (ACF, WP-CLI option commands, imports) mark the
 * snapshot; it is rebuilt once on shutdown. A snapshot of another theme
 * version is ignored until admin_init or WP-CLI rebuilds it; a default snapshot
 * of other installed modules is stale as well, and the module store does not
 * read it (is_current()).
 *
 * @since 1.0.0
 */
final class Snapshot {

	/**
	 * Option of the default language; a secondary language appends "_<lang>".
	 *
	 * @since 1.0.0
	 */
	public const OPTION = 'creationell_wp_theme_settings_snapshot';

	/**
	 * Version of the option form.
	 *
	 * @since 1.0.0
	 */
	public const SCHEMA = 1;

	/**
	 * Action fired after a rebuild changed snapshots, with the changed language codes.
	 *
	 * @since 1.0.0
	 */
	public const REBUILT_ACTION = 'creationell_wp_theme_snapshot_rebuilt';

	/**
	 * Row names of the own settings: global rows, options rows with or without language, each with its reference row.
	 *
	 * @since 1.0.0
	 */
	private const OWN_ROW = '~^_?(?:creationell_wp_theme_global_|options_(?:[a-z0-9][a-z0-9_-]{0,9}_)?creationell_wp_theme_)[a-z0-9_]+$~D';

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Row store.
	 *
	 * @var Option_Store
	 */
	private Option_Store $store;

	/**
	 * Sanitizer for the rows.
	 *
	 * @var Sanitizer
	 */
	private Sanitizer $sanitizer;

	/**
	 * Version of the running theme.
	 *
	 * @var string
	 */
	private string $theme_version;

	/**
	 * Payloads read in this request by option name; null for a missing or broken option.
	 *
	 * @var array<string, array{schema: int, lang: string, theme_version: string, values: array<string, mixed>, hash: string, modules: string|null}|null>
	 */
	private array $cache = array();

	/**
	 * Counts the rebuilds of this instance, so readers can drop their caches.
	 *
	 * @var int
	 */
	private int $revision = 0;

	/**
	 * Whether a foreign writer changed an own row since the last rebuild.
	 *
	 * @var bool
	 */
	private bool $dirty = false;

	/**
	 * Takes store, sanitizer and theme version; without them it builds its own and reads CREATIONELL_WP_THEME_VERSION.
	 *
	 * @since 1.0.0
	 *
	 * @param Option_Store|null $store         Row store.
	 * @param Sanitizer|null    $sanitizer     Sanitizer.
	 * @param string|null       $theme_version Theme version written into and expected in the snapshot.
	 */
	public function __construct( ?Option_Store $store = null, ?Sanitizer $sanitizer = null, ?string $theme_version = null ) {
		$this->store         = $store ?? new Option_Store();
		$this->sanitizer     = $sanitizer ?? new Sanitizer();
		$this->theme_version = $theme_version ?? ( defined( 'CREATIONELL_WP_THEME_VERSION' ) ? CREATIONELL_WP_THEME_VERSION : '' );
	}

	/**
	 * Returns the shared instance.
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
	 * @param self|null $snapshot Instance.
	 * @return void
	 */
	public static function set_instance( ?self $snapshot ): void {
		self::$instance = $snapshot;
	}

	/**
	 * Registers the watchers of foreign writes and the rebuild on admin_init; runs on init.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function boot(): void {
		foreach ( array( 'added_option', 'updated_option', 'deleted_option' ) as $hook ) {
			add_action( $hook, array( self::class, 'on_option_change' ), 10, 1 );
		}
		add_action( 'shutdown', array( self::class, 'on_shutdown' ), 10, 0 );
		add_action( 'admin_init', array( self::class, 'on_admin_init' ), 10, 0 );
	}

	/**
	 * Marks the shared snapshot when an own row changed; callback of added_option, updated_option and deleted_option.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $option Option name.
	 * @return void
	 */
	public static function on_option_change( mixed $option ): void {
		if ( is_string( $option ) && self::is_own_row( $option ) ) {
			self::instance()->dirty = true;
		}
	}

	/**
	 * Rebuilds the shared snapshot once when a foreign writer changed rows; callback of shutdown.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function on_shutdown(): void {
		$snapshot = self::instance();
		if ( $snapshot->dirty ) {
			$snapshot->rebuild();
		}
	}

	/**
	 * Rebuilds a stale shared snapshot; callback of admin_init.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function on_admin_init(): void {
		self::instance()->maybe_rebuild();
	}

	/**
	 * Tells whether an option name is a value or reference row of the settings.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Option name.
	 * @return bool True for own rows; false for the snapshots and other options.
	 */
	public static function is_own_row( string $name ): bool {
		return 1 === preg_match( self::OWN_ROW, $name );
	}

	/**
	 * Returns the option name of a snapshot.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $lang Code of a secondary language; null for the default language.
	 * @return string Option name.
	 */
	public static function option_name( ?string $lang = null ): string {
		return null === $lang ? self::OPTION : self::OPTION . '_' . $lang;
	}

	/**
	 * Returns the number of rebuilds of this instance; readers drop their caches when it changes.
	 *
	 * @since 1.0.0
	 *
	 * @return int Revision.
	 */
	public function revision(): int {
		return $this->revision;
	}

	/**
	 * Returns the backend values of a language.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $lang Language code; null or the default code for the default snapshot.
	 * @return array<string, mixed> Scalar values by key; empty without snapshot or for a snapshot of another theme version.
	 */
	public function values( ?string $lang = null ): array {
		$payload = $this->read( Language::instance()->secondary( $lang ) );
		if ( null === $payload || $payload['theme_version'] !== $this->theme_version ) {
			return array();
		}
		return $payload['values'];
	}

	/**
	 * Returns the fingerprint of the installed modules of the shared catalog.
	 *
	 * The catalog is scanned once per request anyway, for the module gate; the fingerprint adds no query.
	 *
	 * @since 1.0.0
	 *
	 * @return string SHA-1 of the sorted slugs of the installed modules.
	 */
	public static function module_fingerprint(): string {
		$slugs = array_keys( Module_Catalog::instance()->installed() );
		sort( $slugs, SORT_STRING );
		return sha1( implode( ',', $slugs ) );
	}

	/**
	 * Tells whether the default snapshot exists, belongs to the running theme version and to the installed modules.
	 *
	 * Reads only the autoloaded snapshot option and the module catalog and needs no
	 * registry, so it may run from after_setup_theme on.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when the default snapshot holds the module switches of this request.
	 */
	public function is_current(): bool {
		$payload = $this->read( null );
		return null !== $payload && $payload['theme_version'] === $this->theme_version && self::module_fingerprint() === $payload['modules'];
	}

	/**
	 * Tells whether the snapshots need a rebuild: another theme version, other installed modules, or rows without a default snapshot.
	 *
	 * Reads the rows when there is no default snapshot; call it in the admin or from WP-CLI only.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when stale.
	 */
	public function is_stale(): bool {
		$language = Language::instance();
		foreach ( $language->active() as $lang ) {
			$payload = $this->read( $language->secondary( $lang ) );
			if ( null !== $payload && $payload['theme_version'] !== $this->theme_version ) {
				return true;
			}
		}
		$default = $this->read( null );
		if ( null !== $default ) {
			return self::module_fingerprint() !== $default['modules'];
		}
		$definitions = $this->definitions( null );
		wp_prime_option_caches( array_map( static fn( Definition $definition ): string => Option_Store::value_name( $definition ), $definitions ) );
		foreach ( $definitions as $definition ) {
			if ( $this->store->has( $definition ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Rebuilds all snapshots when they are stale.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function maybe_rebuild(): void {
		if ( $this->is_stale() ) {
			$this->rebuild();
		}
	}

	/**
	 * Rebuilds the snapshots of the given languages from the rows.
	 *
	 * Fires creationell_wp_theme_snapshot_rebuilt with the changed languages.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>|null $langs Language codes; null for all active languages.
	 * @return array<int, string> Codes of the languages whose snapshot option was written or deleted.
	 */
	public function rebuild( ?array $langs = null ): array {
		$language = Language::instance();
		$langs    = array_values( array_unique( $langs ?? $language->active() ) );
		$names    = array();
		foreach ( $langs as $lang ) {
			$secondary = $language->secondary( $lang );
			if ( null !== $secondary && ! Option_Store::valid_lang( $secondary ) ) {
				continue;
			}
			foreach ( $this->definitions( $secondary ) as $definition ) {
				$names[] = Option_Store::value_name( $definition, $secondary );
			}
			$names[] = self::option_name( $secondary );
		}
		wp_prime_option_caches( $names );

		$changed = array();
		foreach ( $langs as $lang ) {
			$secondary = $language->secondary( $lang );
			if ( ( null !== $secondary && ! Option_Store::valid_lang( $secondary ) ) || ! $this->write( $secondary, $secondary ?? $language->default() ) ) {
				continue;
			}
			$changed[] = $lang;
		}
		$this->dirty = false;
		$this->cache = array();
		++$this->revision;

		if ( array() !== $changed ) {
			/**
			 * Fires after a rebuild changed snapshots of the settings.
			 *
			 * Readers that cache setting values drop their caches here.
			 *
			 * @since 1.0.0
			 *
			 * @param array<int, string> $langs Codes of the changed languages.
			 */
			do_action( 'creationell_wp_theme_snapshot_rebuilt', $changed );
		}
		return $changed;
	}

	/**
	 * Returns the payload of a snapshot option when its form is valid.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $secondary Code of a secondary language; null for the default language.
	 * @return array{schema: int, lang: string, theme_version: string, values: array<string, mixed>, hash: string, modules: string|null}|null Payload, or null when missing or broken.
	 */
	public function read( ?string $secondary ): ?array {
		$name = self::option_name( $secondary );
		if ( ! array_key_exists( $name, $this->cache ) ) {
			$this->cache[ $name ] = self::payload( get_option( $name, null ) );
		}
		return $this->cache[ $name ];
	}

	/**
	 * Writes or deletes one snapshot option.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $secondary Code of a secondary language; null for the default language.
	 * @param string      $code      Language code written into the payload.
	 * @return bool True when the option changed.
	 */
	private function write( ?string $secondary, string $code ): bool {
		$values = array();
		foreach ( $this->definitions( $secondary ) as $definition ) {
			$raw = $this->store->read( $definition, $secondary );
			if ( null === $raw ) {
				continue;
			}
			$clean = $this->sanitizer->clean( $definition, $raw );
			if ( $clean['valid'] && is_scalar( $clean['value'] ) ) {
				$values[ $definition->key ] = $clean['value'];
			}
		}

		$name     = self::option_name( $secondary );
		$existing = self::payload( get_option( $name, null ) );
		$modules  = self::module_fingerprint();
		if ( array() === $values ) {
			return null !== get_option( $name, null ) && delete_option( $name );
		}
		$json = wp_json_encode( $values );
		$hash = sha1( false === $json ? '' : $json );
		if ( null !== $existing && $existing['hash'] === $hash && $existing['theme_version'] === $this->theme_version && $existing['lang'] === $code && $existing['modules'] === $modules ) {
			return false;
		}
		update_option(
			$name,
			array(
				'schema'        => self::SCHEMA,
				'lang'          => $code,
				'theme_version' => $this->theme_version,
				'values'        => $values,
				'hash'          => $hash,
				'modules'       => $modules,
			),
			true
		);
		return true;
	}

	/**
	 * Returns the definitions a snapshot holds.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $secondary Code of a secondary language; null for the default language.
	 * @return array<int, Definition> All keys except the section "system" for the default language, the translated keys for a secondary one.
	 */
	private function definitions( ?string $secondary ): array {
		$definitions = array();
		foreach ( Registry::instance()->all() as $definition ) {
			if ( 'system' === $definition->section || ( null !== $secondary && ! $definition->translatable ) ) {
				continue;
			}
			$definitions[] = $definition;
		}
		return $definitions;
	}

	/**
	 * Checks the form of a stored snapshot.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $stored Stored option value.
	 * @return array{schema: int, lang: string, theme_version: string, values: array<string, mixed>, hash: string, modules: string|null}|null Payload, or null for another form.
	 */
	private static function payload( mixed $stored ): ?array {
		if ( ! is_array( $stored ) || self::SCHEMA !== ( $stored['schema'] ?? null ) || ! is_array( $stored['values'] ?? null ) ) {
			return null;
		}
		$lang    = $stored['lang'] ?? null;
		$version = $stored['theme_version'] ?? null;
		$hash    = $stored['hash'] ?? null;
		$modules = $stored['modules'] ?? null;
		if ( ! is_string( $lang ) || ! is_string( $version ) || ! is_string( $hash ) ) {
			return null;
		}
		$values = array();
		foreach ( $stored['values'] as $key => $value ) {
			if ( is_string( $key ) && is_scalar( $value ) ) {
				$values[ $key ] = $value;
			}
		}
		return array(
			'schema'        => self::SCHEMA,
			'lang'          => $lang,
			'theme_version' => $version,
			'values'        => $values,
			'hash'          => $hash,
			'modules'       => is_string( $modules ) ? $modules : null,
		);
	}
}
