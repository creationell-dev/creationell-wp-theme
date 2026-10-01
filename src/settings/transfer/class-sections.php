<?php
/**
 * Sections of a settings transfer file.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

use Creationell\WpTheme\Settings\Setter;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Lists the sections this theme version exports and imports.
 *
 * Stage 1 and 3 of the transfer know "settings" and "modules"; later
 * sections (menu locations, theme mods, template parts) join this list.
 * Sections of a file that are not in the list are reported as
 * "section_unknown" and never written.
 *
 * @since 1.0.0
 */
final class Sections {

	/**
	 * Returns the sections in file order.
	 *
	 * @since 1.0.0
	 *
	 * @param Setter|null $setter Setter of the section "settings"; without one the shared setter.
	 * @return array<string, Section_Interface> Sections by ID.
	 */
	public static function all( ?Setter $setter = null ): array {
		$sections = array( new Settings_Section( $setter ), new Modules_Section() );
		$by_id    = array();
		foreach ( $sections as $section ) {
			$by_id[ $section->id() ] = $section;
		}
		return $by_id;
	}

	/**
	 * Returns the IDs of the sections in file order.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> IDs.
	 * @phpstan-return list<string>
	 */
	public static function ids(): array {
		return array_keys( self::all() );
	}
}
