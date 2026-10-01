<?php
/**
 * Store of the backend states of the modules.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the state a module has in the backend layer.
 *
 * Only the module switcher writes; it stores a state only where it differs from
 * the base (registry default and child default) and deletes it otherwise.
 *
 * @since 1.0.0
 */
interface Module_Store_Interface {

	/**
	 * Returns the backend state of a module.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Module slug.
	 * @return string|null State, or null without a valid backend state.
	 */
	public function get( string $slug ): ?string;

	/**
	 * Stores or deletes the backend state of a module.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $slug  Module slug.
	 * @param string|null $state State; null deletes it.
	 * @return void
	 */
	public function set( string $slug, ?string $state ): void;
}
