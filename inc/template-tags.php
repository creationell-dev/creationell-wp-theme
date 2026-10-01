<?php
/**
 * Custom template tags for this theme.
 *
 * Eventually, some of the functionality here could be replaced by core features.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


if ( ! function_exists( 'creationell_wp_theme_category_badge' ) ) :
	/**
	 * Prints the categories of a post as badges.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function creationell_wp_theme_category_badge(): void {
		// Hide category and tag text for pages.
		if ( 'post' === get_post_type() ) {
			echo '<p class="category-badge">';
			$thelist = '';
			$i       = 0;
			foreach ( get_the_category() as $category ) {
				if ( 0 < $i ) {
					$thelist .= ' ';
				}
				/**
				 * Filters the CSS classes of the category badges.
				 *
				 * @since 1.0.0
				 *
				 * @param string $classes Space-separated CSS classes.
				 */
				$badge_class = apply_filters( 'creationell_wp_theme_class_badge_category', 'badge bg-primary-subtle text-primary-emphasis text-decoration-none' );
				$thelist    .= '<a href="' . esc_url( get_category_link( $category->term_id ) ) . '" class="' . esc_attr( is_string( $badge_class ) ? $badge_class : '' ) . '">' . esc_html( $category->name ) . '</a>';
				++$i;
			}
			echo wp_kses_post( $thelist );
			echo '</p>';
		}
	}
endif;


if ( ! function_exists( 'creationell_wp_theme_sticky_badge' ) ) :
	/**
	 * Prints the badge of a sticky post in a loop item: a pin and a text for screen readers.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Template part of the loop item, for example cards-grid.
	 * @return void
	 */
	function creationell_wp_theme_sticky_badge( string $context ): void {
		/**
		 * Filters the CSS classes of the sticky post badge of a loop item.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $context Template part of the loop item, for example cards-grid.
		 */
		$classes = apply_filters( 'creationell_wp_theme_class_loop_card_content_sticky_post_badge', 'badge bg-danger-subtle text-danger-emphasis', $context );

		echo '<p class="sticky-badge"><span class="' . esc_attr( is_string( $classes ) ? $classes : '' ) . '">'
			. wp_kses( creationell_wp_theme_icon( 'pin', false ), creationell_wp_theme_kses_allowed_svg() )
			. '<span class="visually-hidden">' . esc_html__( 'Sticky post', 'creationell-wp-theme' ) . '</span></span></p>';
	}
endif;


if ( ! function_exists( 'creationell_wp_theme_category' ) ) :
	/**
	 * Prints the categories of a post as a comma-separated list.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function creationell_wp_theme_category(): void {
		// Hide category and tag text for pages.
		if ( 'post' === get_post_type() ) {
			/* translators: used between list items, there is a space after the comma */
			$categories_list = get_the_category_list( esc_html__( ', ', 'creationell-wp-theme' ) );
			if ( $categories_list ) {
				printf( '<span class="cat-links">%s</span>', wp_kses_post( $categories_list ) );
			}
		}
	}
endif;


if ( ! function_exists( 'creationell_wp_theme_date' ) ) :

	/**
	 * Prints HTML with meta information for the current post date and time.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function creationell_wp_theme_date(): void {
		$time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';

		// Check if modified time is different from the published time.
		if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
			/**
			 * Filters whether the post date shows the date of the last update.
			 *
			 * @since 1.0.0
			 *
			 * @param bool $show Whether to show the date of the last update.
			 */
			$show_updated_time = apply_filters( 'creationell_wp_theme_meta_time_updated', true );

			// If filter returns false, don't display modified time.
			if ( ! $show_updated_time ) {
				$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time>';
			} else {
				$icon = creationell_wp_theme_icon( 'arrow-clockwise', false );

				$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time> <span class="time-updated-separator">/</span> ' . $icon . ' <time class="updated" datetime="%3$s">%4$s</time>';
			}
		}

		$date          = get_the_date( DATE_W3C );
		$display_date  = get_the_date();
		$modified      = get_the_modified_date( DATE_W3C );
		$display_after = get_the_modified_date();

		$time_string = sprintf(
			$time_string,
			esc_attr( is_string( $date ) ? $date : '' ),
			esc_html( is_string( $display_date ) ? $display_date : '' ),
			esc_attr( is_string( $modified ) ? $modified : '' ),
			esc_html( is_string( $display_after ) ? $display_after : '' )
		);

		echo '<span class="posted-on"><span rel="bookmark">' . wp_kses( $time_string, creationell_wp_theme_kses_allowed_svg( wp_kses_allowed_html( 'post' ) ) ) . '</span></span>';
	}
endif;


if ( ! function_exists( 'creationell_wp_theme_author' ) ) {

	/**
	 * Prints the author of a post with a link to the author archive.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function creationell_wp_theme_author(): void {
		/**
		 * Filters whether the post meta shows the author.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $show Whether to show the author.
		 */
		$display_author = apply_filters( 'creationell_wp_theme_meta_author', true );

		// Check if the filter returns false, if so, return early without displaying the author.
		if ( ! $display_author ) {
			return;
		}

		// A post without a named author (deleted user, import) gets no byline: a link without text has no name.
		$author_name = trim( (string) get_the_author() );
		if ( '' === $author_name ) {
			return;
		}

		$byline = sprintf(
			/* translators: %s: post author */
			esc_html_x( 'by %s', 'post author', 'creationell-wp-theme' ),
			'<span class="author vcard"><a class="url fn n" href="' . esc_url( get_author_posts_url( absint( get_the_author_meta( 'ID' ) ) ) ) . '">' . esc_html( $author_name ) . '</a></span>'
		);

		echo '<span class="byline"> ' . wp_kses_post( $byline ) . '</span>';
	}
}


/**
 * Wraps the author description on author archives in paragraphs.
 *
 * See https://github.com/bootscore/bootscore/pull/1017.
 *
 * @since 1.0.0
 *
 * @param mixed $description Archive description.
 * @return mixed Description with paragraphs on author archives, other values unchanged.
 */
function creationell_wp_theme_author_description_autop( mixed $description ): mixed {
	if ( is_author() && is_string( $description ) ) {
		return wpautop( $description );
	}
	return $description;
}
add_filter( 'get_the_archive_description', 'creationell_wp_theme_author_description_autop' );


if ( ! function_exists( 'creationell_wp_theme_comments' ) ) :
	/**
	 * Prints the comment link of a post in the post meta.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function creationell_wp_theme_comments(): void {

		if ( ! is_single() && ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
			echo ' <span class="comment-divider">|</span> ' . wp_kses( creationell_wp_theme_icon( 'chat', false ), creationell_wp_theme_kses_allowed_svg() ) . ' <span class="comments-link">';
			comments_popup_link( esc_html__( 'Leave a Comment', 'creationell-wp-theme' ) );
			echo '</span>';
		}
	}
endif;


if ( ! function_exists( 'creationell_wp_theme_edit' ) ) :
	/**
	 * Prints HTML with the edit link for the current post.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function creationell_wp_theme_edit(): void {

		edit_post_link(
			esc_html__( 'Edit', 'creationell-wp-theme' ),
			' | <span class="edit-link">',
			'</span>'
		);
	}
endif;


if ( ! function_exists( 'creationell_wp_theme_comment_count' ) ) :
	/**
	 * Prints HTML with the comment count for the current post.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function creationell_wp_theme_comment_count(): void {
		if ( ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
			echo ' <span class="comment-divider">|</span> ' . wp_kses( creationell_wp_theme_icon( 'chat', false ), creationell_wp_theme_kses_allowed_svg() ) . ' <span class="comments-link">';
			comments_popup_link( esc_html__( 'Leave a comment', 'creationell-wp-theme' ) );
			echo '</span>';
		}
	}
endif;


if ( ! function_exists( 'creationell_wp_theme_tags' ) ) :
	/**
	 * Prints HTML with meta information for the tags.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function creationell_wp_theme_tags(): void {
		// Hide category and tag text for pages.
		if ( 'post' === get_post_type() ) {

			$tags_list = get_the_tag_list( '', ' ' );
			if ( is_string( $tags_list ) && '' !== $tags_list ) {
				echo '<div class="tags-links">';

				// Show 'Tagged' heading only on singular post pages.
				if ( is_singular( 'post' ) && get_the_ID() === get_queried_object_id() ) {
					echo '<p class="tags-heading h6">' . esc_html__( 'Tagged', 'creationell-wp-theme' ) . '</p>';
				}

				$tag_links = get_the_tag_list();
				echo wp_kses( is_string( $tag_links ) ? $tag_links : '', creationell_wp_theme_kses_allowed_svg( wp_kses_allowed_html( 'post' ) ) );
				echo '</div>';
			}
		}
	}

	/**
	 * Adds the badge classes and the tag icon to the tag links.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $links Tag link markup, one entry per tag.
	 * @return mixed Links with badge classes, other values unchanged.
	 */
	function creationell_wp_theme_add_tag_class( mixed $links ): mixed {
		if ( ! is_array( $links ) ) {
			return $links;
		}

		/**
		 * Filters the CSS classes of the tag badges.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 */
		$badge_class = apply_filters( 'creationell_wp_theme_class_badge_tag', 'badge bg-primary-subtle text-primary-emphasis text-decoration-none' );

		/**
		 * Filters whether tag badges show an icon.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $show Whether to show the icon.
		 */
		$icon = apply_filters( 'creationell_wp_theme_show_tag_icon', true ) ? creationell_wp_theme_icon( 'tag', false ) . ' ' : '';

		return str_replace(
			array( '<a href="', '">', '</a>' ),
			array( '<a class="' . esc_attr( is_string( $badge_class ) ? $badge_class : '' ) . '" href="', '">' . $icon, '</a> ' ),
			$links
		);
	}
	add_filter( 'term_links-post_tag', 'creationell_wp_theme_add_tag_class' );
endif;


if ( ! function_exists( 'creationell_wp_theme_post_thumbnail' ) ) :
	/**
	 * Displays an optional post thumbnail.
	 *
	 * Wraps the post thumbnail in an anchor element on index views, or a div
	 * element when on single views.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function creationell_wp_theme_post_thumbnail(): void {
		if ( post_password_required() || is_attachment() || ! has_post_thumbnail() ) {
			return;
		}

		if ( is_singular() ) :
			?>

		<div class="post-thumbnail">
			<?php the_post_thumbnail( 'full', array( 'class' => 'rounded mb-3' ) ); ?>
		</div><!-- .post-thumbnail -->

		<?php else : ?>

		<a class="post-thumbnail" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
			<?php
			the_post_thumbnail(
				'post-thumbnail',
				array(
					'alt' => the_title_attribute(
						array(
							'echo' => false,
						)
					),
				)
			);
			?>
		</a>

			<?php
		endif; // End is_singular().
	}
endif;


if ( ! function_exists( 'creationell_wp_theme_excerpt' ) ) {
	/**
	 * Prints the post excerpt with the content as fallback.
	 *
	 * Prints nothing for a password protected post as long as the visitor has not
	 * entered the password: neither the excerpt nor the start of the content.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $post_id    Post ID; null for the current post.
	 * @param int      $word_count Number of words to trim to.
	 * @return void
	 */
	function creationell_wp_theme_excerpt( ?int $post_id = null, int $word_count = 55 ): void {
		$post_id = $post_id ?? get_the_ID();
		if ( false === $post_id || post_password_required( $post_id ) ) {
			return;
		}

		// Get excerpt or fallback to content.
		$excerpt = get_post_field( 'post_excerpt', $post_id );
		if ( empty( $excerpt ) ) {
			$excerpt = get_post_field( 'post_content', $post_id );
		}

		// Clean and trim.
		$excerpt = wp_trim_words( strip_shortcodes( $excerpt ), $word_count );

		// Output.
		echo esc_html( $excerpt );
	}
}


if ( ! function_exists( 'creationell_wp_theme_card_excerpt' ) ) {
	/**
	 * Prints the excerpt of a post card as paragraph, or nothing when the post has no text.
	 *
	 * The text comes from creationell_wp_theme_excerpt(), so a child theme that
	 * replaces that function changes the cards too. A password protected post
	 * gets no excerpt, also with a replaced excerpt function.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Template part of the loop item, for example cards-grid.
	 * @return void
	 */
	function creationell_wp_theme_card_excerpt( string $context ): void {
		if ( post_password_required() ) {
			return;
		}

		ob_start();
		creationell_wp_theme_excerpt();
		$excerpt = trim( (string) ob_get_clean() );
		if ( '' === $excerpt ) {
			return;
		}

		/**
		 * Filters the CSS classes of the excerpt of a loop item.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $context Template part of the loop item, for example cards-grid.
		 */
		$classes = apply_filters( 'creationell_wp_theme_class_loop_card_text_excerpt', 'card-text', $context );

		echo '<p class="' . esc_attr( is_string( $classes ) ? $classes : '' ) . '">' . wp_kses_post( $excerpt ) . '</p>';
	}
}
