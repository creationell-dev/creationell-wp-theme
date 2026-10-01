<?php
/**
 * Checked value of an SCSS variable.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Compiler;

use InvalidArgumentException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * A value that may go into the SCSS source of a compilation.
 *
 * The compiler parses variables as SCSS source, so a value such as
 * `red))} @debug "x"; a{b:c` could add rules or statements. Every factory method
 * accepts only its own plain form (an allowlist per type) and throws for anything
 * else; semicolons, braces, parentheses, slashes, backslashes, comments,
 * interpolation, variables and at-rules never pass.
 *
 * @since 1.0.0
 */
final class Scss_Value {

	/**
	 * Words that SCSS reads as keywords instead of a color or font name.
	 */
	private const RESERVED = array( 'null', 'true', 'false', 'and', 'or', 'not' );

	/**
	 * Units of a length.
	 */
	private const UNITS = 'px|rem|em|%|vw|vh|vmin|vmax|dvh|svh|lvh|ch|ex|pt';

	/**
	 * A number with at most six digits before and after the point.
	 */
	private const NUMBER = '-?(?:\d{1,6}(?:\.\d{1,6})?|\.\d{1,6})';

	/**
	 * One font family: quoted, or unquoted words.
	 */
	private const FAMILY = '(?:"[A-Za-z0-9 ._-]{1,60}"|\'[A-Za-z0-9 ._-]{1,60}\'|-?[A-Za-z][A-Za-z0-9_-]{0,60}(?: [A-Za-z0-9_-]{1,60}){0,5})';

	/**
	 * Sets type and value; use the factory methods.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type  Type: color, length, number, font_stack or boolean.
	 * @param string $value SCSS source of the value.
	 */
	private function __construct( public readonly string $type, public readonly string $value ) {
	}

	/**
	 * Returns a color: "#rgb", "#rgba", "#rrggbb", "#rrggbbaa" or a color keyword such as "transparent".
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Color.
	 * @return self Checked value.
	 * @throws InvalidArgumentException When the value is no plain color.
	 */
	public static function color( string $value ): self {
		return self::checked( 'color', $value, '~^(?:#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})|[a-zA-Z]{3,20})$~D' );
	}

	/**
	 * Returns a length: a number with a unit such as "1.5rem", "-2px" or "50%", or "0".
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Length.
	 * @return self Checked value.
	 * @throws InvalidArgumentException When the value is no plain length.
	 */
	public static function length( string $value ): self {
		return self::checked( 'length', $value, '~^(?:' . self::NUMBER . '(?:' . self::UNITS . ')|0)$~D' );
	}

	/**
	 * Returns a number without unit such as "1.25".
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Number.
	 * @return self Checked value.
	 * @throws InvalidArgumentException When the value is no plain number.
	 */
	public static function number( string $value ): self {
		return self::checked( 'number', $value, '~^' . self::NUMBER . '$~D' );
	}

	/**
	 * Returns a font stack: up to 20 families separated by commas, names quoted or unquoted.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Font stack such as `"Segoe UI", Roboto, sans-serif`.
	 * @return self Checked value.
	 * @throws InvalidArgumentException When the value is no plain font stack.
	 */
	public static function font_stack( string $value ): self {
		return self::checked( 'font_stack', $value, '~^' . self::FAMILY . '(?:, ?' . self::FAMILY . '){0,19}$~D' );
	}

	/**
	 * Returns a boolean as the SCSS keyword "true" or "false".
	 *
	 * @since 1.0.0
	 *
	 * @param bool $value Boolean.
	 * @return self Value.
	 */
	public static function boolean( bool $value ): self {
		return new self( 'boolean', $value ? 'true' : 'false' );
	}

	/**
	 * Checks a value against the pattern of its type and the reserved words.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type    Type.
	 * @param string $value   Value.
	 * @param string $pattern Allowlist pattern of the type.
	 * @return self Checked value.
	 * @throws InvalidArgumentException When the value does not match.
	 */
	private static function checked( string $type, string $value, string $pattern ): self {
		$words = preg_split( '~[\s,]+~', strtolower( $value ), -1, PREG_SPLIT_NO_EMPTY );
		if ( 1 !== preg_match( $pattern, $value ) || array() !== array_intersect( false === $words ? array() : $words, self::RESERVED ) ) {
			$error = new InvalidArgumentException( 'Invalid SCSS value of type ' . $type . '.' );
			throw $error;
		}
		return new self( $type, $value );
	}
}
