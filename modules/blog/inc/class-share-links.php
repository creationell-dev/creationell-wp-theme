<?php
/**
 * Share links below a single post: Shariff while it is active, else own links without requests to third parties.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\Blog;

use WP_Post;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Prints the share section after the terms of a published single post.
 *
 * The setting blog_share picks the way: auto takes the plugin Shariff while
 * it is active (function shariff3uu_render) and the own links otherwise;
 * shariff takes only Shariff; links only the own links; off nothing. When
 * Shariff inserts its buttons itself (after or before the posts, by the meta
 * box of the post, or through its custom hooks at a hook of the theme), the
 * theme prints nothing, so a post never shows two share bars. The own links
 * are plain links to the share pages of the networks: no script, cookie or
 * request to a third party before a click. The template part
 * template-parts/blog/share.php prints the section; the callbacks read the
 * settings when they run.
 *
 * @since 1.0.0
 */
final class Share_Links {

	/**
	 * Networks of the setting blog_share_networks with the name for the link text and the icon.
	 *
	 * Email and copy have no name: the part has their texts.
	 *
	 * @since 1.0.0
	 */
	public const NETWORKS = array(
		'linkedin' => array(
			'name' => 'LinkedIn',
			'icon' => 'linkedin',
		),
		'facebook' => array(
			'name' => 'Facebook',
			'icon' => 'facebook',
		),
		'whatsapp' => array(
			'name' => 'WhatsApp',
			'icon' => 'whatsapp',
		),
		'x'        => array(
			'name' => 'X',
			'icon' => 'twitter-x',
		),
		'bluesky'  => array(
			'name' => 'Bluesky',
			'icon' => 'bluesky',
		),
		'threads'  => array(
			'name' => 'Threads',
			'icon' => 'threads',
		),
		'email'    => array(
			'name' => '',
			'icon' => 'envelope',
		),
		'copy'     => array(
			'name' => '',
			'icon' => 'link-45deg',
		),
	);

	/**
	 * Prefix of the custom hooks of Shariff that print its buttons at a hook of the theme.
	 *
	 * @since 1.0.0
	 */
	private const THEME_HOOK_PREFIX = 'creationell_wp_theme_';

	/**
	 * Adds the share section after the terms of a post; Blog_Module::boot() calls it.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'creationell_wp_theme_after_entry_terms', array( self::class, 'render' ), 10, 1 );
	}

	/**
	 * Prints the share section of the current post; runs on creationell_wp_theme_after_entry_terms.
	 *
	 * Prints template-parts/blog/share.php with the arguments heading,
	 * shariff (markup of Shariff, or empty), items (own links: network, name,
	 * url, icon), url (permalink), title (plain title) and context. Nothing
	 * when mode() says off or Shariff gives nothing.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $context Template, for example single or single-sidebar-left; anything other than a string gives an empty context.
	 * @return void
	 */
	public static function render( mixed $context = '' ): void {
		$post = get_post();
		if ( ! $post instanceof WP_Post ) {
			return;
		}
		$mode = self::mode( $post );
		if ( 'off' === $mode ) {
			return;
		}
		$url     = (string) get_permalink( $post );
		$title   = self::title( $post );
		$shariff = '';
		$items   = array();
		if ( 'shariff' === $mode ) {
			$shariff = self::shariff( $post, $url, $title );
			if ( '' === $shariff ) {
				return;
			}
		} else {
			foreach ( self::networks() as $network ) {
				$items[] = array(
					'network' => $network,
					'name'    => self::NETWORKS[ $network ]['name'],
					'url'     => self::url( $network, $url, $title ),
					'icon'    => self::NETWORKS[ $network ]['icon'],
				);
			}
		}
		get_template_part(
			'template-parts/blog/share',
			null,
			array(
				'heading' => Blog_Module::text( 'blog_share_title', __( 'Share this post', 'creationell-wp-theme' ) ),
				'shariff' => $shariff,
				'items'   => $items,
				'url'     => $url,
				'title'   => $title,
				'context' => is_string( $context ) ? $context : '',
			)
		);
	}

	/**
	 * Returns how a post is shared: off, shariff or links.
	 *
	 * Off for a post that is not published, in a preview, outside the view of
	 * a single post, with the setting off or shariff without the plugin, and
	 * whenever Shariff inserts its buttons itself.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Post.
	 * @return string off, shariff or links.
	 */
	public static function mode( WP_Post $post ): string {
		$mode = self::configured_mode();
		if ( 'off' === $mode || 'publish' !== $post->post_status || is_preview() || ! is_singular( 'post' ) ) {
			return 'off';
		}
		return self::shariff_self_inserts( $post ) ? 'off' : $mode;
	}

	/**
	 * Returns the way of the setting blog_share, without a post: off, shariff or links.
	 *
	 * Shariff counts while its function shariff3uu_render exists. The
	 * setting auto gives shariff with it and links without it; the setting
	 * shariff gives off without it.
	 *
	 * @since 1.0.0
	 *
	 * @return string off, shariff or links.
	 */
	public static function configured_mode(): string {
		$setting = Blog_Module::setting( 'blog_share' );
		if ( ! in_array( $setting, array( 'auto', 'shariff', 'links' ), true ) ) {
			return 'off';
		}
		if ( 'links' === $setting ) {
			return 'links';
		}
		if ( function_exists( 'shariff3uu_render' ) ) {
			return 'shariff';
		}
		return 'auto' === $setting ? 'links' : 'off';
	}

	/**
	 * Tells whether Shariff inserts its buttons on a post itself: by its global options or by the meta box of the post.
	 *
	 * Counts only while Shariff is loaded (its function shariff3uu_render or
	 * its options global): Shariff keeps the meta box switches of a post
	 * after it is deactivated or deleted, and then nothing inserts them.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Post.
	 * @return bool True when Shariff prints its own share bar on the post.
	 */
	public static function shariff_self_inserts( WP_Post $post ): bool {
		if ( ! function_exists( 'shariff3uu_render' ) && ! is_array( $GLOBALS['shariff3uu'] ?? null ) ) {
			return false;
		}
		return self::shariff_inserts_globally()
			|| (bool) get_post_meta( $post->ID, 'shariff_metabox_before', true )
			|| (bool) get_post_meta( $post->ID, 'shariff_metabox_after', true );
	}

	/**
	 * Tells whether the options of Shariff insert its buttons on every post: before or after the posts, or at a hook of the theme.
	 *
	 * Reads the options global of the plugin as the plugin does: the switches
	 * add_before and add_after of the post type post count only as the
	 * integer 1; the custom hooks are separated by a vertical bar.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when Shariff inserts itself on the posts.
	 */
	public static function shariff_inserts_globally(): bool {
		$options = $GLOBALS['shariff3uu'] ?? null;
		if ( ! is_array( $options ) ) {
			return false;
		}
		foreach ( array( 'add_before', 'add_after' ) as $place ) {
			if ( is_array( $options[ $place ] ?? null ) && 1 === ( $options[ $place ]['post'] ?? null ) ) {
				return true;
			}
		}
		$hooks = $options['custom_hooks'] ?? '';
		foreach ( is_string( $hooks ) ? explode( '|', $hooks ) : array() as $hook ) {
			if ( str_starts_with( $hook, self::THEME_HOOK_PREFIX ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns the networks of the setting blog_share_networks in their order, without unknown and repeated names.
	 *
	 * @since 1.0.0
	 *
	 * @return list<string> Keys of NETWORKS.
	 */
	public static function networks(): array {
		$setting  = Blog_Module::setting( 'blog_share_networks' );
		$networks = array();
		foreach ( explode( ',', is_string( $setting ) ? $setting : '' ) as $network ) {
			$network = trim( $network );
			if ( isset( self::NETWORKS[ $network ] ) && ! in_array( $network, $networks, true ) ) {
				$networks[] = $network;
			}
		}
		return $networks;
	}

	/**
	 * Returns the share address of a network for a permalink and a title.
	 *
	 * Each parameter is encoded once with rawurlencode(); a text of title and
	 * permalink is encoded as a whole. The address is raw: escape it with
	 * esc_url() on output.
	 *
	 * @since 1.0.0
	 *
	 * @param string $network   Key of NETWORKS.
	 * @param string $permalink Permalink of the post.
	 * @param string $title     Title of the post as plain text.
	 * @return string Address; empty for copy and unknown networks.
	 */
	public static function url( string $network, string $permalink, string $title ): string {
		$url  = rawurlencode( $permalink );
		$text = rawurlencode( $title );
		$both = rawurlencode( $title . ' ' . $permalink );
		switch ( $network ) {
			case 'linkedin':
				return 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url;
			case 'facebook':
				return 'https://www.facebook.com/sharer/sharer.php?u=' . $url;
			case 'whatsapp':
				return 'https://wa.me/?text=' . $both;
			case 'x':
				return 'https://x.com/intent/post?text=' . $text . '&url=' . $url;
			case 'bluesky':
				return 'https://bsky.app/intent/compose?text=' . $both;
			case 'threads':
				return 'https://www.threads.net/intent/post?text=' . $both;
			case 'email':
				return 'mailto:?subject=' . $text . '&body=' . $url;
		}
		return '';
	}

	/**
	 * Returns the title of a post as plain text: entities decoded, tags removed.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Post.
	 * @return string Title.
	 */
	private static function title( WP_Post $post ): string {
		return wp_strip_all_tags( html_entity_decode( get_the_title( $post ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}

	/**
	 * Returns the share buttons of Shariff for a post, called directly with the permalink and the plain title.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post  Post.
	 * @param string  $url   Permalink of the post.
	 * @param string  $title Title of the post as plain text.
	 * @return string Markup of Shariff; empty when it gives nothing.
	 */
	private static function shariff( WP_Post $post, string $url, string $title ): string {
		if ( ! function_exists( 'shariff3uu_render' ) ) {
			return '';
		}
		$defaults = array(
			'url'      => $url,
			'title'    => $title,
			'headline' => '',
			'theme'    => 'wcag',
		);

		/**
		 * Filters the attributes the theme passes to Shariff for the share buttons below a post.
		 *
		 * The theme passes the permalink and the plain title explicitly, no
		 * headline (the section has its heading) and the theme wcag; all other
		 * attributes come from the settings of Shariff. Any answer other than
		 * an array gives the defaults.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, string> $atts Attributes of shariff3uu_render(): url, title, headline and theme.
		 * @param WP_Post               $post Post that is shared.
		 */
		$atts = apply_filters( 'creationell_wp_theme_shariff_atts', $defaults, $post );
		$html = shariff3uu_render( is_array( $atts ) ? $atts : $defaults );
		return is_string( $html ) && '' !== trim( $html ) ? $html : '';
	}
}
