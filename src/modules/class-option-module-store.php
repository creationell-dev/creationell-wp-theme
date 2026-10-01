<?php
/**
 * Module store on the raw options.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules;

use Creationell\WpTheme\Settings\Registry;
use InvalidArgumentException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Keeps the backend state of a module in the option creationell_wp_theme_global_module_<slug>.
 *
 * The option name follows the ACF form of the settings (post_id
 * creationell_wp_theme_global), without the reference row, and is not
 * autoloaded. This store reads the raw option. The shared module state uses
 * Settings_Module_Store, which reads the same rows from the autoloaded
 * snapshot and falls back to this store while there is no current snapshot. A
 * stored value that is no module state reads as null.
 *
 * @since 1.0.0
 */
final class Option_Module_Store implements Module_Store_Interface {

	/**
	 * Prefix of the option names; the setting key module_<slug> follows.
	 *
	 * @since 1.0.0
	 */
	public const OPTION_PREFIX = 'creationell_wp_theme_global_';

	/**
	 * Returns the option name of a module.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Module slug.
	 * @return string Option name, e.g. creationell_wp_theme_global_module_header_footer.
	 */
	public static function option_name( string $slug ): string {
		return self::OPTION_PREFIX . Registry::module_key( $slug );
	}

	/**
	 * Returns the backend state of a module.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Module slug.
	 * @return string|null State, or null without option or when the option holds no module state.
	 */
	public function get( string $slug ): ?string {
		$state = get_option( self::option_name( $slug ), null );
		return is_string( $state ) && in_array( $state, Module_Manifest::STATES, true ) ? $state : null;
	}

	/**
	 * Stores the backend state of a module without autoload, or deletes it.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $slug  Module slug.
	 * @param string|null $state State; null deletes the option.
	 * @return void
	 * @throws InvalidArgumentException When the state is no module state.
	 */
	public function set( string $slug, ?string $state ): void {
		if ( null === $state ) {
			delete_option( self::option_name( $slug ) );
			return;
		}
		if ( ! in_array( $state, Module_Manifest::STATES, true ) ) {
			throw new InvalidArgumentException( esc_html( sprintf( 'Module %1$s has no state %2$s.', $slug, $state ) ) );
		}
		update_option( self::option_name( $slug ), $state, false );
	}
}
