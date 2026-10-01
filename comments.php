<?php
/**
 * The template for displaying comments
 * Template Version: 6.4.0
 *
 * This is the template that displays the area of the page that contains both the current comments
 * and the comment form.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/*
 * If the current post is protected by a password and
 * the visitor has not yet entered the password we will
 * return early without loading the comments.
 */
if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="comments-area">

	<?php
	// You can start editing here -- including this comment!
	if ( have_comments() ) :
		?>

	<h2 class="comments-title mb-4">
		<?php
		$creationell_wp_theme_comments_number = get_comments_number();
		if ( '1' === $creationell_wp_theme_comments_number ) {
			/* translators: %s: post title */
			printf( esc_html_x( 'One Comment &ldquo;%s&rdquo;', 'comments title', 'creationell-wp-theme' ), esc_html( get_the_title() ) );
		} else {
			printf(
				esc_html(
					/* translators: 1: number of comments, 2: post title */
					_nx(
						'%1$s Comment on &ldquo;%2$s&rdquo;',
						'%1$s Comments on &ldquo;%2$s&rdquo;',
						$creationell_wp_theme_comments_number,
						'comments title',
						'creationell-wp-theme'
					)
				),
				esc_html( number_format_i18n( $creationell_wp_theme_comments_number ) ),
				esc_html( get_the_title() )
			);
		}
		?>
	</h2><!-- .comments-title -->


		<?php
		if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : // Are there comments to navigate through?
			?>
		<nav id="comment-nav-above" class="navigation comment-navigation" aria-label="<?php esc_attr_e( 'Comment navigation above the comments', 'creationell-wp-theme' ); ?>">
		<h2 class="screen-reader-text"><?php esc_html_e( 'Comment navigation', 'creationell-wp-theme' ); ?></h2>
		<div class="nav-links">

			<div class="nav-previous"><?php previous_comments_link( esc_html__( 'Older Comments', 'creationell-wp-theme' ) ); ?></div>
			<div class="nav-next"><?php next_comments_link( esc_html__( 'Newer Comments', 'creationell-wp-theme' ) ); ?></div>

		</div><!-- .nav-links -->
		</nav><!-- #comment-nav-above -->
			<?php
	endif; // Check for comment navigation.
		?>

	<ul class="comment-list">
		<?php
		wp_list_comments(
			array(
				'callback'    => 'creationell_wp_theme_comment',
				'avatar_size' => 128,
			)
		);
		?>
	</ul><!-- .comment-list -->

		<?php
		if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : // Are there comments to navigate through?
			?>
		<nav id="comment-nav-below" class="navigation comment-navigation" aria-label="<?php esc_attr_e( 'Comment navigation below the comments', 'creationell-wp-theme' ); ?>">
		<h2 class="screen-reader-text"><?php esc_html_e( 'Comment navigation', 'creationell-wp-theme' ); ?></h2>
		<div class="nav-links pagination justify-content-center">

			<div class="nav-previous page-item"><?php previous_comments_link( esc_html__( 'Older Comments', 'creationell-wp-theme' ) ); ?></div>
			<div class="nav-next page-item"><?php next_comments_link( esc_html__( 'Newer Comments', 'creationell-wp-theme' ) ); ?></div>

		</div><!-- .nav-links -->
		</nav><!-- #comment-nav-below -->
			<?php
		endif; // Check for comment navigation.

	endif; // Check for have_comments().


	// If comments are closed and there are comments, let's leave a little note, shall we?
	if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) :
		?>

		<?php
		/**
		 * Filters the CSS classes of the notice that comments are closed.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 */
		$creationell_wp_theme_class_comments_closed_alert = apply_filters( 'creationell_wp_theme_class_comments_closed_alert', 'alert alert-info' );
		?>
	<p class="no-comments <?php echo esc_attr( $creationell_wp_theme_class_comments_closed_alert ); ?>">
		<?php
		/**
		 * Filters the notice that comments are closed.
		 *
		 * @since 1.0.0
		 *
		 * @param string $text Escaped notice text.
		 */
		echo wp_kses_post( apply_filters( 'creationell_wp_theme_comments_closed_text', esc_html__( 'Comments are closed.', 'creationell-wp-theme' ) ) );
		?>
	</p>
  
		<?php
	endif;
	?>

	<?php
	comment_form(
		array(
			'id_form'           => 'commentform',
			'id_submit'         => 'commentsubmit',
			'title_reply'       => __( 'Leave a Comment', 'creationell-wp-theme' ),
			/* translators: %s: name of the comment author */
			'title_reply_to'    => __( 'Leave a Comment to %s', 'creationell-wp-theme' ),
			'cancel_reply_link' => __( 'Cancel', 'creationell-wp-theme' ),
			'label_submit'      => __( 'Post Comment', 'creationell-wp-theme' ),
			'comment_field'     => creationell_wp_theme_comment_form_comment_field(),
			// Bootstrap form fields with labels; the core filter keeps plugins and the cookie consent field working.
			'fields'            => apply_filters(
				'comment_form_default_fields', // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core filter of comment_form() for the replaced default fields.
				creationell_wp_theme_comment_form_fields( wp_get_current_commenter(), (bool) get_option( 'require_name_email' ) )
			),
		)
	);

	?>

</div><!-- #comments -->