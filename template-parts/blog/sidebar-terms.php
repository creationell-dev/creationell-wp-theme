<?php
/**
 * Template part: the categories and tags at the top of the blog sidebar.
 *
 * Prints one section per list that is not empty, each named by its heading:
 * the categories as a nested list group with the number of posts, the tags
 * as badge links with the classes and the icon of the tag badges. The current
 * term carries aria-current. The module blog prints this part on the action
 * creationell_wp_theme_before_sidebar_widgets.
 *
 * Arguments (raw values, escaped here): categories and tags (entries of
 * Term_Lists: term, url, count, current, children), counts (bool, whether
 * the numbers show; default true), label_categories and label_tags (headings;
 * empty gives "Categories" and "Tags"), heading_ids (IDs of the headings by
 * section, keys categories and tags; a missing ID comes from wp_unique_id()).
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_args       = isset( $args ) && is_array( $args ) ? $args : array();
$creationell_wp_theme_categories = is_array( $creationell_wp_theme_args['categories'] ?? null ) ? $creationell_wp_theme_args['categories'] : array();
$creationell_wp_theme_tags       = is_array( $creationell_wp_theme_args['tags'] ?? null ) ? $creationell_wp_theme_args['tags'] : array();
if ( array() === $creationell_wp_theme_categories && array() === $creationell_wp_theme_tags ) {
	return;
}
$creationell_wp_theme_counts      = false !== ( $creationell_wp_theme_args['counts'] ?? true );
$creationell_wp_theme_heading_ids = is_array( $creationell_wp_theme_args['heading_ids'] ?? null ) ? $creationell_wp_theme_args['heading_ids'] : array();

/**
 * Returns the text of a number of posts, for example "4 posts".
 *
 * @since 1.0.0
 *
 * @param int $count Number of posts.
 * @return string Text.
 */
$creationell_wp_theme_count_text = static function ( int $count ): string {
	/* translators: %s: number of posts. */
	return sprintf( _n( '%s post', '%s posts', $count, 'creationell-wp-theme' ), number_format_i18n( $count ) );
};

/**
 * Prints the entries of one level of the categories, with their children as a nested list.
 *
 * @since 1.0.0
 *
 * @param array<int, mixed> $items Entries of Term_Lists.
 * @return void
 */
$creationell_wp_theme_category_items = static function ( array $items ) use ( &$creationell_wp_theme_category_items, $creationell_wp_theme_counts, $creationell_wp_theme_count_text ): void {
	foreach ( $items as $item ) {
		if ( ! is_array( $item ) || ! ( $item['term'] ?? null ) instanceof WP_Term || ! is_string( $item['url'] ?? null ) ) {
			continue;
		}
		$current  = in_array( $item['current'] ?? '', array( 'page', 'true' ), true ) ? $item['current'] : '';
		$count    = (int) ( $item['count'] ?? 0 );
		$children = is_array( $item['children'] ?? null ) ? $item['children'] : array();
		?>
		<li class="list-group-item">
			<a href="<?php echo esc_url( $item['url'] ); ?>"<?php echo '' === $current ? '' : ' aria-current="' . esc_attr( $current ) . '"'; ?>><?php echo esc_html( $item['term']->name ); ?></a>
			<?php if ( $creationell_wp_theme_counts ) : ?>
			<span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis"><span aria-hidden="true"><?php echo esc_html( number_format_i18n( $count ) ); ?></span><span class="visually-hidden"><?php echo esc_html( $creationell_wp_theme_count_text( $count ) ); ?></span></span>
			<?php endif; ?>
			<?php if ( array() !== $children ) : ?>
			<ul class="list-group list-group-flush mt-2 ps-3">
				<?php $creationell_wp_theme_category_items( $children ); ?>
			</ul>
			<?php endif; ?>
		</li>
		<?php
	}
};

if ( array() !== $creationell_wp_theme_categories ) :
	$creationell_wp_theme_heading_id = is_string( $creationell_wp_theme_heading_ids['categories'] ?? null ) && '' !== $creationell_wp_theme_heading_ids['categories'] ? $creationell_wp_theme_heading_ids['categories'] : wp_unique_id( 'creationell-theme-blog-terms-categories-' );
	$creationell_wp_theme_label      = is_string( $creationell_wp_theme_args['label_categories'] ?? null ) ? trim( $creationell_wp_theme_args['label_categories'] ) : '';
	$creationell_wp_theme_label      = '' === $creationell_wp_theme_label ? __( 'Categories', 'creationell-wp-theme' ) : $creationell_wp_theme_label;
	?>
<section class="widget blog-terms blog-terms-categories mb-4" aria-labelledby="<?php echo esc_attr( $creationell_wp_theme_heading_id ); ?>">
	<h2 id="<?php echo esc_attr( $creationell_wp_theme_heading_id ); ?>" class="widget-title h5"><?php echo esc_html( $creationell_wp_theme_label ); ?></h2>
	<ul class="list-group list-group-flush">
		<?php $creationell_wp_theme_category_items( $creationell_wp_theme_categories ); ?>
	</ul>
</section>
	<?php
endif;

if ( array() !== $creationell_wp_theme_tags ) :
	$creationell_wp_theme_heading_id = is_string( $creationell_wp_theme_heading_ids['tags'] ?? null ) && '' !== $creationell_wp_theme_heading_ids['tags'] ? $creationell_wp_theme_heading_ids['tags'] : wp_unique_id( 'creationell-theme-blog-terms-tags-' );
	$creationell_wp_theme_label      = is_string( $creationell_wp_theme_args['label_tags'] ?? null ) ? trim( $creationell_wp_theme_args['label_tags'] ) : '';
	$creationell_wp_theme_label      = '' === $creationell_wp_theme_label ? __( 'Tags', 'creationell-wp-theme' ) : $creationell_wp_theme_label;

	/**
	 * Filters the CSS classes of the tag badges.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 */
	$creationell_wp_theme_badge_class = apply_filters( 'creationell_wp_theme_class_badge_tag', 'badge bg-primary-subtle text-primary-emphasis text-decoration-none' );
	$creationell_wp_theme_badge_class = is_string( $creationell_wp_theme_badge_class ) ? $creationell_wp_theme_badge_class : '';

	/**
	 * Filters whether tag badges show an icon.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $show Whether to show the icon.
	 */
	$creationell_wp_theme_icon = apply_filters( 'creationell_wp_theme_show_tag_icon', true ) ? creationell_wp_theme_icon( 'tag', false ) . ' ' : '';
	?>
<section class="widget blog-terms blog-terms-tags mb-4" aria-labelledby="<?php echo esc_attr( $creationell_wp_theme_heading_id ); ?>">
	<h2 id="<?php echo esc_attr( $creationell_wp_theme_heading_id ); ?>" class="widget-title h5"><?php echo esc_html( $creationell_wp_theme_label ); ?></h2>
	<ul class="list-inline">
	<?php
	foreach ( $creationell_wp_theme_tags as $creationell_wp_theme_item ) :
		if ( ! is_array( $creationell_wp_theme_item ) || ! ( $creationell_wp_theme_item['term'] ?? null ) instanceof WP_Term || ! is_string( $creationell_wp_theme_item['url'] ?? null ) ) {
			continue;
		}
		$creationell_wp_theme_current = in_array( $creationell_wp_theme_item['current'] ?? '', array( 'page', 'true' ), true ) ? $creationell_wp_theme_item['current'] : '';
		$creationell_wp_theme_count   = (int) ( $creationell_wp_theme_item['count'] ?? 0 );
		?>
		<li class="list-inline-item"><a class="<?php echo esc_attr( $creationell_wp_theme_badge_class ); ?>" href="<?php echo esc_url( $creationell_wp_theme_item['url'] ); ?>"<?php echo '' === $creationell_wp_theme_current ? '' : ' aria-current="' . esc_attr( $creationell_wp_theme_current ) . '"'; ?>><?php echo wp_kses( $creationell_wp_theme_icon, creationell_wp_theme_kses_allowed_svg( array() ) ); ?><?php echo esc_html( $creationell_wp_theme_item['term']->name ); ?><?php echo $creationell_wp_theme_counts ? '<span aria-hidden="true"> (' . esc_html( number_format_i18n( $creationell_wp_theme_count ) ) . ')</span><span class="visually-hidden">, ' . esc_html( $creationell_wp_theme_count_text( $creationell_wp_theme_count ) ) . '</span>' : ''; ?></a></li>
		<?php
	endforeach;
	?>
	</ul>
</section>
	<?php
endif;
