<?php
/**
 * JSON encoding and decoding of settings transfer files.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

use JsonException;
use stdClass;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Turns transfer files into JSON text and back; the same data always gives the same bytes.
 *
 * Encoding sorts the keys of every object, keeps the order of lists, writes
 * slashes and non-ASCII characters unescaped and ends with a newline. An stdClass
 * is written as an object, so empty maps stay "{}" while an empty array is "[]".
 * Decoding accepts only a JSON object as root, up to DEPTH levels; it never
 * unserializes.
 *
 * @since 1.0.0
 */
final class Json_Codec {

	/**
	 * Maximum nesting depth of a file; the format needs six levels.
	 *
	 * @since 1.0.0
	 */
	public const DEPTH = 32;

	/**
	 * Flags of json_encode().
	 *
	 * @since 1.0.0
	 */
	public const ENCODE_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

	/**
	 * UTF-8 byte order mark.
	 *
	 * @since 1.0.0
	 */
	private const BOM = "\xEF\xBB\xBF";

	/**
	 * Decodes the JSON text of a transfer file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $raw File content, with or without a UTF-8 byte order mark.
	 * @return array<mixed> Root object as associative array; large integers are strings.
	 * @throws Transfer_Exception With invalid_json when the text is no JSON object within DEPTH levels or no valid UTF-8.
	 */
	public static function decode( string $raw ): array {
		if ( str_starts_with( $raw, self::BOM ) ) {
			$raw = substr( $raw, strlen( self::BOM ) );
		}
		if ( ! str_starts_with( ltrim( $raw ), '{' ) ) {
			Transfer_Exception::raise( Transfer_Error::INVALID_JSON, array( 'detail' => 'the root is no object' ) );
		}
		try {
			$data = json_decode( $raw, true, self::DEPTH, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING );
		} catch ( JsonException $exception ) {
			Transfer_Exception::raise( Transfer_Error::INVALID_JSON, array( 'detail' => $exception->getMessage() ) );
		}
		if ( ! is_array( $data ) ) {
			Transfer_Exception::raise( Transfer_Error::INVALID_JSON, array( 'detail' => 'the root is no object' ) );
		}
		return $data;
	}

	/**
	 * Encodes a transfer file as deterministic JSON text.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $data Root object; nested arrays that are lists stay lists, other arrays and stdClass become objects.
	 * @return string JSON text ending with a newline.
	 * @throws JsonException When a value cannot be encoded, e.g. a string that is no valid UTF-8.
	 */
	public static function encode( array $data ): string {
		$json = wp_json_encode( self::normalize( $data ), self::ENCODE_FLAGS, self::DEPTH );
		if ( ! is_string( $json ) ) {
			throw new JsonException( 'The transfer file cannot be encoded.' );
		}
		return $json . "\n";
	}

	/**
	 * Sorts the keys of every object and keeps the order of lists.
	 *
	 * Encoding the result with wp_json_encode(), ENCODE_FLAGS and DEPTH and
	 * adding a newline gives the text of encode(); a download prints it that way.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return mixed Value with sorted objects; associative arrays become stdClass.
	 */
	public static function normalize( mixed $value ): mixed {
		$is_object = $value instanceof stdClass;
		if ( $is_object ) {
			$value = get_object_vars( $value );
		}
		if ( ! is_array( $value ) ) {
			return $value;
		}
		if ( ! $is_object && array_is_list( $value ) ) {
			return array_map( self::normalize( ... ), $value );
		}
		ksort( $value, SORT_STRING );
		$object = new stdClass();
		foreach ( $value as $key => $item ) {
			$object->{$key} = self::normalize( $item );
		}
		return $object;
	}
}
