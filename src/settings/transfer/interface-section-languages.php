<?php
/**
 * Section of a settings transfer file with languages of its own.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Names the languages a section keeps texts in, outside Transfer_File::$translatable.
 *
 * The importer accepts a source of the language map when the file has
 * translated settings in that language or when a section of the file that
 * implements this interface names it, e.g. the texts of "consent_services".
 *
 * @since 1.0.0
 */
interface Section_Languages {

	/**
	 * Returns the languages of the section's texts in the file.
	 *
	 * @since 1.0.0
	 *
	 * @param Transfer_File $file Checked file.
	 * @return array<int, string> Language codes in file order; empty without the section in the file.
	 * @phpstan-return list<string>
	 */
	public function languages( Transfer_File $file ): array;
}
