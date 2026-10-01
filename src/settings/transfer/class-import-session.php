<?php
/**
 * Session between the preview and the apply step of an import.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

use InvalidArgumentException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Keeps an uploaded or restored file for TTL seconds under a random token, bound to the user who started the import.
 *
 * The session is a transient "creationell_wp_theme_import_<token>" with the
 * user ID, the JSON text, its SHA-256, the name (file name or backup ID) and
 * the source ("file" or "backup"). The token is 32 hex digits from
 * random_bytes(); load() checks its form before it reads anything. The data
 * is checked again (decode, migration, Transfer_File) by whoever loads it.
 *
 * @since 1.0.0
 *
 * @phpstan-type Session array{user_id: int, data: string, sha256: string, name: string, source: string}
 */
final class Import_Session {

	/**
	 * Prefix of the transient name; the token follows.
	 *
	 * @since 1.0.0
	 */
	public const PREFIX = 'creationell_wp_theme_import_';

	/**
	 * Lifetime of a session in seconds (15 minutes).
	 *
	 * @since 1.0.0
	 */
	public const TTL = 900;

	/**
	 * Sources of the file.
	 *
	 * @since 1.0.0
	 */
	public const SOURCES = array( 'file', 'backup' );

	/**
	 * Pattern of a token.
	 *
	 * @since 1.0.0
	 */
	public const TOKEN_PATTERN = '~^[0-9a-f]{32}$~D';

	/**
	 * Stores a file for the preview and returns its token.
	 *
	 * @since 1.0.0
	 *
	 * @param string $json    JSON text of the file.
	 * @param string $name    File name of the upload or ID of the backup.
	 * @param string $source  One of SOURCES.
	 * @param int    $user_id User who started the import.
	 * @return string Token of 32 hex digits.
	 * @throws InvalidArgumentException With an unknown source or a negative user ID.
	 */
	public static function store( string $json, string $name, string $source, int $user_id ): string {
		if ( ! in_array( $source, self::SOURCES, true ) ) {
			throw new InvalidArgumentException( 'Unknown import session source.' );
		}
		if ( $user_id < 0 ) {
			throw new InvalidArgumentException( 'The user ID of an import session must not be negative.' );
		}
		$token = bin2hex( random_bytes( 16 ) );
		set_transient(
			self::PREFIX . $token,
			array(
				'user_id' => $user_id,
				'data'    => $json,
				'sha256'  => hash( 'sha256', $json ),
				'name'    => $name,
				'source'  => $source,
			),
			self::TTL
		);
		return $token;
	}

	/**
	 * Returns the session of a token for the user who started it.
	 *
	 * @since 1.0.0
	 *
	 * @param string $token   Token.
	 * @param int    $user_id Current user.
	 * @return array<string, int|string> Session with user_id, data, sha256, name and source.
	 * @phpstan-return Session
	 * @throws Transfer_Exception With token_invalid for a malformed token or broken session, token_expired when none is stored, token_user_mismatch for another user.
	 */
	public static function load( string $token, int $user_id ): array {
		if ( ! self::is_token( $token ) ) {
			Transfer_Exception::raise( Transfer_Error::TOKEN_INVALID );
		}
		$session = get_transient( self::PREFIX . $token );
		if ( false === $session ) {
			Transfer_Exception::raise( Transfer_Error::TOKEN_EXPIRED );
		}
		if ( ! self::is_session( $session ) ) {
			Transfer_Exception::raise( Transfer_Error::TOKEN_INVALID );
		}
		if ( $user_id !== $session['user_id'] ) {
			Transfer_Exception::raise( Transfer_Error::TOKEN_USER_MISMATCH );
		}
		return $session;
	}

	/**
	 * Removes the session of a token; a malformed token is ignored.
	 *
	 * @since 1.0.0
	 *
	 * @param string $token Token.
	 * @return void
	 */
	public static function delete( string $token ): void {
		if ( self::is_token( $token ) ) {
			delete_transient( self::PREFIX . $token );
		}
	}

	/**
	 * Tells whether a string has the form of a token.
	 *
	 * @since 1.0.0
	 *
	 * @param string $token Token.
	 * @return bool True for 32 lower-case hex digits.
	 */
	private static function is_token( string $token ): bool {
		return 1 === preg_match( self::TOKEN_PATTERN, $token );
	}

	/**
	 * Tells whether a stored value is a complete session whose data matches its hash.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $session Stored value.
	 * @return bool True for a valid session.
	 * @phpstan-assert-if-true Session $session
	 */
	private static function is_session( mixed $session ): bool {
		return is_array( $session )
			&& is_int( $session['user_id'] ?? null )
			&& is_string( $session['data'] ?? null )
			&& is_string( $session['sha256'] ?? null )
			&& hash_equals( $session['sha256'], hash( 'sha256', $session['data'] ) )
			&& is_string( $session['name'] ?? null )
			&& in_array( $session['source'] ?? null, self::SOURCES, true );
	}
}
