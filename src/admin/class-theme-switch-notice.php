<?php
/**
 * Notice on the theme screen about what a theme switch takes away.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Admin;

use Creationell\WpTheme\Modules\Module_State;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Warns on Appearance > Themes before another theme is activated.
 *
 * WordPress has no way to stop a theme switch, and the Customizer and WP-CLI
 * skip this screen, so the notice is the only warning. It names the modules
 * with blocks that are not off, whose blocks show nothing under another theme,
 * and says that the cookie banner and the consent lock are gone while the
 * module consent is not off. It does not count contents, so the theme screen
 * stays as fast as without the theme. Only users with switch_themes see it.
 *
 * @since 1.0.0
 */
final class Theme_Switch_Notice {

	/**
	 * Slug of the consent module.
	 *
	 * @since 1.0.0
	 */
	public const CONSENT = 'consent';

	/**
	 * Registers the notice for the theme screen.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_action( 'load-themes.php', array( self::class, 'queue' ), 10, 0 );
	}

	/**
	 * Adds the notice to admin_notices when the current user may switch themes and a module is concerned; runs on load-themes.php.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function queue(): void {
		if ( ! current_user_can( 'switch_themes' ) || '' === self::message() ) {
			return;
		}
		add_action( 'admin_notices', array( self::class, 'render' ), 10, 0 );
	}

	/**
	 * Prints the notice; runs on admin_notices of the theme screen.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function render(): void {
		$message = self::message();
		if ( '' === $message ) {
			return;
		}
		printf(
			'<div id="creationell-wp-theme-notice-theme-switch" class="notice notice-warning"><p>%s</p></div>' . "\n",
			esc_html( $message )
		);
	}

	/**
	 * Returns the notice text, or an empty string when no module is concerned.
	 *
	 * @since 1.0.0
	 *
	 * @return string Plain text.
	 */
	public static function message(): string {
		$state   = Module_State::instance();
		$catalog = $state->catalog();
		$titles  = array();
		$consent = false;
		foreach ( $catalog->slugs() as $slug ) {
			if ( 'off' === $state->state( $slug ) ) {
				continue;
			}
			$consent  = $consent || self::CONSENT === $slug;
			$manifest = $catalog->manifest( $slug );
			if ( null !== $manifest && array() !== $manifest->blocks ) {
				$titles[] = $catalog->title( $slug );
			}
		}
		$blocks = implode( ', ', $titles );
		if ( '' !== $blocks && $consent ) {
			/* translators: %s: titles of the modules, separated by commas. */
			return sprintf( __( 'After a theme switch, the blocks of %s are missing; the cookie banner and the consent lock are gone.', 'creationell-wp-theme' ), $blocks );
		}
		if ( '' !== $blocks ) {
			/* translators: %s: titles of the modules, separated by commas. */
			return sprintf( __( 'After a theme switch, the blocks of %s are missing.', 'creationell-wp-theme' ), $blocks );
		}
		return $consent ? __( 'After a theme switch, the cookie banner and the consent lock are gone.', 'creationell-wp-theme' ) : '';
	}
}
