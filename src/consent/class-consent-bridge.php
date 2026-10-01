<?php
/**
 * JavaScript bridge of the consent interface.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Consent;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Prints window.creationellWpTheme with the consent bridge inline in the head of every front-end page.
 *
 * The handle has no file: its inline code holds the data (theme version,
 * categories, ID of the consent provider) and then assets/js/consent-bridge.js,
 * read from the theme folder without a build step. The bridge offers
 * window.creationellWpTheme.consent with categories, allowed(), hasProvider(),
 * registerProvider() and notify(); the JavaScript adapter of the provider
 * registers itself there.
 *
 * @since 1.0.0
 */
final class Consent_Bridge {

	/**
	 * Script handle.
	 *
	 * @since 1.0.0
	 */
	public const HANDLE = 'creationell-wp-theme-consent';

	/**
	 * Bridge file relative to the theme folder.
	 *
	 * @since 1.0.0
	 */
	public const SCRIPT = 'assets/js/consent-bridge.js';

	/**
	 * Event on document after a consent change; detail: { accepted: string[] }.
	 *
	 * @since 1.0.0
	 */
	public const EVENT = 'creationell-wp-theme:consent-change';

	/**
	 * JSON flags: no "<", ">" or "&" in the inline code.
	 *
	 * @since 1.0.0
	 */
	public const JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES;

	/**
	 * Enqueues the bridge in the head; runs on wp_enqueue_scripts with priority 1, before all other scripts.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function enqueue(): void {
		$path   = get_template_directory() . '/' . self::SCRIPT;
		$bridge = is_readable( $path ) ? file_get_contents( get_template_directory() . '/' . self::SCRIPT ) : false;
		if ( ! is_string( $bridge ) || '' === $bridge ) {
			return;
		}
		wp_register_script( self::HANDLE, false, array(), CREATIONELL_WP_THEME_VERSION, array( 'in_footer' => false ) );
		wp_add_inline_script( self::HANDLE, self::data_script( self::data() ), 'after' );
		wp_add_inline_script( self::HANDLE, $bridge, 'after' );
		wp_enqueue_script( self::HANDLE );
	}

	/**
	 * Returns the data of the bridge.
	 *
	 * @since 1.0.0
	 *
	 * @return array{categories: list<string>, providerId: string|null} Categories and provider ID.
	 */
	public static function data(): array {
		$provider = Consent_Gate::provider();
		return array(
			'categories' => Consent_Gate::categories(),
			'providerId' => null === $provider ? null : $provider->id(),
		);
	}

	/**
	 * Returns the inline code that sets the theme version and the data before the bridge runs.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data of the bridge.
	 * @return string JavaScript.
	 */
	public static function data_script( array $data ): string {
		$version = wp_json_encode( CREATIONELL_WP_THEME_VERSION, self::JSON_FLAGS );
		$json    = wp_json_encode( $data, self::JSON_FLAGS );
		return 'window.creationellWpTheme = Object.assign( window.creationellWpTheme || {}, { version: ' . ( false === $version ? '""' : $version ) . ' } );' . "\n"
			. 'window.creationellWpTheme.consent = ' . ( false === $json ? '{}' : $json ) . ';';
	}
}
