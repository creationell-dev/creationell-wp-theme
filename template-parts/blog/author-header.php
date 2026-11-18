<?php
/**
 * Template part: the header of an author page with profile image, name, job title, biography and links.
 *
 * Prints the only H1 of the author page. Arguments (Author_Pages::header_args()):
 * name (display name, plain text), image_id (attachment ID of the profile
 * image; 0 or an attachment that is no image prints no image, never a
 * Gravatar), image_size (size of wp_get_attachment_image()), job_title (plain
 * text), bio (biographical info, raw; it gets paragraphs and passes
 * wp_kses_post()), links (entries with url and text; printed with rel="me" in
 * the same window), context (author). Empty fields print nothing. The H1 takes
 * the classes of the filter creationell_wp_theme_class_entry_title and the
 * biography those of creationell_wp_theme_class_entry_archive_description,
 * like archive.php.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_args       = isset( $args ) && is_array( $args ) ? $args : array();
$creationell_wp_theme_name       = is_string( $creationell_wp_theme_args['name'] ?? null ) ? $creationell_wp_theme_args['name'] : '';
$creationell_wp_theme_image_id   = is_int( $creationell_wp_theme_args['image_id'] ?? null ) ? $creationell_wp_theme_args['image_id'] : 0;
$creationell_wp_theme_image_size = is_string( $creationell_wp_theme_args['image_size'] ?? null ) || is_array( $creationell_wp_theme_args['image_size'] ?? null ) ? $creationell_wp_theme_args['image_size'] : 'medium';
$creationell_wp_theme_job_title  = is_string( $creationell_wp_theme_args['job_title'] ?? null ) ? trim( $creationell_wp_theme_args['job_title'] ) : '';
$creationell_wp_theme_bio        = is_string( $creationell_wp_theme_args['bio'] ?? null ) ? trim( $creationell_wp_theme_args['bio'] ) : '';
$creationell_wp_theme_bio        = '' !== $creationell_wp_theme_bio ? creationell_wp_theme_author_description_autop( $creationell_wp_theme_bio ) : '';
$creationell_wp_theme_links      = array();
foreach ( is_array( $creationell_wp_theme_args['links'] ?? null ) ? $creationell_wp_theme_args['links'] : array() as $creationell_wp_theme_link ) {
	$creationell_wp_theme_url  = is_array( $creationell_wp_theme_link ) && is_string( $creationell_wp_theme_link['url'] ?? null ) ? $creationell_wp_theme_link['url'] : '';
	$creationell_wp_theme_text = is_array( $creationell_wp_theme_link ) && is_string( $creationell_wp_theme_link['text'] ?? null ) ? $creationell_wp_theme_link['text'] : '';
	if ( '' !== $creationell_wp_theme_url && '' !== $creationell_wp_theme_text ) {
		$creationell_wp_theme_links[] = array( $creationell_wp_theme_url, $creationell_wp_theme_text );
	}
}

/**
 * Filters the CSS classes of the page title.
 *
 * @since 1.0.0
 *
 * @param string $classes Space-separated CSS classes.
 * @param string $context Template, for example page or archive.
 */
$creationell_wp_theme_class_entry_title = apply_filters( 'creationell_wp_theme_class_entry_title', '', 'archive' );
?>
<div class="author-header d-sm-flex align-items-center gap-4 mb-4">
	<?php
	if ( $creationell_wp_theme_image_id > 0 && wp_attachment_is_image( $creationell_wp_theme_image_id ) ) {
		// The name stands next to the image, so the image is decorative.
		echo wp_get_attachment_image(
			$creationell_wp_theme_image_id,
			$creationell_wp_theme_image_size,
			false,
			array(
				'alt'   => '',
				'class' => 'author-image rounded-circle',
			)
		);
	}
	?>
	<div class="author-header-text">
		<h1 class="entry-title <?php echo esc_attr( $creationell_wp_theme_class_entry_title ); ?>"><?php echo esc_html( $creationell_wp_theme_name ); ?></h1>
		<?php if ( '' !== $creationell_wp_theme_job_title ) : ?>
		<p class="author-job-title"><?php echo esc_html( $creationell_wp_theme_job_title ); ?></p>
		<?php endif; ?>
		<?php if ( is_string( $creationell_wp_theme_bio ) && '' !== $creationell_wp_theme_bio ) : ?>
			<?php
			/**
			 * Filters the CSS classes of the archive description.
			 *
			 * @since 1.0.0
			 *
			 * @param string $classes Space-separated CSS classes.
			 */
			$creationell_wp_theme_class_description = apply_filters( 'creationell_wp_theme_class_entry_archive_description', '' );
			?>
		<div class="archive-description <?php echo esc_attr( $creationell_wp_theme_class_description ); ?>"><?php echo wp_kses_post( $creationell_wp_theme_bio ); ?></div>
		<?php endif; ?>
		<?php if ( array() !== $creationell_wp_theme_links ) : ?>
		<ul class="list-unstyled author-links">
			<?php foreach ( $creationell_wp_theme_links as $creationell_wp_theme_link ) : ?>
			<li><a href="<?php echo esc_url( $creationell_wp_theme_link[0] ); ?>" rel="me"><?php echo esc_html( $creationell_wp_theme_link[1] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
	</div>
</div>
