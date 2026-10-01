<?php
/**
 * ACF options pages of the theme settings.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Acf;

use Creationell\WpTheme\Admin\Menu;
use Creationell\WpTheme\Core\Capabilities;
use Creationell\WpTheme\Modules\Module_State;
use Creationell\WpTheme\Settings\Bootstrap_Line;
use Creationell\WpTheme\Settings\Definition;
use Creationell\WpTheme\Settings\Option_Store;
use Creationell\WpTheme\Settings\Registry;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Registers the subpages "Texts" and "Design" below the main menu and one local field group per page.
 *
 * "Texts" (post_id "options", capability creationell_wp_theme_manage_basic)
 * holds the tabs footer, contact and social; "Design" (post_id
 * "creationell_wp_theme_global", capability creationell_wp_theme_view_advanced,
 * so editors see it read-only) holds a note about the Bootstrap line and the
 * tabs colors and fonts. Every active module adds one tab with its settings
 * of the section of the page (FS-19). No page uses edit_posts. The pages only
 * show and post the values; the adapter reads and writes them through the
 * settings and the setter.
 *
 * @since 1.0.0
 */
final class Options_Pages {

	/**
	 * Menu slug of the texts page.
	 *
	 * @since 1.0.0
	 */
	public const TEXTS = 'creationell-wp-theme-texts';

	/**
	 * Menu slug of the design page.
	 *
	 * @since 1.0.0
	 */
	public const DESIGN = 'creationell-wp-theme-design';

	/**
	 * Handle of the stylesheet of the two pages.
	 *
	 * @since 1.0.0
	 */
	public const STYLE_HANDLE = 'creationell-wp-theme-admin-settings';

	/**
	 * Script of ACF the accessible name of its notice close links is added to.
	 *
	 * @since 1.0.0
	 */
	public const NOTICE_SCRIPT_HANDLE = 'acf-input';

	/**
	 * Groups of the core settings per section, in tab order.
	 *
	 * @since 1.0.0
	 */
	public const GROUPS = array(
		'texts'  => array( 'footer', 'contact', 'social' ),
		'design' => array( 'colors', 'fonts' ),
	);

	/**
	 * Returns section, post_id and capability of the pages by menu slug.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array{section: string, post_id: string, capability: string}> Pages.
	 */
	public static function pages(): array {
		return array(
			self::TEXTS  => array(
				'section'    => 'texts',
				'post_id'    => Option_Store::OPTIONS_POST_ID,
				'capability' => Capabilities::MANAGE_BASIC,
			),
			self::DESIGN => array(
				'section'    => 'design',
				'post_id'    => Option_Store::GLOBAL_POST_ID,
				'capability' => Capabilities::VIEW_ADVANCED,
			),
		);
	}

	/**
	 * Adds the two subpages and their field groups; runs on acf/init.
	 *
	 * The action acf/init runs on init with priority 5, before Registry::boot() on
	 * init 10; the registry is booted here first, so the settings of the modules are known.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		Registry::boot();
		$titles = array(
			self::TEXTS  => __( 'Texts', 'creationell-wp-theme' ),
			self::DESIGN => __( 'Design', 'creationell-wp-theme' ),
		);
		foreach ( self::pages() as $slug => $page ) {
			acf_add_options_sub_page(
				array(
					'page_title'      => $titles[ $slug ],
					'menu_title'      => $titles[ $slug ],
					'menu_slug'       => $slug,
					'parent_slug'     => Menu::SLUG,
					'capability'      => $page['capability'],
					'post_id'         => $page['post_id'],
					'autoload'        => false,
					'update_button'   => __( 'Save', 'creationell-wp-theme' ),
					'updated_message' => self::updated_message(),
				)
			);
			acf_add_local_field_group(
				array(
					'key'                   => 'group_creationell_wp_theme_' . $page['section'],
					'title'                 => $titles[ $slug ],
					'fields'                => self::fields( $page['section'] ),
					'location'              => array(
						array(
							array(
								'param'    => 'options_page',
								'operator' => '==',
								'value'    => $slug,
							),
						),
					),
					'style'                 => 'seamless',
					'label_placement'       => 'top',
					'instruction_placement' => 'label',
					'active'                => true,
				)
			);
		}
	}

	/**
	 * Returns the success message the pages pass to ACF as updated_message.
	 *
	 * The theme shows its own result list after a save (Settings_Notices), so
	 * Acf_Adapter::admin_head() removes the ACF notice with this text again; the
	 * text stays for ACF versions whose notice store the adapter does not know.
	 *
	 * @since 1.0.0
	 *
	 * @return string Translated message.
	 */
	public static function updated_message(): string {
		return __( 'The settings were processed.', 'creationell-wp-theme' );
	}

	/**
	 * Removes the success notice ACF adds after a save of a theme page; the result list of the theme stays the only one.
	 *
	 * ACF adds the notice on acf/input/admin_head with priority 10 when the URL
	 * carries message=1 and prints its notice store on admin_notices. Only the
	 * notice of type success with the text of updated_message() goes; validation
	 * errors and other notices stay. Without the store of ACF nothing happens.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function remove_acf_updated_notice(): void {
		$store = function_exists( 'acf_get_store' ) ? acf_get_store( 'notices' ) : false;
		if ( ! $store instanceof \ACF_Data ) {
			return;
		}
		foreach ( $store->get_data() as $id => $notice ) {
			if ( $notice instanceof \ACF_Admin_Notice && 'success' === $notice->get( 'type' ) && self::updated_message() === $notice->get( 'text' ) ) {
				$store->remove( (string) $id );
			}
		}
	}

	/**
	 * Returns the fields of a page: notes, then per tab its settings.
	 *
	 * @since 1.0.0
	 *
	 * @param string $section "texts" or "design".
	 * @return array<int, array<string, mixed>> ACF fields in page order.
	 */
	public static function fields( string $section ): array {
		$fields = array();
		if ( 'design' === $section ) {
			$fields[] = Field_Factory::message( 'bootstrap_line', __( 'Bootstrap line', 'creationell-wp-theme' ), self::line_note() );
		}
		foreach ( self::tabs( $section ) as $group => $tab ) {
			$fields[] = Field_Factory::tab( $group, $tab['label'] );
			foreach ( $tab['definitions'] as $definition ) {
				$fields[] = Field_Factory::field( $definition );
			}
		}
		return $fields;
	}

	/**
	 * Returns the tabs of a section with their definitions.
	 *
	 * First the core groups in the order of GROUPS, then one tab per active
	 * module. A setting belongs to a module when its group is the module slug
	 * or its key starts with the module switch key; settings of inactive modules
	 * and of unknown groups get no tab.
	 *
	 * @since 1.0.0
	 *
	 * @param string $section "texts" or "design".
	 * @return array<string, array{label: string, definitions: array<int, Definition>}> Tabs by group.
	 */
	public static function tabs( string $section ): array {
		$tabs = array();
		foreach ( self::GROUPS[ $section ] ?? array() as $group ) {
			$tabs[ $group ] = array(
				'label'       => self::group_label( $group ),
				'definitions' => array(),
			);
		}
		$state = Module_State::instance();
		foreach ( Registry::instance()->all() as $definition ) {
			if ( $section !== $definition->section ) {
				continue;
			}
			if ( null !== $definition->group && isset( $tabs[ $definition->group ] ) && in_array( $definition->group, self::GROUPS[ $section ] ?? array(), true ) ) {
				$tabs[ $definition->group ]['definitions'][] = $definition;
				continue;
			}
			$slug = self::module_of( $definition );
			if ( null === $slug || 'active' !== $state->state( $slug ) ) {
				continue;
			}
			if ( ! isset( $tabs[ $slug ] ) ) {
				$tabs[ $slug ] = array(
					'label'       => $state->catalog()->title( $slug ),
					'definitions' => array(),
				);
			}
			$tabs[ $slug ]['definitions'][] = $definition;
		}
		return array_filter( $tabs, static fn( array $tab ): bool => array() !== $tab['definitions'] );
	}

	/**
	 * Tells whether a user may change at least one setting of a page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $menu_slug Menu slug.
	 * @param int    $user_id   User ID.
	 * @return bool True when the page has a setting the user may change.
	 */
	public static function writable( string $menu_slug, int $user_id ): bool {
		$page = self::pages()[ $menu_slug ] ?? null;
		if ( null === $page ) {
			return false;
		}
		foreach ( self::tabs( $page['section'] ) as $tab ) {
			foreach ( $tab['definitions'] as $definition ) {
				if ( user_can( $user_id, $definition->capability ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Returns the menu slug of the requested admin page when it is one of the two pages.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null Menu slug, or null on any other page.
	 */
	public static function current(): ?string {
		$plugin_page = $GLOBALS['plugin_page'] ?? null;
		return is_string( $plugin_page ) && isset( self::pages()[ $plugin_page ] ) ? $plugin_page : null;
	}

	/**
	 * Loads the stylesheet of the settings pages on these pages only; runs on admin_enqueue_scripts.
	 *
	 * Also names the close links of the ACF notices there: ACF prints them as
	 * empty links (for example on the notice of a failed validation), so a
	 * screen reader announces nothing. A small script after acf-input gives each
	 * one an aria-label, also for notices ACF adds later. The link keeps its
	 * role: the role button would promise the space key, which a link ignores.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function enqueue_styles(): void {
		if ( null === self::current() ) {
			return;
		}
		wp_enqueue_style( self::STYLE_HANDLE, get_template_directory_uri() . '/assets/css/admin-settings.css', array(), CREATIONELL_WP_THEME_VERSION );
		wp_add_inline_script( self::NOTICE_SCRIPT_HANDLE, self::notice_script( __( 'Dismiss this notice', 'creationell-wp-theme' ) ) );
	}

	/**
	 * Returns the script that names the close links of the ACF notices.
	 *
	 * @since 1.0.0
	 *
	 * @param string $label Accessible name of the close links.
	 * @return string JavaScript code.
	 */
	private static function notice_script( string $label ): string {
		return '( function () {'
			. ' var label = ' . (string) wp_json_encode( $label ) . ';'
			. ' function name() { document.querySelectorAll( ".acf-notice-dismiss:not([aria-label])" ).forEach( function ( link ) { link.setAttribute( "aria-label", label ); } ); }'
			. ' function start() { var root = document.getElementById( "wpbody-content" ) || document.body; name(); new MutationObserver( name ).observe( root, { childList: true, subtree: true } ); }'
			. ' if ( "loading" === document.readyState ) { document.addEventListener( "DOMContentLoaded", start ); } else { start(); }'
			. ' }() );';
	}

	/**
	 * Returns the module a setting belongs to.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition $definition Definition.
	 * @return string|null Installed module slug, or null.
	 */
	private static function module_of( Definition $definition ): ?string {
		$catalog = Module_State::instance()->catalog();
		foreach ( array_keys( $catalog->installed() ) as $slug ) {
			if ( $definition->group === $slug || str_starts_with( $definition->key, Registry::module_key( $slug ) . '_' ) ) {
				return $slug;
			}
		}
		return null;
	}

	/**
	 * Returns the translated label of a core group.
	 *
	 * @since 1.0.0
	 *
	 * @param string $group Group.
	 * @return string Label.
	 */
	private static function group_label( string $group ): string {
		$labels = array(
			'colors'  => __( 'Colors', 'creationell-wp-theme' ),
			'fonts'   => __( 'Fonts', 'creationell-wp-theme' ),
			'footer'  => __( 'Footer', 'creationell-wp-theme' ),
			'contact' => __( 'Contact', 'creationell-wp-theme' ),
			'social'  => __( 'Social media', 'creationell-wp-theme' ),
		);
		return $labels[ $group ] ?? $group;
	}

	/**
	 * Returns the note about the Bootstrap line: the active line and the lines this version does not include.
	 *
	 * @since 1.0.0
	 *
	 * @return string Translated plain text.
	 */
	private static function line_note(): string {
		$line  = Bootstrap_Line::instance();
		$parts = array(
			sprintf(
				/* translators: %d: number of the active Bootstrap line, e.g. 5. */
				__( 'The theme uses Bootstrap line %d. Only the agency can switch the line.', 'creationell-wp-theme' ),
				$line->active()
			),
		);
		foreach ( $line->available() as $other => $available ) {
			if ( ! $available ) {
				$parts[] = sprintf(
					/* translators: %d: number of a Bootstrap line, e.g. 6. */
					__( 'Bootstrap line %d is not included in this theme version.', 'creationell-wp-theme' ),
					$other
				);
			}
		}
		return implode( "\n\n", $parts );
	}
}
