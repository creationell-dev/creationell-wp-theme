<?php
/**
 * Pagination of a post list per block instance.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Posts;

use WP_Query;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Numbers the paginated post lists of a request and gives each its own query variable.
 *
 * Like the core pattern query-<id>-page, list n reads its page from
 * ?creationell-list-<n>-page=<page>; other lists and the main query stay
 * where they are, and every other GET parameter stays in the links. The
 * page is read as digits only (read-only, no nonce): a sign, an exponent or
 * any other character gives page 1. The class registers no hooks and keeps
 * the counter for one request.
 *
 * @since 1.0.0
 */
final class Pagination {

	/**
	 * Pattern of the query variable; %d is the instance.
	 *
	 * @since 1.0.0
	 */
	public const QUERY_VAR = 'creationell-list-%d-page';

	/**
	 * Pages shown on each side of the current page.
	 *
	 * @since 1.0.0
	 */
	public const WINDOW = 2;

	/**
	 * Label of a gap in the page links (horizontal ellipsis).
	 *
	 * @since 1.0.0
	 */
	public const GAP = "\u{2026}";

	/**
	 * Last instance number handed out in this request.
	 *
	 * @var int
	 */
	private static int $instance = 0;

	/**
	 * Returns the number of the next paginated list of the request, from 1.
	 *
	 * @since 1.0.0
	 *
	 * @return int Instance number.
	 */
	public static function next_instance(): int {
		return ++self::$instance;
	}

	/**
	 * Starts the numbering again; for tests.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$instance = 0;
	}

	/**
	 * Returns the query variable of a list.
	 *
	 * @since 1.0.0
	 *
	 * @param int $n Instance number.
	 * @return string Query variable, e.g. creationell-list-2-page.
	 */
	public static function query_var( int $n ): string {
		return sprintf( self::QUERY_VAR, $n );
	}

	/**
	 * Returns the requested page of a list: the digits of its query variable, at least 1.
	 *
	 * @since 1.0.0
	 *
	 * @param int $n Instance number.
	 * @return int Page.
	 */
	public static function current_page( int $n ): int {
		$var = self::query_var( $n );
		$raw = isset( $_GET[ $var ] ) && is_string( $_GET[ $var ] ) ? sanitize_text_field( wp_unslash( $_GET[ $var ] ) ) : '';
		if ( 1 !== preg_match( '~^\d{1,9}$~', $raw ) ) {
			return 1;
		}
		return max( 1, absint( $raw ) );
	}

	/**
	 * Returns a page within 1 and the number of pages.
	 *
	 * The block asks again for the last page when the requested one lies past the end.
	 *
	 * @since 1.0.0
	 *
	 * @param int $page  Requested page.
	 * @param int $pages Number of pages; 0 for an empty result.
	 * @return int Page.
	 */
	public static function clamp( int $page, int $pages ): int {
		return max( 1, min( $page, $pages ) );
	}

	/**
	 * Returns the page links of a list.
	 *
	 * The first and the last page and WINDOW pages around the current page get
	 * an entry; a longer gap becomes one entry with the label GAP and no URL, a
	 * gap of one page shows that page. The current page has no URL. Page 1
	 * drops the query variable of the list.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query Query of the list, with max_num_pages and paged.
	 * @param int      $n     Instance number.
	 * @return list<array{url: ?string, label: string, current: bool}> Links; empty for one page or none.
	 */
	public static function links( WP_Query $query, int $n ): array {
		$pages = (int) $query->max_num_pages;
		if ( $pages < 2 ) {
			return array();
		}
		$paged   = $query->get( 'paged' );
		$current = self::clamp( is_numeric( $paged ) ? (int) $paged : 1, $pages );
		$shown   = array();
		for ( $page = 1; $page <= $pages; $page++ ) {
			if ( 1 === $page || $pages === $page || abs( $page - $current ) <= self::WINDOW ) {
				$shown[] = $page;
			}
		}
		$links    = array();
		$previous = 0;
		foreach ( $shown as $page ) {
			if ( 2 === $page - $previous ) {
				$links[] = self::link( $previous + 1, $current, $n );
			} elseif ( $page - $previous > 2 ) {
				$links[] = array(
					'url'     => null,
					'label'   => self::GAP,
					'current' => false,
				);
			}
			$links[]  = self::link( $page, $current, $n );
			$previous = $page;
		}
		return $links;
	}

	/**
	 * Returns the link of one page.
	 *
	 * @since 1.0.0
	 *
	 * @param int $page    Page.
	 * @param int $current Current page.
	 * @param int $n       Instance number.
	 * @return array{url: ?string, label: string, current: bool} Link; URL null for the current page.
	 */
	private static function link( int $page, int $current, int $n ): array {
		$url = null;
		if ( $page !== $current ) {
			$url = 1 === $page ? remove_query_arg( self::query_var( $n ) ) : add_query_arg( self::query_var( $n ), (string) $page );
		}
		return array(
			'url'     => $url,
			'label'   => (string) $page,
			'current' => $page === $current,
		);
	}
}
