<?php
/**
 * Hooks of the settings transfer.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

use Closure;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Connects the settings transfer to the module core and to WordPress.
 *
 * - Filter creationell_wp_theme_module_backup_available: true while the
 *   backup store can create backups, so the module core leaves out its hint
 *   to back up first.
 * - Action creationell_wp_theme_before_module_switch: a switch over the admin
 *   or WP-CLI first backs up the section "modules" (reason "module_switch").
 *   The import backs up before its own switches and gets no second backup.
 * - Action deleted_user: the ID of the deleted user becomes 0 in the backups
 *   and the log.
 *
 * @since 1.0.0
 */
final class Transfer {

	/**
	 * Filter of the module core that tells whether a backup is made before a switch.
	 *
	 * @since 1.0.0
	 */
	public const BACKUP_FILTER = 'creationell_wp_theme_module_backup_available';

	/**
	 * Action of the module core before a module switch.
	 *
	 * @since 1.0.0
	 */
	public const SWITCH_ACTION = 'creationell_wp_theme_before_module_switch';

	/**
	 * Channels of module switches that get a backup.
	 *
	 * @since 1.0.0
	 */
	public const BACKUP_CHANNELS = array( 'admin', 'cli' );

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Backup store; null for the shared store.
	 *
	 * @var Backup_Store|null
	 */
	private ?Backup_Store $store;

	/**
	 * Transfer log; null for the shared log.
	 *
	 * @var Transfer_Log|null
	 */
	private ?Transfer_Log $log;

	/**
	 * Returns the ID of the current user.
	 *
	 * @var Closure
	 * @phpstan-var Closure(): int
	 */
	private Closure $current_user;

	/**
	 * Takes the store, the log and the current user.
	 *
	 * @since 1.0.0
	 *
	 * @param Backup_Store|null $store        Store; without one the shared store at the time of use.
	 * @param Transfer_Log|null $log          Log; without one the shared log at the time of use.
	 * @param Closure|null      $current_user Returns the ID of the current user; without one get_current_user_id().
	 * @phpstan-param (Closure(): int)|null $current_user
	 */
	public function __construct( ?Backup_Store $store = null, ?Transfer_Log $log = null, ?Closure $current_user = null ) {
		$this->store        = $store;
		$this->log          = $log;
		$this->current_user = $current_user ?? static fn(): int => get_current_user_id();
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
	 * @param self|null $transfer Instance.
	 * @return void
	 */
	public static function set_instance( ?self $transfer ): void {
		self::$instance = $transfer;
	}

	/**
	 * Registers the hooks; runs from Theme::boot().
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( self::BACKUP_FILTER, array( self::class, 'backup_available' ), 10, 1 );
		add_action( self::SWITCH_ACTION, array( self::class, 'before_module_switch' ), 10, 4 );
		add_action( 'deleted_user', array( self::class, 'deleted_user' ), 10, 1 );
	}

	/**
	 * Returns true while the store can create backups, otherwise the value it gets.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $available Whether a backup is made before a module switch.
	 * @return bool Filtered value.
	 */
	public static function backup_available( mixed $available ): bool {
		return self::instance()->store()->can_create() || true === $available;
	}

	/**
	 * Backs up the modules before a switch over the admin or WP-CLI.
	 *
	 * Without a snapshot source the switch goes ahead without a backup; the
	 * filter has then left the hint of the module core in place. A backup that
	 * fails throws out of the action, so the module core stops the switch
	 * before it writes.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $slug    Module slug.
	 * @param mixed $from    State before the switch.
	 * @param mixed $to      State after the switch.
	 * @param mixed $channel Channel of the switch, e.g. "admin", "cli" or "import".
	 * @return void
	 */
	public static function before_module_switch( mixed $slug, mixed $from, mixed $to, mixed $channel ): void {
		unset( $slug, $from, $to );
		if ( ! is_string( $channel ) || ! in_array( $channel, self::BACKUP_CHANNELS, true ) ) {
			return;
		}
		$transfer = self::instance();
		$store    = $transfer->store();
		if ( ! $store->can_create() ) {
			return;
		}
		$store->create( array( Transfer_File::SECTION_MODULES ), 'module_switch', max( 0, ( $transfer->current_user )() ), $channel );
	}

	/**
	 * Sets the ID of a deleted user to 0 in the backups and the log.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $user_id ID of the deleted user.
	 * @return void
	 */
	public static function deleted_user( mixed $user_id ): void {
		if ( ! is_int( $user_id ) && ! ( is_string( $user_id ) && ctype_digit( $user_id ) ) ) {
			return;
		}
		$transfer = self::instance();
		$transfer->store()->anonymize( (int) $user_id );
		$transfer->log()->anonymize( (int) $user_id );
	}

	/**
	 * Returns the backup store.
	 *
	 * @since 1.0.0
	 *
	 * @return Backup_Store Store.
	 */
	public function store(): Backup_Store {
		return $this->store ?? Backup_Store::instance();
	}

	/**
	 * Returns the transfer log.
	 *
	 * @since 1.0.0
	 *
	 * @return Transfer_Log Log.
	 */
	public function log(): Transfer_Log {
		return $this->log ?? Transfer_Log::instance();
	}
}
