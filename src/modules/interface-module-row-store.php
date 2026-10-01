<?php
/**
 * Module store whose rows can be read past the cache of the backend layer.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * A module store that reads the backend layer from a copy (the settings snapshot) and can show the stored row itself.
 *
 * The module switcher compares the row with the state it would store when a
 * switch leaves the configured state unchanged, and rewrites a row that
 * disagrees with the copy.
 *
 * @since 1.0.0
 */
interface Module_Row_Store_Interface extends Module_Store_Interface {

	/**
	 * Returns the stored row of a module as it is in the options, past the copy.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Module slug.
	 * @return string|null Stored value; null without a row; an empty string for a value that is no string.
	 */
	public function row( string $slug ): ?string;
}
