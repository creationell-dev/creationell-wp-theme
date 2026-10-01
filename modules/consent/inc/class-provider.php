<?php
/**
 * Consent provider of the module consent.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\Consent;

use Creationell\WpTheme\Consent\Consent_Gate;
use Creationell\WpTheme\Consent\Consent_Provider_Interface;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CookieConsent as consent provider of the theme core; asks the visitor about the categories of the core.
 *
 * The module offers it on the filter creationell_wp_theme_consent_provider only
 * while no other provider is set and the library file is readable, so a
 * consent plugin of the project keeps its place and a missing library never
 * leaves the site with a provider that cannot run.
 *
 * @since 1.0.0
 */
final class Provider implements Consent_Provider_Interface {

	/**
	 * ID of the provider; the JavaScript adapter registers with the same ID.
	 *
	 * @since 1.0.0
	 */
	public const ID = 'cookieconsent';

	/**
	 * Library script relative to the theme folder.
	 *
	 * @since 1.0.0
	 */
	public const VENDOR_SCRIPT = 'assets/vendor/cookieconsent/cookieconsent.umd.js';

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Returns the shared instance, so the core checks its categories once per request.
	 *
	 * @since 1.0.0
	 *
	 * @return self Provider.
	 */
	public static function instance(): self {
		self::$instance ??= new self();
		return self::$instance;
	}

	/**
	 * Returns the ID of the provider.
	 *
	 * @since 1.0.0
	 *
	 * @return string "cookieconsent".
	 */
	public function id(): string {
		return self::ID;
	}

	/**
	 * Returns the categories of the core.
	 *
	 * @since 1.0.0
	 *
	 * @return list<string> Categories, "necessary" first.
	 */
	public function categories(): array {
		return creationell_wp_theme_consent_categories();
	}

	/**
	 * Offers the provider; runs on creationell_wp_theme_consent_provider with priority 10.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed       $provider    Provider so far, null by default.
	 * @param string|null $vendor_file Library script to check; null for the file of the theme.
	 * @return mixed The own provider for null while the library is readable, otherwise the value unchanged.
	 */
	public static function filter( mixed $provider, ?string $vendor_file = null ): mixed {
		if ( null !== $provider ) {
			return $provider;
		}
		$vendor_file ??= CREATIONELL_WP_THEME_DIR . '/' . self::VENDOR_SCRIPT;
		return is_readable( $vendor_file ) ? self::instance() : null;
	}

	/**
	 * Tells whether this provider is the effective consent provider of the request.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when the core returns this provider.
	 */
	public static function is_effective(): bool {
		return Consent_Gate::provider() instanceof self;
	}
}
