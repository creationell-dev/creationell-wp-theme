<?php
/**
 * Cleaning of raw setting values.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings;

use Closure;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Turns a raw value from a form, the command line or an import into the canonical form of its type, or refuses it.
 *
 * Rules per type:
 *
 * - color: "#rgb" or "#rrggbb" in any case, stored as "#rrggbb" in lower case;
 * - font: a slug of the font catalog; "inherit" only where it is the default;
 * - text: sanitize_textarea_field(); html_inline: wp_kses() with INLINE_HTML;
 * - email: sanitize_email() and is_email(); url: http or https only; tel: digits,
 *   spaces and + ( ) . / -; the three may be empty;
 * - int, bool, enum: also the strings a form posts ("3", "1", "0");
 * - string: sanitize_text_field().
 *
 * Then the maximum length, Definition::accepts() and the validate closure of the
 * definition decide. Nothing here reaches SCSS unchecked: colors and font slugs
 * have a closed form.
 *
 * @since 1.0.0
 */
final class Sanitizer {

	/**
	 * Tags and attributes allowed in inline HTML.
	 *
	 * @since 1.0.0
	 */
	public const INLINE_HTML = array(
		'a'      => array(
			'href'   => true,
			'rel'    => true,
			'target' => true,
		),
		'strong' => array(),
		'em'     => array(),
		'br'     => array(),
	);

	/**
	 * URL protocols allowed in links of inline HTML.
	 *
	 * @since 1.0.0
	 */
	public const INLINE_PROTOCOLS = array( 'http', 'https', 'mailto', 'tel' );

	/**
	 * Slugs of the built-in fonts of Font_Catalog.
	 *
	 * @since 1.0.0
	 */
	public const FONTS = array( 'system-sans', 'system-serif' );

	/**
	 * Font value that keeps the font of the parent element.
	 *
	 * @since 1.0.0
	 */
	public const INHERIT = 'inherit';

	/**
	 * Filters inline HTML like wp_kses().
	 *
	 * @var Closure
	 * @phpstan-var Closure(string, array<string, array<string, bool>>, array<int, string>): string
	 */
	private Closure $kses;

	/**
	 * Returns the slugs of the font catalog.
	 *
	 * @var Closure
	 * @phpstan-var Closure(): array<int, string>
	 */
	private Closure $fonts;

	/**
	 * Takes the HTML filter and the font list; without them it uses wp_kses() and the slugs of Font_Catalog.
	 *
	 * @since 1.0.0
	 *
	 * @param Closure|null $kses  Filters HTML: content, allowed tags, allowed protocols.
	 * @param Closure|null $fonts Returns the slugs of the font catalog.
	 * @phpstan-param (Closure(string, array<string, array<string, bool>>, array<int, string>): string)|null $kses
	 * @phpstan-param (Closure(): array<int, string>)|null $fonts
	 */
	public function __construct( ?Closure $kses = null, ?Closure $fonts = null ) {
		$this->kses  = $kses ?? static fn( string $content, array $allowed, array $protocols ): string => wp_kses( $content, $allowed, $protocols );
		$this->fonts = $fonts ?? static fn(): array => Font_Catalog::instance()->slugs();
	}

	/**
	 * Cleans a raw value.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition $definition Definition.
	 * @param mixed      $value      Raw value.
	 * @return array{valid: bool, value: mixed, message: string} Clean value, or valid false with a translated message.
	 */
	public function clean( Definition $definition, mixed $value ): array {
		$checked = $this->check( $definition, $value );
		if ( null === $checked['error'] ) {
			return array(
				'valid'   => true,
				'value'   => $checked['value'],
				'message' => '',
			);
		}
		$message = match ( $checked['error'] ) {
			'length'   => sprintf(
				/* translators: %d: maximum number of characters. */
				__( 'Use at most %d characters.', 'creationell-wp-theme' ),
				$definition->max_length ?? 0
			),
			'validate' => __( 'This value is not allowed here.', 'creationell-wp-theme' ),
			default    => $this->type_message( $definition ),
		};
		return array(
			'valid'   => false,
			'value'   => null,
			'message' => $message,
		);
	}

	/**
	 * Tells whether a value is already clean: valid and unchanged by cleaning.
	 *
	 * The settings read values of the PHP layers (child theme, constants) with it;
	 * it translates nothing, so it may run before init.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition $definition Definition.
	 * @param mixed      $value      Value.
	 * @return bool True when valid and in canonical form.
	 */
	public function is_clean( Definition $definition, mixed $value ): bool {
		$checked = $this->check( $definition, $value );
		return null === $checked['error'] && $checked['value'] === $value;
	}

	/**
	 * Cleans a raw value without translating.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition $definition Definition.
	 * @param mixed      $value      Raw value.
	 * @return array{error: string|null, value: mixed} Clean value, or an error "type", "length" or "validate".
	 */
	private function check( Definition $definition, mixed $value ): array {
		if ( null !== $definition->sanitize ) {
			$value = ( $definition->sanitize )( $value );
		}
		$clean = $this->by_type( $definition, $value );
		$error = null;
		if ( null === $clean ) {
			$error = 'type';
		} elseif ( is_string( $clean ) && null !== $definition->max_length && mb_strlen( $clean, 'UTF-8' ) > $definition->max_length ) {
			$error = 'length';
		} elseif ( ! $definition->accepts( $clean ) ) {
			$error = 'type';
		} elseif ( null !== $definition->validate && true !== ( $definition->validate )( $clean ) ) {
			$error = 'validate';
		}
		return array(
			'error' => $error,
			'value' => null === $error ? $clean : null,
		);
	}

	/**
	 * Cleans by type.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition $definition Definition.
	 * @param mixed      $value      Raw value.
	 * @return mixed Clean value, or null when the value cannot become the type.
	 */
	private function by_type( Definition $definition, mixed $value ): mixed {
		return match ( $definition->type ) {
			'color'       => self::color( $value ),
			'font'        => $this->font( $definition, $value ),
			'text'        => is_string( $value ) ? sanitize_textarea_field( $value ) : null,
			'html_inline' => is_string( $value ) ? trim( ( $this->kses )( $value, self::INLINE_HTML, self::INLINE_PROTOCOLS ) ) : null,
			'email'       => self::email( $value ),
			'url'         => self::url( $value ),
			'tel'         => is_string( $value ) && 1 === preg_match( '~^[0-9 +()./-]*$~D', trim( $value ) ) ? trim( $value ) : null,
			'int'         => self::integer( $value ),
			'bool'        => self::boolean( $value ),
			'enum'        => self::choice( $definition, $value ),
			default       => is_string( $value ) ? sanitize_text_field( $value ) : null,
		};
	}

	/**
	 * Cleans a color.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw value.
	 * @return string|null "#rrggbb" in lower case, or null.
	 */
	private static function color( mixed $value ): ?string {
		if ( ! is_string( $value ) || 1 !== preg_match( '~^#([0-9a-f]{3}|[0-9a-f]{6})$~iD', trim( $value ), $match ) ) {
			return null;
		}
		$hex = strtolower( $match[1] );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		return '#' . $hex;
	}

	/**
	 * Cleans a font slug.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition $definition Definition.
	 * @param mixed      $value      Raw value.
	 * @return string|null Slug, or null when the catalog does not know it.
	 */
	private function font( Definition $definition, mixed $value ): ?string {
		if ( ! is_string( $value ) ) {
			return null;
		}
		$slug = trim( $value );
		if ( self::INHERIT === $slug ) {
			return self::INHERIT === $definition->default ? $slug : null;
		}
		return in_array( $slug, ( $this->fonts )(), true ) ? $slug : null;
	}

	/**
	 * Cleans an email address; empty stays empty.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw value.
	 * @return string|null Address, empty string, or null.
	 */
	private static function email( mixed $value ): ?string {
		if ( ! is_string( $value ) ) {
			return null;
		}
		if ( '' === trim( $value ) ) {
			return '';
		}
		$email = sanitize_email( $value );
		return '' !== $email && false !== is_email( $email ) ? $email : null;
	}

	/**
	 * Cleans a web address: http or https with a host; empty stays empty.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw value.
	 * @return string|null URL, empty string, or null.
	 */
	private static function url( mixed $value ): ?string {
		if ( ! is_string( $value ) ) {
			return null;
		}
		$url = trim( $value );
		if ( '' === $url ) {
			return '';
		}
		if ( 1 !== preg_match( '~^https?://[^\s<>"]+$~iD', $url ) || false === filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return null;
		}
		return $url;
	}

	/**
	 * Cleans an integer; a string of digits counts.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw value.
	 * @return int|null Integer or null.
	 */
	private static function integer( mixed $value ): ?int {
		if ( is_int( $value ) ) {
			return $value;
		}
		if ( is_string( $value ) && 1 === preg_match( '~^-?\d{1,18}$~D', trim( $value ) ) ) {
			return intval( trim( $value ) );
		}
		return null;
	}

	/**
	 * Cleans a boolean; 1, 0, "1", "0" and "" count, as a checkbox or ACF true_false posts them.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw value.
	 * @return bool|null Boolean or null.
	 */
	private static function boolean( mixed $value ): ?bool {
		if ( is_bool( $value ) ) {
			return $value;
		}
		return match ( $value ) {
			1, '1' => true,
			0, '0', '' => false,
			default => null,
		};
	}

	/**
	 * Cleans an enum value; a string of digits counts for an int choice.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition $definition Definition.
	 * @param mixed      $value      Raw value.
	 * @return int|string|null Choice or null.
	 */
	private static function choice( Definition $definition, mixed $value ): int|string|null {
		foreach ( $definition->values() as $choice ) {
			if ( $value === $choice || ( is_int( $choice ) && is_string( $value ) && trim( $value ) === (string) $choice ) ) {
				return is_int( $choice ) || is_string( $choice ) ? $choice : null;
			}
		}
		return null;
	}

	/**
	 * Returns the message for a value that does not fit the type.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition $definition Definition.
	 * @return string Translated message.
	 */
	private function type_message( Definition $definition ): string {
		return match ( $definition->type ) {
			'color' => __( 'Enter a color as #rrggbb, for example #0d6efd.', 'creationell-wp-theme' ),
			'font'  => __( 'Choose a font from the list.', 'creationell-wp-theme' ),
			'email' => __( 'Enter a valid email address.', 'creationell-wp-theme' ),
			'url'   => __( 'Enter a web address starting with https:// or http://.', 'creationell-wp-theme' ),
			'tel'   => __( 'Use digits, spaces and + ( ) . / - only.', 'creationell-wp-theme' ),
			'enum'  => __( 'Choose one of the available values.', 'creationell-wp-theme' ),
			'int'   => __( 'Enter a whole number.', 'creationell-wp-theme' ),
			'bool'  => __( 'Choose yes or no.', 'creationell-wp-theme' ),
			default => __( 'Enter text.', 'creationell-wp-theme' ),
		};
	}
}
