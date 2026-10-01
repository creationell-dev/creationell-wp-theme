<?php
/**
 * Module store on the settings snapshot.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings;

use Creationell\WpTheme\Modules\Module_Catalog;
use Creationell\WpTheme\Modules\Module_Manifest;
use Creationell\WpTheme\Modules\Module_Row_Store_Interface;
use Creationell\WpTheme\Modules\Option_Module_Store;
use InvalidArgumentException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Keeps the backend states of the modules as setting rows and reads them from the autoloaded snapshot.
 *
 * A state lives in the row creationell_wp_theme_global_module_<slug> without
 * reference row. The switches are not translatable, so the row has no
 * language suffix and only the default snapshot holds it.
 *
 * get() reads the default snapshot directly, without the registry: the module
 * gate resolves the states on after_setup_theme 20, before Registry::boot()
 * adds the switches of the installed modules on init. The front end needs no
 * extra query and no ACF. While there is no current default snapshot (fresh
 * site with rows from before the snapshot, or a snapshot of another theme
 * version until admin_init rebuilds it), get() falls back to the raw rows
 * through Option_Module_Store, primed in one query. get() returns
 * any module state; whether the module knows it is left to Module_State, which
 * reports "invalid_backend".
 *
 * A current snapshot is the only source. A row written past the watchers of
 * the snapshot (before init, SQL) counts only after the next rebuild; row()
 * shows it, so the module switcher can put it in line.
 *
 * set() writes or deletes the row through Option_Store and rebuilds the
 * default snapshot. Only the module switcher calls set(); the setter refuses
 * module switches.
 *
 * @since 1.0.0
 */
final class Settings_Module_Store implements Module_Row_Store_Interface {

	/**
	 * Row store.
	 *
	 * @var Option_Store
	 */
	private Option_Store $store;

	/**
	 * Snapshot; null uses the shared one.
	 *
	 * @var Snapshot|null
	 */
	private ?Snapshot $snapshot;

	/**
	 * Reader of the raw rows while there is no current snapshot.
	 *
	 * @var Option_Module_Store
	 */
	private Option_Module_Store $fallback;

	/**
	 * Whether the raw rows of the catalog modules were primed.
	 *
	 * @var bool
	 */
	private bool $primed = false;

	/**
	 * Takes store and snapshot; without them it builds its own store and uses the shared snapshot.
	 *
	 * @since 1.0.0
	 *
	 * @param Option_Store|null $store    Row store.
	 * @param Snapshot|null     $snapshot Snapshot.
	 */
	public function __construct( ?Option_Store $store = null, ?Snapshot $snapshot = null ) {
		$this->store    = $store ?? new Option_Store();
		$this->snapshot = $snapshot;
		$this->fallback = new Option_Module_Store();
	}

	/**
	 * Returns the backend state of a module from the default snapshot, or from the raw row without a current snapshot.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Module slug.
	 * @return string|null State, or null without a row or with a value that is no module state.
	 */
	public function get( string $slug ): ?string {
		$snapshot = $this->snapshot();
		if ( ! $snapshot->is_current() ) {
			$this->prime();
			return $this->fallback->get( $slug );
		}
		$state = $snapshot->values( null )[ Registry::module_key( $slug ) ] ?? null;
		return is_string( $state ) && in_array( $state, Module_Manifest::STATES, true ) ? $state : null;
	}

	/**
	 * Returns the stored row of a module, past the snapshot.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Module slug.
	 * @return string|null Stored value; null without a row; an empty string for a value that is no string.
	 */
	public function row( string $slug ): ?string {
		$value = get_option( Option_Module_Store::option_name( $slug ), null );
		if ( null === $value ) {
			return null;
		}
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Writes or deletes the backend state of a module and rebuilds the default snapshot.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $slug  Module slug.
	 * @param string|null $state State; null deletes it.
	 * @return void
	 * @throws InvalidArgumentException When the module is unknown or does not know the state.
	 */
	public function set( string $slug, ?string $state ): void {
		$definition = Registry::instance()->get( Registry::module_key( $slug ) );
		if ( null === $state ) {
			$this->store->delete( $definition );
		} elseif ( $definition->accepts( $state ) ) {
			$this->store->write( $definition, $state, null, false );
		} else {
			throw new InvalidArgumentException( esc_html( sprintf( 'Module %1$s has no state %2$s.', $slug, $state ) ) );
		}
		$this->snapshot()->rebuild( array( Language::instance()->default() ) );
	}

	/**
	 * Loads the raw rows of all catalog modules in one query, once per store.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function prime(): void {
		if ( $this->primed ) {
			return;
		}
		$this->primed = true;
		wp_prime_option_caches( array_map( array( Option_Module_Store::class, 'option_name' ), Module_Catalog::instance()->slugs() ) );
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
}
