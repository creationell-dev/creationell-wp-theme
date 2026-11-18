<?php
/**
 * Profile fields of the author pages and the local avatar.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\Blog;

use Creationell\WpTheme\Admin\Notices;
use WP_Comment;
use WP_Post;
use WP_User;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Adds the section "Author page" to the user profile: profile image, job title and external profiles.
 *
 * The fields appear on the screens Profile and Edit User for a user who may
 * write posts, and only such a user can store them. A user who may write
 * posts and has an own profile image gets it as avatar everywhere (comments,
 * admin bar) instead of Gravatar. Blog_Module::boot() adds the
 * hooks while the module is active, before the settings are loaded; every
 * callback checks Author_Pages::enabled() when it runs. While the author pages
 * are off the stored values stay and nothing is shown, saved or replaced.
 *
 * @since 1.0.0
 */
final class Author_Profile_Fields {

	/**
	 * Script of the image selection, relative to the module folder.
	 *
	 * @since 1.0.0
	 */
	public const SCRIPT = 'assets/author-image.js';

	/**
	 * Handle of the script of the image selection.
	 *
	 * @since 1.0.0
	 */
	public const SCRIPT_HANDLE = Blog_Module::HANDLE . '-author-image';

	/**
	 * Admin screens with the profile fields, by hook suffix.
	 *
	 * @since 1.0.0
	 */
	public const SCREENS = array( 'profile.php', 'user-edit.php' );

	/**
	 * Maximum length of the job title in characters.
	 *
	 * @since 1.0.0
	 */
	public const JOB_TITLE_MAX = 120;

	/**
	 * Maximum number of external profiles.
	 *
	 * @since 1.0.0
	 */
	public const PROFILES_MAX = 10;

	/**
	 * Transient of a refused profile image, followed by the ID of the user who saved the form.
	 *
	 * @since 1.0.0
	 */
	private const REFUSED_TRANSIENT = 'creationell_wp_theme_author_image_refused_';

	/**
	 * ID of the notice about a refused profile image.
	 *
	 * @since 1.0.0
	 */
	private const REFUSED_NOTICE = 'author-image-refused';

	/**
	 * Lifetime of the transient of a refused profile image in seconds: long enough for the redirect after saving.
	 *
	 * @since 1.0.0
	 */
	private const REFUSED_LIFETIME = 60;

	/**
	 * Adds the hooks of the meta keys, the profile screens, the admin assets and the avatar; Blog_Module::boot() calls it.
	 *
	 * The load actions of the two profile screens queue the notice about a
	 * refused profile image before admin_notices prints it.
	 *
	 * The meta keys are registered on init with priority 11, after the
	 * settings registry has loaded on init 10.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_meta_keys' ), 11, 0 );
		add_action( 'show_user_profile', array( self::class, 'render' ), 10, 1 );
		add_action( 'edit_user_profile', array( self::class, 'render' ), 10, 1 );
		add_action( 'personal_options_update', array( self::class, 'save' ), 10, 1 );
		add_action( 'edit_user_profile_update', array( self::class, 'save' ), 10, 1 );
		add_action( 'load-profile.php', array( self::class, 'notice' ), 10, 0 );
		add_action( 'load-user-edit.php', array( self::class, 'notice' ), 10, 0 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ), 10, 1 );
		add_filter( 'pre_get_avatar_data', array( self::class, 'avatar' ), 10, 2 );
	}

	/**
	 * Registers the three user meta keys with their sanitizers while the author pages are on; runs on init (priority 11).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register_meta_keys(): void {
		if ( ! Author_Pages::enabled() ) {
			return;
		}
		$keys = array(
			Author_Pages::META_IMAGE     => array( 'integer', 'sanitize_image' ),
			Author_Pages::META_JOB_TITLE => array( 'string', 'sanitize_job_title' ),
			Author_Pages::META_PROFILES  => array( 'string', 'sanitize_profiles' ),
		);
		foreach ( $keys as $key => [ $type, $sanitizer ] ) {
			register_meta(
				'user',
				$key,
				array(
					'type'              => $type,
					'single'            => true,
					'show_in_rest'      => false,
					'sanitize_callback' => array( self::class, $sanitizer ),
				)
			);
		}
	}

	/**
	 * Prints the section "Author page" for a user who may write posts; runs on show_user_profile and edit_user_profile.
	 *
	 * Without JavaScript the profile image is a number field for the
	 * attachment ID. The script author-image.js hides it and shows the buttons
	 * that choose and remove the image through the media library. The buttons
	 * carry display:none next to hidden, because the admin CSS
	 * (.wp-core-ui .button { display: inline-block }) beats the attribute.
	 * The script points the label at the button "Choose image", which the
	 * label and its own text name. For a current user who may not edit the
	 * posts of others, data-library-author limits the media library to the
	 * own uploads, the images save() accepts from such a user.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_User $user User whose profile is edited.
	 * @return void
	 */
	public static function render( WP_User $user ): void {
		if ( ! Author_Pages::enabled() || ! self::may_have_fields( $user ) ) {
			return;
		}
		$id       = (int) $user->ID;
		$image    = self::sanitize_image( get_user_meta( $id, Author_Pages::META_IMAGE, true ) );
		$job      = get_user_meta( $id, Author_Pages::META_JOB_TITLE, true );
		$profiles = get_user_meta( $id, Author_Pages::META_PROFILES, true );
		$library  = current_user_can( 'edit_others_posts' ) ? 0 : get_current_user_id();
		?>
<h2><?php esc_html_e( 'Author page', 'creationell-wp-theme' ); ?></h2>
<table class="form-table" role="presentation">
	<tr>
		<th scope="row"><label for="creationell-wp-theme-author-image" id="creationell-wp-theme-author-image-label"><?php esc_html_e( 'Profile image', 'creationell-wp-theme' ); ?></label></th>
		<td>
			<div class="creationell-wp-theme-author-image" data-creationell-author-image="field" data-frame-title="<?php esc_attr_e( 'Choose profile image', 'creationell-wp-theme' ); ?>" data-frame-button="<?php esc_attr_e( 'Use this image', 'creationell-wp-theme' ); ?>"<?php echo 0 === $library ? '' : ' data-library-author="' . esc_attr( (string) $library ) . '"'; ?>>
				<div data-creationell-author-image="preview"><?php echo wp_get_attachment_image( $image, 'thumbnail', false, array( 'alt' => '' ) ); ?></div>
				<input type="number" min="0" step="1" class="small-text" id="creationell-wp-theme-author-image" name="<?php echo esc_attr( Author_Pages::META_IMAGE ); ?>" value="<?php echo esc_attr( 0 === $image ? '' : (string) $image ); ?>" aria-describedby="creationell-wp-theme-author-image-description" />
				<button type="button" class="button" id="creationell-wp-theme-author-image-choose" data-creationell-author-image="choose" aria-labelledby="creationell-wp-theme-author-image-label creationell-wp-theme-author-image-choose" aria-describedby="creationell-wp-theme-author-image-description" hidden style="display:none"><?php esc_html_e( 'Choose image', 'creationell-wp-theme' ); ?></button>
				<button type="button" class="button" data-creationell-author-image="remove" hidden style="display:none"><?php esc_html_e( 'Remove image', 'creationell-wp-theme' ); ?></button>
			</div>
			<p id="creationell-wp-theme-author-image-description" class="description"><?php esc_html_e( 'An image from the media library, shown on the author page and as avatar instead of Gravatar. Without one the author page shows no image and the avatar follows the discussion settings (Gravatar or the default avatar).', 'creationell-wp-theme' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="creationell-wp-theme-author-job-title"><?php esc_html_e( 'Job title', 'creationell-wp-theme' ); ?></label></th>
		<td>
			<input type="text" maxlength="<?php echo esc_attr( (string) self::JOB_TITLE_MAX ); ?>" class="regular-text" id="creationell-wp-theme-author-job-title" name="<?php echo esc_attr( Author_Pages::META_JOB_TITLE ); ?>" value="<?php echo esc_attr( is_string( $job ) ? $job : '' ); ?>" aria-describedby="creationell-wp-theme-author-job-title-description" />
			<p id="creationell-wp-theme-author-job-title-description" class="description"><?php esc_html_e( 'Shown below the name on the author page, up to 120 characters.', 'creationell-wp-theme' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="creationell-wp-theme-author-profiles"><?php esc_html_e( 'External profiles', 'creationell-wp-theme' ); ?></label></th>
		<td>
			<textarea rows="4" cols="30" class="large-text" id="creationell-wp-theme-author-profiles" name="<?php echo esc_attr( Author_Pages::META_PROFILES ); ?>" aria-describedby="creationell-wp-theme-author-profiles-description"><?php echo esc_textarea( is_string( $profiles ) ? $profiles : '' ); ?></textarea>
			<p id="creationell-wp-theme-author-profiles-description" class="description"><?php esc_html_e( 'One address per line, starting with https:// or http://, up to 10. Shown as links on the author page, after the website.', 'creationell-wp-theme' ); ?></p>
		</td>
	</tr>
</table>
		<?php
	}

	/**
	 * Stores the fields of the form for a user who may write posts and whom the current user may edit; runs on personal_options_update and edit_user_profile_update.
	 *
	 * WordPress checks the nonce update-user_{id} before these hooks. The
	 * edited user needs the same capability as for the section itself, so a
	 * subscriber cannot post the fields for the own profile. A field missing
	 * from the request stays as it is; a value that is empty after sanitizing
	 * removes the meta. The profile image becomes public on the author page
	 * and as avatar, so a newly chosen image is stored only when
	 * may_use_image() allows it; otherwise the stored image stays and the
	 * transient REFUSED_TRANSIENT of the current user makes notice() tell so
	 * on the next load of a profile screen, since WordPress still reports the
	 * profile as updated. The stored image is left as it is without that
	 * check, so an unchanged form never fails, even for an image someone else
	 * chose.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id ID of the edited user.
	 * @return void
	 */
	public static function save( int $user_id ): void {
		if ( ! Author_Pages::enabled() || ! current_user_can( 'edit_user', $user_id ) || ! self::may_have_fields( $user_id ) ) {
			return;
		}
		$fields = array(
			Author_Pages::META_IMAGE     => array( self::class, 'sanitize_image' ),
			Author_Pages::META_JOB_TITLE => array( self::class, 'sanitize_job_title' ),
			Author_Pages::META_PROFILES  => array( self::class, 'sanitize_profiles' ),
		);
		foreach ( $fields as $key => $sanitizer ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}
			$value = $sanitizer( wp_unslash( $_POST[ $key ] ) );
			if ( 0 === $value || '' === $value ) {
				delete_user_meta( $user_id, $key );
				continue;
			}
			if ( Author_Pages::META_IMAGE === $key && self::sanitize_image( get_user_meta( $user_id, $key, true ) ) === $value ) {
				continue;
			}
			if ( Author_Pages::META_IMAGE === $key && ! self::may_use_image( (int) $value, $user_id ) ) {
				set_transient( self::REFUSED_TRANSIENT . get_current_user_id(), 1, self::REFUSED_LIFETIME );
				continue;
			}
			update_user_meta( $user_id, $key, $value );
		}
	}

	/**
	 * Queues the notice about a refused profile image once for the current user; runs on load-profile.php and load-user-edit.php.
	 *
	 * The transient comes from save(), which keeps the stored image instead
	 * of a newly chosen one it refuses; the notice reads it once and removes it.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function notice(): void {
		$transient = self::REFUSED_TRANSIENT . get_current_user_id();
		if ( ! Author_Pages::enabled() || false === get_transient( $transient ) ) {
			return;
		}
		delete_transient( $transient );
		Notices::add(
			self::REFUSED_NOTICE,
			static fn(): string => __( 'This image cannot be used as profile image: choose an image you uploaded, or ask an editor or administrator to set it.', 'creationell-wp-theme' ),
			Notices::WARNING,
			'read'
		);
	}

	/**
	 * Loads the media library and the image selection on the screens Profile and Edit User; runs on admin_enqueue_scripts.
	 *
	 * Only for a user who may upload files: without that capability the media
	 * library cannot list the images, and the number field stays.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $hook_suffix Hook suffix of the admin screen.
	 * @return void
	 */
	public static function enqueue( mixed $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, self::SCREENS, true ) || ! Author_Pages::enabled() || ! current_user_can( 'upload_files' ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			CREATIONELL_WP_THEME_URL . '/modules/blog/' . self::SCRIPT,
			array( 'media-editor' ),
			CREATIONELL_WP_THEME_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Gives a user with an own profile image that image as avatar; runs on pre_get_avatar_data.
	 *
	 * The user comes from an ID, a WP_User, the author of a WP_Post or the
	 * registered author of a WP_Comment, as in get_avatar_data(). An email
	 * address, a guest comment, a user without an own image and a request
	 * with force_default (the previews of the discussion settings) keep the
	 * arguments as they are, so the avatar setting of the site applies. So
	 * does a user who may not write posts: such a user has no author fields,
	 * and an image stored earlier is ignored. That check comes last, so
	 * guests and email addresses cost no capability check. The
	 * avatar box is square, so the image comes in a square size (see
	 * avatar_size()), never as the full upload.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $args        Arguments of get_avatar_data().
	 * @param mixed                $id_or_email User ID, WP_User, WP_Post, WP_Comment or email address.
	 * @return array<string, mixed> Arguments; with url and found_avatar for an own image.
	 */
	public static function avatar( array $args, mixed $id_or_email ): array {
		$user_id = self::avatar_user_id( $id_or_email );
		if ( 0 === $user_id || ! empty( $args['force_default'] ) || ! Author_Pages::enabled() ) {
			return $args;
		}
		$image = self::sanitize_image( get_user_meta( $user_id, Author_Pages::META_IMAGE, true ) );
		if ( 0 === $image || ! self::may_have_fields( $user_id ) ) {
			return $args;
		}
		$size = isset( $args['size'] ) && is_numeric( $args['size'] ) ? (int) $args['size'] : 96;
		$url  = wp_get_attachment_image_url( $image, self::avatar_size( $image, $size ) );
		if ( ! is_string( $url ) || '' === $url ) {
			return $args;
		}
		$args['url']          = $url;
		$args['found_avatar'] = true;
		return $args;
	}

	/**
	 * Returns the attachment ID of an image, else 0.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Submitted or stored value.
	 * @return int Attachment ID of an image, 0 for anything else.
	 */
	public static function sanitize_image( mixed $value ): int {
		$id = is_int( $value ) || ( is_string( $value ) && is_numeric( $value ) ) ? absint( $value ) : 0;
		return 0 !== $id && wp_attachment_is_image( $id ) ? $id : 0;
	}

	/**
	 * Returns the job title as plain text of at most 120 characters.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Submitted or stored value.
	 * @return string Job title, '' for anything that is no text.
	 */
	public static function sanitize_job_title( mixed $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}
		return trim( mb_substr( sanitize_text_field( $value ), 0, self::JOB_TITLE_MAX ) );
	}

	/**
	 * Returns the external profiles, one http or https address per line, each once, at most ten.
	 *
	 * Blank lines, other schemes (javascript:, ftp:, data:) and addresses
	 * without a scheme fall away.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Submitted or stored value.
	 * @return string Addresses joined with a line feed, '' for none.
	 */
	public static function sanitize_profiles( mixed $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}
		$urls  = array();
		$lines = preg_split( '~\R~', $value );
		foreach ( false === $lines ? array() : $lines as $line ) {
			$line   = trim( $line );
			$scheme = '' === $line ? false : wp_parse_url( $line, PHP_URL_SCHEME );
			if ( ! is_string( $scheme ) || ! in_array( strtolower( $scheme ), array( 'http', 'https' ), true ) ) {
				continue;
			}
			$url = esc_url_raw( $line );
			if ( '' === $url || in_array( $url, $urls, true ) ) {
				continue;
			}
			$urls[] = $url;
			if ( self::PROFILES_MAX === count( $urls ) ) {
				break;
			}
		}
		return implode( "\n", $urls );
	}

	/**
	 * Tells whether a user may have the author fields: a user who may write posts.
	 *
	 * The one check behind render(), save() and avatar(), so that a user
	 * without the section can neither store the fields nor get the image as
	 * avatar.
	 *
	 * @since 1.0.0
	 *
	 * @param int|WP_User $user User ID or user.
	 * @return bool Whether the user may have the author fields.
	 */
	private static function may_have_fields( int|WP_User $user ): bool {
		return user_can( $user, 'edit_posts' );
	}

	/**
	 * Tells whether an image may become the profile image of a user.
	 *
	 * The profile image is public: it appears on the author page and as
	 * avatar. So the image must have been uploaded by the edited user, or the
	 * current user must be allowed to edit it and it must be public already:
	 * not attached to a post, or attached to a published one. An image of a
	 * draft or private post of someone else stays hidden. sanitize_image()
	 * has already made sure it is an image.
	 *
	 * @since 1.0.0
	 *
	 * @param int $image   Attachment ID of the image.
	 * @param int $user_id ID of the edited user.
	 * @return bool Whether the image may be stored as profile image.
	 */
	private static function may_use_image( int $image, int $user_id ): bool {
		$post = get_post( $image );
		if ( ! $post instanceof WP_Post ) {
			return false;
		}
		if ( (int) $post->post_author === $user_id ) {
			return true;
		}
		if ( ! current_user_can( 'edit_post', $image ) ) {
			return false;
		}
		$parent = (int) $post->post_parent;
		return 0 === $parent || 'publish' === get_post_status( $parent );
	}

	/**
	 * Picks the square size of an image for an avatar of the given width.
	 *
	 * An array size would let WordPress fall back to the full upload as soon
	 * as no size with the same ratio is large enough, which get_avatar()
	 * triggers with its request for twice the size. So the size is picked by
	 * name from the metadata: the smallest square size at least as wide as
	 * asked, else the largest square size, else the thumbnail (square unless
	 * the site switched off its crop).
	 *
	 * @since 1.0.0
	 *
	 * @param int $image Attachment ID of the image.
	 * @param int $size  Width and height of the avatar in pixels.
	 * @return string Name of the image size.
	 */
	private static function avatar_size( int $image, int $size ): string {
		$meta  = wp_get_attachment_metadata( $image );
		$sizes = is_array( $meta ) ? $meta['sizes'] ?? array() : array();
		$size  = max( 1, $size );
		$best  = 'thumbnail';
		$width = 0;
		foreach ( $sizes as $name => $data ) {
			$w = $data['width'];
			if ( $w !== $data['height'] ) {
				continue;
			}
			$best_fits = $width >= $size;
			if ( $w >= $size ? ( ! $best_fits || $w < $width ) : ( ! $best_fits && $w > $width ) ) {
				$best  = (string) $name;
				$width = $w;
			}
		}
		return $best;
	}

	/**
	 * Finds the user behind the argument of get_avatar_data().
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $id_or_email User ID, WP_User, WP_Post, WP_Comment or email address.
	 * @return int User ID, 0 when there is none.
	 */
	private static function avatar_user_id( mixed $id_or_email ): int {
		if ( is_int( $id_or_email ) || ( is_string( $id_or_email ) && ctype_digit( $id_or_email ) ) ) {
			return (int) $id_or_email;
		}
		if ( $id_or_email instanceof WP_User ) {
			return (int) $id_or_email->ID;
		}
		if ( $id_or_email instanceof WP_Post ) {
			return (int) $id_or_email->post_author;
		}
		if ( $id_or_email instanceof WP_Comment ) {
			return (int) $id_or_email->user_id;
		}
		return 0;
	}
}
