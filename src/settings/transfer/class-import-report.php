<?php
/**
 * Result of a settings import.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

use InvalidArgumentException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Tells whether the import wrote, which backup it made and what happened to every row and module.
 *
 * The methods to_array() and from_array() carry the report through a transient to the
 * result page (Post/Redirect/Get) and to the JSON output of WP-CLI. A module
 * entry holds the slug and the fields of the switch result: status, from, to,
 * effective state, live contents and warnings.
 *
 * @since 1.0.0
 *
 * @phpstan-type Module_Entry array{slug: string, status: string, from: string, to: string, effective: string, live: int, warnings: list<string>}
 */
final class Import_Report {

	/**
	 * Keeps the fields.
	 *
	 * @since 1.0.0
	 *
	 * @param bool                                      $written   Whether anything was written.
	 * @param string|null                               $backup_id ID of the backup made before writing, or null.
	 * @param array<int, Plan_Row>                      $rows      Rows with their final status.
	 * @param array<int, array<string, mixed>>          $modules   Module entries, see the class description.
	 * @param array{write: int, skip: int, reject: int} $counts Rows per group.
	 * @param array<int, string>                        $warnings  Warning codes of the plan.
	 * @phpstan-param list<Plan_Row> $rows
	 * @phpstan-param list<Module_Entry> $modules
	 * @phpstan-param list<string> $warnings
	 */
	public function __construct(
		public readonly bool $written,
		public readonly ?string $backup_id,
		public readonly array $rows,
		public readonly array $modules,
		public readonly array $counts,
		public readonly array $warnings,
	) {
	}

	/**
	 * Builds a report from a plan and the rows after writing.
	 *
	 * @since 1.0.0
	 *
	 * @param Import_Plan               $plan      Plan.
	 * @param bool                      $written   Whether anything was written.
	 * @param string|null               $backup_id Backup ID or null.
	 * @param array<int, Plan_Row>|null $rows      Final rows; null for the rows of the plan.
	 * @phpstan-param list<Plan_Row>|null $rows
	 * @return self Report.
	 */
	public static function from_plan( Import_Plan $plan, bool $written, ?string $backup_id, ?array $rows = null ): self {
		$rows    = $rows ?? $plan->rows;
		$modules = array();
		foreach ( $rows as $row ) {
			if ( null === $row->switch_result ) {
				continue;
			}
			$result    = $row->switch_result;
			$modules[] = array(
				'slug'      => Transfer_File::module_slug( $row->key ),
				'status'    => $result->status,
				'from'      => $result->from,
				'to'        => $result->to,
				'effective' => $result->effective,
				'live'      => null === $result->scan ? 0 : $result->scan->total_live(),
				'warnings'  => $result->warnings,
			);
		}
		return new self( $written, $backup_id, $rows, $modules, Import_Plan::count_rows( $rows ), $plan->warnings );
	}

	/**
	 * Returns the report as array.
	 *
	 * @since 1.0.0
	 *
	 * @return array{written: bool, backup_id: string|null, rows: list<array<string, mixed>>, modules: list<array<string, mixed>>, counts: array{write: int, skip: int, reject: int}, warnings: list<string>} Report.
	 */
	public function to_array(): array {
		return array(
			'written'   => $this->written,
			'backup_id' => $this->backup_id,
			'rows'      => array_values( array_map( static fn( Plan_Row $row ): array => $row->to_array(), $this->rows ) ),
			'modules'   => array_values( $this->modules ),
			'counts'    => $this->counts,
			'warnings'  => array_values( $this->warnings ),
		);
	}

	/**
	 * Builds a report from to_array().
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $data Report as array.
	 * @return self Report.
	 * @throws InvalidArgumentException When a field is missing or has the wrong type.
	 */
	public static function from_array( array $data ): self {
		$backup_id = $data['backup_id'] ?? null;
		if ( ! is_bool( $data['written'] ?? null ) || ( null !== $backup_id && ! is_string( $backup_id ) )
			|| ! is_array( $data['rows'] ?? null ) || ! is_array( $data['modules'] ?? null ) || ! is_array( $data['warnings'] ?? null ) ) {
			throw new InvalidArgumentException( 'The import report is incomplete.' );
		}
		$rows = array();
		foreach ( $data['rows'] as $row ) {
			if ( ! is_array( $row ) ) {
				throw new InvalidArgumentException( 'The import report has a broken row.' );
			}
			$rows[] = Plan_Row::from_array( $row );
		}
		$modules = array();
		foreach ( $data['modules'] as $module ) {
			$modules[] = self::module_entry( $module );
		}
		$warnings = array();
		foreach ( $data['warnings'] as $warning ) {
			if ( ! is_string( $warning ) ) {
				throw new InvalidArgumentException( 'The import report has a broken warning.' );
			}
			$warnings[] = $warning;
		}
		return new self( $data['written'], $backup_id, $rows, $modules, Import_Plan::count_rows( $rows ), $warnings );
	}

	/**
	 * Checks a module entry.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $module Entry.
	 * @return array<string, mixed> Entry.
	 * @phpstan-return Module_Entry
	 * @throws InvalidArgumentException When a field is missing or has the wrong type.
	 */
	private static function module_entry( mixed $module ): array {
		if ( ! is_array( $module ) || ! is_int( $module['live'] ?? null ) || ! is_array( $module['warnings'] ?? null ) || ! array_is_list( $module['warnings'] ) ) {
			throw new InvalidArgumentException( 'The import report has a broken module entry.' );
		}
		$entry = array(
			'slug'      => '',
			'status'    => '',
			'from'      => '',
			'to'        => '',
			'effective' => '',
		);
		foreach ( array_keys( $entry ) as $field ) {
			if ( ! is_string( $module[ $field ] ?? null ) ) {
				throw new InvalidArgumentException( 'The import report has a broken module entry.' );
			}
			$entry[ $field ] = $module[ $field ];
		}
		$warnings = array();
		foreach ( $module['warnings'] as $warning ) {
			if ( ! is_string( $warning ) ) {
				throw new InvalidArgumentException( 'The import report has a broken module entry.' );
			}
			$warnings[] = $warning;
		}
		return $entry + array(
			'live'     => $module['live'],
			'warnings' => $warnings,
		);
	}
}
