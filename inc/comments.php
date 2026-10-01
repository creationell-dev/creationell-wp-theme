<?php
/**
 * Comments.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Enqueues the comment reply script on singular views with threaded comments.
 *
 * @since 1.0.0
 *
 * @return void
 */
function creationell_wp_theme_reply(): void {

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}

add_action( 'wp_enqueue_scripts', 'creationell_wp_theme_reply' );


if ( ! function_exists( 'creationell_wp_theme_comment' ) ) :
	/**
	 * Prints a comment, pingback or trackback.
	 *
	 * Used as a callback by wp_list_comments() for displaying the comments.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Comment           $comment The comment object.
	 * @param array<string, mixed> $args    An array of wp_list_comments() arguments.
	 * @param int                  $depth   Depth of the current comment.
	 * @return void
	 */
	function creationell_wp_theme_comment( WP_Comment $comment, array $args, int $depth ): void {

		if ( 'pingback' === $comment->comment_type || 'trackback' === $comment->comment_type ) : ?>

		<li id="comment-<?php comment_ID(); ?>" <?php comment_class( 'media alert alert-info' ); ?>>
		<div class="comment-body">
			<?php esc_html_e( 'Pingback:', 'creationell-wp-theme' ); ?><?php comment_author_link(); ?><?php edit_comment_link( __( 'Edit', 'creationell-wp-theme' ), '<span class="edit-link">', '</span>' ); ?>
		</div>

		<?php else : ?>

		<li id="comment-<?php comment_ID(); ?>" <?php comment_class( empty( $args['has_children'] ) ? '' : 'parent' ); ?>>

		<article id="div-comment-<?php comment_ID(); ?>" class="comment-body mb-4 d-flex">

			<div class="flex-shrink-0 me-3">
			<?php
			/**
			 * Filters the CSS classes of the avatar in a comment.
			 *
			 * @since 1.0.0
			 *
			 * @param string $classes Space-separated CSS classes.
			 */
			$avatar_classes = apply_filters( 'creationell_wp_theme_class_comment_avatar', 'img-thumbnail rounded-circle' );
			$avatar         = get_avatar( $comment, 80, '', '', array( 'class' => esc_attr( is_string( $avatar_classes ) ? $avatar_classes : '' ) ) );
			echo wp_kses_post( false === $avatar ? '' : $avatar );
			?>
			</div>

			<div class="comment-content">
			<div class="card">
				<div class="card-body">

				<?php printf( '<h3 class="h5">%s</h3>', wp_kses_post( get_comment_author_link() ) ); ?>

				<p class="small comment-meta text-body-secondary">
					<time datetime="<?php comment_time( 'c' ); ?>">
					<?php
					$comment_time = get_comment_time();
					/* translators: 1: date, 2: time */
					printf( esc_html_x( '%1$s at %2$s', '1: date, 2: time', 'creationell-wp-theme' ), esc_html( get_comment_date() ), esc_html( is_string( $comment_time ) ? $comment_time : '' ) );
					?>
					</time>
					<?php edit_comment_link( __( 'Edit', 'creationell-wp-theme' ), '<span class="edit-link">', '</span>' ); ?>
				</p>


				<?php if ( '0' === $comment->comment_approved ) : ?>
					<p class="comment-awaiting-moderation alert alert-info"><?php esc_html_e( 'Your comment is awaiting moderation.', 'creationell-wp-theme' ); ?></p>
				<?php endif; ?>

				<?php comment_text(); ?>

				<?php
				comment_reply_link(
					array_merge(
						$args,
						array(
							'add_below' => 'div-comment',
							'depth'     => $depth,
							'max_depth' => $args['max_depth'] ?? 0,
							'before'    => '<p class="reply comment-reply">',
							'after'     => '</p>',
						)
					)
				);
				?>
				</div> <!-- card-body -->
			</div><!-- card -->
			</div><!-- .comment-content -->

		</article><!-- .comment-body -->
		</li><!-- #comment -->

			<?php
		endif;
	}
endif;


if ( ! function_exists( 'creationell_wp_theme_comment_form_fields' ) ) :
	/**
	 * Returns the name, email and website fields of the comment form with Bootstrap markup.
	 *
	 * Every field has a visible label, the input type and the autocomplete token of core;
	 * name and email are required when the site requires them.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $commenter Current commenter as returned by wp_get_current_commenter().
	 * @param bool                 $required  Whether name and email are required.
	 * @return array<string, string> Field markup by key: author, email, url.
	 */
	function creationell_wp_theme_comment_form_fields( array $commenter, bool $required ): array {
		$indicator = $required ? ' ' . wp_required_field_indicator() : '';
		$attribute = $required ? ' required' : '';
		$value     = static fn( string $key ): string => esc_attr( isset( $commenter[ $key ] ) && is_string( $commenter[ $key ] ) ? $commenter[ $key ] : '' );

		return array(
			'author' => '<p class="comment-form-author mb-3"><label for="author" class="form-label">' . esc_html__( 'Name', 'creationell-wp-theme' ) . $indicator . '</label>'
				. '<input id="author" class="form-control" name="author" type="text" value="' . $value( 'comment_author' ) . '" size="30" maxlength="245" autocomplete="name"' . $attribute . ' /></p>',
			'email'  => '<p class="comment-form-email mb-3"><label for="email" class="form-label">' . esc_html__( 'Email', 'creationell-wp-theme' ) . $indicator . '</label>'
				. '<input id="email" class="form-control" name="email" type="email" value="' . $value( 'comment_author_email' ) . '" size="30" maxlength="100" aria-describedby="email-notes" autocomplete="email"' . $attribute . ' /></p>',
			'url'    => '<p class="comment-form-url mb-3"><label for="url" class="form-label">' . esc_html__( 'Website', 'creationell-wp-theme' ) . '</label>'
				. '<input id="url" class="form-control" name="url" type="url" value="' . $value( 'comment_author_url' ) . '" size="30" maxlength="200" autocomplete="url" /></p>',
		);
	}
endif;


if ( ! function_exists( 'creationell_wp_theme_comment_form_comment_field' ) ) :
	/**
	 * Returns the comment text field of the comment form with a visible label.
	 *
	 * @since 1.0.0
	 *
	 * @return string Field markup.
	 */
	function creationell_wp_theme_comment_form_comment_field(): string {
		return '<p class="comment-form-comment mb-3"><label for="comment" class="form-label">' . esc_html_x( 'Comment', 'noun', 'creationell-wp-theme' ) . ' ' . wp_required_field_indicator() . '</label>'
			. '<textarea id="comment" class="form-control" name="comment" cols="45" rows="8" maxlength="65525" required></textarea></p>';
	}
endif;


/**
 * Renders the reply title as h2.
 *
 * @since 1.0.0
 *
 * @param mixed $defaults Default comment form arguments.
 * @return mixed Arguments with the title markup, other values unchanged.
 */
function creationell_wp_theme_custom_reply_title( mixed $defaults ): mixed {
	if ( ! is_array( $defaults ) ) {
		return $defaults;
	}

	$defaults['title_reply_before'] = '<h2 id="reply-title" class="h4">';
	$defaults['title_reply_after']  = '</h2>';

	return $defaults;
}
add_filter( 'comment_form_defaults', 'creationell_wp_theme_custom_reply_title' );


/**
 * Renders the cookie consent checkbox of the comment form with Bootstrap classes.
 *
 * See https://github.com/bootscore/bootscore/issues/921.
 *
 * @since 1.0.0
 *
 * @param mixed $fields Default comment fields.
 * @return mixed Fields with the checkbox, or without it when the opt-in setting is off.
 */
function creationell_wp_theme_change_comment_form_cookies_consent( mixed $fields ): mixed {
	if ( ! is_array( $fields ) ) {
		return $fields;
	}

	// Check if the "Show comments cookies opt-in checkbox" setting is enabled.
	if ( get_option( 'show_comments_cookies_opt_in' ) ) {
		$commenter         = wp_get_current_commenter();
		$consent           = empty( $commenter['comment_author_email'] ) ? '' : ' checked="checked"';
		$fields['cookies'] = '<p class="comment-form-cookies-consent form-check mb-3">' .
						'<input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes" class="form-check-input"' . $consent . ' />' .
						'<label for="wp-comment-cookies-consent" class="form-check-label">' . esc_html__( 'Save my name, email, and website in this browser for the next time I comment.', 'creationell-wp-theme' ) . '</label>' .
						'</p>';
	} else {
		// Remove the 'cookies' field if the setting is disabled.
		unset( $fields['cookies'] );
	}

	return $fields;
}
add_filter( 'comment_form_default_fields', 'creationell_wp_theme_change_comment_form_cookies_consent' );


if ( ! function_exists( 'creationell_wp_theme_comment_button' ) ) :
	/**
	 * Adds Bootstrap button classes to the submit button of the comment form.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $args Default comment form arguments.
	 * @return mixed Arguments with the button class, other values unchanged.
	 */
	function creationell_wp_theme_comment_button( mixed $args ): mixed {
		if ( is_array( $args ) ) {
			$args['class_submit'] = 'btn btn-outline-primary';
		}

		return $args;
	}

	add_filter( 'comment_form_defaults', 'creationell_wp_theme_comment_button' );
endif;
