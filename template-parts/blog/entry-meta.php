<?php
/**
 * Template part: the meta line of a single post with date, reading time, author and comments.
 *
 * Prints the parts in the order given, separated by a middle dot that
 * screen readers skip and a visually hidden comma that gives a pause. The
 * date and the author come from the template tags creationell_wp_theme_date()
 * and creationell_wp_theme_author(), so their overrides and filters apply;
 * the comment link points to the comments of the post, without an icon. A
 * part that prints nothing drops with its separator; without parts the
 * part prints nothing. The module blog prints this part into the filter
 * creationell_wp_theme_entry_meta_html.
 *
 * Arguments (raw values, escaped here): parts (list of date, reading_time,
 * author and comments, in the order to print), minutes (reading time, at
 * least 1), comments_number (number of comments), context (template, for
 * example single).
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_args     = isset( $args ) && is_array( $args ) ? $args : array();
$creationell_wp_theme_parts    = is_array( $creationell_wp_theme_args['parts'] ?? null ) ? $creationell_wp_theme_args['parts'] : array();
$creationell_wp_theme_minutes  = max( 1, (int) ( $creationell_wp_theme_args['minutes'] ?? 1 ) );
$creationell_wp_theme_comments = max( 0, (int) ( $creationell_wp_theme_args['comments_number'] ?? 0 ) );
$creationell_wp_theme_items    = array();

foreach ( $creationell_wp_theme_parts as $creationell_wp_theme_part ) {
	switch ( $creationell_wp_theme_part ) {
		case 'date':
			ob_start();
			creationell_wp_theme_date();
			$creationell_wp_theme_items[] = (string) ob_get_clean();
			break;
		case 'reading_time':
			/* translators: %s: minutes it takes to read the post. */
			$creationell_wp_theme_items[] = '<span class="reading-time">' . esc_html( sprintf( _n( '%s minute reading time', '%s minutes reading time', $creationell_wp_theme_minutes, 'creationell-wp-theme' ), number_format_i18n( $creationell_wp_theme_minutes ) ) ) . '</span>';
			break;
		case 'author':
			ob_start();
			creationell_wp_theme_author();
			$creationell_wp_theme_items[] = (string) ob_get_clean();
			break;
		case 'comments':
			/* translators: %s: number of comments. */
			$creationell_wp_theme_items[] = '<a class="comments-link" href="#comments">' . esc_html( sprintf( _n( '%s comment', '%s comments', $creationell_wp_theme_comments, 'creationell-wp-theme' ), number_format_i18n( $creationell_wp_theme_comments ) ) ) . '</a>';
			break;
	}
}
$creationell_wp_theme_items = array_values( array_filter( $creationell_wp_theme_items, static fn( string $item ): bool => '' !== trim( $item ) ) );
if ( array() === $creationell_wp_theme_items ) {
	return;
}
/* translators: used between list items, there is a space after the comma */
$creationell_wp_theme_sep = '<span class="entry-meta-sep" aria-hidden="true">·</span><span class="visually-hidden">' . esc_html__( ', ', 'creationell-wp-theme' ) . '</span>';
?>
<p class="entry-meta"><small class="text-body-secondary"><?php echo wp_kses( implode( $creationell_wp_theme_sep, $creationell_wp_theme_items ), creationell_wp_theme_kses_allowed_svg( wp_kses_allowed_html( 'post' ) ) ); ?></small></p>
