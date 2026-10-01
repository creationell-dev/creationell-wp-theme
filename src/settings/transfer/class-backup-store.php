<?php
/**
 * Backups of the theme settings before imports and module switches.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

use Closure;
use InvalidArgumentException;
use JsonException;
use LogicException;
use RuntimeException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Keeps the last MAX_ITEMS backups of the settings as encoded transfer files.
 *
 * The backups live in one option without autoload:
 *
 *     {schema: 1, items: [{id, created_at, user_id, reason, sections, data}, ...]}
 *
 * newest first; "data" is the file as Json_Codec::encode() writes it, so
 * reading a backup takes the same checks as an uploaded file (decode,
 * migration, Transfer_File::from_array()). The IDs have the form
 * YYYYMMDD-HHMMSS-<6 hex> in UTC. A backup covers all languages of its
 * sections; the snapshot source builds that file and checks no capability, the
 * caller does. Every backup writes a log entry "backup".
 *
 * @since 1.0.0
 *
 * @phpstan-type Backup_Item array{id: string, created_at: string, user_id: int, reason: string, sections: list<string>, data: string}
 * @phpstan-type Backup_Summary array{id: string, created_at: string, user_id: int, reason: string, sections: list<string>}
 */
final class Backup_Store {

	/**
	 * Name of the option.
	 *
	 * @since 1.0.0
	 */
	public const OPTION = 'creationell_wp_theme_settings_backups';

	/**
	 * Version of the option structure.
	 *
	 * @since 1.0.0
	 */
	public const SCHEMA = 1;

	/**
	 * Largest number of backups.
	 *
	 * @since 1.0.0
	 */
	public const MAX_ITEMS = 5;

	/**
	 * Reasons a backup is made for.
	 *
	 * @since 1.0.0
	 */
	public const REASONS = array( 'import', 'restore', 'module_switch', 'manual', 'line_switch' );

	/**
	 * Pattern of a backup ID.
	 *
	 * @since 1.0.0
	 */
	public const ID_PATTERN = '~^\d{8}-\d{6}-[0-9a-f]{6}$~D';

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Builds the file of the given sections in all languages; null when the theme has none.
	 *
	 * @var Closure|null
	 * @phpstan-var (Closure(list<string>): Transfer_File)|null
	 */
	private ?Closure $snapshot;

	/**
	 * Log for the entries "backup"; null for the shared log.
	 *
	 * @var Transfer_Log|null
	 */
	private ?Transfer_Log $log;

	/**
	 * Returns the current Unix time.
	 *
	 * @var Closure
	 * @phpstan-var Closure(): int
	 */
	private Closure $clock;

	/**
	 * Takes the snapshot source, the log and the clock.
	 *
	 * @since 1.0.0
	 *
	 * @param Closure|null      $snapshot Builds the transfer file of a list of sections in all languages without a capability check; without one the store cannot create backups.
	 * @param Transfer_Log|null $log      Log; without one the shared log at the time of writing.
	 * @param Closure|null      $clock    Returns the current Unix time; without one time().
	 * @phpstan-param (Closure(list<string>): Transfer_File)|null $snapshot
	 * @phpstan-param (Closure(): int)|null $clock
	 */
	public function __construct( ?Closure $snapshot = null, ?Transfer_Log $log = null, ?Closure $clock = null ) {
		$this->snapshot = $snapshot;
		$this->log      = $log;
		$this->clock    = $clock ?? static fn(): int => time();
	}

	/**
	 * Returns the shared instance.
	 *
	 * Its snapshot source is Exporter::snapshot() of the shared exporter: every
	 * active language of the sections, without a capability check and without
	 * a log entry "export".
	 *
	 * @since 1.0.0
	 *
	 * @return self Instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self( static fn( array $sections ): Transfer_File => Exporter::instance()->snapshot( $sections ) );
		}
		return self::$instance;
	}

	/**
	 * Replaces the shared instance; null builds a new one on the next call of instance().
	 *
	 * @since 1.0.0
	 *
	 * @param self|null $store Instance.
	 * @return void
	 */
	public static function set_instance( ?self $store ): void {
		self::$instance = $store;
	}

	/**
	 * Tells whether the store has a snapshot source and can create backups.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True with a snapshot source.
	 */
	public function can_create(): bool {
		return null !== $this->snapshot;
	}

	/**
	 * Backs up the given sections in all languages, keeps the newest MAX_ITEMS and logs the backup.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $sections Sections, e.g. array( 'settings', 'modules' ).
	 * @param string             $reason   One of REASONS.
	 * @param int                $user_id  User the backup is made for; 0 for none.
	 * @param string|null        $channel  Channel of the log entry, "admin" or "cli"; null for the channel of the request.
	 * @return string ID of the backup.
	 * @throws Transfer_Exception With nothing_selected when no section is given.
	 * @throws InvalidArgumentException With an unknown reason or channel or a negative user ID; nothing is written then.
	 * @throws LogicException Without a snapshot source.
	 * @throws JsonException When the snapshot cannot be encoded.
	 * @throws RuntimeException When the option is not written; nothing is logged then.
	 */
	public function create( array $sections, string $reason, int $user_id, ?string $channel = null ): string {
		$snapshot = $this->snapshot_for( $sections, $reason, $user_id, $channel );
		$file     = $snapshot( array_values( $sections ) );
		$data     = Json_Codec::encode( $file->to_array() );
		$now      = ( $this->clock )();
		$items    = $this->items();
		$id       = self::new_id( $now, array_column( $items, 'id' ) );
		array_unshift(
			$items,
			array(
				'id'         => $id,
				'created_at' => gmdate( 'Y-m-d\TH:i:s\Z', $now ),
				'user_id'    => $user_id,
				'reason'     => $reason,
				'sections'   => $file->sections,
				'data'       => $data,
			)
		);
		$this->save( array_slice( $items, 0, self::MAX_ITEMS ) );

		( $this->log ?? Transfer_Log::instance() )->add( 'backup', $channel ?? Transfer_Log::channel(), $user_id, $file->sections, $file->languages, array(), $id, hash( 'sha256', $data ) );
		return $id;
	}

	/**
	 * Checks the arguments of create() and returns the snapshot source.
	 *
	 * An empty list of sections raises nothing_selected.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $sections Sections.
	 * @param string             $reason   Reason.
	 * @param int                $user_id  User ID.
	 * @param string|null        $channel  Channel or null.
	 * @return Closure Snapshot source.
	 * @phpstan-return Closure(list<string>): Transfer_File
	 * @throws InvalidArgumentException With an unknown reason or channel or a negative user ID.
	 * @throws LogicException Without a snapshot source.
	 */
	private function snapshot_for( array $sections, string $reason, int $user_id, ?string $channel ): Closure {
		if ( array() === $sections ) {
			Transfer_Exception::raise( Transfer_Error::NOTHING_SELECTED );
		}
		if ( ! in_array( $reason, self::REASONS, true ) ) {
			throw new InvalidArgumentException( 'Unknown backup reason.' );
		}
		if ( $user_id < 0 ) {
			throw new InvalidArgumentException( 'The user ID of a backup must not be negative.' );
		}
		if ( null !== $channel && ! in_array( $channel, Transfer_Log::CHANNELS, true ) ) {
			throw new InvalidArgumentException( 'Unknown backup channel.' );
		}
		if ( null === $this->snapshot ) {
			throw new LogicException( 'The backup store has no snapshot source.' );
		}
		return $this->snapshot;
	}

	/**
	 * Returns the backups without their data, newest first.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, mixed>> Backups with id, created_at (ISO 8601 UTC), user_id, reason and sections.
	 * @phpstan-return list<Backup_Summary>
	 */
	public function all(): array {
		return array_map(
			static function ( array $item ): array {
				unset( $item['data'] );
				return $item;
			},
			$this->items()
		);
	}

	/**
	 * Returns the stored file of a backup as JSON text.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Backup ID.
	 * @return string File as Json_Codec::encode() wrote it.
	 * @throws Transfer_Exception With backup_not_found.
	 */
	public function data( string $id ): string {
		foreach ( $this->items() as $item ) {
			if ( $id === $item['id'] ) {
				return $item['data'];
			}
		}
		Transfer_Exception::raise( Transfer_Error::BACKUP_NOT_FOUND );
	}

	/**
	 * Returns the file of a backup after the same checks as an uploaded file.
	 *
	 * @since 1.0.0
	 *
	 * @param string        $id       Backup ID.
	 * @param Migrator|null $migrator Migrator; without one the steps of this theme version.
	 * @return Transfer_File File.
	 * @throws Transfer_Exception With backup_not_found or an error of Json_Codec, Migrator or Transfer_File.
	 */
	public function get( string $id, ?Migrator $migrator = null ): Transfer_File {
		$migrated = ( $migrator ?? new Migrator() )->migrate( Json_Codec::decode( $this->data( $id ) ) );
		return Transfer_File::from_array( $migrated['data'] );
	}

	/**
	 * Sets the user ID of a deleted user to 0 in every backup.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id ID of the deleted user.
	 * @return void
	 * @throws RuntimeException When the option is not written.
	 */
	public function anonymize( int $user_id ): void {
		if ( $user_id <= 0 ) {
			return;
		}
		$items   = $this->items();
		$changed = false;
		foreach ( $items as $index => $item ) {
			if ( $user_id === $item['user_id'] ) {
				$items[ $index ]['user_id'] = 0;
				$changed                    = true;
			}
		}
		if ( $changed ) {
			$this->save( $items );
		}
	}

	/**
	 * Returns the stored backups; another option structure counts as none, broken items are skipped.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, mixed>> Backups, newest first.
	 * @phpstan-return list<Backup_Item>
	 */
	private function items(): array {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) || self::SCHEMA !== ( $stored['schema'] ?? null ) || ! is_array( $stored['items'] ?? null ) ) {
			return array();
		}
		$items = array();
		foreach ( $stored['items'] as $item ) {
			if ( self::is_item( $item ) ) {
				$items[] = $item;
			}
		}
		return $items;
	}

	/**
	 * Writes the backups to the option without autoload.
	 *
	 * Both callers change the stored value (a new backup with a new ID, or a
	 * user ID set to 0), so false from update_option() means the option was
	 * not written, e.g. after a database error.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array<string, mixed>> $items Backups, newest first.
	 * @return void
	 * @throws RuntimeException When the option is not written.
	 */
	private function save( array $items ): void {
		$stored = update_option(
			self::OPTION,
			array(
				'schema' => self::SCHEMA,
				'items'  => array_values( $items ),
			),
			false
		);
		if ( ! $stored ) {
			throw new RuntimeException( 'The backup could not be stored.' );
		}
	}

	/**
	 * Returns a new ID for the time that no stored backup has.
	 *
	 * @since 1.0.0
	 *
	 * @param int                $now   Unix time.
	 * @param array<int, string> $taken IDs of the stored backups.
	 * @return string ID.
	 */
	private static function new_id( int $now, array $taken ): string {
		do {
			$id = gmdate( 'Ymd-His', $now ) . '-' . bin2hex( random_bytes( 3 ) );
		} while ( in_array( $id, $taken, true ) );
		return $id;
	}

	/**
	 * Tells whether a stored value is a complete backup.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $item Stored value.
	 * @return bool True for a backup with all fields of the right type.
	 * @phpstan-assert-if-true Backup_Item $item
	 */
	private static function is_item( mixed $item ): bool {
		return is_array( $item )
			&& is_string( $item['id'] ?? null ) && 1 === preg_match( self::ID_PATTERN, $item['id'] )
			&& is_string( $item['created_at'] ?? null )
			&& is_int( $item['user_id'] ?? null )
			&& in_array( $item['reason'] ?? null, self::REASONS, true )
			&& is_array( $item['sections'] ?? null ) && array_is_list( $item['sections'] )
			&& is_string( $item['data'] ?? null );
	}
}
