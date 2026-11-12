<?php
/**
 * Validator of the consent services.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Consent;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Checks and normalises the data of the consent services store.
 *
 * The check brings the data into the stored shape (version "v", list
 * "services", texts per language), drops unknown fields and reports every
 * broken rule as an error row. The store, the admin page, the import and
 * WP-CLI use it before a write; the store adds stored unknown fields back.
 *
 * Example:
 *
 *     $result = Consent_Services_Validator::check( $data, Consent_Categories::all() );
 *     if ( array() === $result['errors'] ) {
 *         $clean = $result['data'];
 *     }
 *
 * @since 1.0.0
 */
final class Consent_Services_Validator {

	/**
	 * Pattern of a service ID.
	 *
	 * @var string
	 */
	private const ID_PATTERN = '~^[a-z][a-z0-9-]*$~D';

	/**
	 * Pattern of a cookie key.
	 *
	 * @var string
	 */
	private const KEY_PATTERN = '~^c([1-9]\d{0,5})$~D';

	/**
	 * Pattern of a language code.
	 *
	 * @var string
	 */
	private const LANG_PATTERN = '~^[a-z]{2,3}(?:-[a-z0-9]{2,8})*$~D';

	/**
	 * Pattern of a cookie name in the modes "exact" and "prefix": printable ASCII without space, ";", ",", "=", "{" and "}".
	 *
	 * @var string
	 */
	private const NAME_PATTERN = '~^[^\x00-\x20\x7F-\xFF;,={}]+$~D';

	/**
	 * Longest service ID.
	 *
	 * @var int
	 */
	private const ID_MAX = 40;

	/**
	 * Service IDs no service may take: "new" names the form of a new service on the admin page.
	 *
	 * @var list<string>
	 */
	private const RESERVED_IDS = array( 'new' );

	/**
	 * Most services in the store.
	 *
	 * @var int
	 */
	private const SERVICES_MAX = 60;

	/**
	 * Most cookies per service.
	 *
	 * @var int
	 */
	private const COOKIES_MAX = 40;

	/**
	 * Longest values per field.
	 *
	 * @var array<string, int>
	 */
	private const LENGTHS = array(
		'name'                 => 120,
		'provider'             => 120,
		'privacy_url'          => 500,
		'cookie'               => 200,
		'service_purpose'      => 500,
		'cookie_purpose'       => 300,
		'duration'             => 60,
		'category_title'       => 120,
		'category_description' => 2000,
	);

	/**
	 * Tags and attributes of a category description; equal to Modules\Consent\Texts::ALLOWED_HTML, which loads only with the module.
	 *
	 * @var array<string, array<string, bool>>
	 */
	private const ALLOWED_HTML = array(
		'a'      => array(
			'href'   => true,
			'target' => true,
			'rel'    => true,
		),
		'strong' => array(),
		'em'     => array(),
		'br'     => array(),
	);

	/**
	 * URL protocols of a category description; equal to Modules\Consent\Texts::ALLOWED_PROTOCOLS.
	 *
	 * @var list<string>
	 */
	private const ALLOWED_PROTOCOLS = array( 'http', 'https', 'mailto', 'tel' );

	/**
	 * Checks and normalises store data.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed>       $data       Store data from a form, an import, a filter or the option.
	 * @param array<int, string> $categories Known categories, from Consent_Categories::all().
	 * @return array{data: array{v: int, services: list<array{id: string, category: string, name: string, provider: string, privacy_url: string, preset: string, preset_checked: string, cookie_seq: int, cookies: list<array{key: string, name: string, match: string, first_party: bool, parent_domain: bool}>}>, texts: array<string, array<string, mixed>>}, errors: list<array{service: string, cookie: ?int, field: string, code: string, message: string}>} Normalised data and the errors.
	 */
	public static function check( array $data, array $categories ): array {
		$errors = array();
		$input  = is_array( $data['services'] ?? null ) ? array_values( $data['services'] ) : array();
		if ( count( $input ) > self::SERVICES_MAX ) {
			$errors[] = self::error( '', null, 'services', 'limit', self::limit_message( self::SERVICES_MAX ) );
		}

		$services = array();
		$ids      = array();
		$displays = array();
		foreach ( $input as $raw ) {
			$service    = self::service( is_array( $raw ) ? $raw : array(), $categories, $ids, $displays, $errors );
			$services[] = $service;
			if ( '' !== $service['id'] ) {
				$ids[] = $service['id'];
			}
		}

		$normalised = array(
			'v'        => Consent_Services_Store::VERSION,
			'services' => $services,
			'texts'    => self::texts( is_array( $data['texts'] ?? null ) ? $data['texts'] : array(), $services, $categories, $errors ),
		);

		if ( strlen( (string) wp_json_encode( $normalised ) ) > Consent_Services_Store::MAX_BYTES ) {
			$errors[] = self::error(
				'',
				null,
				'services',
				'size',
				sprintf(
					/* translators: %d: size limit in kilobytes. */
					__( 'The list of services is too large; it may hold at most %d KB.', 'creationell-wp-theme' ),
					(int) ( Consent_Services_Store::MAX_BYTES / 1024 )
				)
			);
		}

		return array(
			'data'   => $normalised,
			'errors' => $errors,
		);
	}

	/**
	 * Builds a service ID from a name.
	 *
	 * @since 1.0.0
	 *
	 * @param string             $name  Service name.
	 * @param array<int, string> $taken IDs already in use.
	 * @return string ID of at most 40 characters: the slug of the name, with "-2", "-3" … on a collision or for a reserved ID such as "new", or "service-N" for a name without Latin letters.
	 */
	public static function slug( string $name, array $taken ): string {
		$taken = array_merge( array_values( $taken ), self::RESERVED_IDS );
		$slug  = (string) preg_replace( '~-+~', '-', str_replace( '_', '-', sanitize_title( $name ) ) );
		$slug  = trim( substr( $slug, 0, self::ID_MAX ), '-' );
		if ( 1 !== preg_match( self::ID_PATTERN, $slug ) ) {
			$n = 1;
			while ( in_array( 'service-' . $n, $taken, true ) ) {
				++$n;
			}
			return 'service-' . $n;
		}
		if ( ! in_array( $slug, $taken, true ) ) {
			return $slug;
		}
		$n = 2;
		do {
			$suffix    = '-' . $n;
			$candidate = rtrim( substr( $slug, 0, self::ID_MAX - strlen( $suffix ) ), '-' ) . $suffix;
			++$n;
		} while ( in_array( $candidate, $taken, true ) );
		return $candidate;
	}

	/**
	 * Returns the next cookie key of a service.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $service Service with "cookie_seq" and "cookies".
	 * @return string "c<N>" with N one above the highest key ever used.
	 */
	public static function next_cookie_key( array $service ): string {
		return 'c' . ( self::highest_key( $service ) + 1 );
	}

	/**
	 * Returns the highest cookie key number of a service: its "cookie_seq" or the highest key of its cookies.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $service Service.
	 * @return int Highest number, 0 without keys.
	 */
	private static function highest_key( array $service ): int {
		$highest = is_int( $service['cookie_seq'] ?? null ) ? max( 0, $service['cookie_seq'] ) : 0;
		$cookies = is_array( $service['cookies'] ?? null ) ? $service['cookies'] : array();
		foreach ( $cookies as $cookie ) {
			$number = is_array( $cookie ) ? self::key_number( $cookie['key'] ?? null ) : null;
			if ( null !== $number ) {
				$highest = max( $highest, $number );
			}
		}
		return $highest;
	}

	/**
	 * Returns the number of a cookie key.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $key Key.
	 * @return int|null Number, or null for no valid key.
	 */
	private static function key_number( mixed $key ): ?int {
		return is_string( $key ) && 1 === preg_match( self::KEY_PATTERN, $key, $matches ) ? (int) $matches[1] : null;
	}

	/**
	 * Checks and normalises one service.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed>                                                                             $raw        Service.
	 * @param array<int, string>                                                                       $categories Known categories.
	 * @param array<int, string>                                                                       $ids        IDs of the services before.
	 * @param array<string, true>                                                                      $displays   Display names of the cookies before; extended.
	 * @param list<array{service: string, cookie: ?int, field: string, code: string, message: string}> $errors     Errors; extended.
	 * @return array{id: string, category: string, name: string, provider: string, privacy_url: string, preset: string, preset_checked: string, cookie_seq: int, cookies: list<array{key: string, name: string, match: string, first_party: bool, parent_domain: bool}>} Service.
	 */
	private static function service( array $raw, array $categories, array $ids, array &$displays, array &$errors ): array {
		$id       = self::string( $raw['id'] ?? null );
		$category = self::string( $raw['category'] ?? null );
		$name_ok  = self::is_utf8( $raw['name'] ?? null );
		$prov_ok  = self::is_utf8( $raw['provider'] ?? null );
		$url_ok   = self::is_utf8( $raw['privacy_url'] ?? null );
		$name     = $name_ok ? sanitize_text_field( self::string( $raw['name'] ?? null ) ) : '';
		$provider = $prov_ok ? sanitize_text_field( self::string( $raw['provider'] ?? null ) ) : '';
		$url      = $url_ok ? trim( self::string( $raw['privacy_url'] ?? null ) ) : '';
		$preset   = self::string( $raw['preset'] ?? null );
		$checked  = self::string( $raw['preset_checked'] ?? null );

		$code = self::id_code( $id );
		if ( null === $code && in_array( $id, $ids, true ) ) {
			$code = 'duplicate';
		}
		if ( null !== $code ) {
			$errors[] = self::error( $id, null, 'id', $code, self::message( $code, self::ID_MAX ) );
		}

		if ( '' === $category ) {
			$errors[] = self::error( $id, null, 'category', 'required', self::message( 'required' ) );
		} elseif ( ! in_array( $category, $categories, true ) ) {
			$errors[] = self::error( $id, null, 'category', 'unknown_category', self::message( 'unknown_category' ) );
		}

		if ( ! $name_ok ) {
			$errors[] = self::error( $id, null, 'name', 'encoding', self::message( 'encoding' ) );
		} elseif ( '' === $name ) {
			$errors[] = self::error( $id, null, 'name', 'required', self::message( 'required' ) );
		} elseif ( mb_strlen( $name ) > self::LENGTHS['name'] ) {
			$errors[] = self::error( $id, null, 'name', 'too_long', self::message( 'too_long', self::LENGTHS['name'] ) );
		}

		if ( ! $prov_ok ) {
			$errors[] = self::error( $id, null, 'provider', 'encoding', self::message( 'encoding' ) );
		} elseif ( mb_strlen( $provider ) > self::LENGTHS['provider'] ) {
			$errors[] = self::error( $id, null, 'provider', 'too_long', self::message( 'too_long', self::LENGTHS['provider'] ) );
		}

		if ( ! $url_ok ) {
			$errors[] = self::error( $id, null, 'privacy_url', 'encoding', self::message( 'encoding' ) );
		} elseif ( '' !== $url ) {
			$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
			$host   = wp_parse_url( $url, PHP_URL_HOST );
			$clean  = is_string( $scheme ) && in_array( strtolower( $scheme ), array( 'http', 'https' ), true ) && is_string( $host ) && '' !== $host ? esc_url_raw( $url ) : '';
			if ( '' === $clean ) {
				$errors[] = self::error( $id, null, 'privacy_url', 'url', self::message( 'url' ) );
			} else {
				$url = $clean;
				if ( mb_strlen( $url ) > self::LENGTHS['privacy_url'] ) {
					$errors[] = self::error( $id, null, 'privacy_url', 'too_long', self::message( 'too_long', self::LENGTHS['privacy_url'] ) );
				}
			}
		}

		if ( '' !== $preset && null !== self::id_code( $preset ) ) {
			$errors[] = self::error( $id, null, 'preset', 'pattern', self::message( 'pattern' ) );
		}

		if ( '' !== $checked && 1 !== preg_match( '~^\d{4}-\d{2}-\d{2}$~D', $checked ) ) {
			$errors[] = self::error( $id, null, 'preset_checked', 'pattern', self::message( 'date' ) );
		}

		$input = is_array( $raw['cookies'] ?? null ) ? array_values( $raw['cookies'] ) : array();
		if ( count( $input ) > self::COOKIES_MAX ) {
			$errors[] = self::error( $id, null, 'cookies', 'limit', self::limit_message( self::COOKIES_MAX ) );
		}

		$cookies = array();
		$keys    = array();
		foreach ( $input as $index => $cookie ) {
			$cookies[] = self::cookie( is_array( $cookie ) ? $cookie : array(), $id, $category, $index, $keys, $displays, $errors );
		}

		// Keys for new cookies count up from the highest key ever used.
		$seq = self::highest_key(
			array(
				'cookie_seq' => $raw['cookie_seq'] ?? null,
				'cookies'    => $cookies,
			)
		);
		foreach ( $cookies as $index => $cookie ) {
			if ( '' === $cookie['key'] ) {
				++$seq;
				$cookies[ $index ]['key'] = 'c' . $seq;
			}
		}

		return array(
			'id'             => $id,
			'category'       => $category,
			'name'           => $name,
			'provider'       => $provider,
			'privacy_url'    => $url,
			'preset'         => $preset,
			'preset_checked' => $checked,
			'cookie_seq'     => $seq,
			'cookies'        => $cookies,
		);
	}

	/**
	 * Checks and normalises one cookie.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed>                                                                             $raw      Cookie.
	 * @param string                                                                                   $service  Service ID.
	 * @param string                                                                                   $category Category of the service.
	 * @param int                                                                                      $index    Position in the cookie list.
	 * @param array<string, true>                                                                      $keys     Keys of the cookies before in this service; extended.
	 * @param array<string, true>                                                                      $displays Display names of the cookies before; extended.
	 * @param list<array{service: string, cookie: ?int, field: string, code: string, message: string}> $errors   Errors; extended.
	 * @return array{key: string, name: string, match: string, first_party: bool, parent_domain: bool} Cookie; key empty for a new cookie.
	 */
	private static function cookie( array $raw, string $service, string $category, int $index, array &$keys, array &$displays, array &$errors ): array {
		$key   = self::string( $raw['key'] ?? null );
		$name  = trim( self::string( $raw['name'] ?? null ) );
		$match = array_key_exists( 'match', $raw ) ? self::string( $raw['match'] ) : 'exact';
		$first = self::flag( $raw['first_party'] ?? null );

		if ( '' !== $key ) {
			if ( null === self::key_number( $key ) ) {
				$errors[] = self::error( $service, $index, 'key', 'pattern', self::message( 'pattern' ) );
			} elseif ( isset( $keys[ $key ] ) ) {
				$errors[] = self::error( $service, $index, 'key', 'duplicate', self::message( 'duplicate' ) );
			}
			$keys[ $key ] = true;
		}

		$name_code = null;
		if ( '' === $name ) {
			$name_code = 'required';
		} elseif ( mb_strlen( $name ) > self::LENGTHS['cookie'] ) {
			$name_code = 'too_long';
		}

		if ( ! in_array( $match, Consent_Cookie_Name::MATCHES, true ) ) {
			$errors[] = self::error( $service, $index, 'match', 'pattern', self::message( 'match' ) );
		} elseif ( null === $name_code ) {
			if ( 'regex' === $match ) {
				$name_code = Consent_Cookie_Name::is_safe_regex( $name ) ? null : 'unsafe_regex';
			} elseif ( 1 !== preg_match( self::NAME_PATTERN, $name ) || ( 'prefix' === $match && strlen( $name ) < 2 ) ) {
				$name_code = 'pattern';
			}
			if ( null === $name_code && Consent_Gate::NECESSARY !== $category && Consent_Cookie_Name::hits_denylist( $name, $match ) ) {
				$name_code = 'denylist';
			}
			if ( null === $name_code ) {
				$display = Consent_Cookie_Name::display( $name, $match );
				if ( isset( $displays[ $display ] ) ) {
					$name_code = 'duplicate';
				}
				$displays[ $display ] = true;
			}
		}
		if ( null !== $name_code ) {
			$errors[] = self::error( $service, $index, 'name', $name_code, self::message( $name_code, self::LENGTHS['cookie'] ) );
		}

		return array(
			'key'           => $key,
			'name'          => $name,
			'match'         => $match,
			'first_party'   => $first,
			'parent_domain' => $first && self::flag( $raw['parent_domain'] ?? null ),
		);
	}

	/**
	 * Checks and normalises the texts; texts without a target, in an invalid language or empty fall away.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed>                                                                                                                                                                                                                                                    $raw        Texts by language.
	 * @param list<array{id: string, category: string, name: string, provider: string, privacy_url: string, preset: string, preset_checked: string, cookie_seq: int, cookies: list<array{key: string, name: string, match: string, first_party: bool, parent_domain: bool}>}> $services   Normalised services.
	 * @param array<int, string>                                                                                                                                                                                                                                              $categories Known categories.
	 * @param list<array{service: string, cookie: ?int, field: string, code: string, message: string}>                                                                                                                                                                        $errors     Errors; extended.
	 * @return array<string, array<string, mixed>> Texts by language.
	 */
	private static function texts( array $raw, array $services, array $categories, array &$errors ): array {
		// Cookie positions by service ID and cookie key.
		$targets = array();
		foreach ( $services as $service ) {
			if ( '' === $service['id'] || isset( $targets[ $service['id'] ] ) ) {
				continue;
			}
			$targets[ $service['id'] ] = array();
			foreach ( $service['cookies'] as $index => $cookie ) {
				if ( ! isset( $targets[ $service['id'] ][ $cookie['key'] ] ) ) {
					$targets[ $service['id'] ][ $cookie['key'] ] = $index;
				}
			}
		}

		$texts = array();
		foreach ( $raw as $lang => $entry ) {
			if ( ! is_string( $lang ) || 1 !== preg_match( self::LANG_PATTERN, $lang ) || ! is_array( $entry ) ) {
				continue;
			}
			$field = 'texts.' . $lang . '.';
			$out   = array();

			$category_texts = is_array( $entry['categories'] ?? null ) ? $entry['categories'] : array();
			foreach ( $category_texts as $slug => $text ) {
				if ( ! is_string( $slug ) || ! in_array( $slug, $categories, true ) || ! is_array( $text ) ) {
					continue;
				}
				$row   = 'category:' . $slug;
				$clean = array();
				$title = self::text( $text['title'] ?? null, self::LENGTHS['category_title'], $row, null, $field . 'title', $errors );
				if ( '' !== $title ) {
					$clean['title'] = $title;
				}
				$description = '';
				if ( ! self::is_utf8( $text['description'] ?? null ) ) {
					$errors[] = self::error( $row, null, $field . 'description', 'encoding', self::message( 'encoding' ) );
				} else {
					$description = trim( wp_kses( self::string( $text['description'] ?? null ), self::ALLOWED_HTML, self::ALLOWED_PROTOCOLS ) );
				}
				if ( '' !== $description ) {
					$clean['description'] = $description;
					if ( mb_strlen( $description ) > self::LENGTHS['category_description'] ) {
						$errors[] = self::error( $row, null, $field . 'description', 'too_long', self::message( 'too_long', self::LENGTHS['category_description'] ) );
					}
				}
				if ( array() !== $clean ) {
					$out['categories'][ $slug ] = $clean;
				}
			}

			$service_texts = is_array( $entry['services'] ?? null ) ? $entry['services'] : array();
			foreach ( $service_texts as $id => $text ) {
				if ( ! is_string( $id ) || ! isset( $targets[ $id ] ) || ! is_array( $text ) ) {
					continue;
				}
				$clean   = array();
				$purpose = self::text( $text['purpose'] ?? null, self::LENGTHS['service_purpose'], $id, null, $field . 'purpose', $errors );
				if ( '' !== $purpose ) {
					$clean['purpose'] = $purpose;
				}
				$cookie_texts = is_array( $text['cookies'] ?? null ) ? $text['cookies'] : array();
				foreach ( $cookie_texts as $key => $cookie_text ) {
					if ( ! is_string( $key ) || ! isset( $targets[ $id ][ $key ] ) || ! is_array( $cookie_text ) ) {
						continue;
					}
					$index        = $targets[ $id ][ $key ];
					$clean_cookie = array();
					$purpose      = self::text( $cookie_text['purpose'] ?? null, self::LENGTHS['cookie_purpose'], $id, $index, $field . 'purpose', $errors );
					if ( '' !== $purpose ) {
						$clean_cookie['purpose'] = $purpose;
					}
					$duration = self::text( $cookie_text['duration'] ?? null, self::LENGTHS['duration'], $id, $index, $field . 'duration', $errors );
					if ( '' !== $duration ) {
						$clean_cookie['duration'] = $duration;
					}
					if ( array() !== $clean_cookie ) {
						$clean['cookies'][ $key ] = $clean_cookie;
					}
				}
				if ( array() !== $clean ) {
					$out['services'][ $id ] = $clean;
				}
			}

			if ( array() !== $out ) {
				$texts[ $lang ] = $out;
			}
		}
		return $texts;
	}

	/**
	 * Cleans a plain text with sanitize_text_field() and reports a text over its limit or with invalid UTF-8.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                                                                                    $value   Value.
	 * @param int                                                                                      $max     Longest length.
	 * @param string                                                                                   $service Service column of the error row.
	 * @param int|null                                                                                 $cookie  Cookie column of the error row.
	 * @param string                                                                                   $field   Field column of the error row.
	 * @param list<array{service: string, cookie: ?int, field: string, code: string, message: string}> $errors  Errors; extended.
	 * @return string Clean text.
	 */
	private static function text( mixed $value, int $max, string $service, ?int $cookie, string $field, array &$errors ): string {
		if ( ! self::is_utf8( $value ) ) {
			$errors[] = self::error( $service, $cookie, $field, 'encoding', self::message( 'encoding' ) );
			return '';
		}
		$text = sanitize_text_field( self::string( $value ) );
		if ( mb_strlen( $text ) > $max ) {
			$errors[] = self::error( $service, $cookie, $field, 'too_long', self::message( 'too_long', $max ) );
		}
		return $text;
	}

	/**
	 * Returns the error code of a service ID, or null for a valid one.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id ID.
	 * @return string|null "required", "too_long", "pattern" (also for a reserved ID) or null.
	 */
	private static function id_code( string $id ): ?string {
		if ( '' === $id ) {
			return 'required';
		}
		if ( mb_strlen( $id ) > self::ID_MAX ) {
			return 'too_long';
		}
		return 1 === preg_match( self::ID_PATTERN, $id ) && ! in_array( $id, self::RESERVED_IDS, true ) ? null : 'pattern';
	}

	/**
	 * Returns a string value; other types give an empty string, numbers their digits.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return string String.
	 */
	private static function string( mixed $value ): string {
		return is_string( $value ) || is_int( $value ) || is_float( $value ) ? (string) $value : '';
	}

	/**
	 * Tells whether a value is free of invalid UTF-8; other types than strings count as valid.
	 *
	 * Invalid bytes would pass esc_url_raw() and wp_kses() and later break the JSON of an export or a backup.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return bool True when the value is no string or valid UTF-8.
	 */
	private static function is_utf8( mixed $value ): bool {
		return ! is_string( $value ) || mb_check_encoding( $value, 'UTF-8' );
	}

	/**
	 * Reads a flag: true for true, 1 and "1".
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return bool Flag.
	 */
	private static function flag( mixed $value ): bool {
		return true === $value || 1 === $value || '1' === $value;
	}

	/**
	 * Builds an error row.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $service Service ID, "category:<slug>" for category texts, empty when missing.
	 * @param int|null $cookie  Position of the cookie in the service, null for other fields.
	 * @param string   $field   Field.
	 * @param string   $code    Code.
	 * @param string   $message Translated message.
	 * @return array{service: string, cookie: ?int, field: string, code: string, message: string} Error row.
	 */
	private static function error( string $service, ?int $cookie, string $field, string $code, string $message ): array {
		return array(
			'service' => $service,
			'cookie'  => $cookie,
			'field'   => $field,
			'code'    => $code,
			'message' => $message,
		);
	}

	/**
	 * Returns the message of an error code.
	 *
	 * @since 1.0.0
	 *
	 * @param string $code Code; "date" and "match" are the pattern messages of the check date and the match mode; "encoding" (invalid UTF-8) takes the general format message.
	 * @param int    $max  Longest length for "too_long".
	 * @return string Translated message.
	 */
	private static function message( string $code, int $max = 0 ): string {
		switch ( $code ) {
			case 'required':
				return __( 'Fill in this field.', 'creationell-wp-theme' );
			case 'too_long':
				/* translators: %d: maximum number of characters. */
				return sprintf( __( 'Use at most %d characters.', 'creationell-wp-theme' ), $max );
			case 'duplicate':
				return __( 'This value is already in use.', 'creationell-wp-theme' );
			case 'unknown_category':
				return __( 'This consent category does not exist.', 'creationell-wp-theme' );
			case 'unsafe_regex':
				return __( 'Start each alternative with ^ and two letters; use only letters, digits, _, -, \., \d, ., [ ], one level of ( ) without a quantifier after it, |, $, and per alternative at most one group with |, two ? and one * or +.', 'creationell-wp-theme' );
			case 'denylist':
				return __( 'This cookie belongs to WordPress, the consent banner, the shop or WPML and must not be deleted.', 'creationell-wp-theme' );
			case 'url':
				return __( 'Enter an address that starts with http:// or https://.', 'creationell-wp-theme' );
			case 'date':
				return __( 'Enter the date as YYYY-MM-DD.', 'creationell-wp-theme' );
			case 'match':
				return __( 'Choose exact, prefix or regex.', 'creationell-wp-theme' );
			default:
				return __( 'This value has an invalid format.', 'creationell-wp-theme' );
		}
	}

	/**
	 * Returns the message of a count limit.
	 *
	 * @since 1.0.0
	 *
	 * @param int $max Most entries.
	 * @return string Translated message.
	 */
	private static function limit_message( int $max ): string {
		/* translators: %d: maximum number of entries. */
		return sprintf( __( 'Use at most %d entries.', 'creationell-wp-theme' ), $max );
	}
}
