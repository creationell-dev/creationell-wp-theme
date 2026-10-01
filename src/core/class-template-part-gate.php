<?php
/**
 * Render path and gate of the template parts header and footer.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Core;

use Creationell\WpTheme\Cli\Doctor_Command;
use Creationell\WpTheme\Modules\HeaderFooter\Menu_Scope;
use Creationell\WpTheme\Modules\HeaderFooter\Part_Translations;
use Creationell\WpTheme\Modules\Module_State;
use WP_Block_Template;
use WP_HTML_Tag_Processor;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Shows the template parts header and footer only while the module header-footer is active.
 *
 * The templates header.php and footer.php ask render() first and fall back to their PHP
 * markup when it returns false: for another slug, while the module is not
 * effectively active, and while the active stylesheet has no part or an empty
 * one. A part renders through the template-part block of the active
 * stylesheet, without a theme attribute, so WordPress finds the part of the
 * database before the file of the theme. The wrapper gets the ID of the PHP
 * header ("masthead") or footer ("footer"): the skip link to the footer and the
 * sticky offset of the header script need them.
 *
 * While the module is not active, the lists of template parts leave out header
 * and footer, so the Site Editor offers no part whose changes would never show.
 * The gate belongs to the theme core and runs whatever the module state; so
 * does the doctor section header_footer, which reports the module state, the
 * source of each part, the missing translations and the menu scope.
 *
 * @since 1.0.0
 */
final class Template_Part_Gate {

	/**
	 * Slug of the module that shows the parts.
	 *
	 * @since 1.0.0
	 */
	public const MODULE = 'header-footer';

	/**
	 * Filter for the CSS classes of the part wrappers.
	 *
	 * @since 1.0.0
	 */
	public const FILTER_CLASSES = 'creationell_wp_theme_template_part_classes';

	/**
	 * Wrapper element per part slug.
	 *
	 * @since 1.0.0
	 */
	public const TAGS = array(
		'header' => 'header',
		'footer' => 'footer',
	);

	/**
	 * ID of the wrapper per part slug, the IDs of the PHP header and footer.
	 *
	 * @since 1.0.0
	 */
	public const IDS = array(
		'header' => 'masthead',
		'footer' => 'footer',
	);

	/**
	 * Key of the doctor section.
	 *
	 * @since 1.0.0
	 */
	public const DOCTOR_SECTION = 'header_footer';

	/**
	 * Registers the hooks; called by Theme::boot().
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'get_block_templates', array( self::class, 'filter_templates' ), 10, 3 );
		add_action( 'cli_init', array( self::class, 'add_doctor_section' ), 10, 0 );
	}

	/**
	 * Adds the doctor section header_footer once; runs on cli_init.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function add_doctor_section(): void {
		if ( in_array( self::DOCTOR_SECTION, Doctor_Command::section_keys(), true ) ) {
			return;
		}
		Doctor_Command::add_section( self::DOCTOR_SECTION, array( self::class, 'doctor_section' ) );
	}

	/**
	 * Collects the doctor section header_footer; reads only.
	 *
	 * Keys: state (effective state of the module), parts (per slug the source,
	 * "db" for a part stored for the active stylesheet in any language, else
	 * "file", and missing_languages, the languages without a usable
	 * translation), menus_scope ("limited" while the module keeps editors on
	 * the header and footer menus, else "all": WordPress alone decides) and,
	 * with findings, warnings. Missing translations are looked up only while
	 * the module is active and WPML runs (Part_Translations::missing()).
	 *
	 * Example:
	 *
	 *     wp creationell-theme doctor --format=json | jq -e '.header_footer.parts.header.missing_languages == []'
	 *
	 * @since 1.0.0
	 *
	 * @return array{state: string, parts: array{header: array{source: string, missing_languages: list<string>}, footer: array{source: string, missing_languages: list<string>}}, menus_scope: string, warnings?: list<string>} Values.
	 */
	public static function doctor_section(): array {
		$state    = Module_State::instance()->state( self::MODULE );
		$findings = 'active' === $state && class_exists( Part_Translations::class, false ) ? Part_Translations::missing() : array();
		$parts    = array();
		foreach ( array_keys( self::TAGS ) as $slug ) {
			$languages = array();
			foreach ( $findings as $finding ) {
				if ( $slug === $finding['slug'] ) {
					$languages[] = $finding['lang'];
				}
			}
			$parts[ $slug ] = array(
				'source'            => array() === self::stored_parts( $slug ) ? 'file' : 'db',
				'missing_languages' => $languages,
			);
		}
		$values = array(
			'state'       => $state,
			'parts'       => array(
				'header' => $parts['header'],
				'footer' => $parts['footer'],
			),
			'menus_scope' => false === has_action( 'load-nav-menus.php', array( Menu_Scope::class, 'guard_screen' ) ) ? 'all' : 'limited',
		);
		if ( array() !== $findings ) {
			$values['warnings'] = Part_Translations::doctor_warnings( $findings );
		}
		return $values;
	}

	/**
	 * Returns the IDs of the template parts stored in the database for the active stylesheet under a slug, in every language.
	 *
	 * Reads without query filters, so WPML does not narrow the result to the
	 * current language. Only published parts count; a translation stored under
	 * another slug is not found.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Part slug, header or footer.
	 * @return list<int> Post IDs in ascending order.
	 */
	public static function stored_parts( string $slug ): array {
		$ids        = get_posts(
			array(
				'post_type'        => 'wp_template_part',
				'name'             => $slug,
				'post_status'      => 'publish',
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);
		$stylesheet = get_stylesheet(); // creationell-allow-stylesheet: template parts in the database belong to the active theme (term wp_theme).
		$parts      = array();
		foreach ( $ids as $id ) {
			$themes = wp_get_object_terms( (int) $id, 'wp_theme', array( 'fields' => 'names' ) );
			if ( is_array( $themes ) && in_array( $stylesheet, $themes, true ) ) {
				$parts[] = (int) $id;
			}
		}
		return $parts;
	}

	/**
	 * Prints the template part header or footer of the active stylesheet.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Part slug, header or footer.
	 * @return bool True when the part was printed; false when the caller prints its own markup.
	 */
	public static function render( string $slug ): bool {
		if ( ! isset( self::TAGS[ $slug ] ) ) {
			_doing_it_wrong(
				'creationell_wp_theme_render_template_part',
				esc_html( sprintf( 'Unknown template part "%s"; use header or footer.', $slug ) ),
				'1.0.0'
			);
			return false;
		}
		if ( ! self::active() ) {
			return false;
		}
		$template = get_block_template( get_stylesheet() . '//' . $slug, 'wp_template_part' ); // creationell-allow-stylesheet: template parts belong to the active theme, like the template-part block looks them up.
		if ( ! $template instanceof WP_Block_Template || '' === trim( (string) $template->content ) ) {
			return false;
		}
		$attributes = array(
			'slug'    => $slug,
			'tagName' => self::TAGS[ $slug ],
		);
		$classes    = self::classes()[ $slug ];
		if ( '' !== $classes ) {
			$attributes['className'] = $classes;
		}
		echo self::with_id( do_blocks( '<!-- wp:template-part ' . wp_json_encode( $attributes ) . ' /-->' ), self::IDS[ $slug ] );
		return true;
	}

	/**
	 * Returns the CSS classes of the part wrappers.
	 *
	 * The header starts from the classes of the PHP header (filter
	 * creationell_wp_theme_class_header plus site-header), the footer from
	 * creationell-theme-footer. Every class is sanitized; slugs other than
	 * header and footer are dropped.
	 *
	 * @since 1.0.0
	 *
	 * @return array{header: string, footer: string} Space-separated classes per slug.
	 */
	public static function classes(): array {
		/**
		 * Filters the CSS classes of the site header.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 */
		$header   = apply_filters( 'creationell_wp_theme_class_header', 'sticky-top bg-body-tertiary' );
		$defaults = array(
			'header' => trim( ( is_string( $header ) ? $header : '' ) . ' site-header' ),
			'footer' => 'creationell-theme-footer',
		);

		/**
		 * Filters the CSS classes of the wrappers of the template parts header and footer.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, string> $classes Space-separated classes by part slug, header and footer.
		 */
		$filtered = apply_filters( 'creationell_wp_theme_template_part_classes', $defaults );
		$filtered = is_array( $filtered ) ? $filtered : $defaults;
		$classes  = array();
		foreach ( array_keys( $defaults ) as $slug ) {
			$value            = $filtered[ $slug ] ?? '';
			$names            = preg_split( '~\s+~', is_string( $value ) ? $value : '', -1, PREG_SPLIT_NO_EMPTY );
			$names            = array_filter( array_map( 'sanitize_html_class', false === $names ? array() : $names ) );
			$classes[ $slug ] = implode( ' ', array_unique( $names ) );
		}
		return array(
			'header' => $classes['header'],
			'footer' => $classes['footer'],
		);
	}

	/**
	 * Drops the parts header and footer from lists of template parts while the module is not active; runs on get_block_templates.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $templates     Templates found.
	 * @param mixed $query         Query arguments, unused.
	 * @param mixed $template_type Template type, wp_template or wp_template_part.
	 * @return mixed Templates; values other than a list of template parts unchanged.
	 */
	public static function filter_templates( mixed $templates, mixed $query, mixed $template_type ): mixed {
		unset( $query );
		if ( 'wp_template_part' !== $template_type || ! is_array( $templates ) || self::active() ) {
			return $templates;
		}
		return array_values(
			array_filter(
				$templates,
				static fn( mixed $template ): bool => ! ( $template instanceof WP_Block_Template && isset( self::TAGS[ $template->slug ] ) )
			)
		);
	}

	/**
	 * Tells whether the module header-footer is effectively active.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True while the module is active.
	 */
	private static function active(): bool {
		return 'active' === Module_State::instance()->state( self::MODULE );
	}

	/**
	 * Gives the first tag of the rendered part an ID, unless it has one.
	 *
	 * @since 1.0.0
	 *
	 * @param string $html Rendered part.
	 * @param string $id   ID.
	 * @return string Part with the ID.
	 */
	private static function with_id( string $html, string $id ): string {
		$tags = new WP_HTML_Tag_Processor( $html );
		if ( ! $tags->next_tag() || null !== $tags->get_attribute( 'id' ) ) {
			return $html;
		}
		$tags->set_attribute( 'id', $id );
		return $tags->get_updated_html();
	}
}
