<?php
/**
 * Social networks of the theme settings with their icons.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\HeaderFooter;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Maps the social networks of the settings social_<network> to Bootstrap Icons and reads their links.
 *
 * The order of the map is the order of the links. Bootstrap Icons has no Xing
 * icon, so Xing shows the link icon; the name of each network stays hidden
 * for screen readers next to its icon.
 *
 * @since 1.0.0
 */
final class Social_Networks {

	/**
	 * Icon of Bootstrap Icons by network, in the order of the links.
	 *
	 * @since 1.0.0
	 */
	public const MAP = array(
		'facebook'  => 'facebook',
		'instagram' => 'instagram',
		'linkedin'  => 'linkedin',
		'xing'      => 'link-45deg',
		'youtube'   => 'youtube',
		'mastodon'  => 'mastodon',
	);

	/**
	 * Returns the links of the settings: networks with a URL that esc_url() keeps, in the order of MAP.
	 *
	 * @since 1.0.0
	 *
	 * @return list<array{network: string, name: string, url: string, icon: string}> Links.
	 */
	public static function links(): array {
		$links = array();
		foreach ( self::MAP as $network => $icon ) {
			$url = creationell_wp_theme_setting( 'social_' . $network );
			$url = is_string( $url ) ? trim( $url ) : '';
			if ( '' === $url || '' === esc_url( $url ) ) {
				continue;
			}
			$links[] = array(
				'network' => $network,
				'name'    => self::name( $network ),
				'url'     => $url,
				'icon'    => $icon,
			);
		}
		return $links;
	}

	/**
	 * Returns the name of a network; the names are the labels of the settings.
	 *
	 * @since 1.0.0
	 *
	 * @param string $network Network key of MAP.
	 * @return string Name, or the key for an unknown network.
	 */
	public static function name( string $network ): string {
		return match ( $network ) {
			'facebook'  => __( 'Facebook', 'creationell-wp-theme' ),
			'instagram' => __( 'Instagram', 'creationell-wp-theme' ),
			'linkedin'  => __( 'LinkedIn', 'creationell-wp-theme' ),
			'xing'      => __( 'XING', 'creationell-wp-theme' ),
			'youtube'   => __( 'YouTube', 'creationell-wp-theme' ),
			'mastodon'  => __( 'Mastodon', 'creationell-wp-theme' ),
			default     => $network,
		};
	}
}
