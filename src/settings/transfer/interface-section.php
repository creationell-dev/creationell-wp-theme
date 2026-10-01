<?php
/**
 * Section of a settings transfer file.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Exports, plans and applies one section of a transfer file, e.g. "settings" or "modules".
 *
 * The export returns the content of the section; the exporter adds the header
 * and checks the whole file with Transfer_File::from_array(). plan() never
 * writes. apply() writes the rows of a fresh plan whose status is in the group
 * "write" and returns every row with its final status; the importer calls it
 * after the backup and commits the setter once after all sections. A later
 * section (menu locations, theme mods, template parts) finds its content in
 * Transfer_File::$other_sections.
 *
 * @since 1.0.0
 */
interface Section_Interface {

	/**
	 * Returns the ID of the section, the member name in the file.
	 *
	 * @since 1.0.0
	 *
	 * @return string ID, e.g. "settings".
	 */
	public function id(): string;

	/**
	 * Returns the translated name of the section.
	 *
	 * @since 1.0.0
	 *
	 * @return string Label.
	 */
	public function label(): string;

	/**
	 * Returns the content of the section for an export.
	 *
	 * @since 1.0.0
	 *
	 * @param Export_Request $request Request whose languages are the active ones that were asked for.
	 * @return array<string, mixed> Content, an object in the file.
	 */
	public function export( Export_Request $request ): array;

	/**
	 * Plans the import of the section without writing.
	 *
	 * @since 1.0.0
	 *
	 * @param Transfer_File  $file    Checked file.
	 * @param Import_Options $options Options.
	 * @return array<int, Plan_Row> Rows.
	 * @phpstan-return list<Plan_Row>
	 */
	public function plan( Transfer_File $file, Import_Options $options ): array;

	/**
	 * Writes the rows of the group "write" and returns every row with its final status.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, Plan_Row> $rows    Rows of this section from a fresh plan.
	 * @param Import_Options       $options Options.
	 * @phpstan-param list<Plan_Row> $rows
	 * @return array<int, Plan_Row> Rows in the same order.
	 * @phpstan-return list<Plan_Row>
	 */
	public function apply( array $rows, Import_Options $options ): array;
}
