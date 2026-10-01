<?php
/**
 * Blocks marked scripts until the visitor consents.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Consent;

use Creationell\WpTheme\Admin\Notices;
use WP_HTML_Tag_Processor;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Prints the scripts of marked handles as text/plain with data-category, so they wait for consent.
 *
 * A handle is marked with its category:
 *
 *     wp_script_add_data( 'my-analytics', 'creationell-wp-theme-consent', 'analytics' );
 *
 * On the front end every SCRIPT tag of the handle, including its inline parts
 * (before, after, extra, translations), gets type="text/plain" and
 * data-category; async, defer and data-wp-strategy fall away, src stays. The
 * consent provider runs the scripts after consent. "necessary" leaves the
 * handle unchanged. An unknown category blocks the handle as well and is listed
 * as "unknown_category". Without a provider no marked script ever runs; the
 * handles are kept in a transient for the admin notice and for doctor.
 *
 * @since 1.0.0
 */
final class Script_Marker {

	/**
	 * Key of the script data that marks a handle.
	 *
	 * @since 1.0.0
	 */
	public const DATA_KEY = 'creationell-wp-theme-consent';

	/**
	 * Priority of the tag filters: late, after plugins that add loading attributes.
	 *
	 * @since 1.0.0
	 */
	public const PRIORITY = 999;

	/**
	 * Transient with the handles marked while no provider was active.
	 *
	 * @since 1.0.0
	 */
	public const TRANSIENT = 'creationell_wp_theme_consent_marked';

	/**
	 * Category under which handles with an unknown category are listed.
	 *
	 * @since 1.0.0
	 */
	public const UNKNOWN = 'unknown_category';

	/**
	 * ID of the admin notice.
	 *
	 * @since 1.0.0
	 */
	public const NOTICE_ID = 'consent-marked';

	/**
	 * ID suffixes of the inline scripts of a handle.
	 *
	 * @since 1.0.0
	 */
	public const INLINE_SUFFIXES = array( '-js-before', '-js-after', '-js-extra', '-js-translations' );

	/**
	 * Attributes that would load or run the script early.
	 *
	 * @since 1.0.0
	 */
	public const LOADING_ATTRIBUTES = array( 'async', 'defer', 'data-wp-strategy' );

	/**
	 * Handles blocked in this request with their listed category.
	 *
	 * @var array<string, string>
	 */
	private static array $marked = array();

	/**
	 * Filters the output of a script handle; runs on script_loader_tag.
	 *
	 * The output holds the SCRIPT tag of the file and the inline parts of the
	 * handle. When the tag processor stops in an unfinished tag, the output is
	 * dropped, so the script never runs without consent.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $tag    HTML of the handle.
	 * @param mixed $handle Script handle.
	 * @return mixed Changed HTML, or the input when the handle is not blocked.
	 */
	public static function filter_tag( mixed $tag, mixed $handle ): mixed {
		if ( ! is_string( $tag ) || ! is_string( $handle ) ) {
			return $tag;
		}
		$category = self::block_category( $handle );
		if ( null === $category ) {
			return $tag;
		}
		$processor = new WP_HTML_Tag_Processor( $tag );
		$found     = false;
		while ( $processor->next_tag( array( 'tag_name' => 'script' ) ) ) {
			$found = true;
			$processor->set_attribute( 'type', 'text/plain' );
			$processor->set_attribute( 'data-category', $category );
			foreach ( self::LOADING_ATTRIBUTES as $name ) {
				$processor->remove_attribute( $name );
			}
		}
		if ( $processor->paused_at_incomplete_token() || ( ! $found && false !== stripos( $tag, '<script' ) ) ) {
			return '';
		}
		return $processor->get_updated_html();
	}

	/**
	 * Filters the attributes of an inline script; runs on wp_inline_script_attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $attributes Attributes by name.
	 * @param mixed $data       Inline code; unused.
	 * @return mixed Attributes with type and data-category when the handle is blocked, otherwise the input.
	 */
	public static function filter_inline_attributes( mixed $attributes, mixed $data = '' ): mixed {
		unset( $data );
		if ( ! is_array( $attributes ) || ! isset( $attributes['id'] ) || ! is_string( $attributes['id'] ) ) {
			return $attributes;
		}
		$handle = self::inline_handle( $attributes['id'] );
		if ( null === $handle ) {
			return $attributes;
		}
		$category = self::block_category( $handle );
		if ( null === $category ) {
			return $attributes;
		}
		foreach ( self::LOADING_ATTRIBUTES as $name ) {
			unset( $attributes[ $name ] );
		}
		$attributes['type']          = 'text/plain';
		$attributes['data-category'] = $category;
		return $attributes;
	}

	/**
	 * Escapes the stored inline code of blocked handles; runs on wp_print_scripts and wp_print_footer_scripts.
	 *
	 * WordPress prints no text/plain script whose data contains "<script" or
	 * "</script". The escaping keeps the code working once the provider runs it.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function escape_inline_data(): void {
		if ( is_admin() ) {
			return;
		}
		$scripts = wp_scripts();
		foreach ( array_keys( $scripts->registered ) as $handle ) {
			$handle = (string) $handle;
			if ( null === self::marker( $handle ) || Consent_Gate::NECESSARY === self::marker( $handle ) ) {
				continue;
			}
			foreach ( array( 'before', 'after' ) as $position ) {
				$code = $scripts->get_data( $handle, $position );
				if ( is_array( $code ) ) {
					$scripts->add_data( $handle, $position, array_map( static fn( mixed $part ): mixed => is_string( $part ) ? self::escape_script( $part ) : $part, $code ) );
				}
			}
			$data = $scripts->get_data( $handle, 'data' );
			if ( is_string( $data ) ) {
				$scripts->add_data( $handle, 'data', self::escape_script( $data ) );
			}
		}
	}

	/**
	 * Escapes JavaScript for a SCRIPT element of any type.
	 *
	 * Replaces the "s" of each "<script" and "</script", in any case, by the
	 * escape \u0073 (\u0053 for "S"), like WordPress does for JavaScript. The
	 * code means the same inside strings, regular expressions and identifiers.
	 *
	 * @since 1.0.0
	 *
	 * @param string $javascript Code.
	 * @return string Escaped code.
	 */
	public static function escape_script( string $javascript ): string {
		return (string) preg_replace_callback(
			'~<(/?)(s)(cript)~i',
			static fn( array $found ): string => '<' . $found[1] . ( 's' === $found[2] ? '\u0073' : '\u0053' ) . $found[3],
			$javascript
		);
	}

	/**
	 * Writes the blocked handles into the transient while no provider is active; runs on shutdown.
	 *
	 * Handles of earlier requests stay, so the list covers every page; it is
	 * written only when it changes and expires a day after the last change.
	 * With a provider the transient is deleted. Requests without blocked
	 * handles read and write nothing.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function persist(): void {
		if ( array() === self::$marked ) {
			return;
		}
		if ( Consent_Gate::has_provider() ) {
			if ( false !== get_transient( self::TRANSIENT ) ) {
				delete_transient( self::TRANSIENT );
			}
			return;
		}
		$stored = self::stored();
		$merged = array_column( $stored, 'category', 'handle' );
		foreach ( self::$marked as $handle => $category ) {
			$merged[ $handle ] = $category;
		}
		ksort( $merged, SORT_STRING );
		$list = array();
		foreach ( $merged as $handle => $category ) {
			$list[] = array(
				'handle'   => (string) $handle,
				'category' => $category,
			);
		}
		if ( $list !== $stored ) {
			set_transient( self::TRANSIENT, $list, DAY_IN_SECONDS );
		}
	}

	/**
	 * Returns the handles in the transient.
	 *
	 * @since 1.0.0
	 *
	 * @return list<array{handle: string, category: string}> Entries; entries of another shape are left out.
	 */
	public static function stored(): array {
		$value = get_transient( self::TRANSIENT );
		$list  = array();
		foreach ( is_array( $value ) ? $value : array() as $entry ) {
			if ( is_array( $entry ) && isset( $entry['handle'], $entry['category'] ) && is_string( $entry['handle'] ) && is_string( $entry['category'] ) ) {
				$list[] = array(
					'handle'   => $entry['handle'],
					'category' => $entry['category'],
				);
			}
		}
		return $list;
	}

	/**
	 * Queues the admin notice about marked scripts without provider; runs on admin_init.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function queue_notice(): void {
		if ( Consent_Gate::has_provider() ) {
			return;
		}
		$stored = self::stored();
		if ( array() === $stored ) {
			return;
		}
		$names = implode(
			', ',
			array_map( static fn( array $entry ): string => sprintf( '%1$s (%2$s)', $entry['handle'], $entry['category'] ), $stored )
		);
		Notices::add(
			self::NOTICE_ID,
			static fn(): string => sprintf(
				/* translators: %s: script handles with their consent category, e.g. "my-analytics (analytics)". */
				__( 'These scripts wait for consent, but no consent provider is active, so they never run: %s. Switch on the consent module or remove the marking.', 'creationell-wp-theme' ),
				$names
			),
			Notices::WARNING
		);
	}

	/**
	 * Returns the handles blocked in this request.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Listed category by handle.
	 */
	public static function marked(): array {
		return self::$marked;
	}

	/**
	 * Forgets the handles of this request.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$marked = array();
	}

	/**
	 * Returns the data-category of a handle to block, and lists the handle.
	 *
	 * @since 1.0.0
	 *
	 * @param string $handle Script handle.
	 * @return string|null Category for the attribute; null when the handle stays unchanged.
	 */
	private static function block_category( string $handle ): ?string {
		if ( is_admin() ) {
			return null;
		}
		$marker = self::marker( $handle );
		if ( null === $marker || Consent_Gate::NECESSARY === $marker ) {
			return null;
		}
		$known = '' !== $marker && Consent_Categories::is_known( $marker );
		if ( ! $known && ! isset( self::$marked[ $handle ] ) ) {
			_doing_it_wrong(
				__CLASS__ . '::filter_tag',
				esc_html( sprintf( 'The script "%1$s" is marked for consent with an unknown category; it stays blocked. Use one of: %2$s.', $handle, implode( ', ', Consent_Categories::all() ) ) ),
				'1.0.0'
			);
		}
		self::$marked[ $handle ] = $known ? $marker : self::UNKNOWN;
		return $marker;
	}

	/**
	 * Returns the marker of a handle.
	 *
	 * @since 1.0.0
	 *
	 * @param string $handle Script handle.
	 * @return string|null Category as marked, "" for a marker that is no string, null when the handle is not marked.
	 */
	private static function marker( string $handle ): ?string {
		$value = wp_scripts()->get_data( $handle, self::DATA_KEY );
		if ( false === $value || null === $value || '' === $value ) {
			return null;
		}
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Returns the handle of an inline script ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id ID, e.g. "my-analytics-js-before".
	 * @return string|null Handle, or null for another ID.
	 */
	private static function inline_handle( string $id ): ?string {
		foreach ( self::INLINE_SUFFIXES as $suffix ) {
			if ( str_ends_with( $id, $suffix ) && strlen( $id ) > strlen( $suffix ) ) {
				return substr( $id, 0, -strlen( $suffix ) );
			}
		}
		return null;
	}
}
