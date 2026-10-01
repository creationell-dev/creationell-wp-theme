<?php
/**
 * Theme updater: offers and installs updates of the parent theme from its update manifest.
 *
 * Derived from the JPKCom Plugin Updater via CreaCaptcha (downstream copy in
 * CreaCaptcha 1.1.2, includes/class-plugin-updater.php), licensed under
 * GPL-2.0-or-later; the author is named in THIRD-PARTY-NOTICES.
 *
 * Changes against the source, made by creationell in 2026: updates of a theme
 * instead of a plugin, offered through the filter update_themes_{hostname} of the
 * Update URI header instead of the plugin transient, without a details dialog;
 * assignment by the theme named in the upgrader arguments, otherwise by exact
 * package URL; manifest URL and package URL must use https, the package URL must
 * start with the release prefix; the manifest also names requirements, Bootstrap
 * lines and a details page, checked in Manifest; the offer carries the
 * requirements, the download gate refuses incompatible packages, packages
 * without the Bootstrap line of the site, packages another manifest names and
 * every automatic run; a guard refuses packages with another root folder; an
 * offer of wordpress.org for the slug is replaced by the own offer; automatic
 * updates are locked; messages are translatable; new documentation.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Update;

use Creationell\WpTheme\Admin\Notices;
use Creationell\WpTheme\Core\Environment;
use Creationell\WpTheme\Settings\Bootstrap_Line;
use WP_Error;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Offers updates of the parent theme from the update manifest and installs only verified packages.
 *
 * WordPress asks the filter update_themes_{hostname} of the Update URI for an
 * offer; filter_update() answers with the version, package and requirements of
 * the manifest. Before WordPress installs a package, verify_download() checks it
 * against the manifest (https, checksum, requirements, Bootstrap line) and hands
 * the verified file to the installer. Automatic updates stay locked twice: the
 * filter auto_update_theme says no, and the gate refuses every automatic run,
 * also when a plugin forces automatic updates.
 *
 * The manifest is cached for a day in a transient; a failed fetch is remembered
 * for five minutes and a lock keeps parallel requests from fetching twice.
 * These three transients are the only data the theme writes to the database.
 * With WP_DEBUG the updater writes short lines to the PHP error log.
 *
 * Example:
 *
 *     $status = Theme_Updater::instance()->status();
 *     echo $status['status']; // update_available, up_to_date or the reason.
 *
 * @api
 * @since 1.0.0
 */
final class Theme_Updater {

	/**
	 * Folder name of the parent theme, the root folder of its package.
	 *
	 * @since 1.0.0
	 */
	public const THEME_SLUG = 'creationell-wp-theme';

	/**
	 * Update URI header of the parent theme.
	 *
	 * @since 1.0.0
	 */
	public const UPDATE_URI = 'https://creationell-dev.github.io/creationell-wp-theme/';

	/**
	 * Host of the Update URI; WordPress asks the filter update_themes_{host}.
	 *
	 * @since 1.0.0
	 */
	public const UPDATE_HOST = 'creationell-dev.github.io';

	/**
	 * Start of every package URL: the release downloads of the public repository.
	 *
	 * @since 1.0.0
	 */
	public const PACKAGE_URL_PREFIX = 'https://github.com/creationell-dev/creationell-wp-theme/releases/download/';

	/**
	 * Largest accepted manifest body in bytes.
	 *
	 * @since 1.0.0
	 */
	public const MAX_MANIFEST_BYTES = 1048576;

	/**
	 * Largest accepted nesting depth of the manifest JSON.
	 *
	 * @since 1.0.0
	 */
	public const MANIFEST_JSON_DEPTH = 32;

	/**
	 * How long a fetched manifest is cached, in seconds.
	 *
	 * @since 1.0.0
	 */
	public const CACHE_TTL = DAY_IN_SECONDS;

	/**
	 * How long a failed fetch is remembered, in seconds.
	 *
	 * @since 1.0.0
	 */
	public const FETCH_FAILURE_TTL = 300;

	/**
	 * How long the fetch lock holds at most, in seconds.
	 *
	 * @since 1.0.0
	 */
	public const LOCK_TTL = 30;

	/**
	 * Timeout of the manifest request, in seconds.
	 *
	 * @since 1.0.0
	 */
	public const HTTP_TIMEOUT = 15;

	/**
	 * Transient of the cached manifest.
	 *
	 * @since 1.0.0
	 */
	public const CACHE_KEY = 'creationell_wp_theme_update_manifest';

	/**
	 * Transient of the fetch lock.
	 *
	 * @since 1.0.0
	 */
	public const LOCK_KEY = self::CACHE_KEY . '_lock';

	/**
	 * Transient that remembers a failed fetch.
	 *
	 * @since 1.0.0
	 */
	public const FAILURE_KEY = self::CACHE_KEY . '_fail';

	/**
	 * Exact class of the skin of automatic background updates.
	 *
	 * Compared by name, not with instanceof: the manual update over AJAX uses a
	 * subclass of it.
	 *
	 * @since 1.0.0
	 */
	public const AUTOMATIC_SKIN = 'Automatic_Upgrader_Skin';

	/**
	 * Start of every line in the PHP error log.
	 *
	 * @since 1.0.0
	 */
	public const LOG_PREFIX = 'creationell Theme updater: ';

	/**
	 * Shared updater of the running site.
	 *
	 * @var Theme_Updater|null
	 */
	private static ?Theme_Updater $instance = null;

	/**
	 * URL of the update manifest.
	 *
	 * @var string
	 */
	private string $manifest_url;

	/**
	 * Installed version of the parent theme.
	 *
	 * @var string
	 */
	private string $current_version;

	/**
	 * Stores the manifest URL and the installed version; registers nothing.
	 *
	 * A manifest URL without https is kept for the status, but never fetched.
	 *
	 * @since 1.0.0
	 *
	 * @param string $manifest_url    URL of the update manifest.
	 * @param string $current_version Installed version of the parent theme.
	 */
	public function __construct( string $manifest_url, string $current_version ) {
		$this->manifest_url    = $manifest_url;
		$this->current_version = $current_version;
	}

	/**
	 * Returns the shared updater for the manifest URL of the site and the installed version.
	 *
	 * @since 1.0.0
	 *
	 * @return Theme_Updater Updater.
	 */
	public static function instance(): Theme_Updater {
		if ( null === self::$instance ) {
			self::$instance = new self( Environment::manifest_url(), self::installed_version() );
		}
		return self::$instance;
	}

	/**
	 * Replaces the shared updater; null builds a new one on the next call of instance().
	 *
	 * @since 1.0.0
	 *
	 * @param Theme_Updater|null $updater Updater, or null.
	 * @return void
	 */
	public static function set_instance( ?Theme_Updater $updater ): void {
		self::$instance = $updater;
	}

	/**
	 * Returns the Version header of the installed parent theme.
	 *
	 * @since 1.0.0
	 *
	 * @return string Version, empty when unknown.
	 */
	public static function installed_version(): string {
		$version = wp_get_theme( self::THEME_SLUG )->get( 'Version' );
		return is_string( $version ) ? $version : '';
	}

	/**
	 * Returns the URL of the update manifest.
	 *
	 * @since 1.0.0
	 *
	 * @return string URL.
	 */
	public function manifest_url(): string {
		return $this->manifest_url;
	}

	/**
	 * Returns the installed version the updater compares with.
	 *
	 * @since 1.0.0
	 *
	 * @return string Version.
	 */
	public function current_version(): string {
		return $this->current_version;
	}

	/**
	 * Registers the update hooks; loads nothing later, all update code is loaded before.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'update_themes_' . self::UPDATE_HOST, array( $this, 'filter_update' ), 10, 4 );
		add_filter( 'upgrader_pre_download', array( $this, 'verify_download' ), 10, 4 );
		add_filter( 'upgrader_source_selection', array( $this, 'guard_source_selection' ), 10, 4 );
		add_filter( 'auto_update_theme', array( $this, 'disable_auto_update' ), PHP_INT_MAX, 2 );
		add_action( 'upgrader_process_complete', array( $this, 'clear_cache' ), 10, 2 );
		add_filter( 'pre_set_site_transient_update_themes', array( $this, 'replace_foreign_offer' ), 10, 1 );
	}

	/**
	 * Answers the update check of WordPress for the parent theme; runs on update_themes_{host}.
	 *
	 * Only the parent theme with exactly this Update URI gets an offer. The offer
	 * names theme, version, package, details URL and requirements, never
	 * "autoupdate". Without a valid manifest or checksum there is no offer; when
	 * the package lacks the Bootstrap line of the site, there is none either and
	 * administrators see a notice.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $update           Offer so far, false by default.
	 * @param mixed $theme_data       Theme headers, including "UpdateURI".
	 * @param mixed $theme_stylesheet Folder name of the theme.
	 * @param mixed $locales          Installed locales; unused.
	 * @return mixed Offer as array, false, or the unchanged value for other themes.
	 */
	public function filter_update( mixed $update, mixed $theme_data, mixed $theme_stylesheet, mixed $locales ): mixed {
		unset( $locales );
		if ( self::THEME_SLUG !== $theme_stylesheet || ! is_array( $theme_data ) || self::UPDATE_URI !== ( $theme_data['UpdateURI'] ?? null ) ) {
			return $update;
		}
		$manifest = $this->get_manifest();
		if ( null === $manifest ) {
			return $update;
		}
		if ( ! $manifest->has_checksum() ) {
			self::log( 'the manifest has no checksum; no update is offered.' );
			return $update;
		}
		if ( ! $manifest->supports_line( self::active_line() ) ) {
			$this->queue_line_notice( $manifest );
			return false;
		}
		return $this->offer( $manifest );
	}

	/**
	 * Verifies a package of the parent theme before WordPress installs it; runs on upgrader_pre_download.
	 *
	 * Steps: another handler's value passes; other themes pass; without a theme
	 * name only the package of the manifest is gated. Then automatic runs are
	 * refused, the package URL must use https, the manifest must load and carry a
	 * checksum, the package must be the one of the manifest (the manifest is
	 * reloaded once), the site must meet the requirements and use a Bootstrap line
	 * of the package. The package is downloaded and hashed; the verified file goes
	 * to the installer, a file with another checksum is deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $reply      Value of the filter, false unless another handler answered.
	 * @param mixed $package    Package URL.
	 * @param mixed $upgrader   Upgrader with its skin.
	 * @param mixed $hook_extra Upgrader arguments, e.g. array( 'theme' => ... ).
	 * @return mixed Path of the verified file, a WP_Error, or the unchanged value.
	 */
	public function verify_download( mixed $reply, mixed $package, mixed $upgrader, mixed $hook_extra ): mixed {
		if ( false !== $reply ) {
			return $reply;
		}
		$package    = is_string( $package ) ? $package : '';
		$hook_extra = is_array( $hook_extra ) ? $hook_extra : array();
		if ( array_key_exists( 'theme', $hook_extra ) ) {
			if ( self::THEME_SLUG !== $hook_extra['theme'] ) {
				return $reply;
			}
		} elseif ( ! str_starts_with( $package, self::PACKAGE_URL_PREFIX ) || $package !== $this->get_manifest()?->download_url ) {
			return $reply;
		}

		if ( self::is_automatic_run( $upgrader ) ) {
			return self::refuse( Update_Error::AUTO_UPDATE_BLOCKED );
		}
		if ( ! self::is_https_url( $package ) ) {
			return self::refuse( Update_Error::INSECURE_PACKAGE_URL );
		}
		$manifest = $this->get_manifest();
		if ( null === $manifest ) {
			return self::refuse( Update_Error::MANIFEST_UNAVAILABLE );
		}
		if ( ! $manifest->has_checksum() ) {
			return self::refuse( Update_Error::CHECKSUM_MISSING );
		}
		if ( $package !== $manifest->download_url ) {
			$manifest = $this->get_manifest( true );
			if ( null === $manifest || $package !== $manifest->download_url ) {
				return self::refuse( Update_Error::PACKAGE_MISMATCH );
			}
			if ( ! $manifest->has_checksum() ) {
				return self::refuse( Update_Error::CHECKSUM_MISSING );
			}
		}
		if ( ! $this->is_compatible( $manifest ) ) {
			return self::refuse( Update_Error::INCOMPATIBLE );
		}
		if ( ! $manifest->supports_line( self::active_line() ) ) {
			return self::refuse( Update_Error::LINE_UNAVAILABLE );
		}
		return $this->download( $package, (string) $manifest->checksum_sha256 );
	}

	/**
	 * Refuses an update of the parent theme whose package unpacks to another folder; runs on upgrader_source_selection.
	 *
	 * WordPress would otherwise install the package next to the theme or replace
	 * the theme folder with a folder of another name.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $source        Unpacked folder, or a WP_Error.
	 * @param mixed $remote_source Working folder; unused.
	 * @param mixed $upgrader      Upgrader; unused.
	 * @param mixed $hook_extra    Upgrader arguments, e.g. array( 'theme' => ... ).
	 * @return mixed The unchanged source, or a WP_Error.
	 */
	public function guard_source_selection( mixed $source, mixed $remote_source, mixed $upgrader, mixed $hook_extra ): mixed {
		unset( $remote_source, $upgrader );
		if ( ! is_string( $source ) || ! is_array( $hook_extra ) || self::THEME_SLUG !== ( $hook_extra['theme'] ?? null ) ) {
			return $source;
		}
		if ( self::THEME_SLUG === basename( $source ) ) {
			return $source;
		}
		return self::refuse( Update_Error::WRONG_ROOT_FOLDER );
	}

	/**
	 * Switches automatic updates of the parent theme off; runs last on auto_update_theme.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $update Whether to update automatically.
	 * @param mixed $item   Update offer with the theme folder name under "theme".
	 * @return mixed False for the parent theme, otherwise the unchanged value.
	 */
	public function disable_auto_update( mixed $update, mixed $item ): mixed {
		if ( is_object( $item ) ) {
			$item = get_object_vars( $item );
		}
		return is_array( $item ) && self::THEME_SLUG === ( $item['theme'] ?? null ) ? false : $update;
	}

	/**
	 * Replaces an offer of wordpress.org for the slug by the own offer; runs on pre_set_site_transient_update_themes.
	 *
	 * When wordpress.org answers for the slug, WordPress skips the filter of the
	 * Update URI. An entry for the slug without this Update URI as "id" is removed;
	 * with a valid manifest the own offer takes its place, under "response" when
	 * it is newer than the installed version, otherwise under "no_update".
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value of the transient update_themes.
	 * @return mixed The value, with the own offer for the slug.
	 */
	public function replace_foreign_offer( mixed $value ): mixed {
		if ( ! $value instanceof \stdClass ) {
			return $value;
		}
		$foreign = false;
		foreach ( array( 'response', 'no_update' ) as $list ) {
			$entries = $value->{$list} ?? null;
			if ( ! is_array( $entries ) || ! array_key_exists( self::THEME_SLUG, $entries ) ) {
				continue;
			}
			$entry = $entries[ self::THEME_SLUG ];
			$entry = is_object( $entry ) ? get_object_vars( $entry ) : $entry;
			if ( ! is_array( $entry ) || self::UPDATE_URI !== ( $entry['id'] ?? null ) ) {
				$foreign = true;
			}
		}
		if ( ! $foreign ) {
			return $value;
		}
		foreach ( array( 'response', 'no_update' ) as $list ) {
			if ( isset( $value->{$list} ) && is_array( $value->{$list} ) ) {
				unset( $value->{$list}[ self::THEME_SLUG ] );
			}
		}
		$offer = $this->filter_update( false, self::theme_headers(), self::THEME_SLUG, array() );
		if ( ! is_array( $offer ) || ! is_string( $offer['version'] ?? null ) ) {
			return $value;
		}
		$offer['id']                 = self::UPDATE_URI;
		$offer['new_version']        = $offer['version'];
		$list                        = version_compare( $offer['version'], $this->current_version, '>' ) ? 'response' : 'no_update';
		$entries                     = isset( $value->{$list} ) && is_array( $value->{$list} ) ? $value->{$list} : array();
		$entries[ self::THEME_SLUG ] = $offer;
		$value->{$list}              = $entries;
		return $value;
	}

	/**
	 * Clears the cached manifest and the failure marker after an update of the parent theme; runs on upgrader_process_complete.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $upgrader Upgrader; unused.
	 * @param mixed $options  Arguments: "type", and "theme" or "themes".
	 * @return void
	 */
	public function clear_cache( mixed $upgrader, mixed $options ): void {
		unset( $upgrader );
		if ( ! is_array( $options ) || 'theme' !== ( $options['type'] ?? null ) ) {
			return;
		}
		$themes = isset( $options['themes'] ) && is_array( $options['themes'] ) ? $options['themes'] : array();
		if ( isset( $options['theme'] ) ) {
			$themes[] = $options['theme'];
		}
		if ( in_array( self::THEME_SLUG, $themes, true ) ) {
			delete_transient( self::CACHE_KEY );
			delete_transient( self::FAILURE_KEY );
		}
	}

	/**
	 * Returns the checked manifest from the cache or the network.
	 *
	 * A cached value passes the same checks as a fetched one; a failed fetch is
	 * remembered for FETCH_FAILURE_TTL seconds, including HTTP 429. While another
	 * request holds the lock, the result is null.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $force True skips the cache and the failure marker, not the lock.
	 * @return Manifest|null Manifest, or null when none is available.
	 */
	public function get_manifest( bool $force = false ): ?Manifest {
		if ( ! self::is_https_url( $this->manifest_url ) ) {
			return null;
		}
		if ( ! $force ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( false !== $cached ) {
				$manifest = Manifest::from_decoded( $cached );
				if ( null !== $manifest ) {
					return $manifest;
				}
				delete_transient( self::CACHE_KEY );
				self::log( 'the cached manifest failed the checks; fetching it again.' );
			}
			if ( false !== get_transient( self::FAILURE_KEY ) ) {
				return null;
			}
		}
		if ( false !== get_transient( self::LOCK_KEY ) ) {
			return null;
		}

		set_transient( self::LOCK_KEY, 1, self::LOCK_TTL );
		$manifest = $this->fetch();
		delete_transient( self::LOCK_KEY );

		if ( null === $manifest ) {
			set_transient( self::FAILURE_KEY, 1, self::FETCH_FAILURE_TTL );
			return null;
		}
		set_transient( self::CACHE_KEY, $manifest->to_array(), self::CACHE_TTL );
		delete_transient( self::FAILURE_KEY );
		return $manifest;
	}

	/**
	 * Reports the update state of the parent theme for the command line.
	 *
	 * The status is "update_available", "up_to_date" or the short code of the
	 * reason why nothing can be installed: "manifest_unavailable",
	 * "checksum_missing", "line_unavailable", "incompatible".
	 *
	 * @since 1.0.0
	 *
	 * @param bool $refresh True fetches the manifest again.
	 * @return array{slug: string, installed_version: string, manifest_url: string, status: string, error_code: string, available_version: string, package: string, bootstrap_lines: array<int, int>, active_line: int, requires: string, requires_php: string, tested: string, auto_updates: string, failure_cached: bool} State.
	 */
	public function status( bool $refresh = false ): array {
		$manifest = $this->get_manifest( $refresh );
		$line     = self::active_line();
		$error    = null;
		$status   = 'up_to_date';
		if ( null === $manifest ) {
			$error = Update_Error::MANIFEST_UNAVAILABLE;
		} elseif ( ! $manifest->has_checksum() ) {
			$error = Update_Error::CHECKSUM_MISSING;
		} elseif ( ! $manifest->supports_line( $line ) ) {
			$error = Update_Error::LINE_UNAVAILABLE;
		} elseif ( version_compare( $manifest->version, $this->current_version, '>' ) ) {
			$error  = $this->is_compatible( $manifest ) ? null : Update_Error::INCOMPATIBLE;
			$status = 'update_available';
		}
		return array(
			'slug'              => self::THEME_SLUG,
			'installed_version' => $this->current_version,
			'manifest_url'      => $this->manifest_url,
			'status'            => null === $error ? $status : $error->short_code(),
			'error_code'        => null === $error ? '' : $error->value,
			'available_version' => null === $manifest ? '' : $manifest->version,
			'package'           => null === $manifest ? '' : $manifest->download_url,
			'bootstrap_lines'   => null === $manifest ? array() : $manifest->bootstrap_lines,
			'active_line'       => $line,
			'requires'          => null === $manifest ? '' : $manifest->requires,
			'requires_php'      => null === $manifest ? '' : $manifest->requires_php,
			'tested'            => null === $manifest ? '' : $manifest->tested,
			'auto_updates'      => 'disabled',
			'failure_cached'    => false !== get_transient( self::FAILURE_KEY ),
		);
	}

	/**
	 * Fetches and checks the manifest; logs why a fetch fails.
	 *
	 * @since 1.0.0
	 *
	 * @return Manifest|null Manifest, or null on any failure.
	 */
	private function fetch(): ?Manifest {
		$response = wp_safe_remote_get(
			$this->manifest_url,
			array(
				'timeout'             => self::HTTP_TIMEOUT,
				'headers'             => array( 'Accept' => 'application/json' ),
				'limit_response_size' => self::MAX_MANIFEST_BYTES + 1,
			)
		);
		if ( $response instanceof WP_Error ) {
			self::log( 'the manifest request failed (' . $response->get_error_code() . ').' );
			return null;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			self::log( 'the manifest request answered HTTP ' . $code . '.' );
			return null;
		}
		$body = wp_remote_retrieve_body( $response );
		if ( strlen( $body ) > self::MAX_MANIFEST_BYTES ) {
			self::log( 'the manifest is larger than ' . self::MAX_MANIFEST_BYTES . ' bytes.' );
			return null;
		}
		$data = json_decode( $body, true, self::MANIFEST_JSON_DEPTH );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			self::log( 'the manifest is no valid JSON (' . json_last_error_msg() . ').' );
			return null;
		}
		$manifest = Manifest::from_decoded( $data );
		if ( null === $manifest ) {
			self::log( 'the manifest was rejected: a field is missing or invalid.' );
		}
		return $manifest;
	}

	/**
	 * Downloads the package and compares its SHA-256 with the manifest.
	 *
	 * @since 1.0.0
	 *
	 * @param string $package  Package URL.
	 * @param string $expected SHA-256 from the manifest, lower case.
	 * @return string|WP_Error Path of the verified file, or an error.
	 */
	private function download( string $package, string $expected ): string|WP_Error {
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$file = download_url( $package );
		if ( $file instanceof WP_Error ) {
			return self::refuse( Update_Error::DOWNLOAD_FAILED, array( 'reason' => $file->get_error_code() ) );
		}
		$actual = hash_file( 'sha256', $file );
		if ( false === $actual || ! hash_equals( $expected, $actual ) ) {
			wp_delete_file( $file );
			return self::refuse(
				Update_Error::CHECKSUM_MISMATCH,
				array(
					'expected' => $expected,
					'actual'   => false === $actual ? '' : $actual,
				)
			);
		}
		self::log( 'the package matches the checksum of the manifest.' );
		return $file;
	}

	/**
	 * Returns the offer for WordPress from the manifest.
	 *
	 * @since 1.0.0
	 *
	 * @param Manifest $manifest Manifest.
	 * @return array{theme: string, version: string, package: string, url: string, requires: string, requires_php: string, tested: string} Offer.
	 */
	private function offer( Manifest $manifest ): array {
		return array(
			'theme'        => self::THEME_SLUG,
			'version'      => $manifest->version,
			'package'      => $manifest->download_url,
			'url'          => $manifest->details_url,
			'requires'     => $manifest->requires,
			'requires_php' => $manifest->requires_php,
			'tested'       => $manifest->tested,
		);
	}

	/**
	 * Tells whether the site meets the WordPress and PHP requirements of the manifest.
	 *
	 * Suffixes such as "-RC1" count as the release, as in WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @param Manifest $manifest Manifest.
	 * @return bool True when both requirements are met.
	 */
	private function is_compatible( Manifest $manifest ): bool {
		return version_compare( self::release( Environment::wp_version() ), $manifest->requires, '>=' )
			&& version_compare( self::release( PHP_VERSION ), $manifest->requires_php, '>=' );
	}

	/**
	 * Queues the notice that the new version lacks the Bootstrap line of the site.
	 *
	 * @since 1.0.0
	 *
	 * @param Manifest $manifest Manifest.
	 * @return void
	 */
	private function queue_line_notice( Manifest $manifest ): void {
		$version = $manifest->version;
		$line    = self::active_line();
		Notices::add(
			'update-line',
			static fn(): string => sprintf(
				/* translators: 1: version of the theme, 2: Bootstrap line, e.g. 5. */
				__( 'Version %1$s of the creationell Theme does not contain Bootstrap line %2$d, which this site uses, so WordPress does not offer the update. Switch the site to a Bootstrap line of the new version first, as the theme documentation describes, then update.', 'creationell-wp-theme' ),
				$version,
				$line
			),
			Notices::WARNING
		);
	}

	/**
	 * Returns the headers of the installed parent theme in the form of the update check.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Headers.
	 */
	private static function theme_headers(): array {
		$theme   = wp_get_theme( self::THEME_SLUG );
		$headers = array();
		foreach ( array( 'Name', 'Version', 'UpdateURI' ) as $header ) {
			$value              = $theme->get( $header );
			$headers[ $header ] = is_string( $value ) ? $value : '';
		}
		return $headers;
	}

	/**
	 * Returns the active Bootstrap line of the site.
	 *
	 * @since 1.0.0
	 *
	 * @return int Line, e.g. 5.
	 */
	private static function active_line(): int {
		return Bootstrap_Line::instance()->active();
	}

	/**
	 * Tells whether an update runs automatically.
	 *
	 * Automatic background updates use exactly Automatic_Upgrader_Skin. Updates
	 * forced by a plugin run the automatic updater directly, inside the cron
	 * action wp_maybe_auto_update or with wp_doing_cron() switched on; those
	 * signals count as well. Manual updates, also over AJAX, pass.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $upgrader Upgrader with its skin.
	 * @return bool True for an automatic run.
	 */
	private static function is_automatic_run( mixed $upgrader ): bool {
		$skin = is_object( $upgrader ) ? ( get_object_vars( $upgrader )['skin'] ?? null ) : null;
		if ( is_object( $skin ) && self::AUTOMATIC_SKIN === get_class( $skin ) ) {
			return true;
		}
		return doing_action( 'wp_maybe_auto_update' ) || wp_doing_cron();
	}

	/**
	 * Tells whether a URL uses https and names a host.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url URL.
	 * @return bool True for an https URL.
	 */
	private static function is_https_url( string $url ): bool {
		return 1 === preg_match( '~^https://[^/\s?#@:]+(?::[0-9]+)?(?:[/?#]|$)~i', $url );
	}

	/**
	 * Returns a version without the suffix after the first hyphen.
	 *
	 * @since 1.0.0
	 *
	 * @param string $version Version, e.g. "7.2-RC1".
	 * @return string Release, e.g. "7.2".
	 */
	private static function release( string $version ): string {
		$release = strtok( $version, '-' );
		return false === $release ? '' : $release;
	}

	/**
	 * Logs the refusal and returns it as WP_Error.
	 *
	 * @since 1.0.0
	 *
	 * @param Update_Error $error Reason.
	 * @param mixed        $data  Error data.
	 * @return WP_Error Error.
	 */
	private static function refuse( Update_Error $error, mixed $data = '' ): WP_Error {
		self::log( 'refused the package: ' . $error->short_code() . '.' );
		return $error->to_wp_error( $data );
	}

	/**
	 * Writes a line to the PHP error log, only with WP_DEBUG.
	 *
	 * The lines name no URL, so they stay short and free of site data.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Message without the prefix.
	 * @return void
	 */
	private static function log( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && constant( 'WP_DEBUG' ) ) {
			error_log( self::LOG_PREFIX . $message );
		}
	}
}
