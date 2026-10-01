<?php
/**
 * Password protected form.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


if ( ! function_exists( 'creationell_wp_theme_pw_form' ) ) :
	/**
	 * Returns the password form of protected posts with Bootstrap classes.
	 *
	 * Follows the markup of core: a visible label, a required field, the redirect back to
	 * the post and, after a wrong password, the message of core as an alert that
	 * describes the field.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $output           Password form markup of core.
	 * @param mixed $post             Post object.
	 * @param mixed $invalid_password Message after a wrong password; empty otherwise.
	 * @return string Form markup.
	 */
	function creationell_wp_theme_pw_form( mixed $output, mixed $post = null, mixed $invalid_password = '' ): string {
		unset( $output );
		$post_id  = is_object( $post ) && isset( $post->ID ) && is_numeric( $post->ID ) ? absint( $post->ID ) : 0;
		$field_id = 'pwbox-' . ( 0 === $post_id ? wp_unique_id() : (string) $post_id );
		$message  = is_string( $invalid_password ) ? $invalid_password : '';

		$redirect = '';
		if ( 0 !== $post_id ) {
			$permalink = get_permalink( $post_id );
			$redirect  = false === $permalink ? '' : '<input type="hidden" name="redirect_to" value="' . esc_attr( $permalink ) . '" />';
		}

		$error = '';
		$aria  = '';
		$class = '';
		if ( '' !== $message ) {
			$error = '<div class="alert alert-danger post-password-form-invalid-password" role="alert"><p id="error-' . esc_attr( $field_id ) . '" class="mb-0">' . esc_html( $message ) . '</p></div>';
			$aria  = ' aria-describedby="error-' . esc_attr( $field_id ) . '" aria-invalid="true"';
			$class = ' password-form-error';
		}

		return '<form action="' . esc_url( site_url( 'wp-login.php?action=postpass', 'login_post' ) ) . '" class="post-password-form pw_form mb-4' . $class . '" method="post">' . $redirect . $error
			. '<p>' . esc_html__( 'This content is password-protected. To view it, please enter the password below.', 'creationell-wp-theme' ) . '</p>'
			. '<label for="' . esc_attr( $field_id ) . '" class="form-label">' . esc_html__( 'Password', 'creationell-wp-theme' ) . '</label>'
			. '<div class="input-group">'
			. '<input name="post_password" id="' . esc_attr( $field_id ) . '" type="password" class="form-control" spellcheck="false" required size="20"' . $aria . ' />'
			. '<input type="submit" name="Submit" class="btn btn-outline-primary" value="' . esc_attr_x( 'Enter', 'post password form', 'creationell-wp-theme' ) . '" />'
			. '</div></form>';
	}

	add_filter( 'the_password_form', 'creationell_wp_theme_pw_form', 10, 3 );
endif;
