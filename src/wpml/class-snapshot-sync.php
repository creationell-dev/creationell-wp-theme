<?php
/**
 * Snapshots that follow the WPML language list.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Wpml;

use Closure;
use Creationell\WpTheme\Settings\Language;
use Creationell\WpTheme\Settings\Option_Store;
use Creationell\WpTheme\Settings\Registry;
use Creationell\WpTheme\Settings\Snapshot;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Keeps one settings snapshot per active WPML language: rebuilds them when languages are added and deletes those of removed languages.
 *
 * The last seen language list is stored in the transient
 * creationell_wp_theme_wpml_langs (sorted codes joined with ",", no
 * expiration). On admin_init the sync compares it with languages(), the
 * active languages including the hidden ones; on a difference it rebuilds all
 * snapshots, deletes the snapshots of the languages no longer active and
 * stores the new list. A secondary language
 * with rows options_<lang>_creationell_wp_theme_* but without its snapshot is
 * stale and rebuilt, too. Translations written by the ACFML dashboard need no
 * sync: they are option writes, which Snapshot rebuilds on shutdown.
 *
 * Without WPML the sync reads and writes nothing.
 *
 * @since 1.0.0
 */
final class Snapshot_Sync {

	/**
	 * Transient with the language list of the last sync.
	 *
	 * @since 1.0.0
	 */
	public const TRANSIENT = 'creationell_wp_theme_wpml_langs';

	/**
	 * WPML setting with the codes of all active languages, hidden ones included.
	 *
	 * @since 1.0.0
	 */
	public const SETTING_ACTIVE = 'active_languages';

	/**
	 * WPML setting with the codes of the hidden languages; fallback when SETTING_ACTIVE holds no list.
	 *
	 * @since 1.0.0
	 */
	public const SETTING_HIDDEN = 'hidden_languages';

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Tells whether WPML is active.
	 *
	 * @var Closure
	 * @phpstan-var Closure(): bool
	 */
	private Closure $wpml_active;

	/**
	 * Takes the WPML check; without one it asks Wpml_Integration.
	 *
	 * @since 1.0.0
	 *
	 * @param Closure|null $wpml_active Returns whether WPML is active.
	 * @phpstan-param (Closure(): bool)|null $wpml_active
	 */
	public function __construct( ?Closure $wpml_active = null ) {
		$this->wpml_active = $wpml_active ?? Wpml_Integration::is_active( ... );
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
	 * @param self|null $sync Instance.
	 * @return void
	 */
	public static function set_instance( ?self $sync ): void {
		self::$instance = $sync;
	}

	/**
	 * Syncs the snapshots with the language list; callback of admin_init (priority 20).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function on_admin_init(): void {
		self::instance()->maybe_sync();
	}

	/**
	 * Returns the active WPML languages independent of the user of the request.
	 *
	 * The filter wpml_active_languages leaves out hidden languages unless the
	 * user may see them (administrators in the admin, users with the option "Display
	 * hidden languages"); editors, anonymous admin-ajax calls and WP-CLI get a
	 * shorter list. The WPML setting active_languages holds all active codes,
	 * hidden ones included, and is added; without it the setting
	 * hidden_languages is added instead. A code in hidden_languages alone
	 * does not count while active_languages holds a list, because WPML keeps
	 * the codes of deactivated languages there.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Valid codes, unique, in the order of wpml_active_languages followed by the added codes.
	 */
	public function languages(): array {
		$added = self::setting_codes( self::SETTING_ACTIVE );
		if ( array() === $added ) {
			$added = self::setting_codes( self::SETTING_HIDDEN );
		}
		$codes = array();
		foreach ( array_merge( Language::instance()->active(), $added ) as $code ) {
			if ( is_string( $code ) && Option_Store::valid_lang( $code ) ) {
				$codes[ $code ] = $code;
			}
		}
		return array_values( $codes );
	}

	/**
	 * Compares the active languages with the last seen list and brings the snapshots in line.
	 *
	 * With $write false nothing is rebuilt, deleted or stored; the result says
	 * what a sync would change (for "wp creationell-theme wpml status" and
	 * doctor). Without a stored list nothing counts as added or removed; a
	 * sync then rebuilds all snapshots and stores the list.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $write Whether to rebuild, delete and store.
	 * @return array<int, string> Sorted codes of the added, removed and stale languages; empty without WPML.
	 */
	public function maybe_sync( bool $write = true ): array {
		if ( ! ( $this->wpml_active )() ) {
			return array();
		}
		$active  = self::codes( $this->languages() );
		$list    = implode( ',', $active );
		$stored  = get_transient( self::TRANSIENT );
		$changed = array();

		if ( $stored !== $list ) {
			$known   = is_string( $stored ) ? self::codes( explode( ',', $stored ) ) : $active;
			$removed = array_values( array_diff( $known, $active ) );
			$changed = array_merge( array_values( array_diff( $active, $known ) ), $removed );
			if ( $write ) {
				Snapshot::instance()->rebuild( $active );
				foreach ( $removed as $code ) {
					delete_option( Snapshot::option_name( $code ) );
				}
				set_transient( self::TRANSIENT, $list, 0 );
			}
		}

		$stale = $this->stale_languages();
		if ( $write && array() !== $stale ) {
			Snapshot::instance()->rebuild( $stale );
		}
		return self::codes( array_merge( $changed, $stale ) );
	}

	/**
	 * Returns the active secondary languages that have rows but no snapshot.
	 *
	 * The default language is left to Snapshot::is_stale(), which admin_init
	 * checks already. Reads the rows with one priming call; call it in the
	 * admin or from WP-CLI only.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Sorted language codes; empty without WPML.
	 */
	public function stale_languages(): array {
		if ( ! ( $this->wpml_active )() ) {
			return array();
		}
		$language    = Language::instance();
		$snapshot    = Snapshot::instance();
		$registry    = Registry::instance();
		$definitions = array_map( array( $registry, 'get' ), $registry->translatable_keys() );
		$candidates  = array();
		foreach ( $this->languages() as $code ) {
			$secondary = $language->secondary( $code );
			if ( null !== $secondary && null === $snapshot->read( $secondary ) ) {
				$candidates[] = $secondary;
			}
		}
		if ( array() === $candidates || array() === $definitions ) {
			return array();
		}

		$names = array();
		foreach ( $candidates as $code ) {
			foreach ( $definitions as $definition ) {
				$names[] = Option_Store::value_name( $definition, $code );
			}
		}
		wp_prime_option_caches( $names );

		$store = new Option_Store();
		$stale = array();
		foreach ( $candidates as $code ) {
			foreach ( $definitions as $definition ) {
				if ( $store->has( $definition, $code ) ) {
					$stale[] = $code;
					break;
				}
			}
		}
		return $stale;
	}

	/**
	 * Returns the valid language codes of a WPML setting that holds a list of codes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Setting key, one of the SETTING_* constants.
	 * @return array<int, string> Sorted codes; empty without WPML or for another value.
	 */
	private static function setting_codes( string $key ): array {
		$value = apply_filters( 'wpml_setting', null, $key );
		return is_array( $value ) ? self::codes( array_values( $value ) ) : array();
	}

	/**
	 * Returns the valid language codes of a list, unique and sorted.
	 *
	 * Codes that cannot appear in an option name (Option_Store::valid_lang()) are dropped.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, mixed> $codes Language codes.
	 * @return array<int, string> Codes.
	 */
	private static function codes( array $codes ): array {
		$valid = array();
		foreach ( $codes as $code ) {
			if ( is_string( $code ) && Option_Store::valid_lang( $code ) ) {
				$valid[ $code ] = $code;
			}
		}
		sort( $valid );
		return $valid;
	}
}
