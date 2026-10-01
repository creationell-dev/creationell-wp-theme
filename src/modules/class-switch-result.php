<?php
/**
 * Result of a module switch.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules;

use Creationell\WpTheme\Core\Scan_Result;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Status, states, scan and warnings of one switch.
 *
 * Statuses: "switched", "unchanged" (the configured state already is the
 * target), "needs_confirmation" (switching off needs a confirmation, the scan
 * tells why), "denied" (capability missing), "locked" (child lock or constant),
 * "invalid" (unknown module or state), "dry_run" (would switch).
 *
 * Warnings: "dependency_missing:<reason>" (the module stays effectively off,
 * e.g. dependency_missing:missing_plugin:contact-form-7/wp-contact-form-7.php),
 * "callback_failed" (on_change of the module threw; the switch stays),
 * "backup_hint" (switching off without a backup of the settings).
 *
 * @since 1.0.0
 */
final class Switch_Result {

	/**
	 * The state was written.
	 *
	 * @since 1.0.0
	 */
	public const SWITCHED = 'switched';

	/**
	 * The configured state already is the target.
	 *
	 * @since 1.0.0
	 */
	public const UNCHANGED = 'unchanged';

	/**
	 * Switching off needs a confirmation.
	 *
	 * @since 1.0.0
	 */
	public const NEEDS_CONFIRMATION = 'needs_confirmation';

	/**
	 * The current user may not switch modules.
	 *
	 * @since 1.0.0
	 */
	public const DENIED = 'denied';

	/**
	 * A child lock or a constant holds the state.
	 *
	 * @since 1.0.0
	 */
	public const LOCKED = 'locked';

	/**
	 * The module or the target state is unknown.
	 *
	 * @since 1.0.0
	 */
	public const INVALID = 'invalid';

	/**
	 * The switch would run; nothing was changed.
	 *
	 * @since 1.0.0
	 */
	public const DRY_RUN = 'dry_run';

	/**
	 * Prefix of the warning that the module stays effectively off; the reason follows.
	 *
	 * @since 1.0.0
	 */
	public const WARNING_DEPENDENCY_MISSING = 'dependency_missing:';

	/**
	 * Warning that the on_change callback of the module failed.
	 *
	 * @since 1.0.0
	 */
	public const WARNING_CALLBACK_FAILED = 'callback_failed';

	/**
	 * Warning that no backup of the settings is available before switching off.
	 *
	 * @since 1.0.0
	 */
	public const WARNING_BACKUP_HINT = 'backup_hint';

	/**
	 * Keeps the fields.
	 *
	 * @since 1.0.0
	 *
	 * @param string             $status    Status, one of the constants above.
	 * @param string             $from      Configured state before the switch.
	 * @param string             $to        Target state of the request.
	 * @param string             $effective Effective state after the switch, or the current one when nothing was written.
	 * @param Scan_Result|null   $scan      Counted contents of the blocks of the module; null when not counted.
	 * @param array<int, string> $warnings  Warnings.
	 * @phpstan-param list<string> $warnings
	 */
	public function __construct(
		public readonly string $status,
		public readonly string $from,
		public readonly string $to,
		public readonly string $effective,
		public readonly ?Scan_Result $scan = null,
		public readonly array $warnings = array(),
	) {
	}

	/**
	 * Tells whether the state was written.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True for switched.
	 */
	public function switched(): bool {
		return self::SWITCHED === $this->status;
	}
}
