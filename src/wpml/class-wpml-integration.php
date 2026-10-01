<?php
/**
 * Detection, boot and status of WPML.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Wpml;

use Creationell\WpTheme\Cli\Doctor_Command;
use Creationell\WpTheme\Modules\HeaderFooter\Part_Translations;
use Creationell\WpTheme\Settings\Language;
use Creationell\WpTheme\Settings\Snapshot;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Tells whether WPML runs on the site, registers the WPML hooks of the theme and reports the WPML state; the theme reads WPML only through its filters and constants.
 *
 * Without WPML the theme prints no language switcher, shows no notice and
 * calls no WPML hook besides the detection; the doctor section "wpml" then
 * says active false.
 *
 * @since 1.0.0
 */
final class Wpml_Integration {

	/**
	 * Constant that WPML (Multilingual CMS) defines with its version.
	 *
	 * @since 1.0.0
	 */
	public const VERSION_CONSTANT = 'ICL_SITEPRESS_VERSION';

	/**
	 * Constant that WPML String Translation defines with its version.
	 *
	 * @since 1.0.0
	 */
	public const ST_VERSION_CONSTANT = 'WPML_ST_VERSION';

	/**
	 * Constant that ACFML (ACF Multilingual) defines with its version.
	 *
	 * @since 1.0.0
	 */
	public const ACFML_VERSION_CONSTANT = 'ACFML_VERSION';

	/**
	 * Key of the doctor section and of the output of "wp creationell-theme wpml status".
	 *
	 * @since 1.0.0
	 */
	public const DOCTOR_SECTION = 'wpml';

	/**
	 * URL modes by the value of the WPML setting language_negotiation_type.
	 *
	 * @since 1.0.0
	 */
	public const URL_MODES = array(
		1 => 'directory',
		2 => 'domain',
		3 => 'parameter',
	);

	/**
	 * The URL mode the theme is tested with; other modes are reported as info.
	 *
	 * @since 1.0.0
	 */
	public const TESTED_URL_MODE = 'directory';

	/**
	 * Tells whether WPML is active and set up.
	 *
	 * True when ICL_SITEPRESS_VERSION is defined and the filter
	 * wpml_default_language gives a language code. During the WPML setup the
	 * default language is still missing, so the theme treats WPML as inactive.
	 * Reads no option and translates nothing.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when WPML runs with a default language.
	 */
	public static function is_active(): bool {
		if ( ! defined( self::VERSION_CONSTANT ) ) {
			return false;
		}
		$default_language = apply_filters( 'wpml_default_language', null );
		return is_string( $default_language ) && '' !== $default_language;
	}

	/**
	 * Adds the doctor section and, with WPML, the snapshot sync and the ACF Extended exclusion; runs on after_setup_theme (priority 20).
	 *
	 * Translates nothing. A second call adds nothing twice.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function boot(): void {
		if ( ! in_array( self::DOCTOR_SECTION, Doctor_Command::section_keys(), true ) ) {
			Doctor_Command::add_section( self::DOCTOR_SECTION, array( self::class, 'doctor_section' ) );
		}
		if ( ! self::is_active() ) {
			return;
		}
		if ( false === has_action( 'admin_init', array( Snapshot_Sync::class, 'on_admin_init' ) ) ) {
			add_action( 'admin_init', array( Snapshot_Sync::class, 'on_admin_init' ), 20, 0 );
		}
		if ( false === has_filter( Acf_Compat::EXCLUDE_FILTER, array( Acf_Compat::class, 'exclude_options' ) ) ) {
			add_filter( Acf_Compat::EXCLUDE_FILTER, array( Acf_Compat::class, 'exclude_options' ), 10, 1 );
		}
	}

	/**
	 * Collects the doctor section "wpml"; same as status().
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Values.
	 */
	public static function doctor_section(): array {
		return self::status();
	}

	/**
	 * Returns the WPML state of the site; reads only.
	 *
	 * Keys in this order: active, versions (wpml, st, acfml), default,
	 * languages (Snapshot_Sync::languages(), hidden languages included, so
	 * the list does not depend on the user), url_mode, config_file,
	 * acfe_multilang, snapshots (codes of these languages with a snapshot),
	 * with the module header-footer loaded parts (the template parts without a
	 * usable translation, Part_Translations::missing(); the doctor section
	 * header_footer warns about them), findings (id, level, message).
	 * Findings: wpml.st_missing, wpml.acfml_missing, wpml.snapshot_stale,
	 * wpml.acfe_multilang, wpml.config_missing (warnings) and
	 * wpml.url_mode_untested (info). Without WPML only active false, the
	 * versions, config_file and no finding. Call it after init; the messages
	 * are translated.
	 *
	 * @since 1.0.0
	 *
	 * @param Snapshot_Sync|null $sync        Sync asked for the languages and stale snapshots; the shared instance when null.
	 * @param string|null        $config_file Path of the theme's wpml-config.xml; the file in the theme folder when null.
	 * @return array{active: bool, versions: array{wpml: ?string, st: ?string, acfml: ?string}, default: ?string, languages: array<int, string>, url_mode: ?string, config_file: bool, acfe_multilang: bool, snapshots: array<int, string>, parts?: list<array{slug: string, lang: string, reason: string}>, findings: array<int, array{id: string, level: string, message: string}>} Status.
	 */
	public static function status( ?Snapshot_Sync $sync = null, ?string $config_file = null ): array {
		$active = self::is_active();
		$status = array(
			'active'         => $active,
			'versions'       => array(
				'wpml'  => self::version( self::VERSION_CONSTANT ),
				'st'    => self::version( self::ST_VERSION_CONSTANT ),
				'acfml' => self::version( self::ACFML_VERSION_CONSTANT ),
			),
			'default'        => null,
			'languages'      => array(),
			'url_mode'       => null,
			'config_file'    => is_file( $config_file ?? CREATIONELL_WP_THEME_DIR . '/wpml-config.xml' ),
			'acfe_multilang' => false,
			'snapshots'      => array(),
			'findings'       => array(),
		);
		if ( ! $active ) {
			return $status;
		}

		$sync                     = $sync ?? Snapshot_Sync::instance();
		$language                 = Language::instance();
		$status['default']        = $language->default();
		$status['languages']      = $sync->languages();
		$status['url_mode']       = self::url_mode();
		$status['acfe_multilang'] = $language->acfe_multilang_active();
		$status['snapshots']      = self::snapshots( $status['languages'] );
		if ( class_exists( Part_Translations::class, false ) ) {
			// The key parts stands before findings.
			unset( $status['findings'] );
			$status['parts']    = Part_Translations::missing();
			$status['findings'] = array();
		}

		$findings = array();
		if ( null === $status['versions']['st'] ) {
			$findings[] = self::finding( 'wpml.st_missing', 'warning', __( 'WPML String Translation is not active; corrections of theme texts and further languages need it.', 'creationell-wp-theme' ) );
		}
		if ( null === $status['versions']['acfml'] ) {
			$findings[] = self::finding( 'wpml.acfml_missing', 'warning', __( 'ACF Multilingual (ACFML) is not active; the translated settings cannot be translated per language.', 'creationell-wp-theme' ) );
		}
		$stale = $sync->maybe_sync( false );
		if ( array() !== $stale ) {
			/* translators: %s: comma-separated language codes. */
			$findings[] = self::finding( 'wpml.snapshot_stale', 'warning', sprintf( __( 'The settings snapshots do not match the languages %s; open any admin page to rebuild them.', 'creationell-wp-theme' ), implode( ', ', $stale ) ) );
		}
		$suffixed = Acf_Compat::suffixed_rows( $status['languages'] );
		if ( array() !== $suffixed ) {
			/* translators: %s: comma-separated option names. */
			$findings[] = self::finding( 'wpml.acfe_multilang', 'warning', sprintf( __( 'Global settings were saved per language (%s); the theme ignores these rows. Keep the global settings page out of the multilang module of ACF Extended and delete the rows.', 'creationell-wp-theme' ), implode( ', ', $suffixed ) ) );
		}
		if ( ! $status['config_file'] ) {
			$findings[] = self::finding( 'wpml.config_missing', 'warning', __( 'The theme has no wpml-config.xml; WPML does not know which block texts to translate.', 'creationell-wp-theme' ) );
		}
		if ( self::TESTED_URL_MODE !== $status['url_mode'] ) {
			/* translators: %s: WPML URL mode, e.g. "parameter". */
			$findings[] = self::finding( 'wpml.url_mode_untested', 'info', sprintf( __( 'WPML uses the URL mode "%s"; the theme is tested with language directories.', 'creationell-wp-theme' ), $status['url_mode'] ?? 'unknown' ) );
		}
		$status['findings'] = $findings;
		return $status;
	}

	/**
	 * Returns the URL mode of WPML.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null "directory", "domain" or "parameter"; null for another or no value.
	 */
	public static function url_mode(): ?string {
		$type = apply_filters( 'wpml_setting', null, 'language_negotiation_type' );
		if ( ! is_int( $type ) && ! ( is_string( $type ) && ctype_digit( $type ) ) ) {
			return null;
		}
		return self::URL_MODES[ (int) $type ] ?? null;
	}

	/**
	 * Returns the version a constant holds.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Constant name.
	 * @return string|null Version; null when the constant is undefined or holds no string.
	 */
	private static function version( string $name ): ?string {
		$version = defined( $name ) ? constant( $name ) : null;
		return is_string( $version ) && '' !== $version ? $version : null;
	}

	/**
	 * Returns the languages with a snapshot option.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $languages Language codes.
	 * @return array<int, string> Codes in the given order.
	 */
	private static function snapshots( array $languages ): array {
		$language = Language::instance();
		$snapshot = Snapshot::instance();
		$codes    = array();
		foreach ( $languages as $code ) {
			if ( null !== $snapshot->read( $language->secondary( $code ) ) ) {
				$codes[] = $code;
			}
		}
		return $codes;
	}

	/**
	 * Builds one finding.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id      Finding ID, e.g. "wpml.st_missing".
	 * @param string $level   "warning" or "info".
	 * @param string $message Translated message.
	 * @return array{id: string, level: string, message: string} Finding.
	 */
	private static function finding( string $id, string $level, string $message ): array {
		return array(
			'id'      => $id,
			'level'   => $level,
			'message' => $message,
		);
	}
}
