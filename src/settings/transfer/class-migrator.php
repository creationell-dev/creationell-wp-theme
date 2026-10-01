<?php
/**
 * Migration of older settings transfer files.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Brings a decoded transfer file to the current schema version, one step per version.
 *
 * Each step is a pure function from the file of version N to the file of
 * version N + 1, registered under N in steps(). The migrator sets the schema
 * version after each step itself. A file of a newer schema stops the import; a
 * missing step as well.
 *
 * Example of a step, registered when the schema moves to version 2:
 *
 *     1 => static function ( array $data ): array {
 *         $data['settings']['values']['color_brand'] = $data['settings']['values']['color_primary'] ?? null;
 *         return $data;
 *     },
 *
 * @since 1.0.0
 */
final class Migrator {

	/**
	 * Current schema version of transfer files.
	 *
	 * @since 1.0.0
	 */
	public const CURRENT = 1;

	/**
	 * Steps by the version they start from.
	 *
	 * @var array<int, callable(array<mixed>): array<mixed>>
	 */
	private array $steps;

	/**
	 * Schema version the steps lead to.
	 *
	 * @var int
	 */
	private int $current;

	/**
	 * Takes the steps and the target version; without arguments the steps of this theme version and CURRENT.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, callable>|null $steps   Steps by the version they start from.
	 * @param int                       $current Target schema version.
	 * @phpstan-param array<int, callable(array<mixed>): array<mixed>>|null $steps
	 */
	public function __construct( ?array $steps = null, int $current = self::CURRENT ) {
		$this->steps   = $steps ?? self::registered_steps();
		$this->current = $current;
	}

	/**
	 * Returns the steps by the version they start from.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, callable> Steps; empty while the schema has one version.
	 * @phpstan-return array<int, callable(array<mixed>): array<mixed>>
	 */
	public function steps(): array {
		return $this->steps;
	}

	/**
	 * Returns the target schema version.
	 *
	 * @since 1.0.0
	 *
	 * @return int Version.
	 */
	public function current(): int {
		return $this->current;
	}

	/**
	 * Checks format and schema version and runs the missing steps.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $data Decoded file.
	 * @return array{data: array<mixed>, applied: list<string>} Migrated file and the steps run, e.g. "1->2".
	 * @throws Transfer_Exception With wrong_format, schema_invalid, schema_newer or no_migration.
	 */
	public function migrate( array $data ): array {
		if ( Transfer_File::FORMAT !== ( $data['format'] ?? null ) ) {
			Transfer_Exception::raise( Transfer_Error::WRONG_FORMAT, array( 'path' => '$.format' ) );
		}
		$version = $data['schema_version'] ?? null;
		if ( ! is_int( $version ) || $version < 1 ) {
			Transfer_Exception::raise(
				Transfer_Error::SCHEMA_INVALID,
				array(
					'path'   => '$.schema_version',
					'detail' => 'expected an integer of at least 1',
				)
			);
		}
		if ( $version > $this->current ) {
			Transfer_Exception::raise(
				Transfer_Error::SCHEMA_NEWER,
				array(
					'schema_version' => $version,
					'current'        => $this->current,
				)
			);
		}
		$applied = array();
		while ( $version < $this->current ) {
			if ( ! isset( $this->steps[ $version ] ) ) {
				Transfer_Exception::raise( Transfer_Error::NO_MIGRATION, array( 'schema_version' => $version ) );
			}
			$data                   = ( $this->steps[ $version ] )( $data );
			$applied[]              = $version . '->' . ( $version + 1 );
			$data['schema_version'] = ++$version;
		}
		return array(
			'data'    => $data,
			'applied' => $applied,
		);
	}

	/**
	 * Returns the steps of this theme version.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, callable> Steps by the version they start from; none yet.
	 * @phpstan-return array<int, callable(array<mixed>): array<mixed>>
	 */
	private static function registered_steps(): array {
		return array();
	}
}
