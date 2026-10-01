<?php
/**
 * Site-independent references in settings transfer files.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

use Creationell\WpTheme\Settings\Definition;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Writes IDs and URLs of a site as typed references and finds them again on the importing site.
 *
 * A setting with the reference type "page", "media" or "term" stores an ID; the
 * file carries the ID for information and the slug for the lookup:
 *
 *     {"$ref": "page", "id": 12, "slug": "legal/imprint"}
 *     {"$ref": "media", "id": 34, "slug": "logo"}
 *     {"$ref": "term", "taxonomy": "category", "id": 5, "slug": "news"}
 *     {"$ref": "url", "value": "https://example.test/contact/"}
 *
 * The slug of a page is its full path, so child pages are found again; only
 * published pages resolve. With a language, the object found goes through the
 * WPML filter wpml_object_id to its translation in that language (the original
 * when there is none), because translations may share the slug. A URL
 * below the source site of the file moves below home_url() of the importing
 * site; other URLs stay. Empty values (0, "") mean "no object" and pass
 * unchanged, as do values of settings without a reference type. IDs stored as
 * digit strings count as IDs. A reference type the codec does not know is
 * reported with _doing_it_wrong() and never resolves, so no ID of another
 * site is written.
 *
 * @since 1.0.0
 */
final class Reference_Codec {

	/**
	 * Reference types and the fields of their file shape besides "$ref".
	 *
	 * @since 1.0.0
	 */
	public const TYPES = array(
		'page'  => array( 'id', 'slug' ),
		'media' => array( 'id', 'slug' ),
		'term'  => array( 'id', 'slug', 'taxonomy' ),
		'url'   => array( 'value' ),
	);

	/**
	 * Tells whether a file value has the shape of a reference.
	 *
	 * Every field of the type must be present and nothing else; "id" is an integer
	 * of at least 0, "slug", "taxonomy" and "value" are strings.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value File value.
	 * @return bool True for a reference.
	 */
	public static function is_reference( mixed $value ): bool {
		if ( ! is_array( $value ) || ! isset( $value['$ref'] ) || ! is_string( $value['$ref'] ) || ! isset( self::TYPES[ $value['$ref'] ] ) ) {
			return false;
		}
		$fields   = self::TYPES[ $value['$ref'] ];
		$expected = array_merge( array( '$ref' ), $fields );
		$actual   = array_map( 'strval', array_keys( $value ) );
		sort( $expected );
		sort( $actual );
		if ( $expected !== $actual ) {
			return false;
		}
		foreach ( $fields as $field ) {
			$valid = 'id' === $field ? is_int( $value[ $field ] ) && $value[ $field ] >= 0 : is_string( $value[ $field ] );
			if ( ! $valid ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Turns a backend value into its file value.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition $definition Definition of the setting.
	 * @param mixed      $value      Backend value.
	 * @return mixed Reference for a non-empty value of a setting with a known reference type, otherwise the value.
	 */
	public static function encode( Definition $definition, mixed $value ): mixed {
		$type = $definition->reference_type;
		if ( null !== $type && ! isset( self::TYPES[ $type ] ) ) {
			self::report_type( $definition, __METHOD__ );
			return $value;
		}
		if ( 'url' === $type ) {
			return is_string( $value ) && '' !== $value
				? array(
					'$ref'  => 'url',
					'value' => $value,
				)
				: $value;
		}
		$id = self::id( $value );
		if ( null === $type || null === $id ) {
			return $value;
		}
		if ( 'term' === $type ) {
			return array(
				'$ref'     => 'term',
				'taxonomy' => self::term_field( 'taxonomy', $id ),
				'id'       => $id,
				'slug'     => self::term_field( 'slug', $id ),
			);
		}
		return array(
			'$ref' => $type,
			'id'   => $id,
			'slug' => 'page' === $type ? self::page_path( $id ) : self::attachment_name( $id ),
		);
	}

	/**
	 * Returns the ID of a backend value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Backend value.
	 * @return int|null ID above 0 from an integer or a digit string, otherwise null.
	 */
	private static function id( mixed $value ): ?int {
		if ( is_string( $value ) && '' !== $value && ctype_digit( $value ) ) {
			$value = (int) $value;
		}
		return is_int( $value ) && $value > 0 ? $value : null;
	}

	/**
	 * Reports a reference type the codec does not know.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition $definition Definition.
	 * @param string     $method     Method that met the type.
	 * @return void
	 */
	private static function report_type( Definition $definition, string $method ): void {
		_doing_it_wrong(
			esc_html( $method ),
			esc_html( sprintf( 'Setting %1$s has the reference type %2$s, which the settings transfer does not know; its value never resolves on import.', $definition->key, (string) $definition->reference_type ) ),
			'1.0.0'
		);
	}

	/**
	 * Finds the object of a file value on this site.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition  $definition Definition of the setting.
	 * @param mixed       $value      File value, never null (null resets the setting before any lookup).
	 * @param string      $source     Home URL of the site that wrote the file.
	 * @param string|null $lang       Language code the value is imported for; null asks WPML for no translation.
	 * @return mixed ID or URL on this site, the unchanged value for empty values and settings without reference type,
	 *               null when the reference cannot be resolved or the reference type is unknown.
	 */
	public static function resolve( Definition $definition, mixed $value, string $source, ?string $lang = null ): mixed {
		$type = $definition->reference_type;
		if ( null === $type ) {
			return is_array( $value ) ? null : $value;
		}
		if ( ! isset( self::TYPES[ $type ] ) ) {
			self::report_type( $definition, __METHOD__ );
			return null;
		}
		if ( 0 === $value || '' === $value ) {
			return $value;
		}
		if ( ! self::is_reference( $value ) || ! is_array( $value ) || $type !== $value['$ref'] ) {
			return null;
		}
		return match ( $type ) {
			'page'  => self::find_page( self::string_field( $value, 'slug' ), $lang ),
			'media' => self::find_attachment( self::string_field( $value, 'slug' ), $lang ),
			'term'  => self::find_term( self::string_field( $value, 'slug' ), self::string_field( $value, 'taxonomy' ), $lang ),
			default => self::move_url( self::string_field( $value, 'value' ), $source ),
		};
	}

	/**
	 * Returns the path of a page.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Post ID.
	 * @return string Path such as "legal/imprint", empty when the post is no page.
	 */
	private static function page_path( int $id ): string {
		if ( 'page' !== get_post_type( $id ) ) {
			return '';
		}
		$path = get_page_uri( $id );
		return is_string( $path ) ? $path : '';
	}

	/**
	 * Returns the post name of an attachment.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Post ID.
	 * @return string Post name, empty when the post is no attachment.
	 */
	private static function attachment_name( int $id ): string {
		if ( 'attachment' !== get_post_type( $id ) ) {
			return '';
		}
		$name = get_post_field( 'post_name', $id, 'raw' );
		return is_string( $name ) ? $name : '';
	}

	/**
	 * Returns a field of a term.
	 *
	 * @since 1.0.0
	 *
	 * @param string $field Field: "slug" or "taxonomy".
	 * @param int    $id    Term ID.
	 * @return string Value, empty when the term does not exist.
	 */
	private static function term_field( string $field, int $id ): string {
		$value = get_term_field( $field, $id, '', 'raw' );
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Returns a string field of a reference.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $reference Reference.
	 * @param string       $field     Field.
	 * @return string Value, empty when it is no string.
	 */
	private static function string_field( array $reference, string $field ): string {
		return isset( $reference[ $field ] ) && is_string( $reference[ $field ] ) ? $reference[ $field ] : '';
	}

	/**
	 * Finds a published page by its path, in the given language.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $path Path.
	 * @param string|null $lang Language code, or null.
	 * @return int|null Page ID, null when not found or not published.
	 */
	private static function find_page( string $path, ?string $lang ): ?int {
		if ( '' === $path ) {
			return null;
		}
		$page = get_page_by_path( $path, ARRAY_A, 'page' );
		$id   = is_array( $page ) && isset( $page['ID'] ) && is_int( $page['ID'] ) && $page['ID'] > 0 ? $page['ID'] : null;
		$id   = null === $id ? null : self::translation( $id, 'page', $lang );
		return null !== $id && 'page' === get_post_type( $id ) && 'publish' === get_post_field( 'post_status', $id, 'raw' ) ? $id : null;
	}

	/**
	 * Returns the translation of an object through the WPML filter wpml_object_id.
	 *
	 * Without WPML the filter returns the ID unchanged; without a translation
	 * WPML returns the original.
	 *
	 * @since 1.0.0
	 *
	 * @param int         $id   Object ID.
	 * @param string      $type WPML element type: "page", "attachment" or a taxonomy.
	 * @param string|null $lang Language code; null asks nothing.
	 * @return int|null ID in that language, null for an invalid answer.
	 */
	private static function translation( int $id, string $type, ?string $lang ): ?int {
		if ( null === $lang ) {
			return $id;
		}
		$translated = apply_filters( 'wpml_object_id', $id, $type, true, $lang );
		return self::id( $translated );
	}

	/**
	 * Finds an attachment by its post name, in the given language.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $name Post name.
	 * @param string|null $lang Language code, or null.
	 * @return int|null Attachment ID, the lowest of several; null when not found.
	 */
	private static function find_attachment( string $name, ?string $lang ): ?int {
		if ( '' === $name ) {
			return null;
		}
		$ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'name'           => $name,
				'posts_per_page' => 1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		$id  = $ids[0] ?? null;
		$id  = is_int( $id ) && $id > 0 ? self::translation( $id, 'attachment', $lang ) : null;
		return null !== $id && 'attachment' === get_post_type( $id ) ? $id : null;
	}

	/**
	 * Finds a term by slug and taxonomy, in the given language.
	 *
	 * The translation from wpml_object_id is only a proposal: it counts only
	 * when it is a term of the same taxonomy, like the checks of pages and
	 * attachments.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $slug     Term slug.
	 * @param string      $taxonomy Taxonomy.
	 * @param string|null $lang     Language code, or null.
	 * @return int|null Term ID, null when not found.
	 */
	private static function find_term( string $slug, string $taxonomy, ?string $lang ): ?int {
		if ( '' === $slug || '' === $taxonomy ) {
			return null;
		}
		$term = get_term_by( 'slug', $slug, $taxonomy, ARRAY_A );
		$id   = is_array( $term ) && isset( $term['term_id'] ) && is_int( $term['term_id'] ) && $term['term_id'] > 0 ? $term['term_id'] : null;
		$id   = null === $id ? null : self::translation( $id, $taxonomy, $lang );
		return null !== $id && self::term_field( 'taxonomy', $id ) === $taxonomy ? $id : null;
	}

	/**
	 * Moves a URL below the source site to this site.
	 *
	 * The source matches only as a whole: the URL continues after it with "/",
	 * "?", "#" or ends there.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url    URL from the file.
	 * @param string $source Home URL of the site that wrote the file.
	 * @return string|null URL on this site or the unchanged external URL, null for an empty URL.
	 */
	private static function move_url( string $url, string $source ): ?string {
		if ( '' === $url ) {
			return null;
		}
		$source = untrailingslashit( $source );
		if ( '' === $source || ! str_starts_with( $url, $source ) ) {
			return $url;
		}
		$rest = substr( $url, strlen( $source ) );
		if ( '' !== $rest && ! in_array( $rest[0], array( '/', '?', '#' ), true ) ) {
			return $url;
		}
		return untrailingslashit( home_url() ) . $rest;
	}
}
