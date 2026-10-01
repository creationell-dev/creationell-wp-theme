<?php
/**
 * Preview of a settings import.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

use Creationell\WpTheme\Modules\Switch_Result;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Holds the file, the planned rows, the module switches, the warnings and what needs a confirmation.
 *
 * Built by Importer::plan(), which writes nothing. Warnings are codes:
 * "migrated:<from>-><to>", "line_differs", "theme_newer".
 *
 * @since 1.0.0
 */
final class Import_Plan {

	/**
	 * Keeps the fields.
	 *
	 * @since 1.0.0
	 *
	 * @param Transfer_File                                          $file               File.
	 * @param array<int, Plan_Row>                                   $rows               Rows in section order.
	 * @param array<int, array{slug: string, result: Switch_Result}> $modules Dry runs of the module switcher.
	 * @param array<int, string>                                     $warnings           Warning codes.
	 * @param bool                                                   $needs_confirmation Whether a module switch needs a confirmation.
	 * @param array<string, int>                                     $seen_live          Live contents per module slug that needs a confirmation.
	 * @phpstan-param list<Plan_Row> $rows
	 * @phpstan-param list<array{slug: string, result: Switch_Result}> $modules
	 * @phpstan-param list<string> $warnings
	 */
	public function __construct(
		public readonly Transfer_File $file,
		public readonly array $rows,
		public readonly array $modules,
		public readonly array $warnings,
		public readonly bool $needs_confirmation,
		public readonly array $seen_live,
	) {
	}

	/**
	 * Counts the rows per group.
	 *
	 * @since 1.0.0
	 *
	 * @return array{write: int, skip: int, reject: int} Counts.
	 */
	public function counts(): array {
		return self::count_rows( $this->rows );
	}

	/**
	 * Tells whether any row writes.
	 *
	 * Module rows that need a confirmation count as writes when $confirmed is true.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $confirmed Whether the module switches are confirmed.
	 * @return bool True when the import would write.
	 */
	public function has_writes( bool $confirmed = false ): bool {
		foreach ( $this->rows as $row ) {
			if ( 'write' === $row->group() || ( $confirmed && Row_Status::MODULE_NEEDS_CONFIRMATION === $row->status ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns the migration steps named in the warnings.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Steps such as "1->2".
	 * @phpstan-return list<string>
	 */
	public function migrated(): array {
		$steps = array();
		foreach ( $this->warnings as $warning ) {
			if ( str_starts_with( $warning, 'migrated:' ) ) {
				$steps[] = substr( $warning, strlen( 'migrated:' ) );
			}
		}
		return $steps;
	}

	/**
	 * Counts rows per group.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, Plan_Row> $rows Rows.
	 * @return array{write: int, skip: int, reject: int} Counts.
	 */
	public static function count_rows( array $rows ): array {
		$counts = array(
			'write'  => 0,
			'skip'   => 0,
			'reject' => 0,
		);
		foreach ( $rows as $row ) {
			$group = $row->group();
			if ( 'write' === $group ) {
				++$counts['write'];
			} elseif ( 'reject' === $group ) {
				++$counts['reject'];
			} else {
				++$counts['skip'];
			}
		}
		return $counts;
	}
}
