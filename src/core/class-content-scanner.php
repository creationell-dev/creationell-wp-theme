<?php
/**
 * Counts the contents that use blocks.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Core;

use wpdb;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Finds the posts and block widgets whose content holds one of the given blocks.
 *
 * The needle of a block is the start of its comment, "<!-- wp:<name> ", which
 * matches the block with and without attributes and the self-closing form, but
 * no other block whose name starts the same. One query counts the posts per
 * post type and kind (live or stored), a second one reads up to 20 live
 * examples; block widgets are read from the option widget_block. The counts
 * are contents, not occurrences: a post with the block twice counts once.
 *
 * The module switcher counts the blocks of a module before it goes off; the
 * scanner itself knows nothing of modules, so other parts may count any block.
 *
 * Example:
 *
 *     $result = ( new Content_Scanner() )->count_blocks( array( 'creationell-theme/card' ) );
 *     if ( $result->total_live() > 0 ) {
 *         // Ask before removing the block.
 *     }
 *
 * @since 1.0.0
 */
final class Content_Scanner {

	/**
	 * Maximum number of examples.
	 *
	 * @since 1.0.0
	 */
	public const EXAMPLES = 20;

	/**
	 * Locations after the post types, in display order; other post types count as "post_type:<type>".
	 *
	 * @since 1.0.0
	 */
	public const LOCATIONS = array( 'wp_block', 'wp_template_part', 'wp_template', 'wp_navigation', 'revision', 'widget_block' );

	/**
	 * Post types with a location of their own.
	 *
	 * @since 1.0.0
	 */
	private const OWN_POST_TYPES = array( 'wp_block', 'wp_template_part', 'wp_template', 'wp_navigation', 'revision' );

	/**
	 * Database; null uses the global $wpdb.
	 *
	 * @var wpdb|null
	 */
	private ?wpdb $db;

	/**
	 * Takes the database; without one the scanner uses the global $wpdb when it first queries.
	 *
	 * @since 1.0.0
	 *
	 * @param wpdb|null $db Database.
	 */
	public function __construct( ?wpdb $db = null ) {
		$this->db = $db;
	}

	/**
	 * Counts the contents that use one of the blocks.
	 *
	 * Empty names and duplicates are dropped; without names nothing is queried.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $names Full block names, e.g. creationell-theme/card.
	 * @return Scan_Result Counts per location and examples.
	 */
	public function count_blocks( array $names ): Scan_Result {
		$names = array_values( array_unique( array_filter( $names, static fn( mixed $name ): bool => is_string( $name ) && '' !== trim( $name ) ) ) );
		if ( array() === $names ) {
			return new Scan_Result( array(), array(), array() );
		}
		$needles = array_map( static fn( string $name ): string => '<!-- wp:' . $name . ' ', $names );
		$wpdb    = $this->db();
		$likes   = array();
		foreach ( $needles as $needle ) {
			$likes[] = '%' . $wpdb->esc_like( $needle ) . '%';
		}

		$counts = array();
		$found  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_type, (post_type = 'revision' OR post_status IN ('trash', 'auto-draft')) AS is_stored, COUNT(*) AS contents FROM %i WHERE (" . implode( ' OR ', array_fill( 0, count( $needles ), 'post_content LIKE %s' ) ) . ') GROUP BY post_type, is_stored',
				$wpdb->posts,
				...$likes
			),
			ARRAY_A
		);
		foreach ( is_array( $found ) ? $found : array() as $row ) {
			$type = $row['post_type'] ?? null;
			if ( ! is_string( $type ) ) {
				continue;
			}
			$kind                          = 'revision' === $type || 1 === absint( $row['is_stored'] ?? 0 ) ? 'stored' : 'live';
			$location                      = self::location( $type );
			$counts[ $location ]         ??= array(
				'live'   => 0,
				'stored' => 0,
			);
			$counts[ $location ][ $kind ] += absint( $row['contents'] ?? 0 );
		}
		$live_posts = array_sum( array_column( $counts, 'live' ) );

		$widgets = self::count_widgets( $needles );
		if ( $widgets > 0 ) {
			$counts['widget_block'] = array(
				'live'   => $widgets,
				'stored' => 0,
			);
		}

		$examples = array();
		if ( $live_posts > 0 ) {
			$found = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT ID, post_title, post_type FROM %i WHERE (' . implode( ' OR ', array_fill( 0, count( $needles ), 'post_content LIKE %s' ) ) . ") AND post_type <> 'revision' AND post_status NOT IN ('trash', 'auto-draft', 'inherit') ORDER BY post_modified DESC LIMIT 20",
					$wpdb->posts,
					...$likes
				),
				ARRAY_A
			);
			foreach ( array_slice( is_array( $found ) ? $found : array(), 0, self::EXAMPLES ) as $row ) {
				$id    = absint( $row['ID'] ?? 0 );
				$type  = $row['post_type'] ?? null;
				$title = $row['post_title'] ?? null;
				if ( 0 === $id || ! is_string( $type ) ) {
					continue;
				}
				$examples[] = array(
					'id'       => $id,
					'title'    => is_string( $title ) ? $title : '',
					'location' => self::location( $type ),
					'edit_url' => get_edit_post_link( $id, 'raw' ) ?? '',
				);
			}
		}

		return new Scan_Result( $names, self::rows( $counts ), $examples );
	}

	/**
	 * Returns the location of a post type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type Post type.
	 * @return string Location.
	 */
	private static function location( string $type ): string {
		return in_array( $type, self::OWN_POST_TYPES, true ) ? $type : 'post_type:' . $type;
	}

	/**
	 * Counts the block widgets whose content holds a needle.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $needles Needles.
	 * @return int Widgets.
	 */
	private static function count_widgets( array $needles ): int {
		$widgets = get_option( 'widget_block', array() );
		$count   = 0;
		foreach ( is_array( $widgets ) ? $widgets : array() as $widget ) {
			$content = is_array( $widget ) ? ( $widget['content'] ?? null ) : null;
			if ( ! is_string( $content ) ) {
				continue;
			}
			foreach ( $needles as $needle ) {
				if ( str_contains( $content, $needle ) ) {
					++$count;
					break;
				}
			}
		}
		return $count;
	}

	/**
	 * Puts the counts in display order: post types by name, then the other locations.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, array{live: int, stored: int}> $counts Counts by location.
	 * @return array<int, array{location: string, live: int, stored: int}> Rows.
	 * @phpstan-return list<array{location: string, live: int, stored: int}>
	 */
	private static function rows( array $counts ): array {
		$rank = static function ( string $location ): array {
			$index = array_search( $location, self::LOCATIONS, true );
			return false === $index ? array( 0, $location ) : array( 1 + $index, '' );
		};
		uksort( $counts, static fn( string $a, string $b ): int => $rank( $a ) <=> $rank( $b ) );
		$rows = array();
		foreach ( $counts as $location => $count ) {
			$rows[] = array(
				'location' => $location,
				'live'     => $count['live'],
				'stored'   => $count['stored'],
			);
		}
		return $rows;
	}

	/**
	 * Returns the database.
	 *
	 * @since 1.0.0
	 *
	 * @return wpdb Database.
	 */
	private function db(): wpdb {
		if ( null !== $this->db ) {
			return $this->db;
		}
		global $wpdb;
		$this->db = $wpdb;
		return $wpdb;
	}
}
