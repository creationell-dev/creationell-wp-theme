<?php
/**
 * The only write path of the module states.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules;

use Closure;
use Creationell\WpTheme\Core\Capabilities;
use Creationell\WpTheme\Core\Content_Scanner;
use Creationell\WpTheme\Core\Scan_Result;
use InvalidArgumentException;
use Throwable;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Switches a module for the module page, WP-CLI and the settings import.
 *
 * Steps, in this order:
 * 1. Without the capability creationell_wp_theme_manage_modules: "denied".
 * 2. Module not in the catalog or target outside its states: "invalid".
 * 3. Child lock or constant: "locked".
 * 4. Configured state is the target: "unchanged"; a stored row that disagrees
 *    with the copy of a Module_Row_Store_Interface is put in line, except in a
 *    dry run.
 * 5. Target "off": the blocks of the module are counted. A confirmation is
 *    needed when the module has warn_before_off or live contents use its
 *    blocks; without it, or when the person saw fewer live contents than
 *    there are now: "needs_confirmation" with the scan.
 * 6. Dry run: "dry_run", nothing written, no action.
 * 7. Action creationell_wp_theme_before_module_switch.
 * 8. The store keeps the target only where it differs from the base (registry
 *    default and child default) and deletes it otherwise; the state resolver
 *    forgets its cache.
 * 9. The pattern caches of parent and child theme are cleared, for
 *    woocommerce also the WooCommerce template cache. Nothing is compiled.
 * 10. on_change of the module runs; when it throws, the switch stays, the
 *     result warns "callback_failed" and the PHP error log names the module and
 *     the class of the exception.
 * 11. Action creationell_wp_theme_module_state_changed; a module that stays
 *     effectively off warns "dependency_missing:<reason>". Status "switched".
 *
 * Without a backup of the settings (filter
 * creationell_wp_theme_module_backup_available) the results "needs_confirmation"
 * and "switched" with the target "off" warn "backup_hint". A switch takes
 * effect with the next request: a loaded bootstrap.php stays loaded.
 *
 * Example:
 *
 *     $request = new Switch_Request( 'post-lists', 'off', 'import', true );
 *     $result  = Module_Switcher::instance()->switch( $request );
 *     if ( Switch_Result::NEEDS_CONFIRMATION === $result->status ) {
 *         // Show $result->scan and ask.
 *     }
 *
 * @since 1.0.0
 */
final class Module_Switcher {

	/**
	 * Action before a module switch is written.
	 *
	 * @since 1.0.0
	 */
	public const BEFORE_ACTION = 'creationell_wp_theme_before_module_switch';

	/**
	 * Action after a module switch was written.
	 *
	 * @since 1.0.0
	 */
	public const CHANGED_ACTION = 'creationell_wp_theme_module_state_changed';

	/**
	 * Filter that tells whether a backup of the settings is available.
	 *
	 * @since 1.0.0
	 */
	public const BACKUP_FILTER = 'creationell_wp_theme_module_backup_available';

	/**
	 * Prefix of the lines in the PHP error log.
	 *
	 * @since 1.0.0
	 */
	public const LOG_PREFIX = 'creationell-wp-theme: ';

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * State resolver.
	 *
	 * @var Module_State
	 */
	private Module_State $state;

	/**
	 * Store of the backend layer, from the state resolver.
	 *
	 * @var Module_Store_Interface
	 */
	private Module_Store_Interface $store;

	/**
	 * Scanner that counts the blocks of a module.
	 *
	 * @var Content_Scanner
	 */
	private Content_Scanner $scanner;

	/**
	 * Clears the caches after a switch; takes the module slug.
	 *
	 * @var Closure
	 * @phpstan-var Closure(string): void
	 */
	private Closure $clear_caches;

	/**
	 * Writes a line to the error log.
	 *
	 * @var Closure
	 * @phpstan-var Closure(string): void
	 */
	private Closure $log;

	/**
	 * Takes the state resolver with its store, the scanner, the cache clearing and the log.
	 *
	 * @since 1.0.0
	 *
	 * @param Module_State    $state        State resolver; its store receives the writes.
	 * @param Content_Scanner $scanner   Scanner for the blocks of a module.
	 * @param Closure|null    $clear_caches Clears the caches after a switch, takes the slug; null clears the pattern caches of parent and child and, for woocommerce, the WooCommerce template cache.
	 * @param Closure|null    $log          Writes a line to the error log; null uses error_log().
	 * @phpstan-param (Closure(string): void)|null $clear_caches
	 * @phpstan-param (Closure(string): void)|null $log
	 * @throws InvalidArgumentException When the state resolver has no store.
	 */
	public function __construct( Module_State $state, Content_Scanner $scanner, ?Closure $clear_caches = null, ?Closure $log = null ) {
		$store = $state->store();
		if ( null === $store ) {
			throw new InvalidArgumentException( 'The module switcher needs a module state with a store.' );
		}
		$this->state        = $state;
		$this->store        = $store;
		$this->scanner      = $scanner;
		$this->clear_caches = $clear_caches ?? static function ( string $slug ): void {
			self::clear_caches( $slug );
		};
		$this->log          = $log ?? static function ( string $line ): void {
			error_log( $line );
		};
	}

	/**
	 * Returns the shared instance over the shared state resolver and a scanner on the global database.
	 *
	 * @since 1.0.0
	 *
	 * @return self Instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self( Module_State::instance(), new Content_Scanner() );
		}
		return self::$instance;
	}

	/**
	 * Replaces the shared instance; null builds a new one on the next call of instance().
	 *
	 * @since 1.0.0
	 *
	 * @param self|null $switcher Instance.
	 * @return void
	 */
	public static function set_instance( ?self $switcher ): void {
		self::$instance = $switcher;
	}

	/**
	 * Returns the state resolver the switcher writes through.
	 *
	 * @since 1.0.0
	 *
	 * @return Module_State State resolver.
	 */
	public function state(): Module_State {
		return $this->state;
	}

	/**
	 * Returns the scanner that counts the blocks of a module before it goes off.
	 *
	 * The module page and "wp creationell-theme module scan" count through it,
	 * so they show the same numbers the switcher asks about.
	 *
	 * @since 1.0.0
	 *
	 * @return Content_Scanner Scanner.
	 */
	public function scanner(): Content_Scanner {
		return $this->scanner;
	}

	/**
	 * Switches a module, see the class description for the steps.
	 *
	 * @since 1.0.0
	 *
	 * @param Switch_Request $request Request.
	 * @return Switch_Result Result.
	 */
	public function switch( Switch_Request $request ): Switch_Result {
		$slug    = $request->slug;
		$target  = $request->target;
		$catalog = $this->state->catalog();
		$from    = $this->state->configured( $slug );

		if ( ! current_user_can( Capabilities::MANAGE_MODULES ) ) {
			return $this->result( Switch_Result::DENIED, $request, $from );
		}
		if ( ! $catalog->is_known( $slug ) || ! in_array( $target, $catalog->states( $slug ), true ) ) {
			return $this->result( Switch_Result::INVALID, $request, $from );
		}
		if ( $this->state->is_locked( $slug ) ) {
			return $this->result( Switch_Result::LOCKED, $request, $from );
		}
		if ( $from === $target ) {
			if ( ! $request->dry_run ) {
				$this->repair( $slug, $target );
			}
			return $this->result( Switch_Result::UNCHANGED, $request, $from );
		}

		$manifest = $catalog->manifest( $slug );
		$scan     = null;
		if ( 'off' === $target ) {
			$blocks = null === $manifest ? array() : $manifest->blocks;
			$scan   = array() === $blocks ? null : $this->scanner->count_blocks( $blocks );
			$live   = null === $scan ? 0 : $scan->total_live();
			$ask    = ( null !== $manifest && $manifest->warn_before_off ) || $live > 0;
			if ( $ask && ( ! $request->confirmed || ( null !== $request->seen_live && $request->seen_live < $live ) ) ) {
				return $this->result( Switch_Result::NEEDS_CONFIRMATION, $request, $from, $scan, $this->backup_warnings() );
			}
		}
		if ( $request->dry_run ) {
			return $this->result( Switch_Result::DRY_RUN, $request, $from, $scan );
		}

		/**
		 * Fires before a module switch is written.
		 *
		 * A backup of the settings, for example, is made here. The state is
		 * written after the callbacks return, whatever they do.
		 *
		 * @since 1.0.0
		 *
		 * @param string $slug    Module slug.
		 * @param string $from    Configured state before the switch.
		 * @param string $to      Target state.
		 * @param string $channel Channel: admin, cli or import.
		 */
		do_action( 'creationell_wp_theme_before_module_switch', $slug, $from, $target, $request->channel );

		$this->store->set( $slug, $target === $this->state->base( $slug ) ? null : $target );
		$this->state->flush();
		( $this->clear_caches )( $slug );

		$warnings = array();
		if ( null !== $manifest && null !== $manifest->on_change ) {
			try {
				( $manifest->on_change )( $from, $target );
			} catch ( Throwable $error ) {
				$warnings[] = Switch_Result::WARNING_CALLBACK_FAILED;
				( $this->log )( sprintf( '%1$smodule %2$s: on_change failed with %3$s.', self::LOG_PREFIX, $slug, get_class( $error ) ) );
			}
		}

		/**
		 * Fires after a module switch was written.
		 *
		 * The new state takes effect with the next request.
		 *
		 * @since 1.0.0
		 *
		 * @param string $slug    Module slug.
		 * @param string $from    Configured state before the switch.
		 * @param string $to      Configured state after the switch.
		 * @param string $channel Channel: admin, cli or import.
		 */
		do_action( 'creationell_wp_theme_module_state_changed', $slug, $from, $target, $request->channel );

		if ( 'off' !== $target && 'off' === $this->state->state( $slug ) ) {
			$warnings[] = Switch_Result::WARNING_DEPENDENCY_MISSING . $this->state->reason( $slug );
		}
		if ( 'off' === $target ) {
			$warnings = array_merge( $warnings, $this->backup_warnings() );
		}
		return $this->result( Switch_Result::SWITCHED, $request, $from, $scan, $warnings );
	}

	/**
	 * Puts a stored row in line with an unchanged configured state, without actions and cache clearing.
	 *
	 * A store that reads a copy (Module_Row_Store_Interface) may hold a row the
	 * copy does not show, e.g. written before init or by SQL; it would come
	 * back with the next rebuild of the copy.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug   Module slug.
	 * @param string $target Configured state, equal to the target.
	 * @return void
	 */
	private function repair( string $slug, string $target ): void {
		if ( ! $this->store instanceof Module_Row_Store_Interface ) {
			return;
		}
		$row = $target === $this->state->base( $slug ) ? null : $target;
		if ( $this->store->row( $slug ) === $row ) {
			return;
		}
		$this->store->set( $slug, $row );
		$this->state->flush();
	}

	/**
	 * Builds a result with the current effective state.
	 *
	 * @since 1.0.0
	 *
	 * @param string             $status   Status.
	 * @param Switch_Request     $request  Request.
	 * @param string             $from     Configured state before the switch.
	 * @param Scan_Result|null   $scan     Scan, if counted.
	 * @param array<int, string> $warnings Warnings.
	 * @phpstan-param list<string> $warnings
	 * @return Switch_Result Result.
	 */
	private function result( string $status, Switch_Request $request, string $from, ?Scan_Result $scan = null, array $warnings = array() ): Switch_Result {
		return new Switch_Result( $status, $from, $request->target, $this->state->state( $request->slug ), $scan, $warnings );
	}

	/**
	 * Returns the backup hint when no backup of the settings is available.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> The warning backup_hint, or nothing.
	 * @phpstan-return list<string>
	 */
	private function backup_warnings(): array {
		/**
		 * Filters whether a backup of the settings is available before a module goes off.
		 *
		 * Without one, switching a module off warns that nothing backs up its
		 * settings; the backup of the settings import returns true.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $available Whether a backup is available; false in the theme.
		 */
		$available = apply_filters( 'creationell_wp_theme_module_backup_available', false );
		return true === $available ? array() : array( Switch_Result::WARNING_BACKUP_HINT );
	}

	/**
	 * Clears the pattern caches of parent and child theme and, for woocommerce, the WooCommerce template cache.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Module slug.
	 * @return void
	 */
	private static function clear_caches( string $slug ): void {
		foreach ( array_unique( array( get_template(), get_stylesheet() ) ) as $stylesheet ) { // creationell-allow-stylesheet: the active theme folder names the pattern cache to clear, never a key.
			wp_get_theme( $stylesheet )->delete_pattern_cache();
		}
		if ( 'woocommerce' === $slug && function_exists( 'wc_clear_template_cache' ) ) {
			wc_clear_template_cache();
		}
	}
}
