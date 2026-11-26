<?php
/**
 * Template part: the share section below a single post.
 *
 * Prints the section only around content: the share buttons of the plugin
 * Shariff, unchanged, or the own list of share links. A network link opens a
 * new tab and says so; the mail link opens none. The copy button and the
 * button for the share dialog of the system stay hidden until the script
 * share.js finds the clipboard (in a secure context) or the dialog; the
 * status line below the list announces the result. The module blog prints
 * this part on the action creationell_wp_theme_after_entry_terms.
 *
 * Arguments (raw values, escaped here): heading (empty gives "Share this
 * post"), heading_id (ID of the heading; a missing ID comes from
 * wp_unique_id()), shariff (markup of Shariff, printed unchanged; when it is
 * not empty it replaces the list), items (own links in their order, each with
 * network, name, url and icon; email gets the mail texts, copy needs no url
 * and brings the button of the share dialog), url (permalink for the copy and
 * share buttons), title (plain title for the share dialog), context
 * (template, for example single).
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_args    = isset( $args ) && is_array( $args ) ? $args : array();
$creationell_wp_theme_shariff = is_string( $creationell_wp_theme_args['shariff'] ?? null ) && '' !== trim( $creationell_wp_theme_args['shariff'] ) ? $creationell_wp_theme_args['shariff'] : '';
$creationell_wp_theme_url     = is_string( $creationell_wp_theme_args['url'] ?? null ) ? $creationell_wp_theme_args['url'] : '';
$creationell_wp_theme_title   = is_string( $creationell_wp_theme_args['title'] ?? null ) ? $creationell_wp_theme_args['title'] : '';
$creationell_wp_theme_entries = array();
if ( '' === $creationell_wp_theme_shariff ) {
	foreach ( is_array( $creationell_wp_theme_args['items'] ?? null ) ? $creationell_wp_theme_args['items'] : array() as $creationell_wp_theme_item ) {
		if ( ! is_array( $creationell_wp_theme_item ) || ! is_string( $creationell_wp_theme_item['network'] ?? null ) ) {
			continue;
		}
		$creationell_wp_theme_network = $creationell_wp_theme_item['network'];
		$creationell_wp_theme_href    = 'copy' === $creationell_wp_theme_network ? $creationell_wp_theme_url : ( is_string( $creationell_wp_theme_item['url'] ?? null ) ? $creationell_wp_theme_item['url'] : '' );
		$creationell_wp_theme_name    = is_string( $creationell_wp_theme_item['name'] ?? null ) ? trim( $creationell_wp_theme_item['name'] ) : '';
		if ( '' === esc_url( $creationell_wp_theme_href ) || ( '' === $creationell_wp_theme_name && ! in_array( $creationell_wp_theme_network, array( 'copy', 'email' ), true ) ) ) {
			continue;
		}
		$creationell_wp_theme_entries[] = array(
			'network' => $creationell_wp_theme_network,
			'href'    => $creationell_wp_theme_href,
			'name'    => $creationell_wp_theme_name,
			'icon'    => creationell_wp_theme_icon( is_string( $creationell_wp_theme_item['icon'] ?? null ) ? $creationell_wp_theme_item['icon'] : '', false ),
		);
	}
	if ( array() === $creationell_wp_theme_entries ) {
		return;
	}
}
$creationell_wp_theme_heading    = is_string( $creationell_wp_theme_args['heading'] ?? null ) ? trim( $creationell_wp_theme_args['heading'] ) : '';
$creationell_wp_theme_heading    = '' === $creationell_wp_theme_heading ? __( 'Share this post', 'creationell-wp-theme' ) : $creationell_wp_theme_heading;
$creationell_wp_theme_heading_id = is_string( $creationell_wp_theme_args['heading_id'] ?? null ) && '' !== $creationell_wp_theme_args['heading_id'] ? $creationell_wp_theme_args['heading_id'] : wp_unique_id( 'creationell-theme-blog-share-' );
$creationell_wp_theme_allowed    = creationell_wp_theme_kses_allowed_svg( wp_kses_allowed_html( 'post' ) );
?>
<section class="share" aria-labelledby="<?php echo esc_attr( $creationell_wp_theme_heading_id ); ?>">
	<h2 id="<?php echo esc_attr( $creationell_wp_theme_heading_id ); ?>" class="h6"><?php echo esc_html( $creationell_wp_theme_heading ); ?></h2>
<?php
if ( '' !== $creationell_wp_theme_shariff ) :
	// The markup of Shariff, unchanged: the plugin escapes its own output.
	echo $creationell_wp_theme_shariff . "\n";
else :
	?>
	<ul class="list-inline share-list">
	<?php
	foreach ( $creationell_wp_theme_entries as $creationell_wp_theme_entry ) :
		if ( 'copy' === $creationell_wp_theme_entry['network'] ) :
			?>
		<li class="list-inline-item" hidden data-creationell-share-copy-item><button type="button" class="btn btn-outline-secondary btn-sm" data-creationell-share-copy data-creationell-share-url="<?php echo esc_attr( $creationell_wp_theme_entry['href'] ); ?>" data-msg-ok="<?php esc_attr_e( 'Link copied', 'creationell-wp-theme' ); ?>" data-msg-error="<?php esc_attr_e( 'The link could not be copied.', 'creationell-wp-theme' ); ?>"><?php echo wp_kses( $creationell_wp_theme_entry['icon'], $creationell_wp_theme_allowed ); ?> <?php esc_html_e( 'Copy link', 'creationell-wp-theme' ); ?></button></li>
		<li class="list-inline-item" hidden data-creationell-share-native-item><button type="button" class="btn btn-outline-secondary btn-sm" data-creationell-share-native data-creationell-share-url="<?php echo esc_attr( $creationell_wp_theme_entry['href'] ); ?>" data-creationell-share-title="<?php echo esc_attr( $creationell_wp_theme_title ); ?>" data-msg-error="<?php esc_attr_e( 'The link could not be shared.', 'creationell-wp-theme' ); ?>"><?php echo wp_kses( creationell_wp_theme_icon( 'share', false ), $creationell_wp_theme_allowed ); ?> <?php esc_html_e( 'Share…', 'creationell-wp-theme' ); ?></button></li>
			<?php
		elseif ( 'email' === $creationell_wp_theme_entry['network'] ) :
			?>
		<li class="list-inline-item"><a class="btn btn-outline-secondary btn-sm" href="<?php echo esc_url( $creationell_wp_theme_entry['href'] ); ?>"><?php echo wp_kses( $creationell_wp_theme_entry['icon'], $creationell_wp_theme_allowed ); ?><span class="visually-hidden"><?php esc_html_e( 'Share by email', 'creationell-wp-theme' ); ?></span></a></li>
			<?php
		else :
			/* translators: %s: name of a network, for example LinkedIn. */
			$creationell_wp_theme_text = sprintf( __( 'Share on %s (opens in a new tab)', 'creationell-wp-theme' ), $creationell_wp_theme_entry['name'] );
			?>
		<li class="list-inline-item"><a class="btn btn-outline-secondary btn-sm" href="<?php echo esc_url( $creationell_wp_theme_entry['href'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo wp_kses( $creationell_wp_theme_entry['icon'], $creationell_wp_theme_allowed ); ?><span class="visually-hidden"><?php echo esc_html( $creationell_wp_theme_text ); ?></span></a></li>
			<?php
		endif;
	endforeach;
	?>
	</ul>
	<p class="share-status small" role="status" data-creationell-share-status></p>
	<?php
endif;
?>
</section>
