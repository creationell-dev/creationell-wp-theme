<?php
/**
 * Store of the consent services.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Consent;

use Creationell\WpTheme\Core\Capabilities;
use Creationell\WpTheme\Settings\Language;
use Creationell\WpTheme\Settings\Set_Result;
use Creationell\WpTheme\Settings\Set_Status;
use Creationell\WpTheme\Settings\Write_Context;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Keeps the services and cookies of the consent dialog in the option creationell_wp_theme_consent_services.
 *
 * The option holds the version "v", the list "services" (structure without
 * language) and "texts" per language code. save() is the only write path: it
 * checks the capability, validates everything with Consent_Services_Validator,
 * writes all or nothing and then fires creationell_wp_theme_consent_services_saved.
 * Unknown fields of the stored option survive a save.
 *
 * Example:
 *
 *     $result = Consent_Services_Store::save( $data, new Write_Context( 'admin', get_current_user_id() ) );
 *     if ( Set_Status::INVALID === $result->status ) {
 *         $errors = $result->data['errors'];
 *     }
 *
 * @since 1.0.0
 */
final class Consent_Services_Store {

	/**
	 * Option name.
	 *
	 * @since 1.0.0
	 */
	public const OPTION = 'creationell_wp_theme_consent_services';

	/**
	 * Version of the stored shape.
	 *
	 * @since 1.0.0
	 */
	public const VERSION = 1;

	/**
	 * Action after a save, with the stored data and the write context.
	 *
	 * @since 1.0.0
	 */
	public const ACTION_SAVED = 'creationell_wp_theme_consent_services_saved';

	/**
	 * Largest JSON size of the store in bytes.
	 *
	 * @since 1.0.0
	 */
	public const MAX_BYTES = 131072;

	/**
	 * JSON size in bytes from which the doctor warns.
	 *
	 * @since 1.0.0
	 */
	public const WARN_BYTES = 65536;

	/**
	 * Known top-level fields; others in the stored option are kept.
	 *
	 * @var list<string>
	 */
	private const TOP_FIELDS = array( 'v', 'services', 'texts' );

	/**
	 * Known service fields.
	 *
	 * @var list<string>
	 */
	private const SERVICE_FIELDS = array( 'id', 'category', 'name', 'provider', 'privacy_url', 'preset', 'preset_checked', 'cookie_seq', 'cookies' );

	/**
	 * Known cookie fields.
	 *
	 * @var list<string>
	 */
	private const COOKIE_FIELDS = array( 'key', 'name', 'match', 'first_party', 'parent_domain' );

	/**
	 * Returns the stored data; never writes.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Data with "v" (int), "services" (list) and "texts" (array) first, then unknown stored fields; the empty store for a missing or broken option.
	 */
	public static function read(): array {
		$stored = get_option( self::OPTION, null );
		$stored = is_array( $stored ) ? $stored : array();
		$data   = array(
			'v'        => is_int( $stored['v'] ?? null ) ? $stored['v'] : self::VERSION,
			'services' => is_array( $stored['services'] ?? null ) && array_is_list( $stored['services'] ) ? $stored['services'] : array(),
			'texts'    => is_array( $stored['texts'] ?? null ) ? $stored['texts'] : array(),
		);
		foreach ( $stored as $field => $value ) {
			if ( ! in_array( $field, self::TOP_FIELDS, true ) ) {
				$data[ $field ] = $value;
			}
		}
		return $data;
	}

	/**
	 * Checks and writes the whole store.
	 *
	 * Order: capability, language "all", validation, stored unknown fields
	 * back, unchanged, size of the data to write (INVALID with code "size"),
	 * write. A dry run writes and fires nothing.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed>  $data    Store data.
	 * @param Write_Context $context Writer, channel, language and dry run.
	 * @return Set_Result Result for the key OPTION; INVALID carries data.errors.
	 */
	public static function save( array $data, Write_Context $context ): Set_Result {
		if ( ! user_can( $context->user_id, Capabilities::MANAGE_CONSENT ) ) {
			return new Set_Result( self::OPTION, Set_Status::FORBIDDEN, __( 'You are not allowed to change the cookies and services.', 'creationell-wp-theme' ) );
		}
		if ( Language::ALL === $context->lang ) {
			return new Set_Result( self::OPTION, Set_Status::LANGUAGE_ALL_UNSUPPORTED, __( 'Choose one language; settings cannot be saved for all languages at once.', 'creationell-wp-theme' ) );
		}

		$stored  = self::read();
		$by_id   = self::stored_services( $stored );
		$data    = self::raise_sequences( $data, $by_id );
		$checked = Consent_Services_Validator::check( $data, Consent_Categories::all() );
		if ( array() !== $checked['errors'] ) {
			return new Set_Result(
				self::OPTION,
				Set_Status::INVALID,
				__( 'The cookies and services were not saved; correct the marked fields.', 'creationell-wp-theme' ),
				array( 'errors' => $checked['errors'] )
			);
		}

		$clean = self::keep_unknown( $checked['data'], $stored, $by_id );
		if ( $clean === $stored ) {
			return new Set_Result( self::OPTION, Set_Status::UNCHANGED );
		}
		// The kept unknown fields count towards the limit of the data actually written.
		if ( strlen( (string) wp_json_encode( $clean ) ) > self::MAX_BYTES ) {
			return new Set_Result(
				self::OPTION,
				Set_Status::INVALID,
				__( 'The cookies and services were not saved; correct the marked fields.', 'creationell-wp-theme' ),
				array(
					'errors' => array(
						array(
							'service' => '',
							'cookie'  => null,
							'field'   => 'services',
							'code'    => 'size',
							'message' => sprintf(
								/* translators: %d: size limit in kilobytes. */
								__( 'The list of services is too large; it may hold at most %d KB.', 'creationell-wp-theme' ),
								(int) ( self::MAX_BYTES / 1024 )
							),
						),
					),
				)
			);
		}
		if ( ! $context->dry_run ) {
			update_option( self::OPTION, $clean, null );
			/**
			 * Fires after the consent services were saved.
			 *
			 * @since 1.0.0
			 *
			 * @param array<string, mixed> $clean   Stored data.
			 * @param Write_Context        $context Write context.
			 */
			do_action( 'creationell_wp_theme_consent_services_saved', $clean, $context );
		}
		return new Set_Result( self::OPTION, Set_Status::SAVED );
	}

	/**
	 * Returns the services with their texts resolved for one language.
	 *
	 * Each text comes from the language, then from the default language, then is empty.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $data         Store data.
	 * @param string       $lang         Language code.
	 * @param string       $default_lang Default language code.
	 * @return list<array{id: string, category: string, name: string, provider: string, privacy_url: string, purpose: string, cookies: list<array{key: string, name: string, match: string, first_party: bool, parent_domain: bool, purpose: string, duration: string}>}> Services.
	 */
	public static function services_for( array $data, string $lang, string $default_lang ): array {
		$texts    = is_array( $data['texts'] ?? null ) ? $data['texts'] : array();
		$services = is_array( $data['services'] ?? null ) ? $data['services'] : array();
		$out      = array();
		foreach ( $services as $service ) {
			if ( ! is_array( $service ) ) {
				continue;
			}
			$id      = self::string( $service['id'] ?? null );
			$cookies = array();
			$list    = is_array( $service['cookies'] ?? null ) ? $service['cookies'] : array();
			foreach ( $list as $cookie ) {
				if ( ! is_array( $cookie ) ) {
					continue;
				}
				$key       = self::string( $cookie['key'] ?? null );
				$path      = array( 'services', $id, 'cookies', $key );
				$cookies[] = array(
					'key'           => $key,
					'name'          => self::string( $cookie['name'] ?? null ),
					'match'         => self::string( $cookie['match'] ?? null ),
					'first_party'   => true === ( $cookie['first_party'] ?? null ),
					'parent_domain' => true === ( $cookie['parent_domain'] ?? null ),
					'purpose'       => self::resolve( $texts, $lang, $default_lang, array_merge( $path, array( 'purpose' ) ) ),
					'duration'      => self::resolve( $texts, $lang, $default_lang, array_merge( $path, array( 'duration' ) ) ),
				);
			}
			$out[] = array(
				'id'          => $id,
				'category'    => self::string( $service['category'] ?? null ),
				'name'        => self::string( $service['name'] ?? null ),
				'provider'    => self::string( $service['provider'] ?? null ),
				'privacy_url' => self::string( $service['privacy_url'] ?? null ),
				'purpose'     => self::resolve( $texts, $lang, $default_lang, array( 'services', $id, 'purpose' ) ),
				'cookies'     => $cookies,
			);
		}
		return $out;
	}

	/**
	 * Returns the category texts of exactly one language, without fallback.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $data Store data.
	 * @param string       $lang Language code.
	 * @return array<string, array{title: string, description: string}> Texts by category; a missing text is empty.
	 */
	public static function category_texts( array $data, string $lang ): array {
		$categories = $data['texts'][ $lang ]['categories'] ?? null;
		$out        = array();
		if ( ! is_array( $categories ) ) {
			return $out;
		}
		foreach ( $categories as $slug => $text ) {
			if ( ! is_string( $slug ) || ! is_array( $text ) ) {
				continue;
			}
			$out[ $slug ] = array(
				'title'       => self::string( $text['title'] ?? null ),
				'description' => self::string( $text['description'] ?? null ),
			);
		}
		return $out;
	}

	/**
	 * Returns the stored services by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $stored Stored data.
	 * @return array<string, array<mixed>> Services.
	 */
	private static function stored_services( array $stored ): array {
		$by_id    = array();
		$services = is_array( $stored['services'] ) ? $stored['services'] : array();
		foreach ( $services as $service ) {
			if ( is_array( $service ) && is_string( $service['id'] ?? null ) && ! isset( $by_id[ $service['id'] ] ) ) {
				$by_id[ $service['id'] ] = $service;
			}
		}
		return $by_id;
	}

	/**
	 * Raises the cookie sequence of each incoming service to at least the stored one, so that no key is handed out twice.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed>                $data  Incoming data.
	 * @param array<string, array<mixed>> $by_id Stored services by ID.
	 * @return array<mixed> Data.
	 */
	private static function raise_sequences( array $data, array $by_id ): array {
		if ( ! is_array( $data['services'] ?? null ) ) {
			return $data;
		}
		foreach ( $data['services'] as $index => $service ) {
			if ( ! is_array( $service ) || ! is_string( $service['id'] ?? null ) || ! isset( $by_id[ $service['id'] ] ) ) {
				continue;
			}
			$stored                                   = is_int( $by_id[ $service['id'] ]['cookie_seq'] ?? null ) ? $by_id[ $service['id'] ]['cookie_seq'] : 0;
			$incoming                                 = is_int( $service['cookie_seq'] ?? null ) ? $service['cookie_seq'] : 0;
			$data['services'][ $index ]['cookie_seq'] = max( $stored, $incoming );
		}
		return $data;
	}

	/**
	 * Adds the unknown fields of the stored option back: on top, per service by ID and per cookie by key.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed>        $clean  Validated data.
	 * @param array<string, mixed>        $stored Stored data.
	 * @param array<string, array<mixed>> $by_id  Stored services by ID.
	 * @return array<string, mixed> Data to store.
	 * @phpstan-param array{v: int, services: list<array{id: string, category: string, name: string, provider: string, privacy_url: string, preset: string, preset_checked: string, cookie_seq: int, cookies: list<array{key: string, name: string, match: string, first_party: bool, parent_domain: bool}>}>, texts: array<string, array<string, mixed>>} $clean
	 */
	private static function keep_unknown( array $clean, array $stored, array $by_id ): array {
		$services = array();
		foreach ( $clean['services'] as $service ) {
			$old = $by_id[ $service['id'] ] ?? null;
			if ( null === $old ) {
				$services[] = $service;
				continue;
			}
			$old_cookies = array();
			$list        = is_array( $old['cookies'] ?? null ) ? $old['cookies'] : array();
			foreach ( $list as $cookie ) {
				if ( is_array( $cookie ) && is_string( $cookie['key'] ?? null ) && ! isset( $old_cookies[ $cookie['key'] ] ) ) {
					$old_cookies[ $cookie['key'] ] = $cookie;
				}
			}
			$cookies = array();
			foreach ( $service['cookies'] as $cookie ) {
				$cookies[] = isset( $old_cookies[ $cookie['key'] ] ) ? self::merge_unknown( $cookie, $old_cookies[ $cookie['key'] ], self::COOKIE_FIELDS ) : $cookie;
			}
			$service['cookies'] = $cookies;
			$services[]         = self::merge_unknown( $service, $old, self::SERVICE_FIELDS );
		}
		$clean['services'] = $services;
		return self::merge_unknown( $clean, $stored, self::TOP_FIELDS );
	}

	/**
	 * Appends the fields of a stored entry that are not known and not set.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed>       $entry  Validated entry.
	 * @param array<mixed>       $old    Stored entry.
	 * @param array<int, string> $fields Known fields.
	 * @return array<mixed> Entry.
	 */
	private static function merge_unknown( array $entry, array $old, array $fields ): array {
		foreach ( $old as $field => $value ) {
			if ( ! in_array( $field, $fields, true ) && ! array_key_exists( $field, $entry ) ) {
				$entry[ $field ] = $value;
			}
		}
		return $entry;
	}

	/**
	 * Returns the text at a path in the language, else in the default language, else an empty string.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed>       $texts        Texts by language.
	 * @param string             $lang         Language code.
	 * @param string             $default_lang Default language code.
	 * @param array<int, string> $path         Keys below the language.
	 * @return string Text.
	 */
	private static function resolve( array $texts, string $lang, string $default_lang, array $path ): string {
		foreach ( array( $lang, $default_lang ) as $code ) {
			$value = $texts[ $code ] ?? null;
			foreach ( $path as $key ) {
				$value = is_array( $value ) ? ( $value[ $key ] ?? null ) : null;
			}
			if ( is_string( $value ) && '' !== $value ) {
				return $value;
			}
		}
		return '';
	}

	/**
	 * Returns a string value, an empty string for other types.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return string String.
	 */
	private static function string( mixed $value ): string {
		return is_string( $value ) ? $value : '';
	}
}
