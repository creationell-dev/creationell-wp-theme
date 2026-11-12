<?php
/**
 * Catalog of ready-made consent services.
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
 * Offers vendor presets (Matomo, Google Analytics 4, YouTube, ...) that the admin page and the CLI apply to the services store.
 *
 * The built-in list lives in data/service-presets.php; the filter
 * creationell_wp_theme_consent_service_presets adds, replaces or removes
 * entries by ID. Every entry is checked like a one-service store; a broken
 * entry is dropped and reported by findings(). Nothing is cached: apply() must
 * see the texts and the filter output under each switched locale.
 *
 * Example:
 *
 *     $result = Consent_Service_Catalog::apply( 'matomo', new Write_Context( 'admin', get_current_user_id() ) );
 *
 * @since 1.0.0
 */
final class Consent_Service_Catalog {

	/**
	 * Filter over the presets, keyed by ID.
	 *
	 * @since 1.0.0
	 */
	public const FILTER = 'creationell_wp_theme_consent_service_presets';

	/**
	 * Longest note in characters.
	 *
	 * @var int
	 */
	private const NOTE_MAX = 500;

	/**
	 * Returns the valid presets.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Presets by ID with id, category, name, provider, privacy_url, checked, sources, note, purpose and cookies (name, match, first_party, parent_domain, purpose, duration).
	 */
	public static function all(): array {
		return self::build()['presets'];
	}

	/**
	 * Returns the reasons why presets were dropped and reports each with _doing_it_wrong().
	 *
	 * @since 1.0.0
	 *
	 * @return list<string> Findings in English.
	 */
	public static function findings(): array {
		$findings = self::build()['findings'];
		foreach ( $findings as $finding ) {
			_doing_it_wrong( __CLASS__ . '::all', esc_html( $finding ), '1.0.0' );
		}
		return $findings;
	}

	/**
	 * Adds a preset to the services store with its texts in every active language.
	 *
	 * @since 1.0.0
	 *
	 * @param string        $id      Preset ID.
	 * @param Write_Context $context Writer, channel, language and dry run.
	 * @return Set_Result Result of the store (SAVED, UNCHANGED, FORBIDDEN, INVALID, ...); INVALID with code "unknown_preset" or "duplicate" before the store is asked.
	 */
	public static function apply( string $id, Write_Context $context ): Set_Result {
		if ( ! user_can( $context->user_id, Capabilities::MANAGE_CONSENT ) ) {
			return new Set_Result( Consent_Services_Store::OPTION, Set_Status::FORBIDDEN, __( 'You are not allowed to change the cookies and services.', 'creationell-wp-theme' ) );
		}
		$preset = self::all()[ $id ] ?? null;
		if ( null === $preset ) {
			return self::refusal( $id, 'unknown_preset', __( 'This preset does not exist.', 'creationell-wp-theme' ) );
		}
		$store = Consent_Services_Store::read();
		foreach ( $store['services'] as $stored ) {
			if ( is_array( $stored ) && ( $stored['preset'] ?? null ) === $id ) {
				return self::refusal( $id, 'duplicate', __( 'This preset is already in the list of services.', 'creationell-wp-theme' ) );
			}
		}

		$taken = array();
		foreach ( $store['services'] as $stored ) {
			if ( is_array( $stored ) && is_string( $stored['id'] ?? null ) ) {
				$taken[] = $stored['id'];
			}
		}
		$sid     = Consent_Services_Validator::slug( $id, $taken );
		$cookies = array();
		foreach ( $preset['cookies'] as $index => $cookie ) {
			$cookies[] = array(
				'key'           => 'c' . ( $index + 1 ),
				'name'          => $cookie['name'],
				'match'         => $cookie['match'],
				'first_party'   => $cookie['first_party'],
				'parent_domain' => $cookie['parent_domain'],
			);
		}
		$store['services'][] = array(
			'id'             => $sid,
			'category'       => $preset['category'],
			'name'           => $preset['name'],
			'provider'       => $preset['provider'],
			'privacy_url'    => $preset['privacy_url'],
			'preset'         => $id,
			'preset_checked' => $preset['checked'],
			'cookie_seq'     => count( $cookies ),
			'cookies'        => $cookies,
		);

		$language = Language::instance();
		foreach ( $language->active() as $code ) {
			$switched = switch_to_locale( $language->locale( $code ) );
			try {
				$localised = self::build()['presets'][ $id ] ?? null;
			} finally {
				if ( $switched ) {
					restore_previous_locale();
				}
			}
			$texts = array( 'purpose' => (string) ( $localised['purpose'] ?? '' ) );
			foreach ( $cookies as $index => $cookie ) {
				$texts['cookies'][ $cookie['key'] ] = array(
					'purpose'  => (string) ( $localised['cookies'][ $index ]['purpose'] ?? '' ),
					'duration' => (string) ( $localised['cookies'][ $index ]['duration'] ?? '' ),
				);
			}
			$store['texts'][ $code ]['services'][ $sid ] = $texts;
		}

		return Consent_Services_Store::save( $store, $context );
	}

	/**
	 * Returns the checked date of the catalog when it is newer than the one of the service.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $service Stored service with "preset" and "preset_checked".
	 * @return string|null Date YYYY-MM-DD; null when the service has no preset, the preset is not in the catalog or the service is up to date.
	 */
	public static function newer( array $service ): ?string {
		$id = $service['preset'] ?? null;
		if ( ! is_string( $id ) || '' === $id ) {
			return null;
		}
		$preset = self::all()[ $id ] ?? null;
		if ( null === $preset ) {
			return null;
		}
		$checked = (string) $preset['checked'];
		$have    = $service['preset_checked'] ?? '';
		return strcmp( $checked, is_string( $have ) ? $have : '' ) > 0 ? $checked : null;
	}

	/**
	 * Builds the presets from the data file and the filter.
	 *
	 * @since 1.0.0
	 *
	 * @return array{presets: array<string, array<string, mixed>>, findings: list<string>} Valid presets by ID and the findings.
	 */
	private static function build(): array {
		$builtin = array();
		$loader  = require __DIR__ . '/data/service-presets.php';
		foreach ( $loader() as $entry ) {
			$builtin[ $entry['id'] ] = $entry;
		}

		$findings = array();
		/**
		 * Filters the consent service presets.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, array<string, mixed>> $presets Presets by ID.
		 */
		$list = apply_filters( 'creationell_wp_theme_consent_service_presets', $builtin );
		if ( ! is_array( $list ) ) {
			$findings[] = sprintf( 'The filter %s must return an array; it is ignored.', self::FILTER );
			$list       = $builtin;
		}

		$presets = array();
		foreach ( $list as $key => $entry ) {
			$id    = is_string( $key ) ? $key : ( is_array( $entry ) && is_string( $entry['id'] ?? null ) ? $entry['id'] : (string) $key );
			$clean = self::entry( $id, $entry );
			if ( is_string( $clean ) ) {
				$findings[] = $clean;
			} else {
				$presets[ $id ] = $clean;
			}
		}
		return array(
			'presets'  => $presets,
			'findings' => $findings,
		);
	}

	/**
	 * Checks one entry: its own fields first, then as a one-service store through the validator.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id    ID (the filter key).
	 * @param mixed  $entry Entry.
	 * @return array<string, mixed>|string Normalised preset; the finding (a string) when it is dropped.
	 */
	private static function entry( string $id, mixed $entry ): array|string {
		$fail = static fn( string $field, string $message ): string => sprintf( 'The preset "%s" of the filter %s %s: %s; it is ignored.', $id, self::FILTER, $field, $message );
		if ( ! is_array( $entry ) ) {
			return $fail( 'entry', 'is not an array' );
		}

		$checked = $entry['checked'] ?? null;
		$m       = array();
		if ( ! is_string( $checked ) || 1 !== preg_match( '~^(\d{4})-(\d{2})-(\d{2})$~D', $checked, $m ) || ! checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
			return $fail( 'checked', 'must be a date YYYY-MM-DD' );
		}

		$sources = $entry['sources'] ?? array();
		if ( ! is_array( $sources ) || ! array_is_list( $sources ) ) {
			return $fail( 'sources', 'must be a list of URLs' );
		}
		foreach ( $sources as $source ) {
			if ( ! is_string( $source ) || 1 !== preg_match( '~^https?://[^\s/]+~i', $source ) ) {
				return $fail( 'sources', 'must contain http or https URLs only' );
			}
		}

		$raw_note = $entry['note'] ?? '';
		$note     = is_string( $raw_note ) ? sanitize_text_field( $raw_note ) : '';
		if ( ! is_string( $raw_note ) || mb_strlen( $note ) > self::NOTE_MAX ) {
			return $fail( 'note', sprintf( 'must be a text of at most %d characters', self::NOTE_MAX ) );
		}

		$cookies = $entry['cookies'] ?? null;
		if ( ! is_array( $cookies ) || ! array_is_list( $cookies ) ) {
			return $fail( 'cookies', 'must be a list' );
		}

		$service_cookies = array();
		$cookie_texts    = array();
		foreach ( $cookies as $index => $cookie ) {
			$cookie               = is_array( $cookie ) ? $cookie : array();
			$key                  = 'c' . ( $index + 1 );
			$service_cookies[]    = array(
				'key'           => $key,
				'name'          => $cookie['name'] ?? '',
				'match'         => $cookie['match'] ?? 'exact',
				'first_party'   => $cookie['first_party'] ?? false,
				'parent_domain' => $cookie['parent_domain'] ?? false,
			);
			$cookie_texts[ $key ] = array(
				'purpose'  => $cookie['purpose'] ?? '',
				'duration' => $cookie['duration'] ?? '',
			);
		}
		$store         = array(
			'v'        => Consent_Services_Store::VERSION,
			'services' => array(
				array(
					'id'          => $id,
					'category'    => $entry['category'] ?? '',
					'name'        => $entry['name'] ?? '',
					'provider'    => $entry['provider'] ?? '',
					'privacy_url' => $entry['privacy_url'] ?? '',
					'cookies'     => $service_cookies,
				),
			),
			'texts'    => array(
				'en' => array(
					'services' => array(
						$id => array(
							'purpose' => $entry['purpose'] ?? '',
							'cookies' => $cookie_texts,
						),
					),
				),
			),
		);
		$checked_store = Consent_Services_Validator::check( $store, Consent_Categories::all() );
		if ( array() !== $checked_store['errors'] ) {
			$error = $checked_store['errors'][0];
			$field = ( null === $error['cookie'] ? $error['field'] : sprintf( 'cookie %d, %s', $error['cookie'] + 1, $error['field'] ) ) . ' (' . $error['code'] . ')';
			return $fail( $field, rtrim( $error['message'], '.' ) );
		}

		$service = $checked_store['data']['services'][0];
		$texts   = $checked_store['data']['texts']['en']['services'][ $service['id'] ] ?? array();
		$out     = array();
		foreach ( $service['cookies'] as $index => $cookie ) {
			$text  = $texts['cookies'][ $cookie['key'] ] ?? array();
			$out[] = array(
				'name'          => $cookie['name'],
				'match'         => $cookie['match'],
				'first_party'   => $cookie['first_party'],
				'parent_domain' => $cookie['parent_domain'],
				'purpose'       => (string) ( $text['purpose'] ?? '' ),
				'duration'      => (string) ( $text['duration'] ?? '' ),
			);
		}
		return array(
			'id'          => $service['id'],
			'category'    => $service['category'],
			'name'        => $service['name'],
			'provider'    => $service['provider'],
			'privacy_url' => $service['privacy_url'],
			'checked'     => $checked,
			'sources'     => $sources,
			'note'        => $note,
			'purpose'     => (string) ( $texts['purpose'] ?? '' ),
			'cookies'     => $out,
		);
	}

	/**
	 * Builds an INVALID result for a refused apply().
	 *
	 * @since 1.0.0
	 *
	 * @param string $id      Preset ID.
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @return Set_Result Result.
	 */
	private static function refusal( string $id, string $code, string $message ): Set_Result {
		return new Set_Result(
			Consent_Services_Store::OPTION,
			Set_Status::INVALID,
			$message,
			array(
				'errors' => array(
					array(
						'service' => $id,
						'cookie'  => null,
						'field'   => 'preset',
						'code'    => $code,
						'message' => $message,
					),
				),
			)
		);
	}
}
