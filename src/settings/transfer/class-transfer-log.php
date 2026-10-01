<?php
/**
 * Log of the settings transfer.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

use Closure;
use InvalidArgumentException;
use RuntimeException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Records who exported, imported, restored or backed up which sections and when.
 *
 * The log lives in one option without autoload, newest entry first. An entry
 * holds the time (Unix time), the user ID, the channel, the action, the
 * sections and languages, counts, the backup ID and the SHA-256 of the file;
 * never setting values and never an IP address. Writing keeps at most
 * MAX_ENTRIES entries not older than MAX_AGE; reading drops nothing. When a
 * user is deleted, anonymize() sets their ID to 0.
 *
 * @since 1.0.0
 *
 * @phpstan-type Log_Entry array{time: int, user_id: int, channel: string, action: string, sections: list<string>, langs: list<string>, counts: array<string, int>, backup_id: string|null, file_sha256: string|null}
 */
final class Transfer_Log {

	/**
	 * Name of the option.
	 *
	 * @since 1.0.0
	 */
	public const OPTION = 'creationell_wp_theme_settings_transfer_log';

	/**
	 * Largest number of entries.
	 *
	 * @since 1.0.0
	 */
	public const MAX_ENTRIES = 100;

	/**
	 * Largest age of an entry in seconds (90 days).
	 *
	 * @since 1.0.0
	 */
	public const MAX_AGE = 7776000;

	/**
	 * Actions an entry can record.
	 *
	 * @since 1.0.0
	 */
	public const ACTIONS = array( 'export', 'import', 'restore', 'backup' );

	/**
	 * Channels an action can come from.
	 *
	 * @since 1.0.0
	 */
	public const CHANNELS = array( 'admin', 'cli' );

	/**
	 * Pattern of a SHA-256 in hex.
	 *
	 * @since 1.0.0
	 */
	private const SHA256_PATTERN = '~^[0-9a-f]{64}$~D';

	/**
	 * Pattern of a backup ID, as in Backup_Store.
	 *
	 * @since 1.0.0
	 */
	private const BACKUP_ID_PATTERN = '~^\d{8}-\d{6}-[0-9a-f]{6}$~D';

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Returns the current Unix time.
	 *
	 * @var Closure
	 * @phpstan-var Closure(): int
	 */
	private Closure $clock;

	/**
	 * Takes the clock; without one it uses time().
	 *
	 * @since 1.0.0
	 *
	 * @param Closure|null $clock Returns the current Unix time; tests inject it.
	 * @phpstan-param (Closure(): int)|null $clock
	 */
	public function __construct( ?Closure $clock = null ) {
		$this->clock = $clock ?? static fn(): int => time();
	}

	/**
	 * Returns the shared instance, which uses the real clock.
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
	 * @param self|null $log Instance.
	 * @return void
	 */
	public static function set_instance( ?self $log ): void {
		self::$instance = $log;
	}

	/**
	 * Returns the channel of the running request: "cli" inside WP-CLI, otherwise "admin".
	 *
	 * @since 1.0.0
	 *
	 * @return string Channel.
	 */
	public static function channel(): string {
		return defined( 'WP_CLI' ) && WP_CLI ? 'cli' : 'admin';
	}

	/**
	 * Adds an entry and drops the entries beyond the limits.
	 *
	 * When the option is not written, save() throws a RuntimeException.
	 *
	 * @since 1.0.0
	 *
	 * @param string             $action      One of ACTIONS.
	 * @param string             $channel     One of CHANNELS.
	 * @param int                $user_id     User who acted; 0 for none.
	 * @param array<int, string> $sections    Sections involved.
	 * @param array<int, string> $langs       Language codes involved.
	 * @param array<string, int> $counts      Counts by name, e.g. "write", "skip" and "reject" of an import.
	 * @param string|null        $backup_id   ID of the backup made or used.
	 * @param string|null        $file_sha256 SHA-256 of the file in hex.
	 * @return array<string, mixed> Stored entry.
	 * @phpstan-return Log_Entry
	 * @throws InvalidArgumentException When a field has no valid value; nothing is written then.
	 */
	public function add( string $action, string $channel, int $user_id, array $sections, array $langs, array $counts = array(), ?string $backup_id = null, ?string $file_sha256 = null ): array {
		if ( ! in_array( $action, self::ACTIONS, true ) ) {
			throw new InvalidArgumentException( 'Unknown transfer log action.' );
		}
		if ( ! in_array( $channel, self::CHANNELS, true ) ) {
			throw new InvalidArgumentException( 'Unknown transfer log channel.' );
		}
		if ( $user_id < 0 ) {
			throw new InvalidArgumentException( 'The user ID of a transfer log entry must not be negative.' );
		}
		if ( null !== $backup_id && 1 !== preg_match( self::BACKUP_ID_PATTERN, $backup_id ) ) {
			throw new InvalidArgumentException( 'Invalid backup ID in the transfer log.' );
		}
		if ( null !== $file_sha256 && 1 !== preg_match( self::SHA256_PATTERN, $file_sha256 ) ) {
			throw new InvalidArgumentException( 'Invalid SHA-256 in the transfer log.' );
		}
		$entry = array(
			'time'        => ( $this->clock )(),
			'user_id'     => $user_id,
			'channel'     => $channel,
			'action'      => $action,
			'sections'    => self::codes( $sections, Transfer_File::SECTION_PATTERN ),
			'langs'       => self::codes( $langs, Transfer_File::LANGUAGE_PATTERN ),
			'counts'      => self::counts( $counts ),
			'backup_id'   => $backup_id,
			'file_sha256' => $file_sha256,
		);

		$oldest  = $entry['time'] - self::MAX_AGE;
		$entries = array_filter( $this->all(), static fn( array $kept ): bool => $kept['time'] >= $oldest );
		array_unshift( $entries, $entry );
		$this->save( array_slice( $entries, 0, self::MAX_ENTRIES ) );
		return $entry;
	}

	/**
	 * Returns the stored entries, newest first; broken entries are skipped.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<string, mixed>> Entries.
	 * @phpstan-return list<Log_Entry>
	 */
	public function all(): array {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			return array();
		}
		$entries = array();
		foreach ( $stored as $entry ) {
			if ( self::is_entry( $entry ) ) {
				$entries[] = $entry;
			}
		}
		return $entries;
	}

	/**
	 * Sets the user ID of a deleted user to 0 in every entry.
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
		$entries = $this->all();
		$changed = false;
		foreach ( $entries as $index => $entry ) {
			if ( $user_id === $entry['user_id'] ) {
				$entries[ $index ]['user_id'] = 0;
				$changed                      = true;
			}
		}
		if ( $changed ) {
			$this->save( $entries );
		}
	}

	/**
	 * Writes the entries to the option without autoload.
	 *
	 * WordPress answers false also when the value does not change, which a new
	 * entry can do when the log is full of equal entries of the same second;
	 * only false with another stored value means the option was not written.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array<string, mixed>> $entries Entries.
	 * @return void
	 * @throws RuntimeException When the option is not written.
	 */
	private function save( array $entries ): void {
		$entries = array_values( $entries );
		if ( ! update_option( self::OPTION, $entries, false ) && get_option( self::OPTION ) !== $entries ) {
			throw new RuntimeException( 'The transfer log could not be stored.' );
		}
	}

	/**
	 * Checks a list of codes.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int|string, mixed> $codes   Codes.
	 * @param string                   $pattern Pattern of one code.
	 * @return list<string> Codes.
	 * @throws InvalidArgumentException When the list has keys or a code does not match.
	 */
	private static function codes( array $codes, string $pattern ): array {
		if ( ! array_is_list( $codes ) ) {
			throw new InvalidArgumentException( 'The codes of a transfer log entry must be a list.' );
		}
		foreach ( $codes as $code ) {
			if ( ! is_string( $code ) || 1 !== preg_match( $pattern, $code ) ) {
				throw new InvalidArgumentException( 'Invalid section or language code in the transfer log.' );
			}
		}
		return $codes;
	}

	/**
	 * Checks the counts.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $counts Counts by name.
	 * @return array<string, int> Counts.
	 * @throws InvalidArgumentException When a name is no string or a count no integer.
	 */
	private static function counts( array $counts ): array {
		foreach ( $counts as $name => $count ) {
			if ( ! is_string( $name ) || ! is_int( $count ) ) {
				throw new InvalidArgumentException( 'Counts of a transfer log entry are integers by name.' );
			}
		}
		return $counts;
	}

	/**
	 * Tells whether a stored value is a complete entry.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $entry Stored value.
	 * @return bool True for an entry with all fields of the right type.
	 * @phpstan-assert-if-true Log_Entry $entry
	 */
	private static function is_entry( mixed $entry ): bool {
		return is_array( $entry )
			&& is_int( $entry['time'] ?? null )
			&& is_int( $entry['user_id'] ?? null )
			&& in_array( $entry['channel'] ?? null, self::CHANNELS, true )
			&& in_array( $entry['action'] ?? null, self::ACTIONS, true )
			&& is_array( $entry['sections'] ?? null ) && array_is_list( $entry['sections'] )
			&& is_array( $entry['langs'] ?? null ) && array_is_list( $entry['langs'] )
			&& is_array( $entry['counts'] ?? null )
			&& array_key_exists( 'backup_id', $entry ) && ( null === $entry['backup_id'] || is_string( $entry['backup_id'] ) )
			&& array_key_exists( 'file_sha256', $entry ) && ( null === $entry['file_sha256'] || is_string( $entry['file_sha256'] ) );
	}
}
