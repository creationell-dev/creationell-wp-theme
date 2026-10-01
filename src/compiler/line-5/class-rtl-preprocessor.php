<?php
/**
 * Text step of the line 5 chain before the CSS parser.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Compiler\Line5;

use InvalidArgumentException;
use RuntimeException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Prepares the expanded scssphp output for the parser, in both directions.
 *
 * Both directions: an empty custom property ("--x: ;") gets the placeholder
 * EMPTY_VALUE, because the parser drops a declaration without a value.
 * Right to left: the value directives of Node RTLCSS ("/*rtl:<value>*\/",
 * "ignore", "prepend:", "append:", "insert:") are applied as text, because the
 * parser drops comments inside a value and the port knows no value directives.
 * A declaration whose value a directive changed or kept gets a declaration-level
 * "/*rtl:ignore*\/" in front: Node RTLCSS flips nothing else of it. Directives
 * before a rule or a declaration stay for the port. A raw directive at the end
 * of a declaration block (after the last declaration, or in an empty block)
 * gets the anchor declaration ANCHOR: ANCHOR_VALUE after it, because the parser
 * attaches a comment only to a following declaration; Rtl_Port::flip() removes
 * every anchor of the document. A raw directive inside a value has no place and
 * stops the build with a message that names the declaration. Comments inside strings, selectors
 * and at-rule preludes stay as they are.
 *
 * Example:
 *
 *     $css = Rtl_Preprocessor::prepare( $expanded, 'rtl' );
 *
 * @since 1.0.0
 */
final class Rtl_Preprocessor {

	/**
	 * Placeholder for the value of an empty custom property while the parser holds the stylesheet.
	 *
	 * @since 1.0.0
	 */
	public const EMPTY_VALUE = '__creationell_empty__';

	/**
	 * Directive that stops the port from flipping a declaration.
	 *
	 * @since 1.0.0
	 */
	public const IGNORE = '/*rtl:ignore*/';

	/**
	 * Name of the anchor declaration after a raw directive at the end of a declaration block; Rtl_Port drops it.
	 *
	 * @since 1.0.0
	 */
	public const ANCHOR = '--creationell-rtl-anchor';

	/**
	 * Value of the anchor declaration; only the preprocessor writes it, so a real declaration of that name stays.
	 *
	 * @since 1.0.0
	 */
	public const ANCHOR_VALUE = '__creationell_rtl_anchor__';

	/**
	 * Prepares a stylesheet for the parser.
	 *
	 * @since 1.0.0
	 *
	 * @param string $css       Expanded stylesheet of scssphp.
	 * @param string $direction "ltr" or "rtl".
	 * @return string Stylesheet with placeholders and, right to left, applied value directives.
	 * @throws InvalidArgumentException When the direction is unknown.
	 * @throws RuntimeException         When a raw directive stands inside a value (right to left).
	 */
	public static function prepare( string $css, string $direction ): string {
		if ( 'ltr' !== $direction && 'rtl' !== $direction ) {
			$error = new InvalidArgumentException( 'Unknown direction ' . $direction . '.' );
			throw $error;
		}
		$css = self::protect_empty( $css );
		return 'rtl' === $direction ? self::value_directives( $css ) : $css;
	}

	/**
	 * Replaces the empty value of custom properties with the placeholder.
	 *
	 * @since 1.0.0
	 *
	 * @param string $css Stylesheet.
	 * @return string Stylesheet with placeholders.
	 */
	public static function protect_empty( string $css ): string {
		return (string) preg_replace( '~(--[A-Za-z0-9_-]+)\s*:\s*(?=[;}])~', '$1:' . self::EMPTY_VALUE, $css );
	}

	/**
	 * Applies the value directives of every declaration that has one.
	 *
	 * A small scanner splits the stylesheet at "{", "}" and ";" outside strings,
	 * comments and brackets. A part that ends with "{" is a selector or prelude;
	 * every other part with a colon outside comments is a declaration.
	 *
	 * @since 1.0.0
	 *
	 * @param string $css Stylesheet.
	 * @return string Stylesheet with applied value directives.
	 */
	private static function value_directives( string $css ): string {
		if ( ! str_contains( $css, 'rtl:' ) ) {
			return $css;
		}
		$out    = '';
		$blocks = array();
		$start  = 0;
		$depth  = 0;
		$length = strlen( $css );
		for ( $i = 0; $i < $length; $i++ ) {
			$char = $css[ $i ];
			if ( '/' === $char && '*' === ( $css[ $i + 1 ] ?? '' ) ) {
				$end = strpos( $css, '*/', $i + 2 );
				$i   = false === $end ? $length : $end + 1;
			} elseif ( '"' === $char || "'" === $char ) {
				$i = self::string_end( $css, $i );
			} elseif ( '(' === $char ) {
				++$depth;
			} elseif ( ')' === $char ) {
				$depth = max( 0, $depth - 1 );
			} elseif ( 0 === $depth && ( '{' === $char || '}' === $char || ';' === $char ) ) {
				$part = substr( $css, $start, $i - $start );
				if ( '{' === $char ) {
					$blocks[] = self::is_declaration_block( $part );
					$out     .= $part;
				} else {
					$out .= self::declaration( $part );
					if ( '}' === $char && true === array_pop( $blocks ) && self::ends_with_raw( $part ) ) {
						$out .= self::ANCHOR . ':' . self::ANCHOR_VALUE;
					}
				}
				$out  .= $char;
				$start = $i + 1;
			}
		}
		return $out . substr( $css, $start );
	}

	/**
	 * Tells whether the block after a prelude holds declarations (a rule, @font-face, @page) or rules (@media, @supports ...).
	 *
	 * @since 1.0.0
	 *
	 * @param string $prelude Selector or at-rule prelude, comments included.
	 * @return bool True for a declaration block.
	 */
	private static function is_declaration_block( string $prelude ): bool {
		$prelude = trim( (string) preg_replace( '~/\*.*?\*/~s', '', $prelude ) );
		return ! str_starts_with( $prelude, '@' ) || 1 === preg_match( '~^@(?:font-face|page|property|counter-style|font-palette-values)\b~i', $prelude );
	}

	/**
	 * Tells whether the rest of a block is a raw directive without a declaration after it.
	 *
	 * @since 1.0.0
	 *
	 * @param string $part Text between the last ";" or "{" and "}".
	 * @return bool True when the part holds a raw directive and nothing but comments and white space.
	 */
	private static function ends_with_raw( string $part ): bool {
		return 1 === preg_match( '~/\*\s*!?\s*rtl:(?:begin:)?raw:~', $part ) && '' === trim( (string) preg_replace( '~/\*.*?\*/~s', '', $part ) );
	}

	/**
	 * Returns the position of the closing quote of a string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $css   Stylesheet.
	 * @param int    $start Position of the opening quote.
	 * @return int Position of the closing quote; the end of the text when it is missing.
	 */
	private static function string_end( string $css, int $start ): int {
		$quote  = $css[ $start ];
		$length = strlen( $css );
		for ( $i = $start + 1; $i < $length; $i++ ) {
			if ( '\\' === $css[ $i ] ) {
				++$i;
			} elseif ( $quote === $css[ $i ] ) {
				return $i;
			}
		}
		return $length;
	}

	/**
	 * Applies the value directives of one declaration.
	 *
	 * @since 1.0.0
	 *
	 * @param string $part Text between two of "{", "}" and ";".
	 * @return string The same text, or the declaration with the directive applied.
	 */
	private static function declaration( string $part ): string {
		$parts = self::split( $part );
		if ( null === $parts || array() === $parts['directives'] ) {
			return $part;
		}
		$value     = trim( $parts['value'] );
		$important = '';
		if ( 1 === preg_match( '~^(.*?)\s*(!\s*important)$~is', $value, $matches ) ) {
			$value     = $matches[1];
			$important = ' !important';
		}
		$value = trim( self::apply( $parts['directives'], $value ) );
		return $parts['lead'] . self::IGNORE . $parts['property'] . ': ' . $value . $important;
	}

	/**
	 * Splits a declaration into its lead, property, value and value directives.
	 *
	 * The lead is the white space and the comments before the property. In the
	 * value every value directive is cut out; an insert directive leaves a
	 * marker ("\0" and its number) at its place.
	 *
	 * @since 1.0.0
	 *
	 * @param string $part Declaration text.
	 * @return array{lead: string, property: string, value: string, directives: list<array{name: string, param: string}>}|null Parts; null without a colon outside comments.
	 * @throws RuntimeException When a raw directive stands inside the value.
	 */
	private static function split( string $part ): ?array {
		$length = strlen( $part );
		$lead   = 0;
		while ( $lead < $length ) {
			if ( ctype_space( $part[ $lead ] ) ) {
				++$lead;
			} elseif ( str_starts_with( substr( $part, $lead, 2 ), '/*' ) ) {
				$end  = strpos( $part, '*/', $lead + 2 );
				$lead = false === $end ? $length : $end + 2;
			} else {
				break;
			}
		}
		$colon = null;
		for ( $i = $lead; $i < $length; $i++ ) {
			if ( '/' === $part[ $i ] && '*' === ( $part[ $i + 1 ] ?? '' ) ) {
				$end = strpos( $part, '*/', $i + 2 );
				$i   = false === $end ? $length : $end + 1;
			} elseif ( ':' === $part[ $i ] ) {
				$colon = $i;
				break;
			}
		}
		if ( null === $colon ) {
			return null;
		}
		$value      = substr( $part, $colon + 1 );
		$directives = array();
		$text       = '';
		$length     = strlen( $value );
		for ( $i = 0; $i < $length; $i++ ) {
			$char = $value[ $i ];
			if ( '"' === $char || "'" === $char ) {
				$end   = self::string_end( $value, $i );
				$text .= substr( $value, $i, $end - $i + 1 );
				$i     = $end;
			} elseif ( '/' === $char && '*' === ( $value[ $i + 1 ] ?? '' ) ) {
				$end     = strpos( $value, '*/', $i + 2 );
				$end     = false === $end ? $length : $end;
				$comment = substr( $value, $i + 2, $end - $i - 2 );
				if ( 1 === preg_match( '~^\s*!?\s*rtl:(?:begin:)?raw:~', $comment ) ) {
					$error = new RuntimeException( 'rtl:raw inside the value of ' . trim( substr( $part, $lead, $colon - $lead ) ) . ': put the directive before or after the declaration.' );
					throw $error;
				}
				if ( 1 === preg_match( '~^\s*!?\s*rtl:(.*)$~s', $comment, $matches ) ) {
					$directive    = self::directive( $matches[1] );
					$text        .= 'insert' === $directive['name'] ? "\0" . count( $directives ) . "\0" : '';
					$directives[] = $directive;
				} else {
					$text .= substr( $value, $i, $end + 2 - $i );
				}
				$i = $end + 1;
			} else {
				$text .= $char;
			}
		}
		return array(
			'lead'       => substr( $part, 0, $lead ),
			'property'   => trim( substr( $part, $lead, $colon - $lead ) ),
			'value'      => $text,
			'directives' => $directives,
		);
	}

	/**
	 * Reads the name and the parameter of a value directive.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Comment text after "rtl:".
	 * @return array{name: string, param: string} Name ignore, prepend, append or insert, an empty name to replace the value.
	 */
	private static function directive( string $text ): array {
		if ( 1 === preg_match( '~^(ignore|prepend|append|insert)(?::(.*))?\s*$~s', $text, $matches ) ) {
			return array(
				'name'  => $matches[1],
				'param' => $matches[2] ?? '',
			);
		}
		return array(
			'name'  => '',
			'param' => $text,
		);
	}

	/**
	 * Applies the value directives in the order of Node RTLCSS: ignore, prepend, append, insert, replace.
	 *
	 * Only the first kind that occurs takes effect; insert markers of other kinds disappear.
	 *
	 * @since 1.0.0
	 *
	 * @param list<array{name: string, param: string}> $directives Directives of the value.
	 * @param string                                   $value      Value without directives, with insert markers.
	 * @return string New value.
	 */
	private static function apply( array $directives, string $value ): string {
		$names  = array_column( $directives, 'name' );
		$insert = in_array( 'insert', $names, true ) && ! array_intersect( array( 'ignore', 'prepend', 'append' ), $names );
		$value  = (string) preg_replace_callback(
			'~\s*\x00(\d+)\x00~',
			static fn( array $marker ): string => $insert ? ' ' . trim( $directives[ (int) $marker[1] ]['param'] ) : '',
			$value
		);
		foreach ( array( 'ignore', 'prepend', 'append', 'insert' ) as $name ) {
			if ( ! in_array( $name, $names, true ) ) {
				continue;
			}
			$params = '';
			foreach ( $directives as $directive ) {
				$params .= $name === $directive['name'] ? $directive['param'] : '';
			}
			return match ( $name ) {
				'prepend' => $params . trim( $value ),
				'append'  => trim( $value ) . $params,
				default   => $value,
			};
		}
		$last = $directives[ count( $directives ) - 1 ];
		return trim( $last['param'] );
	}
}
