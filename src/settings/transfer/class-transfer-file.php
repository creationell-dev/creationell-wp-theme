<?php
/**
 * Parsed settings transfer file.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

use DateTimeImmutable;
use DateTimeZone;
use stdClass;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Holds a transfer file of the current schema after a strict check of every field.
 *
 * A file is a JSON object with a header and one member per listed section:
 *
 *     {
 *         "format": "creationell-wp-theme-settings", "schema_version": 1,
 *         "theme_version": "1.0.0", "bootstrap_line": 5, "child": null,
 *         "exported_at": "2026-09-25T10:00:00Z", "source": "https://example.test",
 *         "wp_version": "7.1", "languages": ["de", "en"], "default_language": "de",
 *         "sections": ["settings", "modules"], "omitted_sensitive": [],
 *         "settings": {"values": {"color_primary": "#6f2da8"},
 *                      "translatable": {"de": {"footer_text": "..."}}},
 *         "modules": {"module_consent": "active", "module_post_lists": null}
 *     }
 *
 * Values are scalars, null (no deviation from the default) or references
 * (Reference_Codec). Sections the theme does not know stay unchecked in
 * $other_sections, so the import can report them. from_array() is the only way
 * to build a file; it throws schema_invalid with the JSON path of the first
 * broken field.
 *
 * @since 1.0.0
 */
final class Transfer_File {

	/**
	 * Value of the format field.
	 *
	 * @since 1.0.0
	 */
	public const FORMAT = 'creationell-wp-theme-settings';

	/**
	 * Section with the setting values.
	 *
	 * @since 1.0.0
	 */
	public const SECTION_SETTINGS = 'settings';

	/**
	 * Section with the module states.
	 *
	 * @since 1.0.0
	 */
	public const SECTION_MODULES = 'modules';

	/**
	 * Header fields in file order.
	 *
	 * @since 1.0.0
	 */
	public const HEADER = array( 'format', 'schema_version', 'theme_version', 'bootstrap_line', 'child', 'exported_at', 'source', 'wp_version', 'languages', 'default_language', 'sections', 'omitted_sensitive' );

	/**
	 * Bootstrap lines a file may name.
	 *
	 * @since 1.0.0
	 */
	public const LINES = array( 5, 6 );

	/**
	 * Language code: WPML code such as "de", "pt-br" or "zh-hans".
	 *
	 * @since 1.0.0
	 */
	public const LANGUAGE_PATTERN = '~^[a-z]{2,3}(?:-[a-z0-9]+)?$~D';

	/**
	 * Setting key, as in Definition.
	 *
	 * @since 1.0.0
	 */
	public const KEY_PATTERN = '~^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$~D';

	/**
	 * Key of a module switch.
	 *
	 * @since 1.0.0
	 */
	public const MODULE_KEY_PATTERN = '~^module_[a-z0-9]+(?:_[a-z0-9]+)*$~D';

	/**
	 * Module state such as "active", "hidden" or "off".
	 *
	 * @since 1.0.0
	 */
	public const MODULE_STATE_PATTERN = '~^[a-z]+(?:_[a-z]+)*$~D';

	/**
	 * Section name.
	 *
	 * @since 1.0.0
	 */
	public const SECTION_PATTERN = '~^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$~D';

	/**
	 * Theme version X.Y.Z with an optional pre-release or build part.
	 *
	 * @since 1.0.0
	 */
	public const VERSION_PATTERN = '~^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$~D';

	/**
	 * WordPress version such as "7.1", "7.1.2" or "7.2-beta1".
	 *
	 * @since 1.0.0
	 */
	public const WP_VERSION_PATTERN = '~^\d+\.\d+(?:\.\d+)?(?:-[0-9A-Za-z.-]+)?$~D';

	/**
	 * Time of the export in UTC, with "Z" or "+00:00".
	 *
	 * @since 1.0.0
	 */
	public const TIME_PATTERN = '~^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})(?:Z|\+00:00)$~D';

	/**
	 * Sets the checked fields.
	 *
	 * @since 1.0.0
	 *
	 * @param int                                    $schema_version    Schema version, always Migrator::CURRENT.
	 * @param string                                 $theme_version     Theme version of the exporting site.
	 * @param int                                    $bootstrap_line    Bootstrap line of the exporting site.
	 * @param array{slug: string, name: string}|null $child          Active child theme of the exporting site.
	 * @param string                                 $exported_at       Time of the export in UTC (ISO 8601).
	 * @param string                                 $source            Home URL of the exporting site.
	 * @param string                                 $wp_version        WordPress version of the exporting site.
	 * @param array<int, string>                     $languages         Language codes of the file.
	 * @param string                                 $default_language  Default language of the exporting site.
	 * @param array<int, string>                     $sections          Sections of the file.
	 * @param array<int, string>                     $omitted_sensitive Sensitive keys left out.
	 * @param array<string, mixed>                   $values            Values of the settings that are not translatable.
	 * @param array<string, array<string, mixed>>    $translatable      Values of the translatable settings per language.
	 * @param array<string, string|null>             $modules           Module states per switch key.
	 * @param array<string, array<mixed>>            $other_sections    Unchecked content of unknown sections.
	 * @phpstan-param list<string> $languages
	 * @phpstan-param list<string> $sections
	 * @phpstan-param list<string> $omitted_sensitive
	 */
	private function __construct(
		public readonly int $schema_version,
		public readonly string $theme_version,
		public readonly int $bootstrap_line,
		public readonly ?array $child,
		public readonly string $exported_at,
		public readonly string $source,
		public readonly string $wp_version,
		public readonly array $languages,
		public readonly string $default_language,
		public readonly array $sections,
		public readonly array $omitted_sensitive,
		public readonly array $values,
		public readonly array $translatable,
		public readonly array $modules,
		public readonly array $other_sections,
	) {
	}

	/**
	 * Checks a decoded and migrated file and builds the object.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $data File as associative array.
	 * @return self File.
	 * @throws Transfer_Exception With wrong_format for another format name, otherwise schema_invalid with the JSON path in the context.
	 */
	public static function from_array( array $data ): self {
		if ( self::FORMAT !== ( $data['format'] ?? null ) ) {
			Transfer_Exception::raise( Transfer_Error::WRONG_FORMAT, array( 'path' => '$.format' ) );
		}
		foreach ( self::HEADER as $field ) {
			if ( ! array_key_exists( $field, $data ) ) {
				self::fail( '$.' . $field, 'missing' );
			}
		}
		if ( Migrator::CURRENT !== $data['schema_version'] ) {
			self::fail( '$.schema_version', 'expected ' . Migrator::CURRENT );
		}
		$languages = self::codes( $data['languages'], '$.languages', self::LANGUAGE_PATTERN, false );
		$default   = $data['default_language'];
		if ( ! is_string( $default ) || ! in_array( $default, $languages, true ) ) {
			self::fail( '$.default_language', 'expected one of the languages' );
		}
		$sections = self::codes( $data['sections'], '$.sections', self::SECTION_PATTERN, false );
		foreach ( array_keys( $data ) as $key ) {
			if ( ! in_array( $key, self::HEADER, true ) && ! in_array( $key, $sections, true ) ) {
				self::fail( '$.' . $key, 'not a header field or listed section' );
			}
		}
		$other = array();
		foreach ( $sections as $section ) {
			if ( ! array_key_exists( $section, $data ) ) {
				self::fail( '$.' . $section, 'listed section missing' );
			}
			if ( self::SECTION_SETTINGS !== $section && self::SECTION_MODULES !== $section ) {
				if ( ! is_array( $data[ $section ] ) ) {
					self::fail( '$.' . $section, 'expected an object or a list' );
				}
				$other[ $section ] = $data[ $section ];
			}
		}
		$settings = in_array( self::SECTION_SETTINGS, $sections, true ) ? self::settings( $data[ self::SECTION_SETTINGS ], $languages ) : array(
			'values'       => array(),
			'translatable' => array(),
		);

		return new self(
			schema_version: Migrator::CURRENT,
			theme_version: self::matching( $data['theme_version'], '$.theme_version', self::VERSION_PATTERN ),
			bootstrap_line: self::line( $data['bootstrap_line'] ),
			child: self::child( $data['child'] ),
			exported_at: self::time( $data['exported_at'] ),
			source: self::matching( $data['source'], '$.source', '~^https?://\S+$~iD' ),
			wp_version: self::matching( $data['wp_version'], '$.wp_version', self::WP_VERSION_PATTERN ),
			languages: $languages,
			default_language: $default,
			sections: $sections,
			omitted_sensitive: self::codes( $data['omitted_sensitive'], '$.omitted_sensitive', self::KEY_PATTERN, true ),
			values: $settings['values'],
			translatable: $settings['translatable'],
			modules: in_array( self::SECTION_MODULES, $sections, true ) ? self::modules( $data[ self::SECTION_MODULES ] ) : array(),
			other_sections: $other,
		);
	}

	/**
	 * Returns the file as array for Json_Codec::encode().
	 *
	 * Empty maps are stdClass, so they are written as "{}".
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> File.
	 */
	public function to_array(): array {
		$data = array(
			'format'            => self::FORMAT,
			'schema_version'    => $this->schema_version,
			'theme_version'     => $this->theme_version,
			'bootstrap_line'    => $this->bootstrap_line,
			'child'             => $this->child,
			'exported_at'       => $this->exported_at,
			'source'            => $this->source,
			'wp_version'        => $this->wp_version,
			'languages'         => $this->languages,
			'default_language'  => $this->default_language,
			'sections'          => $this->sections,
			'omitted_sensitive' => $this->omitted_sensitive,
		);
		if ( $this->has_section( self::SECTION_SETTINGS ) ) {
			$translatable = array();
			foreach ( $this->translatable as $lang => $values ) {
				$translatable[ $lang ] = self::map( $values );
			}
			$data[ self::SECTION_SETTINGS ] = array(
				'values'       => self::map( $this->values ),
				'translatable' => self::map( $translatable ),
			);
		}
		if ( $this->has_section( self::SECTION_MODULES ) ) {
			$data[ self::SECTION_MODULES ] = self::map( $this->modules );
		}
		foreach ( $this->other_sections as $section => $content ) {
			$data[ $section ] = array() === $content ? new stdClass() : $content;
		}
		return $data;
	}

	/**
	 * Tells whether the file lists a section.
	 *
	 * @since 1.0.0
	 *
	 * @param string $section Section name.
	 * @return bool True when listed.
	 */
	public function has_section( string $section ): bool {
		return in_array( $section, $this->sections, true );
	}

	/**
	 * Returns the download name of an export.
	 *
	 * The host is lower case; characters other than letters, digits, dots and
	 * dashes become dashes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $host      Host of the exporting site, e.g. from home_url().
	 * @param int    $timestamp Time of the export (Unix time, written in UTC).
	 * @return string Name "creationell-wp-theme-settings-<host>-<YYYYMMDD-HHMMSS>.json".
	 */
	public static function file_name( string $host, int $timestamp ): string {
		$host = preg_replace( '~[^a-z0-9.-]+~', '-', strtolower( $host ) );
		$host = trim( is_string( $host ) ? $host : '', '.-' );
		return self::FORMAT . '-' . ( '' === $host ? 'site' : $host ) . '-' . gmdate( 'Ymd-His', $timestamp ) . '.json';
	}

	/**
	 * Returns the module slug of a switch key: the rest after "module_" with dashes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Switch key, e.g. "module_contact_form_7".
	 * @return string Slug, e.g. "contact-form-7".
	 */
	public static function module_slug( string $key ): string {
		return str_replace( '_', '-', substr( $key, strlen( 'module_' ) ) );
	}

	/**
	 * Returns the switch key of a module slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Slug, e.g. "contact-form-7".
	 * @return string Switch key, e.g. "module_contact_form_7".
	 */
	public static function module_key( string $slug ): string {
		return 'module_' . str_replace( '-', '_', $slug );
	}

	/**
	 * Checks the settings section.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed              $settings  Section content.
	 * @param array<int, string> $languages Languages of the file.
	 * @return array{values: array<string, mixed>, translatable: array<string, array<string, mixed>>} Checked values.
	 * @throws Transfer_Exception With schema_invalid.
	 */
	private static function settings( mixed $settings, array $languages ): array {
		$settings = self::object( $settings, '$.settings' );
		foreach ( array( 'values', 'translatable' ) as $part ) {
			if ( ! array_key_exists( $part, $settings ) ) {
				self::fail( '$.settings.' . $part, 'missing' );
			}
		}
		foreach ( array_keys( $settings ) as $part ) {
			if ( 'values' !== $part && 'translatable' !== $part ) {
				self::fail( '$.settings.' . $part, 'unknown part' );
			}
		}
		$translatable = array();
		foreach ( self::object( $settings['translatable'], '$.settings.translatable' ) as $lang => $values ) {
			$path = '$.settings.translatable.' . $lang;
			if ( ! in_array( $lang, $languages, true ) ) {
				self::fail( $path, 'expected one of the languages' );
			}
			$translatable[ $lang ] = self::values( $values, $path );
		}
		return array(
			'values'       => self::values( $settings['values'], '$.settings.values' ),
			'translatable' => $translatable,
		);
	}

	/**
	 * Checks a map of setting values.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $values Map of key to value.
	 * @param string $path   JSON path of the map.
	 * @return array<string, mixed> Checked values.
	 * @throws Transfer_Exception With schema_invalid.
	 */
	private static function values( mixed $values, string $path ): array {
		$checked = array();
		foreach ( self::object( $values, $path ) as $key => $value ) {
			if ( 1 !== preg_match( self::KEY_PATTERN, $key ) ) {
				self::fail( $path . '.' . $key, 'invalid key' );
			}
			if ( ! is_scalar( $value ) && null !== $value && ! Reference_Codec::is_reference( $value ) ) {
				self::fail( $path . '.' . $key, 'expected a scalar, null or a reference' );
			}
			$checked[ $key ] = $value;
		}
		return $checked;
	}

	/**
	 * Checks the modules section.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $modules Section content.
	 * @return array<string, string|null> Module states per switch key.
	 * @throws Transfer_Exception With schema_invalid.
	 */
	private static function modules( mixed $modules ): array {
		$checked = array();
		foreach ( self::object( $modules, '$.modules' ) as $key => $state ) {
			if ( 1 !== preg_match( self::MODULE_KEY_PATTERN, $key ) ) {
				self::fail( '$.modules.' . $key, 'expected module_<slug>' );
			}
			if ( null !== $state && ( ! is_string( $state ) || 1 !== preg_match( self::MODULE_STATE_PATTERN, $state ) ) ) {
				self::fail( '$.modules.' . $key, 'expected a module state or null' );
			}
			$checked[ $key ] = $state;
		}
		return $checked;
	}

	/**
	 * Checks that a value is a JSON object whose keys are no integers; an empty array counts as empty object.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $value Value.
	 * @param string $path  JSON path.
	 * @return array<string, mixed> Object.
	 * @throws Transfer_Exception With schema_invalid.
	 */
	private static function object( mixed $value, string $path ): array {
		if ( ! is_array( $value ) || ( array() !== $value && array_is_list( $value ) ) ) {
			self::fail( $path, 'expected an object' );
		}
		$object = array();
		foreach ( $value as $key => $item ) {
			if ( ! is_string( $key ) ) {
				self::fail( $path . '.' . $key, 'invalid key' );
			}
			$object[ $key ] = $item;
		}
		return $object;
	}

	/**
	 * Checks a list of unique codes.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $value       Value.
	 * @param string $path        JSON path.
	 * @param string $pattern     Pattern of one code.
	 * @param bool   $allow_empty Whether the list may be empty.
	 * @return list<string> Codes.
	 * @throws Transfer_Exception With schema_invalid.
	 */
	private static function codes( mixed $value, string $path, string $pattern, bool $allow_empty ): array {
		if ( ! is_array( $value ) || ! array_is_list( $value ) || ( ! $allow_empty && array() === $value ) ) {
			self::fail( $path, $allow_empty ? 'expected a list' : 'expected a non-empty list' );
		}
		$codes = array();
		foreach ( $value as $index => $code ) {
			if ( ! is_string( $code ) || 1 !== preg_match( $pattern, $code ) ) {
				self::fail( $path . '[' . $index . ']', 'invalid code' );
			}
			if ( in_array( $code, $codes, true ) ) {
				self::fail( $path . '[' . $index . ']', 'duplicate' );
			}
			$codes[] = $code;
		}
		return $codes;
	}

	/**
	 * Checks a string against a pattern.
	 *
	 * All patterns of this class end with "$" and the D modifier, so a trailing newline does not match.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $value   Value.
	 * @param string $path    JSON path.
	 * @param string $pattern Pattern.
	 * @return string Value.
	 * @throws Transfer_Exception With schema_invalid.
	 */
	private static function matching( mixed $value, string $path, string $pattern ): string {
		if ( ! is_string( $value ) || 1 !== preg_match( $pattern, $value ) ) {
			self::fail( $path, 'invalid value' );
		}
		return $value;
	}

	/**
	 * Checks the Bootstrap line.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return int Line.
	 * @throws Transfer_Exception With schema_invalid.
	 */
	private static function line( mixed $value ): int {
		if ( ! is_int( $value ) || ! in_array( $value, self::LINES, true ) ) {
			self::fail( '$.bootstrap_line', 'expected 5 or 6' );
		}
		return $value;
	}

	/**
	 * Checks the child theme field.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return array{slug: string, name: string}|null Child theme.
	 * @throws Transfer_Exception With schema_invalid.
	 */
	private static function child( mixed $value ): ?array {
		if ( null === $value ) {
			return null;
		}
		$child = self::object( $value, '$.child' );
		foreach ( array_keys( $child ) as $field ) {
			if ( 'slug' !== $field && 'name' !== $field ) {
				self::fail( '$.child.' . $field, 'unknown field' );
			}
		}
		foreach ( array( 'slug', 'name' ) as $field ) {
			if ( ! isset( $child[ $field ] ) || ! is_string( $child[ $field ] ) || '' === $child[ $field ] ) {
				self::fail( '$.child.' . $field, 'expected a non-empty string' );
			}
		}
		return array(
			'slug' => $child['slug'],
			'name' => $child['name'],
		);
	}

	/**
	 * Checks the export time.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return string Time as written in the file.
	 * @throws Transfer_Exception With schema_invalid.
	 */
	private static function time( mixed $value ): string {
		if ( ! is_string( $value ) || 1 !== preg_match( self::TIME_PATTERN, $value, $matches ) ) {
			self::fail( '$.exported_at', 'expected an ISO 8601 time in UTC' );
		}
		$time = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i:s', $matches[1], new DateTimeZone( 'UTC' ) );
		if ( false === $time || $time->format( 'Y-m-d\TH:i:s' ) !== $matches[1] ) {
			self::fail( '$.exported_at', 'invalid date' );
		}
		return $value;
	}

	/**
	 * Throws schema_invalid for a JSON path.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path   JSON path.
	 * @param string $detail English detail.
	 * @return never
	 * @throws Transfer_Exception Always.
	 */
	private static function fail( string $path, string $detail ): never {
		Transfer_Exception::raise(
			Transfer_Error::SCHEMA_INVALID,
			array(
				'path'   => $path,
				'detail' => $detail,
			)
		);
	}

	/**
	 * Returns a map, or an stdClass when it is empty.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $map Map.
	 * @return array<string, mixed>|stdClass Map for the encoder.
	 */
	private static function map( array $map ): array|stdClass {
		return array() === $map ? new stdClass() : $map;
	}
}
