<?php
/**
 * Consent gate of the theme core.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Consent;

use Creationell\WpTheme\Cli\Doctor_Command;
use WeakMap;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Answers whether the visitor allowed a consent category; fails closed.
 *
 * PHP knows no visitor choice: allowed() is true for "necessary" only, also
 * with a consent provider. The choice lives in the browser, where
 * window.creationellWpTheme.consent.allowed( category ) asks the provider
 * (Consent_Bridge). Scripts that need consent are marked and printed as
 * text/plain (Script_Marker); the provider runs them after consent.
 *
 * The consent provider comes from the filter creationell_wp_theme_consent_provider,
 * the categories from Consent_Categories. This part of the core cannot be
 * switched off.
 *
 * @since 1.0.0
 */
final class Consent_Gate {

	/**
	 * The category that is always allowed.
	 *
	 * @since 1.0.0
	 */
	public const NECESSARY = 'necessary';

	/**
	 * Filter for the consent provider; starts with null.
	 *
	 * @since 1.0.0
	 */
	public const FILTER_PROVIDER = 'creationell_wp_theme_consent_provider';

	/**
	 * Filter for the consent categories.
	 *
	 * @since 1.0.0
	 */
	public const FILTER_CATEGORIES = 'creationell_wp_theme_consent_categories';

	/**
	 * Pattern of a provider ID.
	 *
	 * @since 1.0.0
	 */
	public const PROVIDER_ID_PATTERN = '~^[a-z0-9]+(?:-[a-z0-9]+)*$~';

	/**
	 * Key of the doctor section.
	 *
	 * @since 1.0.0
	 */
	public const DOCTOR_SECTION = 'consent';

	/**
	 * Providers whose categories were checked in this request.
	 *
	 * @var WeakMap<Consent_Provider_Interface, true>|null
	 */
	private static ?WeakMap $checked = null;

	/**
	 * Registers the hooks of the consent interface; runs in Theme::boot().
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', array( Consent_Bridge::class, 'enqueue' ), 1, 0 );
		add_filter( 'script_loader_tag', array( Script_Marker::class, 'filter_tag' ), Script_Marker::PRIORITY, 2 );
		add_filter( 'wp_inline_script_attributes', array( Script_Marker::class, 'filter_inline_attributes' ), Script_Marker::PRIORITY, 2 );
		add_action( 'wp_print_scripts', array( Script_Marker::class, 'escape_inline_data' ), 1, 0 );
		add_action( 'wp_print_footer_scripts', array( Script_Marker::class, 'escape_inline_data' ), 1, 0 );
		add_action( 'shutdown', array( Script_Marker::class, 'persist' ), 10, 0 );
		add_action( 'admin_init', array( Script_Marker::class, 'queue_notice' ), 10, 0 );
		add_action( 'cli_init', array( self::class, 'add_doctor_section' ), 10, 0 );
	}

	/**
	 * Tells whether the visitor allowed a category.
	 *
	 * @since 1.0.0
	 *
	 * @param string $category Category, e.g. "necessary" or "analytics".
	 * @return bool True for "necessary" only; PHP never knows the visitor's choice.
	 */
	public static function allowed( string $category ): bool {
		return self::NECESSARY === $category;
	}

	/**
	 * Returns the consent provider of the filter creationell_wp_theme_consent_provider.
	 *
	 * A value that is no Consent_Provider_Interface, or a provider with an
	 * invalid ID, counts as no provider. Provider categories outside
	 * Consent_Categories are ignored. Both give a _doing_it_wrong() notice,
	 * the categories once per provider and request.
	 *
	 * @since 1.0.0
	 *
	 * @return Consent_Provider_Interface|null Provider, or null.
	 */
	public static function provider(): ?Consent_Provider_Interface {
		/**
		 * Filters the consent provider.
		 *
		 * The consent module returns its provider here when the value is still
		 * null, so a provider of another plugin stays.
		 *
		 * @since 1.0.0
		 *
		 * @param Consent_Provider_Interface|null $provider Provider, null by default.
		 */
		$provider = apply_filters( 'creationell_wp_theme_consent_provider', null );
		if ( null === $provider ) {
			return null;
		}
		if ( ! $provider instanceof Consent_Provider_Interface ) {
			_doing_it_wrong( __METHOD__, esc_html( sprintf( 'The filter %s returned no Consent_Provider_Interface; the theme works without a consent provider.', self::FILTER_PROVIDER ) ), '1.0.0' );
			return null;
		}
		$id = $provider->id();
		if ( 1 !== preg_match( self::PROVIDER_ID_PATTERN, $id ) ) {
			_doing_it_wrong( __METHOD__, esc_html( sprintf( 'The consent provider ID "%s" is invalid: use lower case letters, digits and hyphens. The theme works without a consent provider.', $id ) ), '1.0.0' );
			return null;
		}
		self::check_categories( $provider );
		return $provider;
	}

	/**
	 * Tells whether a consent provider is active.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when provider() returns one.
	 */
	public static function has_provider(): bool {
		return null !== self::provider();
	}

	/**
	 * Returns the known categories.
	 *
	 * @since 1.0.0
	 *
	 * @return list<string> Categories, "necessary" first.
	 */
	public static function categories(): array {
		return Consent_Categories::all();
	}

	/**
	 * Adds the section "consent" to "wp creationell-theme doctor"; runs on cli_init.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function add_doctor_section(): void {
		if ( in_array( self::DOCTOR_SECTION, Doctor_Command::section_keys(), true ) ) {
			return;
		}
		Doctor_Command::add_section( self::DOCTOR_SECTION, array( self::class, 'doctor_section' ) );
	}

	/**
	 * Collects the doctor section: provider ID, categories, marked scripts without provider.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Values; "warnings" when marked scripts wait for a provider.
	 */
	public static function doctor_section(): array {
		$provider = self::provider();
		$marked   = null === $provider ? Script_Marker::stored() : array();
		$values   = array(
			'provider'                => null === $provider ? null : $provider->id(),
			'categories'              => self::categories(),
			'marked_without_provider' => $marked,
		);
		if ( array() !== $marked ) {
			$values['warnings'] = array(
				sprintf(
					'%1$d marked %2$s never run: no consent provider is active (%3$s).',
					count( $marked ),
					1 === count( $marked ) ? 'script' : 'scripts',
					implode( ', ', array_column( $marked, 'handle' ) )
				),
			);
		}
		return $values;
	}

	/**
	 * Reports provider categories outside the known list once per provider and request.
	 *
	 * @since 1.0.0
	 *
	 * @param Consent_Provider_Interface $provider Provider.
	 * @return void
	 */
	private static function check_categories( Consent_Provider_Interface $provider ): void {
		self::$checked ??= new WeakMap();
		if ( isset( self::$checked[ $provider ] ) ) {
			return;
		}
		self::$checked[ $provider ] = true;
		$unknown                    = array_diff( $provider->categories(), self::categories() );
		if ( array() !== $unknown ) {
			_doing_it_wrong(
				__CLASS__ . '::provider',
				esc_html( sprintf( 'The consent provider "%1$s" names categories the theme does not know; they are ignored: %2$s. Add them with the filter %3$s.', $provider->id(), implode( ', ', $unknown ), self::FILTER_CATEGORIES ) ),
				'1.0.0'
			);
		}
	}
}
