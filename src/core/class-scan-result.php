<?php
/**
 * Result of a content scan.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Tells how many contents use the searched blocks, per location, with examples.
 *
 * A content counts once, however often it holds a block. Live contents are
 * the ones a visitor or an editor may still see; stored contents are
 * revisions, posts in the trash and auto drafts. Locations: "post_type:<type>"
 * for every post type without its own location, "wp_block", "wp_template_part",
 * "wp_template", "wp_navigation", "revision" and "widget_block".
 *
 * @since 1.0.0
 */
final class Scan_Result {

	/**
	 * Keeps the fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>                                                            $needles  Searched block names, e.g. creationell-theme/card.
	 * @param array<int, array{location: string, live: int, stored: int}>                   $rows     Counts per location; only locations with contents.
	 * @param array<int, array{id: int, title: string, location: string, edit_url: string}> $examples Up to 20 live posts, newest first; the edit URL is raw and may be empty.
	 * @phpstan-param list<string> $needles
	 * @phpstan-param list<array{location: string, live: int, stored: int}> $rows
	 * @phpstan-param list<array{id: int, title: string, location: string, edit_url: string}> $examples
	 */
	public function __construct(
		public readonly array $needles,
		public readonly array $rows,
		public readonly array $examples,
	) {
	}

	/**
	 * Returns the number of live contents over all locations.
	 *
	 * @since 1.0.0
	 *
	 * @return int Live contents.
	 */
	public function total_live(): int {
		return array_sum( array_column( $this->rows, 'live' ) );
	}

	/**
	 * Returns the number of stored contents over all locations.
	 *
	 * @since 1.0.0
	 *
	 * @return int Stored contents.
	 */
	public function total_stored(): int {
		return array_sum( array_column( $this->rows, 'stored' ) );
	}
}
