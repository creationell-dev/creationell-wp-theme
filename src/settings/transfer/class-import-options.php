<?php
/**
 * Options of a settings import.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

use InvalidArgumentException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Tells the importer what to import, for whom, through which channel and what the person confirmed.
 *
 * $seen_live holds, per module slug, the number of live contents the person
 * saw in the preview; the import stops with confirmation_required when the
 * module switcher counts more at the time of the import.
 *
 * Example:
 *
 *     $options = new Import_Options(
 *         sections: array( 'settings', 'modules' ),
 *         langs: null,
 *         user_id: get_current_user_id(),
 *         channel: 'cli',
 *         confirm_modules: true,
 *         seen_live: $plan->seen_live,
 *     );
 *     $report = ( new Importer() )->apply( $plan, $options );
 *
 * @since 1.0.0
 */
final class Import_Options {

	/**
	 * Reasons of an import; a restore is an import of a backup.
	 *
	 * @since 1.0.0
	 */
	public const REASONS = array( 'import', 'restore' );

	/**
	 * Checks the fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>      $sections        Section IDs to import; sections the file does not have are ignored.
	 * @param array<int, string>|null $langs           Languages of the translated values to import; null for all.
	 * @param int                     $user_id         User who imports; the capabilities are checked for this user.
	 * @param string                  $channel         Channel: "admin" or "cli".
	 * @param bool                    $confirm_modules Whether the person confirmed the consequences of the module switches.
	 * @param array<string, int>      $seen_live       Live contents the person saw per module slug.
	 * @param bool                    $strict          Whether a single rejected row stops the whole import.
	 * @param bool                    $dry_run         Whether to report only and write nothing.
	 * @param string                  $reason          "import" or "restore"; names the backup and the log entry.
	 * @phpstan-param list<string> $sections
	 * @phpstan-param list<string>|null $langs
	 * @throws InvalidArgumentException When a field has no valid value.
	 */
	public function __construct(
		public readonly array $sections,
		public readonly ?array $langs,
		public readonly int $user_id,
		public readonly string $channel,
		public readonly bool $confirm_modules = false,
		public readonly array $seen_live = array(),
		public readonly bool $strict = false,
		public readonly bool $dry_run = false,
		public readonly string $reason = 'import',
	) {
		Export_Request::check_codes( $sections, 'section' );
		if ( null !== $langs ) {
			Export_Request::check_codes( $langs, 'language' );
		}
		if ( $user_id < 0 ) {
			throw new InvalidArgumentException( 'The user ID of an import must not be negative.' );
		}
		if ( ! in_array( $channel, Transfer_Log::CHANNELS, true ) ) {
			throw new InvalidArgumentException( esc_html( sprintf( 'Import channel %s is unknown.', $channel ) ) );
		}
		foreach ( $seen_live as $slug => $count ) {
			if ( ! is_string( $slug ) || ! is_int( $count ) || $count < 0 ) {
				throw new InvalidArgumentException( 'Seen live contents must be counts of at least 0 by module slug.' );
			}
		}
		if ( ! in_array( $reason, self::REASONS, true ) ) {
			throw new InvalidArgumentException( esc_html( sprintf( 'Import reason %s is unknown.', $reason ) ) );
		}
	}

	/**
	 * Returns the live contents the person saw for a module.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Module slug.
	 * @return int Count; 0 when the person saw none.
	 */
	public function seen( string $slug ): int {
		return $this->seen_live[ $slug ] ?? 0;
	}

	/**
	 * Tells whether the translated values of a language are imported.
	 *
	 * @since 1.0.0
	 *
	 * @param string $lang Language code.
	 * @return bool True without a language filter or when the language is listed.
	 */
	public function wants_lang( string $lang ): bool {
		return null === $this->langs || in_array( $lang, $this->langs, true );
	}
}
