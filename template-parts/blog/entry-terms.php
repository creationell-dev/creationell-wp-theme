<?php
/**
 * Template part: the categories and tags below a single post.
 *
 * Prints one row per list that is not empty, each list named by its label
 * (no heading, the sidebar has the headings): the categories as badges with
 * the classes of the category badge, the tags as the tag links of WordPress
 * after the filter term_links-post_tag, which gives them the classes and the
 * icon of the tag badges. The module blog prints this part into the filter
 * creationell_wp_theme_entry_terms_html.
 *
 * Arguments (raw values, escaped here): categories and tags (lists of name
 * and url), label_categories and label_tags (labels; empty gives
 * "Categories" and "Tags"), heading_ids (IDs of the labels by row, keys
 * categories and tags; a missing ID comes from wp_unique_id()), context
 * (template, for example single).
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_args = isset( $args ) && is_array( $args ) ? $args : array();

/**
 * Returns the valid entries of a list argument: name and url as strings, the url not empty.
 *
 * @since 1.0.0
 *
 * @param mixed $items List of the arguments.
 * @return list<array{name: string, url: string}> Entries.
 */
$creationell_wp_theme_entries = static function ( mixed $items ): array {
	$entries = array();
	foreach ( is_array( $items ) ? $items : array() as $item ) {
		if ( is_array( $item ) && is_string( $item['name'] ?? null ) && is_string( $item['url'] ?? null ) && '' !== $item['url'] ) {
			$entries[] = array(
				'name' => $item['name'],
				'url'  => $item['url'],
			);
		}
	}
	return $entries;
};

$creationell_wp_theme_categories = $creationell_wp_theme_entries( $creationell_wp_theme_args['categories'] ?? null );
$creationell_wp_theme_links      = array();
foreach ( $creationell_wp_theme_entries( $creationell_wp_theme_args['tags'] ?? null ) as $creationell_wp_theme_item ) {
	$creationell_wp_theme_links[] = '<a href="' . esc_url( $creationell_wp_theme_item['url'] ) . '" rel="tag">' . esc_html( $creationell_wp_theme_item['name'] ) . '</a>';
}
if ( array() !== $creationell_wp_theme_links ) {
	// The tag links of get_the_term_list(), filtered like there: the theme adds the badge classes and the icon.
	$creationell_wp_theme_links = apply_filters( 'term_links-post_tag', $creationell_wp_theme_links );
	$creationell_wp_theme_links = is_array( $creationell_wp_theme_links ) ? array_values( array_filter( $creationell_wp_theme_links, static fn( mixed $link ): bool => is_string( $link ) && '' !== trim( $link ) ) ) : array();
}
if ( array() === $creationell_wp_theme_categories && array() === $creationell_wp_theme_links ) {
	return;
}
$creationell_wp_theme_heading_ids = is_array( $creationell_wp_theme_args['heading_ids'] ?? null ) ? $creationell_wp_theme_args['heading_ids'] : array();
$creationell_wp_theme_allowed     = creationell_wp_theme_kses_allowed_svg( wp_kses_allowed_html( 'post' ) );
?>
<div class="entry-terms tags-links">
<?php
if ( array() !== $creationell_wp_theme_categories ) :
	$creationell_wp_theme_label_id = is_string( $creationell_wp_theme_heading_ids['categories'] ?? null ) && '' !== $creationell_wp_theme_heading_ids['categories'] ? $creationell_wp_theme_heading_ids['categories'] : wp_unique_id( 'creationell-theme-blog-entry-terms-categories-' );
	$creationell_wp_theme_label    = is_string( $creationell_wp_theme_args['label_categories'] ?? null ) ? trim( $creationell_wp_theme_args['label_categories'] ) : '';
	$creationell_wp_theme_label    = '' === $creationell_wp_theme_label ? __( 'Categories', 'creationell-wp-theme' ) : $creationell_wp_theme_label;

	/**
	 * Filters the CSS classes of the category badges.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 */
	$creationell_wp_theme_badge_class = apply_filters( 'creationell_wp_theme_class_badge_category', 'badge bg-primary-subtle text-primary-emphasis text-decoration-none' );
	$creationell_wp_theme_badge_class = is_string( $creationell_wp_theme_badge_class ) ? $creationell_wp_theme_badge_class : '';
	?>
	<div class="entry-terms-row"><span class="entry-terms-label h6" id="<?php echo esc_attr( $creationell_wp_theme_label_id ); ?>"><?php echo esc_html( $creationell_wp_theme_label ); ?></span>
		<ul class="list-inline" aria-labelledby="<?php echo esc_attr( $creationell_wp_theme_label_id ); ?>">
		<?php foreach ( $creationell_wp_theme_categories as $creationell_wp_theme_item ) : ?>
			<li class="list-inline-item"><a class="<?php echo esc_attr( $creationell_wp_theme_badge_class ); ?>" href="<?php echo esc_url( $creationell_wp_theme_item['url'] ); ?>"><?php echo esc_html( $creationell_wp_theme_item['name'] ); ?></a></li>
		<?php endforeach; ?>
		</ul></div>
	<?php
endif;

if ( array() !== $creationell_wp_theme_links ) :
	$creationell_wp_theme_label_id = is_string( $creationell_wp_theme_heading_ids['tags'] ?? null ) && '' !== $creationell_wp_theme_heading_ids['tags'] ? $creationell_wp_theme_heading_ids['tags'] : wp_unique_id( 'creationell-theme-blog-entry-terms-tags-' );
	$creationell_wp_theme_label    = is_string( $creationell_wp_theme_args['label_tags'] ?? null ) ? trim( $creationell_wp_theme_args['label_tags'] ) : '';
	$creationell_wp_theme_label    = '' === $creationell_wp_theme_label ? __( 'Tags', 'creationell-wp-theme' ) : $creationell_wp_theme_label;
	?>
	<div class="entry-terms-row"><span class="entry-terms-label h6 tags-heading" id="<?php echo esc_attr( $creationell_wp_theme_label_id ); ?>"><?php echo esc_html( $creationell_wp_theme_label ); ?></span>
		<ul class="list-inline" aria-labelledby="<?php echo esc_attr( $creationell_wp_theme_label_id ); ?>">
		<?php foreach ( $creationell_wp_theme_links as $creationell_wp_theme_link ) : ?>
			<li class="list-inline-item"><?php echo wp_kses( trim( $creationell_wp_theme_link ), $creationell_wp_theme_allowed ); ?></li>
		<?php endforeach; ?>
		</ul></div>
	<?php
endif;
?>
</div>
