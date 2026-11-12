<?php
/**
 * Cookie names of the consent services.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Consent;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Reads the cookie names of the consent services: display name, regex, safe regex subset, deny list and domains.
 *
 * A cookie of a service matches by name ("exact"), by the start of the name
 * ("prefix") or by a regular expression ("regex"). A regex must stay inside a
 * small subset that PHP and JavaScript read the same way (is_safe_regex()).
 * The deny list protects the cookies of WordPress, the consent banner, the
 * shop and WPML: outside the category "necessary" no service cookie may hit
 * it, because revoking consent deletes the cookies of a category.
 *
 * Example:
 *
 *     Consent_Cookie_Name::display( '_pk_id.', 'prefix' ); // "_pk_id.…"
 *     Consent_Cookie_Name::regex( '_pk_id.', 'prefix' );   // "^_pk_id\."
 *
 * @since 1.0.0
 */
final class Consent_Cookie_Name {

	/**
	 * Match modes of a cookie.
	 *
	 * @since 1.0.0
	 */
	public const MATCHES = array( 'exact', 'prefix', 'regex' );

	/**
	 * Protected cookie names as PCRE bodies without delimiters.
	 *
	 * @since 1.0.0
	 */
	public const DENYLIST = array(
		'^creationell_consent$',
		'^cc_cookie$',
		'^wordpress_',
		'^wp-settings',
		'^comment_author',
		'^wp-postpass',
		'^PHPSESSID$',
		'^woocommerce_',
		'^wp_woocommerce_session_',
		'^mtm_consent',
		'^mtm_cookie_consent$',
		'^wp-wpml_current_language$',
	);

	/**
	 * Public suffixes of two labels at which parent_domain() stops.
	 *
	 * @since 1.0.0
	 */
	public const PUBLIC_SUFFIXES = array(
		'co.uk',
		'com.au',
		'co.jp',
		'com.br',
		'gv.at',
		'org.uk',
		'gov.uk',
		'net.au',
		'org.au',
		'ne.jp',
		'or.jp',
		'co.at',
		'or.at',
		'co.nz',
		'co.za',
		'com.mx',
		'com.tr',
	);

	/**
	 * Real cookie names per deny-list entry, for the simulation of prefixes and regular expressions.
	 *
	 * @var array<string, list<string>>
	 */
	private const SAMPLES = array(
		'^creationell_consent$'      => array( 'creationell_consent' ),
		'^cc_cookie$'                => array( 'cc_cookie' ),
		'^wordpress_'                => array( 'wordpress_5f4dcc3b5aa765d61d8327deb882cf99', 'wordpress_logged_in_5f4dcc3b5aa765d61d8327deb882cf99', 'wordpress_sec_5f4dcc3b5aa765d61d8327deb882cf99' ),
		'^wp-settings'               => array( 'wp-settings-1', 'wp-settings-time-1' ),
		'^comment_author'            => array( 'comment_author_5f4dcc3b5aa765d61d8327deb882cf99', 'comment_author_email_5f4dcc3b5aa765d61d8327deb882cf99', 'comment_author_url_5f4dcc3b5aa765d61d8327deb882cf99' ),
		'^wp-postpass'               => array( 'wp-postpass_5f4dcc3b5aa765d61d8327deb882cf99' ),
		'^PHPSESSID$'                => array( 'PHPSESSID' ),
		'^woocommerce_'              => array( 'woocommerce_cart_hash', 'woocommerce_items_in_cart', 'woocommerce_recently_viewed' ),
		'^wp_woocommerce_session_'   => array( 'wp_woocommerce_session_5f4dcc3b5aa765d61d8327deb882cf99' ),
		'^mtm_consent'               => array( 'mtm_consent', 'mtm_consent_removed' ),
		'^mtm_cookie_consent$'       => array( 'mtm_cookie_consent' ),
		'^wp-wpml_current_language$' => array( 'wp-wpml_current_language' ),
	);

	/**
	 * Longest accepted regular expression.
	 *
	 * @var int
	 */
	private const REGEX_MAX = 200;

	/**
	 * Returns the name as shown in the dialog.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name  Name or pattern.
	 * @param string $mode  Match mode.
	 * @return string The name; for "prefix" followed by "…".
	 */
	public static function display( string $name, string $mode ): string {
		return 'prefix' === $mode ? $name . "\u{2026}" : $name;
	}

	/**
	 * Returns the PCRE body (without delimiters) that matches the cookie names.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name  Name or pattern.
	 * @param string $mode  Match mode.
	 * @return string|null Body for "prefix" and "regex", null for "exact".
	 */
	public static function regex( string $name, string $mode ): ?string {
		if ( 'prefix' === $mode ) {
			return '^' . preg_quote( $name, '/' );
		}
		return 'regex' === $mode ? $name : null;
	}

	/**
	 * Tells whether a pattern stays inside the subset that PHP and JavaScript read the same way.
	 *
	 * Every top-level branch starts with "^" and two required literal
	 * characters. Allowed are the literals A-Z, a-z, 0-9, "_" and "-", the
	 * escapes "\." and "\d", ".", classes "[…]" without nested "[" or POSIX
	 * classes, one level of groups "( )", "|", "^", "$" and the quantifiers
	 * "*", "+" and "?" after a literal, ".", an escape or a class. Never a
	 * quantifier after a group or after another quantifier, at most one
	 * unbounded quantifier ("*" or "+"), at most two "?" and at most one
	 * group with "|" per top-level branch (no nested, adjacent or chained
	 * repetition or alternation that backtracks without end), never "(?",
	 * "{", "}" or "/".
	 *
	 * @since 1.0.0
	 *
	 * @param string $pattern PCRE body without delimiters.
	 * @return bool True when safe and compiling in PHP.
	 */
	public static function is_safe_regex( string $pattern ): bool {
		$length = strlen( $pattern );
		if ( $length > self::REGEX_MAX || ! self::anchored_at( $pattern, 0 ) ) {
			return false;
		}
		foreach ( array( '(?', '{', '}', '/' ) as $forbidden ) {
			if ( str_contains( $pattern, $forbidden ) ) {
				return false;
			}
		}

		// Kind of the previous token: atom, or other (start, anchor, "|", "(", ")", quantifier).
		$previous  = 'other';
		$depth     = 0;
		$unbounded = 0;
		$optional  = 0;
		$choices   = 0;
		$choice    = false;
		for ( $i = 0; $i < $length; $i++ ) {
			$char = $pattern[ $i ];
			if ( 1 === preg_match( '~^[A-Za-z0-9_-]$~', $char ) || '.' === $char ) {
				$previous = 'atom';
			} elseif ( '\\' === $char ) {
				$next = $pattern[ $i + 1 ] ?? '';
				if ( '.' !== $next && 'd' !== $next ) {
					return false;
				}
				++$i;
				$previous = 'atom';
			} elseif ( '[' === $char ) {
				$end = self::class_end( $pattern, $i );
				if ( null === $end ) {
					return false;
				}
				$i        = $end;
				$previous = 'atom';
			} elseif ( '(' === $char ) {
				if ( 0 !== $depth ) {
					return false;
				}
				$depth    = 1;
				$choice   = false;
				$previous = 'other';
			} elseif ( ')' === $char ) {
				if ( 1 !== $depth || ( $choice && ++$choices > 1 ) ) {
					return false;
				}
				$depth    = 0;
				$previous = 'other';
			} elseif ( '*' === $char || '+' === $char || '?' === $char ) {
				if ( 'atom' !== $previous || ( '?' === $char ? ++$optional > 2 : ++$unbounded > 1 ) ) {
					return false;
				}
				$previous = 'other';
			} elseif ( '|' === $char ) {
				if ( 0 === $depth ) {
					if ( ! self::anchored_at( $pattern, $i + 1 ) ) {
						return false;
					}
					$unbounded = 0;
					$optional  = 0;
					$choices   = 0;
				} else {
					$choice = true;
				}
				$previous = 'other';
			} elseif ( '^' === $char || '$' === $char ) {
				$previous = 'other';
			} else {
				return false;
			}
		}
		if ( 0 !== $depth ) {
			return false;
		}
		// The structural checks are meant to leave only patterns that compile; the call confirms it.
		return false !== preg_match( '/' . $pattern . '/', '' );
	}

	/**
	 * Tells whether a cookie of this name and mode would hit a protected cookie.
	 *
	 * "exact" checks the name against the entries. "prefix" and "regex" fail
	 * closed: a prefix, and the literal start of every top-level branch of a
	 * regex, count as a hit unless they diverge from every entry, that is,
	 * differ from its text at some position both have. A prefix or literal
	 * start that is a prefix of an entry's text hits; so does one that starts
	 * with the text of an open entry (one without "$"). A regex also hits when
	 * it matches a real cookie name of the samples, when a branch does not
	 * start with "^", and when it is outside the safe subset of is_safe_regex()
	 * (which never accepts a pattern that does not compile). So "^wo(r)dpress_a" hits
	 * ("wo" leads into "wordpress_"), "^comment_x" does not.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name  Name or pattern.
	 * @param string $mode  Match mode.
	 * @return bool True when a protected cookie could be hit.
	 */
	public static function hits_denylist( string $name, string $mode ): bool {
		if ( 'regex' === $mode ) {
			// Only a pattern of the safe subset is simulated; any other fails closed without being compiled.
			if ( ! self::is_safe_regex( $name ) ) {
				return true;
			}
			foreach ( self::SAMPLES as $samples ) {
				foreach ( $samples as $sample ) {
					if ( 1 === preg_match( '/' . $name . '/', $sample ) ) {
						return true;
					}
				}
			}
			foreach ( self::branches( $name ) as $branch ) {
				if ( ! str_starts_with( $branch, '^' ) || self::leads_into_entry( self::literal_start( $branch ) ) ) {
					return true;
				}
			}
			return false;
		}
		if ( 'prefix' === $mode ) {
			return self::leads_into_entry( $name );
		}
		return self::matches_entry( $name );
	}

	/**
	 * Returns the parent domain of a host: the host without its first label.
	 *
	 * @since 1.0.0
	 *
	 * @param string $host Host, may carry a port.
	 * @return string|null Parent domain; null when fewer than two labels remain, for a public suffix, an IP address and localhost.
	 */
	public static function parent_domain( string $host ): ?string {
		$host = strtolower( trim( $host ) );
		if ( str_starts_with( $host, '[' ) ) {
			return null;
		}
		$host = rtrim( (string) preg_replace( '~:\d+$~', '', $host ), '.' );
		if ( '' === $host || 'localhost' === $host || false !== filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return null;
		}
		$labels = explode( '.', $host );
		array_shift( $labels );
		if ( count( $labels ) < 2 || in_array( '', $labels, true ) ) {
			return null;
		}
		$parent = implode( '.', $labels );
		return in_array( $parent, self::PUBLIC_SUFFIXES, true ) ? null : $parent;
	}

	/**
	 * Tells whether a host lies in a cookie domain.
	 *
	 * @since 1.0.0
	 *
	 * @param string $host   Host without port, as wp_parse_url( …, PHP_URL_HOST ) gives it.
	 * @param string $domain Cookie domain; leading dots are ignored.
	 * @return string|null The lower-case domain without leading dots when the host is it or ends with "." and it; otherwise null.
	 */
	public static function host_matches( string $host, string $domain ): ?string {
		$host   = strtolower( $host );
		$domain = ltrim( strtolower( $domain ), '.' );
		if ( '' === $domain ) {
			return null;
		}
		return $host === $domain || str_ends_with( $host, '.' . $domain ) ? $domain : null;
	}

	/**
	 * Tells whether "^" and two required literal characters start the pattern at an offset.
	 *
	 * @since 1.0.0
	 *
	 * @param string $pattern Pattern.
	 * @param int    $offset  Offset of the "^".
	 * @return bool True when anchored.
	 */
	private static function anchored_at( string $pattern, int $offset ): bool {
		if ( '^' !== ( $pattern[ $offset ] ?? '' ) ) {
			return false;
		}
		$i = $offset + 1;
		for ( $count = 0; $count < 2; $count++ ) {
			$char = $pattern[ $i ] ?? '';
			if ( '\\' === $char && '.' === ( $pattern[ $i + 1 ] ?? '' ) ) {
				$i += 2;
			} elseif ( '' !== $char && 1 === preg_match( '~^[A-Za-z0-9_-]$~', $char ) ) {
				++$i;
			} else {
				return false;
			}
		}
		// A quantifier that makes the second literal optional leaves only one required literal.
		$next = $pattern[ $i ] ?? '';
		return '?' !== $next && '*' !== $next;
	}

	/**
	 * Returns the offset of the "]" that closes a class, or null for a class outside the subset.
	 *
	 * Inside the class: printable ASCII, the escapes "\." and "\d", no "[".
	 * The first character (after an optional "^") is neither "]" nor one of
	 * ":", "." and "=", which PCRE reads as the start of a POSIX class, a
	 * collating element or an equivalence class. A "-" is literal first and
	 * last; elsewhere it must join two plain characters in ascending order.
	 *
	 * @since 1.0.0
	 *
	 * @param string $pattern Pattern.
	 * @param int    $start   Offset of the "[".
	 * @return int|null Offset of the closing "]".
	 */
	private static function class_end( string $pattern, int $start ): ?int {
		$i = $start + 1;
		if ( '^' === ( $pattern[ $i ] ?? '' ) ) {
			++$i;
		}
		// An empty class differs between PHP ("[]a]") and JavaScript ("[]" matches nothing);
		// "[:", "[." and "[=" open POSIX syntax that PCRE refuses outside a class.
		if ( in_array( $pattern[ $i ] ?? '', array( ']', ':', '.', '=' ), true ) ) {
			return null;
		}
		$first  = $i;
		$low    = null;
		$length = strlen( $pattern );
		for ( ; $i < $length; $i++ ) {
			$char = $pattern[ $i ];
			if ( ']' === $char ) {
				return $i;
			}
			if ( '[' === $char || ord( $char ) < 0x20 || ord( $char ) > 0x7E ) {
				return null;
			}
			if ( '\\' === $char ) {
				$next = $pattern[ $i + 1 ] ?? '';
				if ( '.' !== $next && 'd' !== $next ) {
					return null;
				}
				++$i;
				$low = null;
			} elseif ( '-' === $char && $i !== $first && ']' !== ( $pattern[ $i + 1 ] ?? '' ) ) {
				$high = $pattern[ $i + 1 ] ?? '';
				if ( null === $low || '' === $high || str_contains( '\\[-', $high ) || ord( $high ) < 0x20 || ord( $high ) > 0x7E || ord( $low ) > ord( $high ) ) {
					return null;
				}
				++$i;
				$low = null;
			} else {
				$low = '-' === $char ? null : $char;
			}
		}
		return null;
	}

	/**
	 * Returns the literal characters after the leading "^" of a pattern; "\." reads as ".".
	 *
	 * A literal followed by "?" or "*" is optional and ends the run before it;
	 * one followed by "+" ends the run after it. "\d", ".", a class, a group,
	 * "|" and "$" end the run.
	 *
	 * @since 1.0.0
	 *
	 * @param string $pattern Pattern.
	 * @return string Literal start; empty without a leading "^".
	 */
	private static function literal_start( string $pattern ): string {
		if ( ! str_starts_with( $pattern, '^' ) ) {
			return '';
		}
		$run    = '';
		$length = strlen( $pattern );
		for ( $i = 1; $i < $length; $i++ ) {
			$char = $pattern[ $i ];
			if ( '\\' === $char && '.' === ( $pattern[ $i + 1 ] ?? '' ) ) {
				$literal = '.';
				$width   = 2;
			} elseif ( 1 === preg_match( '~^[A-Za-z0-9_-]$~', $char ) ) {
				$literal = $char;
				$width   = 1;
			} else {
				break;
			}
			$next = $pattern[ $i + $width ] ?? '';
			if ( '?' === $next || '*' === $next ) {
				break;
			}
			$run .= $literal;
			$i   += $width - 1;
		}
		return $run;
	}

	/**
	 * Splits a pattern at its top-level "|", outside groups, classes and escapes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $pattern Pattern.
	 * @return array<int, string> Branches; one for a pattern without top-level "|".
	 */
	private static function branches( string $pattern ): array {
		$branches = array();
		$current  = '';
		$depth    = 0;
		$length   = strlen( $pattern );
		for ( $i = 0; $i < $length; $i++ ) {
			$char = $pattern[ $i ];
			if ( '\\' === $char ) {
				$current .= $char . ( $pattern[ $i + 1 ] ?? '' );
				++$i;
			} elseif ( '[' === $char ) {
				// A "]" right after "[" or "[^" is a literal in PCRE.
				$end = $i + 1;
				if ( '^' === ( $pattern[ $end ] ?? '' ) ) {
					++$end;
				}
				if ( ']' === ( $pattern[ $end ] ?? '' ) ) {
					++$end;
				}
				while ( $end < $length && ']' !== $pattern[ $end ] ) {
					$end += '\\' === $pattern[ $end ] ? 2 : 1;
				}
				$current .= substr( $pattern, $i, $end - $i + 1 );
				$i        = $end;
			} elseif ( '(' === $char ) {
				++$depth;
				$current .= $char;
			} elseif ( ')' === $char ) {
				$depth    = max( 0, $depth - 1 );
				$current .= $char;
			} elseif ( '|' === $char && 0 === $depth ) {
				$branches[] = $current;
				$current    = '';
			} else {
				$current .= $char;
			}
		}
		$branches[] = $current;
		return $branches;
	}

	/**
	 * Tells whether a fixed start of cookie names has not diverged from a deny-list entry.
	 *
	 * True when the start is a prefix of an entry's text (an empty start
	 * always), or when it begins with the text of an open entry (one without
	 * "$"). The sample names are not needed: every sample begins with its
	 * entry's text.
	 *
	 * @since 1.0.0
	 *
	 * @param string $start Fixed start: a prefix or the literal start of a regex branch.
	 * @return bool True on a possible hit.
	 */
	private static function leads_into_entry( string $start ): bool {
		foreach ( self::DENYLIST as $entry ) {
			$open = ! str_ends_with( $entry, '$' );
			$text = rtrim( substr( $entry, 1 ), '$' );
			if ( str_starts_with( $text, $start ) || ( $open && str_starts_with( $start, $text ) ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Tells whether a name matches one of the deny-list entries.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Name.
	 * @return bool True on a match.
	 */
	private static function matches_entry( string $name ): bool {
		if ( '' === $name ) {
			return false;
		}
		foreach ( self::DENYLIST as $entry ) {
			if ( 1 === preg_match( '/' . $entry . '/', $name ) ) {
				return true;
			}
		}
		return false;
	}
}
