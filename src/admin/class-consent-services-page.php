<?php
/**
 * Page "Cookies and services" of the theme settings.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Admin;

use Closure;
use Creationell\WpTheme\Consent\Consent_Categories;
use Creationell\WpTheme\Consent\Consent_Cookie_Name;
use Creationell\WpTheme\Consent\Consent_Gate;
use Creationell\WpTheme\Consent\Consent_Service_Catalog;
use Creationell\WpTheme\Consent\Consent_Services_Store;
use Creationell\WpTheme\Consent\Consent_Services_Validator;
use Creationell\WpTheme\Core\Capabilities;
use Creationell\WpTheme\Modules\Consent\Texts;
use Creationell\WpTheme\Modules\Module_State;
use Creationell\WpTheme\Settings\Language;
use Creationell\WpTheme\Settings\Registry;
use Creationell\WpTheme\Settings\Set_Result;
use Creationell\WpTheme\Settings\Set_Status;
use Creationell\WpTheme\Settings\Setter;
use Creationell\WpTheme\Settings\Settings;
use Creationell\WpTheme\Settings\Write_Context;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Lists, adds, edits and deletes the services of the consent dialog and edits the category texts, without ACF.
 *
 * The page is a subpage of the theme menu for users with
 * creationell_wp_theme_manage_consent and exists only while the module
 * consent is installed. The overview shows one table per consent category
 * with the services of Consent_Services_Store, their origin (preset, import
 * or backend) and the read-only cookies of the detail filter of the child
 * theme. Below follow the preset form and the category texts of the current
 * language. The edit view (view=edit, service=<id|new>) holds the fields of
 * one service and one group per cookie; outside the default language only the
 * texts of that language are editable, with "All languages" nothing. Each form
 * posts to its own admin-post action; the handlers check the nonce of the
 * entry, stop with 403 without the capability, write through the store and
 * redirect back with codes only (result, service, key, error). Values of a
 * refused service form and of "More cookies" wait in a draft transient of the
 * user for ten minutes; the redirect carries only its token. After a change of
 * the services the page offers to raise consent_revision, so that every
 * visitor is asked again. The page reads the store once per request.
 *
 * @since 1.0.0
 */
final class Consent_Services_Page {

	/**
	 * Slug of the page below the theme menu.
	 *
	 * @since 1.0.0
	 */
	public const PAGE = 'creationell-wp-theme-consent-services';

	/**
	 * Action of the service form; the nonce action adds the service ID (or "new") and the language.
	 *
	 * @since 1.0.0
	 */
	public const ACTION_SAVE = 'creationell_wp_theme_consent_service_save';

	/**
	 * Action of the delete form; the nonce action adds the service ID.
	 *
	 * @since 1.0.0
	 */
	public const ACTION_DELETE = 'creationell_wp_theme_consent_service_delete';

	/**
	 * Action and nonce action of the preset form.
	 *
	 * @since 1.0.0
	 */
	public const ACTION_PRESET = 'creationell_wp_theme_consent_preset_add';

	/**
	 * Action of the category texts form; the nonce action adds the language.
	 *
	 * @since 1.0.0
	 */
	public const ACTION_CATEGORIES = 'creationell_wp_theme_consent_category_texts';

	/**
	 * Action and nonce action of the revision form.
	 *
	 * @since 1.0.0
	 */
	public const ACTION_REVISION = 'creationell_wp_theme_consent_revision_bump';

	/**
	 * Results the page reports after a redirect.
	 *
	 * @since 1.0.0
	 */
	public const RESULTS = array( 'saved', 'unchanged', 'deleted', 'preset_added', 'preset_duplicate', 'texts_saved', 'texts_invalid', 'invalid', 'not_found', 'revision_raised', 'revision_stale', 'revision_invalid', 'revision_locked' );

	/**
	 * Prefix of the transient name of a draft; the name adds the user ID, "_" and the token.
	 *
	 * @since 1.0.0
	 */
	public const DRAFT_PREFIX = 'creationell_wp_theme_consent_draft_';

	/**
	 * Lifetime of a draft in seconds.
	 *
	 * @since 1.0.0
	 */
	public const DRAFT_TTL = 600;

	/**
	 * Most cookie groups of the service form.
	 *
	 * @since 1.0.0
	 */
	public const MAX_COOKIES = 40;

	/**
	 * Empty cookie groups after the stored ones and per "More cookies".
	 *
	 * @since 1.0.0
	 */
	public const EMPTY_ROWS = 3;

	/**
	 * Errors the edit view reports after a redirect.
	 *
	 * @since 1.0.0
	 */
	public const ERRORS = array( 'invalid', 'incomplete', 'changed' );

	/**
	 * Prefix of the element IDs of the page.
	 */
	private const ID = 'creationell-wp-theme-consent-services-';

	/**
	 * Prefix of the element IDs of the service form.
	 */
	private const FIELD = 'creationell-wp-theme-consent-';

	/**
	 * Pattern of a draft token.
	 */
	private const TOKEN = '~^[a-f0-9]{16}$~D';

	/**
	 * Posted service fields.
	 */
	private const SERVICE_FIELDS = array( 'name', 'category', 'provider', 'privacy_url', 'purpose' );

	/**
	 * Posted fields of a cookie group.
	 */
	private const COOKIE_FIELDS = array( 'key', 'name', 'match', 'first_party', 'parent_domain', 'purpose', 'duration', 'remove' );

	/**
	 * Query arguments of the page.
	 */
	private const QUERY_KEYS = array( 'view', 'service', 'result', 'key', 'error', 'draft', 'more' );

	/**
	 * Results after which the page offers to raise the revision.
	 */
	private const ASK_REVISION = array( 'saved', 'deleted', 'preset_added' );

	/**
	 * Setting key of the consent revision.
	 */
	private const REVISION = 'consent_revision';

	/**
	 * Ends the request after a redirect; null for exit.
	 *
	 * @var (Closure(): never)|null
	 */
	private static ?Closure $halt = null;

	/**
	 * Store data read in this request; null until the first read and after a write.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $store = null;

	/**
	 * Registers the page, the handlers and the document title.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_action( 'admin_menu', array( self::class, 'add_page' ), 10, 0 );
		add_action( 'admin_post_' . self::ACTION_DELETE, array( self::class, 'handle_delete' ), 10, 0 );
		add_action( 'admin_post_' . self::ACTION_PRESET, array( self::class, 'handle_preset' ), 10, 0 );
		add_action( 'admin_post_' . self::ACTION_CATEGORIES, array( self::class, 'handle_categories' ), 10, 0 );
		add_action( 'admin_post_' . self::ACTION_REVISION, array( self::class, 'handle_revision' ), 10, 0 );
		add_action( 'admin_post_' . self::ACTION_SAVE, array( self::class, 'handle_save' ), 10, 0 );
		add_filter( 'admin_title', array( self::class, 'filter_admin_title' ), 10, 1 );
	}

	/**
	 * Adds the page below the theme menu while the module consent is installed; runs on admin_menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function add_page(): void {
		if ( ! self::installed() ) {
			return;
		}
		add_submenu_page(
			Menu::SLUG,
			__( 'Cookies and services', 'creationell-wp-theme' ),
			__( 'Cookies and services', 'creationell-wp-theme' ),
			Capabilities::MANAGE_CONSENT,
			self::PAGE,
			array( self::class, 'render' )
		);
	}

	/**
	 * Replaces the end of the request after a redirect; null goes back to exit.
	 *
	 * @since 1.0.0
	 *
	 * @param Closure|null $halt Closure that ends the request.
	 * @return void
	 *
	 * @phpstan-param (Closure(): never)|null $halt
	 */
	public static function set_halt( ?Closure $halt ): void {
		self::$halt = $halt;
	}

	/**
	 * Forgets the store data read in this request, so that the next use reads the option again.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::forget();
	}

	/**
	 * Returns the URL of the page with query arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $args Query arguments after the page.
	 * @return string Raw URL.
	 */
	public static function url( array $args = array() ): string {
		return admin_url( 'admin.php?' . http_build_query( array_merge( array( 'page' => self::PAGE ), $args ), '', '&' ) );
	}

	/**
	 * Prints the page for the current request; the callback of the page.
	 *
	 * It keeps the store read of the document title of the same request.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function render(): void {
		self::page( self::query() );
	}

	/**
	 * Puts the result of the last change in front of the document title of the page; runs on admin_title.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $admin_title Document title.
	 * @return mixed Document title.
	 */
	public static function filter_admin_title( mixed $admin_title ): mixed {
		if ( ! is_string( $admin_title ) || self::PAGE !== ( $GLOBALS['plugin_page'] ?? null ) ) {
			return $admin_title;
		}
		return self::admin_title( $admin_title, self::query() );
	}

	/**
	 * Returns the document title with the result of the last change, or the error of the edit view, in front.
	 *
	 * It starts a new request: the store is read again.
	 *
	 * @since 1.0.0
	 *
	 * @param string                $admin_title Document title.
	 * @param array<string, string> $query       Query arguments: view, result, service, key, error.
	 * @return string Document title.
	 */
	public static function admin_title( string $admin_title, array $query ): string {
		self::forget();
		if ( 'edit' === ( $query['view'] ?? '' ) ) {
			$error = $query['error'] ?? '';
			return in_array( $error, self::ERRORS, true )
				/* translators: %s: error message. */
				? sprintf( __( 'Error: %s', 'creationell-wp-theme' ), self::error_heading( $error ) ) . ' &lsaquo; ' . $admin_title
				: $admin_title;
		}
		$result = self::result_message( $query );
		return null === $result ? $admin_title : $result[1] . ' &lsaquo; ' . $admin_title;
	}

	/**
	 * Prints the page: the edit or delete view, or the overview with presets and category texts.
	 *
	 * It starts a new request: the store is read again.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $query Query arguments: view, service, result, key, error, draft, more.
	 * @return void
	 */
	public static function render_page( array $query ): void {
		self::forget();
		self::page( $query );
	}

	/**
	 * Prints the page with the store read of this request.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $query Query arguments: view, service, result, key, error, draft, more.
	 * @return void
	 */
	private static function page( array $query ): void {
		$view    = $query['view'] ?? '';
		$service = $query['service'] ?? '';
		if ( 'edit' === $view && ( 'new' === $service || null !== self::stored( $service ) ) ) {
			self::render_edit( $query );
			return;
		}
		if ( 'edit' === $view ) {
			$query = array(
				'result'  => 'not_found',
				'service' => $service,
			);
		}
		if ( 'delete' === $view ) {
			$found = self::service( $service );
			if ( null !== $found ) {
				self::render_delete( $found );
				return;
			}
			$query = array(
				'result'  => 'not_found',
				'service' => $service,
			);
		}

		echo '<div class="wrap">' . "\n";
		printf( '<h1>%s</h1>' . "\n", esc_html__( 'Cookies and services', 'creationell-wp-theme' ) );
		self::render_result( $query );
		if ( ! self::active() ) {
			printf(
				'<div class="notice notice-warning inline"><p>%1$s <a href="%2$s">%3$s</a></p></div>' . "\n",
				esc_html__( 'The cookie consent module is off. The entries take effect as soon as it is switched on.', 'creationell-wp-theme' ),
				esc_url( Module_Page::url() ),
				esc_html__( 'Go to the theme modules', 'creationell-wp-theme' )
			);
		}
		printf( '<p>%s</p>' . "\n", esc_html__( 'The services listed here appear in the cookie settings dialog. Their cookies, except those of the category Necessary, are deleted when a visitor withdraws the consent.', 'creationell-wp-theme' ) );
		self::render_tables();
		printf(
			'<p><a class="button" href="%1$s">%2$s</a></p>' . "\n",
			esc_url(
				self::url(
					array(
						'view'    => 'edit',
						'service' => 'new',
					)
				)
			),
			esc_html__( 'Add service', 'creationell-wp-theme' )
		);
		self::render_presets();
		self::render_category_texts( $query );
		echo '</div>' . "\n";
	}

	/**
	 * Deletes a service and its texts in all languages; runs on admin_post_creationell_wp_theme_consent_service_delete.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function handle_delete(): void {
		$id = isset( $_POST['service'] ) ? sanitize_key( wp_unslash( $_POST['service'] ) ) : '';
		check_admin_referer( self::ACTION_DELETE . '_' . $id );
		self::require_capability();

		self::forget();
		$data     = self::store();
		$services = array();
		$found    = false;
		foreach ( is_array( $data['services'] ) ? $data['services'] : array() as $service ) {
			if ( '' !== $id && is_array( $service ) && ( $service['id'] ?? null ) === $id ) {
				$found = true;
				continue;
			}
			$services[] = $service;
		}
		if ( ! $found ) {
			self::finish( array( 'result' => 'not_found' ) );
		}
		$data['services'] = $services;
		$texts            = is_array( $data['texts'] ) ? $data['texts'] : array();
		foreach ( $texts as $lang => $entry ) {
			$list = is_array( $entry ) ? ( $entry['services'] ?? null ) : null;
			if ( is_array( $entry ) && is_array( $list ) ) {
				unset( $list[ $id ] );
				$entry['services'] = $list;
				$texts[ $lang ]    = $entry;
			}
		}
		$data['texts'] = $texts;

		$result = Consent_Services_Store::save( $data, self::context( null ) );
		self::forget();
		self::finish(
			array(
				'result'  => self::code( $result, 'deleted', 'invalid' ),
				'service' => $id,
			)
		);
	}

	/**
	 * Adds a preset of the catalog; runs on admin_post_creationell_wp_theme_consent_preset_add.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function handle_preset(): void {
		$id = isset( $_POST['preset'] ) ? sanitize_key( wp_unslash( $_POST['preset'] ) ) : '';
		check_admin_referer( self::ACTION_PRESET );
		self::require_capability();
		if ( ! isset( Consent_Service_Catalog::all()[ $id ] ) ) {
			self::finish( array( 'result' => 'invalid' ) );
		}

		$result = Consent_Service_Catalog::apply( $id, self::context( null ) );
		self::forget();
		if ( Set_Status::INVALID === $result->status && 'duplicate' === self::first_error( $result, 'code' ) ) {
			self::finish( array( 'result' => 'preset_duplicate' ) );
		}
		$code = self::code( $result, 'preset_added', 'invalid' );
		$args = array( 'result' => $code );
		if ( 'preset_added' === $code ) {
			foreach ( self::services() as $service ) {
				if ( $id === $service['preset'] ) {
					$args['service'] = $service['id'];
				}
			}
		}
		self::finish( $args );
	}

	/**
	 * Saves the category texts of one language; runs on admin_post_creationell_wp_theme_consent_category_texts.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function handle_categories(): void {
		$lang = isset( $_POST['lang'] ) ? sanitize_key( wp_unslash( $_POST['lang'] ) ) : '';
		check_admin_referer( self::ACTION_CATEGORIES . '_' . $lang );
		self::require_capability();
		self::require_language( $lang );

		self::forget();
		$data  = self::store();
		$texts = is_array( $data['texts'] ) ? $data['texts'] : array();
		$entry = is_array( $texts[ $lang ] ?? null ) ? $texts[ $lang ] : array();
		$list  = is_array( $entry['categories'] ?? null ) ? $entry['categories'] : array();
		foreach ( Consent_Categories::all() as $slug ) {
			// The store narrows the description to the tags of the consent texts and checks both lengths.
			$title       = isset( $_POST['categories'][ $slug ]['title'] ) && is_string( $_POST['categories'][ $slug ]['title'] ) ? sanitize_text_field( wp_unslash( $_POST['categories'][ $slug ]['title'] ) ) : '';
			$description = isset( $_POST['categories'][ $slug ]['description'] ) && is_string( $_POST['categories'][ $slug ]['description'] ) ? trim( wp_kses_post( wp_unslash( $_POST['categories'][ $slug ]['description'] ) ) ) : '';
			unset( $list[ $slug ] );
			if ( '' !== $title || '' !== $description ) {
				$list[ $slug ] = array(
					'title'       => $title,
					'description' => $description,
				);
			}
		}
		$entry['categories'] = $list;
		$texts[ $lang ]      = $entry;
		$data['texts']       = $texts;

		$result = Consent_Services_Store::save( $data, self::context( $lang ) );
		self::forget();
		$code = self::code( $result, 'texts_saved', 'texts_invalid' );
		$args = array( 'result' => $code );
		$row  = self::first_error( $result, 'service' );
		if ( 'texts_invalid' === $code && str_starts_with( $row, 'category:' ) ) {
			$args['key'] = substr( $row, strlen( 'category:' ) );
		}
		self::finish( $args );
	}

	/**
	 * Raises consent_revision by one when the shown value is still current; runs on admin_post_creationell_wp_theme_consent_revision_bump.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function handle_revision(): void {
		check_admin_referer( self::ACTION_REVISION );
		self::require_capability();
		if ( ! current_user_can( Capabilities::MANAGE_DESIGN ) ) {
			self::deny_revision();
		}
		if ( ! Registry::instance()->has( self::REVISION ) ) {
			self::finish( array( 'result' => 'revision_invalid' ) );
		}
		$from = isset( $_POST['from'] ) ? absint( wp_unslash( $_POST['from'] ) ) : -1;
		$now  = self::revision();
		if ( $from !== $now ) {
			self::finish( array( 'result' => 'revision_stale' ) );
		}
		$setter = Setter::instance();
		$result = $setter->set( self::REVISION, $now + 1, new Write_Context( 'admin', get_current_user_id() ) );
		$setter->commit();
		$code = match ( $result->status ) {
			Set_Status::SAVED     => 'revision_raised',
			Set_Status::LOCKED    => 'revision_locked',
			Set_Status::FORBIDDEN => self::deny_revision(),
			default               => 'revision_invalid',
		};
		self::finish( array( 'result' => $code ) );
	}

	/**
	 * Saves a service from the service form, or keeps the values for "More cookies"; runs on admin_post_creationell_wp_theme_consent_service_save.
	 *
	 * Order: nonce of service and language, capability, language, new
	 * services only in the default language, a complete form, "More cookies",
	 * the hash of the shown service, then the store. A refused or interrupted
	 * form keeps its values in a draft of the user.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function handle_save(): void {
		$id   = isset( $_POST['service'] ) ? sanitize_key( wp_unslash( $_POST['service'] ) ) : '';
		$lang = isset( $_POST['lang'] ) ? sanitize_key( wp_unslash( $_POST['lang'] ) ) : '';
		check_admin_referer( self::ACTION_SAVE . '_' . $id . '_' . $lang );
		self::require_capability();
		self::require_language( $lang );
		$primary = Language::instance()->default() === $lang;
		if ( 'new' === $id && ! $primary ) {
			wp_die( esc_html__( 'Create new services in the default language.', 'creationell-wp-theme' ), '', array( 'response' => 400 ) );
		}

		self::forget();
		$post = (array) wp_unslash( $_POST );
		$back = array(
			'view'    => 'edit',
			'service' => $id,
		);
		$form = self::posted( $post );
		if ( null === $form ) {
			self::finish( $back + array( 'error' => 'incomplete' ) );
		}
		$hash  = self::text( $post['hash'] ?? null );
		$draft = array(
			'service' => $id,
			'lang'    => $lang,
			'hash'    => $hash,
			'rows'    => count( $form['cookies'] ),
			'fields'  => $form['fields'],
			'cookies' => $form['cookies'],
			'errors'  => array(),
		);
		if ( 'more' === self::text( $post['do'] ?? null ) ) {
			$draft['rows'] = min( self::MAX_COOKIES, $draft['rows'] + self::EMPTY_ROWS );
			self::finish(
				$back + array(
					'more'  => '1',
					'draft' => self::save_draft( $draft ),
				)
			);
		}
		$stored = 'new' === $id ? null : self::stored( $id );
		if ( ( 'new' !== $id && null === $stored ) || ! hash_equals( self::hash( $id, $lang ), $hash ) ) {
			self::finish(
				$back + array(
					'error' => 'changed',
					'draft' => self::save_draft( $draft ),
				)
			);
		}

		$payload = $primary ? self::payload( $id, $stored, $form, $lang ) : self::payload_texts( $id, $stored ?? array(), $form, $lang );
		$result  = Consent_Services_Store::save( $payload['data'], self::context( $lang ) );
		self::forget();
		if ( Set_Status::FORBIDDEN === $result->status ) {
			self::deny();
		}
		if ( Set_Status::SAVED === $result->status || Set_Status::UNCHANGED === $result->status ) {
			self::finish(
				array(
					'result'  => Set_Status::SAVED === $result->status ? 'saved' : 'unchanged',
					'service' => $payload['id'],
				)
			);
		}
		$draft['errors'] = self::form_errors( $result, $payload['id'], $payload['positions'], $primary );
		self::finish(
			$back + array(
				'error' => 'invalid',
				'draft' => self::save_draft( $draft ),
			)
		);
	}

	/**
	 * Reads the posted service form; null when it arrived incomplete.
	 *
	 * Complete means: _complete is "1", rows is a number up to MAX_COOKIES and
	 * the cookie groups are exactly 0 to rows - 1. The privacy link and the
	 * cookie name are only trimmed, because sanitize_text_field() would delete
	 * their percent-encoded octets; the validator checks both, as on an import.
	 * Every other value passes sanitize_text_field(); a checkbox is "1" or empty.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $post Posted fields, unslashed.
	 * @return array{fields: array<string, string>, cookies: list<array<string, string>>}|null Fields and cookie groups by row.
	 */
	private static function posted( array $post ): ?array {
		$rows = $post['rows'] ?? null;
		$list = $post['cookies'] ?? array();
		if ( '1' !== ( $post['_complete'] ?? null ) || ! is_string( $rows ) || ! ctype_digit( $rows ) || (int) $rows > self::MAX_COOKIES || ! is_array( $list ) ) {
			return null;
		}
		$keys = array_keys( $list );
		sort( $keys );
		$expected = '0' === $rows ? array() : range( 0, (int) $rows - 1 );
		if ( $expected !== $keys ) {
			return null;
		}
		$fields = array();
		foreach ( self::SERVICE_FIELDS as $field ) {
			$fields[ $field ] = 'privacy_url' === $field ? self::trimmed( $post[ $field ] ?? null ) : self::text( $post[ $field ] ?? null );
		}
		$cookies = array();
		for ( $row = 0; $row < (int) $rows; ++$row ) {
			if ( ! is_array( $list[ $row ] ) ) {
				return null;
			}
			$cookie = array();
			foreach ( self::COOKIE_FIELDS as $field ) {
				$cookie[ $field ] = 'name' === $field ? self::trimmed( $list[ $row ][ $field ] ?? null ) : self::text( $list[ $row ][ $field ] ?? null );
			}
			foreach ( array( 'first_party', 'parent_domain', 'remove' ) as $flag ) {
				$cookie[ $flag ] = '1' === $cookie[ $flag ] ? '1' : '';
			}
			$cookies[] = $cookie;
		}
		return array(
			'fields'  => $fields,
			'cookies' => $cookies,
		);
	}

	/**
	 * Builds the store data from a form of the default language: structure and texts of that language.
	 *
	 * A group marked for removal falls away, and so does an empty new group
	 * (name, purpose and duration empty); a stored cookie is never dropped for
	 * being empty, the validator reports its missing name instead. A posted key
	 * counts only once and only when it is a stored key of this service; every
	 * other group gets the next free key.
	 *
	 * @since 1.0.0
	 *
	 * @param string                                                                     $id     Service ID or "new".
	 * @param array<mixed>|null                                                          $stored Stored service; null for a new one.
	 * @param array{fields: array<string, string>, cookies: list<array<string, string>>} $form Posted form.
	 * @param string                                                                     $lang   Language code.
	 * @return array{data: array<string, mixed>, id: string, positions: array<int, int>} Data, service ID and form row per saved cookie.
	 */
	private static function payload( string $id, ?array $stored, array $form, string $lang ): array {
		$data     = self::store();
		$services = is_array( $data['services'] ) ? $data['services'] : array();
		$taken    = array();
		foreach ( $services as $entry ) {
			if ( is_array( $entry ) && is_string( $entry['id'] ?? null ) ) {
				$taken[] = $entry['id'];
			}
		}
		$sid     = null === $stored ? Consent_Services_Validator::slug( $form['fields']['name'], $taken ) : $id;
		$service = $stored ?? array(
			'id'             => $sid,
			'preset'         => '',
			'preset_checked' => '',
		);
		$probe   = array(
			'cookie_seq' => is_int( $service['cookie_seq'] ?? null ) ? $service['cookie_seq'] : 0,
			'cookies'    => is_array( $service['cookies'] ?? null ) ? $service['cookies'] : array(),
		);
		$old     = self::cookie_keys( $service );

		$cookies   = array();
		$texts     = array( 'purpose' => $form['fields']['purpose'] );
		$positions = array();
		$used      = array();
		foreach ( $form['cookies'] as $row => $cookie ) {
			$key   = $cookie['key'];
			$known = in_array( $key, $old, true ) && ! isset( $used[ $key ] );
			if ( '1' === $cookie['remove'] || ( ! $known && '' === $cookie['name'] . $cookie['purpose'] . $cookie['duration'] ) ) {
				continue;
			}
			if ( ! $known ) {
				$key                 = Consent_Services_Validator::next_cookie_key( $probe );
				$probe['cookie_seq'] = (int) substr( $key, 1 );
			}
			$used[ $key ]                   = true;
			$positions[ count( $cookies ) ] = $row;
			$cookies[]                      = array(
				'key'           => $key,
				'name'          => $cookie['name'],
				'match'         => $cookie['match'],
				'first_party'   => '1' === $cookie['first_party'],
				'parent_domain' => '1' === $cookie['parent_domain'],
			);
			$texts['cookies'][ $key ]       = array(
				'purpose'  => $cookie['purpose'],
				'duration' => $cookie['duration'],
			);
		}

		$service['id']          = $sid;
		$service['category']    = $form['fields']['category'];
		$service['name']        = $form['fields']['name'];
		$service['provider']    = $form['fields']['provider'];
		$service['privacy_url'] = $form['fields']['privacy_url'];
		$service['cookie_seq']  = $probe['cookie_seq'];
		$service['cookies']     = $cookies;
		$index                  = null === $stored ? null : self::stored_index( $sid );
		if ( null === $index ) {
			$services[] = $service;
		} else {
			$services[ $index ] = $service;
		}
		$data['services'] = $services;
		$data['texts']    = self::with_texts( $data['texts'], $lang, $sid, $texts );
		return array(
			'data'      => $data,
			'id'        => $sid,
			'positions' => $positions,
		);
	}

	/**
	 * Builds the store data from a form of another language: only the purposes and durations of that language.
	 *
	 * @since 1.0.0
	 *
	 * @param string                                                                     $id     Service ID.
	 * @param array<mixed>                                                               $stored Stored service.
	 * @param array{fields: array<string, string>, cookies: list<array<string, string>>} $form Posted form.
	 * @param string                                                                     $lang   Language code.
	 * @return array{data: array<string, mixed>, id: string, positions: array<int, int>} Data, service ID and form row per stored cookie.
	 */
	private static function payload_texts( string $id, array $stored, array $form, string $lang ): array {
		$data      = self::store();
		$index     = array_flip( self::cookie_keys( $stored ) );
		$texts     = array( 'purpose' => $form['fields']['purpose'] );
		$positions = array();
		foreach ( $form['cookies'] as $row => $cookie ) {
			$key = $cookie['key'];
			if ( ! isset( $index[ $key ] ) || isset( $texts['cookies'][ $key ] ) ) {
				continue;
			}
			$positions[ $index[ $key ] ] = $row;
			$texts['cookies'][ $key ]    = array(
				'purpose'  => $cookie['purpose'],
				'duration' => $cookie['duration'],
			);
		}
		$data['texts'] = self::with_texts( $data['texts'], $lang, $id, $texts );
		return array(
			'data'      => $data,
			'id'        => $id,
			'positions' => $positions,
		);
	}

	/**
	 * Returns the texts with the entry of one service in one language replaced.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $texts Texts by language.
	 * @param string               $lang  Language code.
	 * @param string               $id    Service ID.
	 * @param array<string, mixed> $entry Purpose and cookie texts of the service.
	 * @return array<mixed> Texts.
	 */
	private static function with_texts( mixed $texts, string $lang, string $id, array $entry ): array {
		$texts                = is_array( $texts ) ? $texts : array();
		$language             = is_array( $texts[ $lang ] ?? null ) ? $texts[ $lang ] : array();
		$list                 = is_array( $language['services'] ?? null ) ? $language['services'] : array();
		$list[ $id ]          = $entry;
		$language['services'] = $list;
		$texts[ $lang ]       = $language;
		return $texts;
	}

	/**
	 * Maps the errors of the store to the fields of the form.
	 *
	 * The cookie position of an error is the index in the saved list; the
	 * positions give its form row. Errors without a field of the form keep an
	 * empty ID and show only in the summary.
	 *
	 * @since 1.0.0
	 *
	 * @param Set_Result      $result    Refused result with data.errors.
	 * @param string          $id        Service ID.
	 * @param array<int, int> $positions Form row by saved cookie index.
	 * @param bool            $primary   Whether the form is the one of the default language.
	 * @return list<array{id: string, label: string, message: string}> Errors with element ID, field label and message.
	 */
	private static function form_errors( Set_Result $result, string $id, array $positions, bool $primary ): array {
		$labels = self::labels();
		$errors = is_array( $result->data['errors'] ?? null ) ? $result->data['errors'] : array();
		$out    = array();
		foreach ( $errors as $error ) {
			if ( ! is_array( $error ) ) {
				continue;
			}
			$field  = self::string( $error['field'] ?? null );
			$part   = str_starts_with( $field, 'texts.' ) ? substr( $field, (int) strrpos( $field, '.' ) + 1 ) : $field;
			$cookie = $error['cookie'] ?? null;
			$own    = self::string( $error['service'] ?? null ) === $id;
			$target = '';
			$label  = '';
			if ( $own && is_int( $cookie ) && isset( $positions[ $cookie ] ) ) {
				$name = match ( $part ) {
					'name', 'key' => 'name',
					'match', 'purpose', 'duration' => $part,
					default => '',
				};
				if ( '' !== $name && ( $primary || ( 'match' !== $name && 'name' !== $name ) ) ) {
					$target = self::FIELD . 'cookie-' . $positions[ $cookie ] . '-' . $name;
					/* translators: 1: number of the cookie group, 2: label of the field. */
					$label = sprintf( __( 'Cookie %1$d, %2$s', 'creationell-wp-theme' ), $positions[ $cookie ] + 1, $labels[ $name ] );
				}
			} elseif ( $own && null === $cookie ) {
				$name = match ( $part ) {
					'id', 'name' => 'name',
					'category', 'provider', 'privacy_url', 'purpose' => $part,
					default => '',
				};
				if ( '' !== $name && ( $primary || 'purpose' === $name ) ) {
					$target = self::FIELD . $name;
					$label  = $labels[ $name ];
				}
			}
			$out[] = array(
				'id'      => $target,
				'label'   => $label,
				'message' => self::string( $error['message'] ?? null ),
			);
		}
		return $out;
	}

	/**
	 * Stores a draft of the current user and returns its token.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $draft Service, language, hash, rows, fields, cookies and errors.
	 * @return string Token of 16 hex digits.
	 */
	private static function save_draft( array $draft ): string {
		$token = bin2hex( random_bytes( 8 ) );
		set_transient( self::DRAFT_PREFIX . get_current_user_id() . '_' . $token, $draft, self::DRAFT_TTL );
		return $token;
	}

	/**
	 * Reads and deletes the draft of the current user named in the query.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $query Query arguments with draft.
	 * @param string                $id    Service ID or "new" of the view.
	 * @param string                $lang  Language of the view.
	 * @return array{0: array<mixed>|null, 1: bool} The draft, null without one; true when a token named a draft that is gone or belongs elsewhere.
	 */
	private static function take_draft( array $query, string $id, string $lang ): array {
		$token = $query['draft'] ?? '';
		if ( 1 !== preg_match( self::TOKEN, $token ) ) {
			return array( null, false );
		}
		$name  = self::DRAFT_PREFIX . get_current_user_id() . '_' . $token;
		$draft = get_transient( $name );
		if ( false !== $draft ) {
			delete_transient( $name );
		}
		if ( ! is_array( $draft ) || ( $draft['service'] ?? null ) !== $id || ( $draft['lang'] ?? null ) !== $lang ) {
			return array( null, true );
		}
		return array( $draft, false );
	}

	/**
	 * Ends the request with 400 unless the language is one of the active languages; "all" is refused.
	 *
	 * @since 1.0.0
	 *
	 * @param string $lang Language code.
	 * @return void
	 */
	private static function require_language( string $lang ): void {
		if ( Language::ALL === $lang || ! in_array( $lang, Language::instance()->active(), true ) ) {
			wp_die( esc_html__( 'Choose an active language to edit the texts.', 'creationell-wp-theme' ), '', array( 'response' => 400 ) );
		}
	}

	/**
	 * Prints the form that raises the consent revision, nothing without the design capability or the setting.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function revision_form(): void {
		if ( ! current_user_can( Capabilities::MANAGE_DESIGN ) || ! Registry::instance()->has( self::REVISION ) ) {
			return;
		}
		printf(
			'<form method="post" action="%1$s"><input type="hidden" name="action" value="%2$s" /><input type="hidden" name="from" value="%3$d" /><input type="hidden" name="_wpnonce" value="%4$s" /><p>%5$s</p><p><button type="submit" class="button">%6$s</button></p></form>' . "\n",
			esc_url( admin_url( 'admin-post.php' ) ),
			esc_attr( self::ACTION_REVISION ),
			absint( self::revision() ),
			esc_attr( wp_create_nonce( self::ACTION_REVISION ) ),
			esc_html__( 'Visitors who already made a choice are not asked about the changed services until the revision is raised.', 'creationell-wp-theme' ),
			esc_html__( 'Ask visitors again (raise revision)', 'creationell-wp-theme' )
		);
	}

	/**
	 * Returns the title of a category: the text of the current language, the child theme, the default language, the theme, the slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Category.
	 * @return string Plain text title.
	 */
	private static function caption( string $slug ): string {
		$data     = self::store();
		$language = Language::instance();
		$default  = $language->default();
		$current  = $language->current();
		$current  = Language::ALL === $current ? $default : $current;

		$title = Consent_Services_Store::category_texts( $data, $current )[ $slug ]['title'] ?? '';
		if ( '' !== $title ) {
			return $title;
		}
		if ( class_exists( Texts::class ) ) {
			$child = Texts::details()[ $slug ]['title'] ?? null;
			$child = is_string( $child ) ? sanitize_text_field( $child ) : '';
			if ( '' !== $child ) {
				return $child;
			}
		}
		$title = Consent_Services_Store::category_texts( $data, $default )[ $slug ]['title'] ?? '';
		if ( '' !== $title ) {
			return $title;
		}
		$defaults = array(
			'necessary'  => __( 'Necessary', 'creationell-wp-theme' ),
			'functional' => __( 'Functional', 'creationell-wp-theme' ),
			'analytics'  => __( 'Statistics', 'creationell-wp-theme' ),
			'marketing'  => __( 'Marketing', 'creationell-wp-theme' ),
		);
		return $defaults[ $slug ] ?? $slug;
	}

	/**
	 * Prints the confirmation before a service is deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param array{id: string, category: string, name: string, provider: string, preset: string, preset_checked: string, cookies: int, displays: list<string>} $service Service.
	 * @return void
	 */
	private static function render_delete( array $service ): void {
		echo '<div class="wrap">' . "\n";
		printf( '<h1>%s</h1>' . "\n", esc_html__( 'Delete service', 'creationell-wp-theme' ) );
		printf(
			'<p>%s</p>' . "\n",
			esc_html(
				sprintf(
					/* translators: 1: name of the service, 2: number of cookies. */
					_n(
						'Delete the service %1$s with %2$d cookie? Its texts in all languages are deleted too.',
						'Delete the service %1$s with %2$d cookies? Its texts in all languages are deleted too.',
						$service['cookies'],
						'creationell-wp-theme'
					),
					$service['name'],
					$service['cookies']
				)
			)
		);
		printf(
			'<form method="post" action="%1$s"><input type="hidden" name="action" value="%2$s" /><input type="hidden" name="service" value="%3$s" /><input type="hidden" name="_wpnonce" value="%4$s" /><p><button type="submit" class="button button-primary">%5$s</button> <a href="%6$s">%7$s</a></p></form>' . "\n",
			esc_url( admin_url( 'admin-post.php' ) ),
			esc_attr( self::ACTION_DELETE ),
			esc_attr( $service['id'] ),
			esc_attr( wp_create_nonce( self::ACTION_DELETE . '_' . $service['id'] ) ),
			esc_html__( 'Delete service', 'creationell-wp-theme' ),
			esc_url( self::url() ),
			esc_html__( 'Cancel', 'creationell-wp-theme' )
		);
		echo '</div>' . "\n";
	}

	/**
	 * Prints the result of the last change as a notice whose heading gets the focus, with the revision form after a change of the services.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $query Query arguments: result, service, key.
	 * @return void
	 */
	private static function render_result( array $query ): void {
		$result = self::result_message( $query );
		if ( null === $result ) {
			return;
		}
		printf(
			'<div class="notice notice-%1$s inline"><h2 id="%2$s" tabindex="-1">%3$s</h2></div>' . "\n",
			esc_attr( $result[0] ),
			esc_attr( self::ID . 'result' ),
			esc_html( $result[1] )
		);
		wp_print_inline_script_tag( 'document.getElementById(' . wp_json_encode( self::ID . 'result' ) . ').focus();' );
		if ( in_array( $query['result'] ?? '', self::ASK_REVISION, true ) ) {
			self::revision_form();
		}
		if ( 'saved' === ( $query['result'] ?? '' ) ) {
			self::render_preset_warning( $query['service'] ?? '' );
		}
	}

	/**
	 * Prints a warning when a service of a preset outside Necessary was saved into Necessary.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Service ID.
	 * @return void
	 */
	private static function render_preset_warning( string $id ): void {
		$service = self::service( $id );
		if ( null === $service || Consent_Gate::NECESSARY !== $service['category'] ) {
			return;
		}
		$preset   = Consent_Service_Catalog::all()[ $service['preset'] ] ?? null;
		$category = is_array( $preset ) ? self::string( $preset['category'] ?? null ) : '';
		if ( '' === $category || Consent_Gate::NECESSARY === $category ) {
			return;
		}
		printf(
			'<div class="notice notice-warning inline"><p>%s</p></div>' . "\n",
			/* translators: %s: title of the category of the preset. */
			esc_html( sprintf( __( 'This service comes from a preset in category %s. Cookies in Necessary are never deleted.', 'creationell-wp-theme' ), self::caption( $category ) ) )
		);
	}

	/**
	 * Returns the notice type and the message of a result.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $query Query arguments: result, service, key.
	 * @return array{0: string, 1: string}|null Type and message; null without a known result.
	 */
	private static function result_message( array $query ): ?array {
		$result = $query['result'] ?? '';
		if ( ! in_array( $result, self::RESULTS, true ) ) {
			return null;
		}
		$id      = $query['service'] ?? '';
		$service = '' === $id ? null : self::service( $id );
		$name    = null === $service ? $id : $service['name'];
		$key     = $query['key'] ?? '';
		$known   = in_array( $key, Consent_Categories::all(), true );
		$texts   = $known
			/* translators: %s: title of the category. */
			? sprintf( __( 'Error: the category texts were not saved; check the texts of %s.', 'creationell-wp-theme' ), self::caption( $key ) )
			: __( 'Error: the category texts were not saved; check the marked fields.', 'creationell-wp-theme' );
		$messages = array(
			/* translators: %s: name of the service. */
			'saved'            => array( 'success', sprintf( __( 'Saved: %s.', 'creationell-wp-theme' ), $name ) ),
			'unchanged'        => array( 'info', '' === $name ? __( 'Nothing changed.', 'creationell-wp-theme' ) : sprintf( /* translators: %s: name of the service. */ __( 'Unchanged: %s.', 'creationell-wp-theme' ), $name ) ),
			/* translators: %s: name of the service. */
			'deleted'          => array( 'success', sprintf( __( 'Deleted: %s.', 'creationell-wp-theme' ), $name ) ),
			/* translators: %s: name of the service. */
			'preset_added'     => array( 'success', sprintf( __( 'Preset added: %s.', 'creationell-wp-theme' ), $name ) ),
			'preset_duplicate' => array( 'error', __( 'Error: this preset is already in the list of services.', 'creationell-wp-theme' ) ),
			'texts_saved'      => array( 'success', __( 'The category texts are saved.', 'creationell-wp-theme' ) ),
			'texts_invalid'    => array( 'error', $texts ),
			'invalid'          => array( 'error', __( 'Error: the request is not valid; nothing was changed.', 'creationell-wp-theme' ) ),
			'not_found'        => array( 'error', __( 'Error: this service does not exist.', 'creationell-wp-theme' ) ),
			'revision_raised'  => array( 'success', __( 'The revision is raised: every visitor is asked again.', 'creationell-wp-theme' ) ),
			'revision_stale'   => array( 'error', __( 'Error: the revision has changed in the meantime; it was not raised again.', 'creationell-wp-theme' ) ),
			'revision_invalid' => array( 'error', __( 'Error: the revision cannot be raised; it is at most 9999.', 'creationell-wp-theme' ) ),
			'revision_locked'  => array( 'error', __( 'Error: the child theme or a constant locks the revision.', 'creationell-wp-theme' ) ),
		);
		return $messages[ $result ];
	}

	/**
	 * Prints one table per category with the services of the store and the cookies of the child theme.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function render_tables(): void {
		$services   = self::services();
		$categories = Consent_Categories::all();
		foreach ( $services as $service ) {
			if ( ! in_array( $service['category'], $categories, true ) ) {
				$categories[] = $service['category'];
			}
		}
		$details  = self::active() && class_exists( Texts::class ) ? Texts::details() : array();
		$replaced = array();
		foreach ( $services as $service ) {
			foreach ( $service['displays'] as $display ) {
				$replaced[ $display ] ??= $service['name'];
			}
		}

		printf( '<h2>%s</h2>' . "\n", esc_html__( 'Services', 'creationell-wp-theme' ) );
		foreach ( $categories as $category ) {
			printf(
				'<table class="widefat striped"><caption>%1$s</caption><thead><tr><th scope="col">%2$s</th><th scope="col">%3$s</th><th scope="col">%4$s</th><th scope="col">%5$s</th><th scope="col">%6$s</th></tr></thead><tbody>' . "\n",
				esc_html( self::caption( $category ) ),
				esc_html__( 'Service', 'creationell-wp-theme' ),
				esc_html__( 'Provider', 'creationell-wp-theme' ),
				esc_html__( 'Cookies', 'creationell-wp-theme' ),
				esc_html__( 'Origin', 'creationell-wp-theme' ),
				esc_html__( 'Actions', 'creationell-wp-theme' )
			);
			$rows = 0;
			foreach ( $services as $service ) {
				if ( $category !== $service['category'] ) {
					continue;
				}
				++$rows;
				printf(
					'<tr id="%1$s"><th scope="row">%2$s</th><td>%3$s</td><td>%4$s</td><td>',
					esc_attr( self::ID . 'service-' . $service['id'] ),
					esc_html( $service['name'] ),
					esc_html( '' === $service['provider'] ? __( 'Operator of this website', 'creationell-wp-theme' ) : $service['provider'] ),
					esc_html( number_format_i18n( $service['cookies'] ) )
				);
				self::origin( $service );
				printf(
					'</td><td><a href="%1$s">%2$s<span class="screen-reader-text"> %3$s</span></a> | <a href="%4$s">%5$s<span class="screen-reader-text"> %3$s</span></a></td></tr>' . "\n",
					esc_url(
						self::url(
							array(
								'view'    => 'edit',
								'service' => $service['id'],
							)
						)
					),
					esc_html__( 'Edit', 'creationell-wp-theme' ),
					esc_html( $service['name'] ),
					esc_url(
						self::url(
							array(
								'view'    => 'delete',
								'service' => $service['id'],
							)
						)
					),
					esc_html__( 'Delete', 'creationell-wp-theme' )
				);
			}
			$cookies = $details[ $category ]['cookies'] ?? array();
			foreach ( $cookies as $cookie ) {
				++$rows;
				$label = is_string( $cookie['service'] ?? null ) ? sanitize_text_field( $cookie['service'] ) : '';
				printf(
					'<tr><th scope="row">%1$s</th><td></td><td><code>%2$s</code></td><td>%3$s</td><td></td></tr>' . "\n",
					esc_html( '' === $label ? $cookie['name'] : $label ),
					esc_html( $cookie['name'] ),
					esc_html(
						isset( $replaced[ $cookie['name'] ] )
							/* translators: %s: name of the service whose cookie replaces the one of the child theme. */
							? sprintf( __( 'Child theme – replaced by %s', 'creationell-wp-theme' ), $replaced[ $cookie['name'] ] )
							: __( 'Child theme – read only', 'creationell-wp-theme' )
					)
				);
			}
			if ( 0 === $rows ) {
				printf( '<tr><td colspan="5">%s</td></tr>' . "\n", esc_html__( 'No services in this category.', 'creationell-wp-theme' ) );
			}
			echo '</tbody></table>' . "\n";
		}
	}

	/**
	 * Prints the origin of a service: preset with its date, notice and sources, import or backend.
	 *
	 * @since 1.0.0
	 *
	 * @param array{id: string, category: string, name: string, provider: string, preset: string, preset_checked: string, cookies: int, displays: list<string>} $service Service.
	 * @return void
	 */
	private static function origin( array $service ): void {
		if ( '' === $service['preset'] ) {
			echo esc_html__( 'Backend', 'creationell-wp-theme' );
			return;
		}
		$preset = Consent_Service_Catalog::all()[ $service['preset'] ] ?? null;
		if ( null === $preset ) {
			echo esc_html__( 'Import', 'creationell-wp-theme' );
			return;
		}
		/* translators: 1: name of the preset, 2: date the preset was checked, YYYY-MM-DD. */
		echo esc_html( sprintf( __( 'Preset %1$s, as of %2$s', 'creationell-wp-theme' ), self::string( $preset['name'] ?? null ), $service['preset_checked'] ) );
		$newer = Consent_Service_Catalog::newer( $service );
		if ( null !== $newer ) {
			/* translators: %s: date of the newer preset, YYYY-MM-DD. */
			echo '<br /><strong>' . esc_html( sprintf( __( 'Preset newer: as of %s.', 'creationell-wp-theme' ), $newer ) ) . '</strong>';
		}
		self::preset_notice( $preset, $service['preset_checked'] );
	}

	/**
	 * Prints the notice of a preset: not legally reviewed, the note and the sources.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $preset  Preset of the catalog.
	 * @param string               $checked Date the information was checked, YYYY-MM-DD.
	 * @return void
	 */
	private static function preset_notice( array $preset, string $checked ): void {
		/* translators: %s: date the preset was checked, YYYY-MM-DD. */
		echo '<p class="description">' . esc_html( sprintf( __( "Information from the provider's documents, as of %s. Not legally reviewed; check before going live.", 'creationell-wp-theme' ), $checked ) );
		$note = self::string( $preset['note'] ?? null );
		if ( '' !== $note ) {
			echo '<br />' . esc_html( $note );
		}
		$first   = true;
		$sources = is_array( $preset['sources'] ?? null ) ? $preset['sources'] : array();
		foreach ( $sources as $source ) {
			if ( is_string( $source ) ) {
				echo ( $first ? '<br />' . esc_html__( 'Sources:', 'creationell-wp-theme' ) . ' ' : ', ' ) . '<a href="' . esc_url( $source ) . '">' . esc_html( $source ) . '</a>';
				$first = false;
			}
		}
		echo '</p>';
	}

	/**
	 * Prints the form that adds a preset and the information on the presets.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function render_presets(): void {
		$presets = Consent_Service_Catalog::all();
		$applied = array_column( self::services(), 'preset' );
		printf( '<h2>%s</h2>' . "\n", esc_html__( 'Add a preset', 'creationell-wp-theme' ) );
		printf(
			'<form method="post" action="%1$s"><input type="hidden" name="action" value="%2$s" /><input type="hidden" name="_wpnonce" value="%3$s" /><p><label for="creationell-wp-theme-consent-preset">%4$s</label> <select id="creationell-wp-theme-consent-preset" name="preset">',
			esc_url( admin_url( 'admin-post.php' ) ),
			esc_attr( self::ACTION_PRESET ),
			esc_attr( wp_create_nonce( self::ACTION_PRESET ) ),
			esc_html__( 'Preset', 'creationell-wp-theme' )
		);
		foreach ( $presets as $id => $preset ) {
			$name = self::string( $preset['name'] ?? null );
			printf(
				'<option value="%1$s">%2$s</option>',
				esc_attr( $id ),
				esc_html(
					in_array( $id, $applied, true )
						/* translators: %s: name of the preset. */
						? sprintf( __( '%s (already applied)', 'creationell-wp-theme' ), $name )
						: $name
				)
			);
		}
		printf( '</select> <button type="submit" class="button button-primary">%s</button></p></form>' . "\n", esc_html__( 'Add preset', 'creationell-wp-theme' ) );

		printf( '<details id="creationell-wp-theme-consent-preset-info"><summary>%s</summary><dl>' . "\n", esc_html__( 'About the presets', 'creationell-wp-theme' ) );
		foreach ( $presets as $preset ) {
			printf( '<dt>%s</dt><dd>', esc_html( self::string( $preset['name'] ?? null ) ) );
			self::preset_notice( $preset, self::string( $preset['checked'] ?? null ) );
			echo '</dd>' . "\n";
		}
		echo '</dl></details>' . "\n";
	}

	/**
	 * Prints the form of the category texts of the current language; with "All languages" only a hint.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $query Query arguments: result, key.
	 * @return void
	 */
	private static function render_category_texts( array $query ): void {
		$language = Language::instance();
		$lang     = $language->current();
		printf( '<h2>%s</h2>' . "\n", esc_html__( 'Category texts', 'creationell-wp-theme' ) );
		if ( Language::ALL === $lang || ! in_array( $lang, $language->active(), true ) ) {
			printf( '<p>%s</p>' . "\n", esc_html__( 'Choose a language in the admin bar to edit the category texts.', 'creationell-wp-theme' ) );
			return;
		}
		$texts   = Consent_Services_Store::category_texts( self::store(), $lang );
		$invalid = 'texts_invalid' === ( $query['result'] ?? '' ) ? ( $query['key'] ?? '' ) : '';
		printf(
			'<form method="post" action="%1$s"><input type="hidden" name="action" value="%2$s" /><input type="hidden" name="lang" value="%3$s" /><input type="hidden" name="_wpnonce" value="%4$s" />' . "\n",
			esc_url( admin_url( 'admin-post.php' ) ),
			esc_attr( self::ACTION_CATEGORIES ),
			esc_attr( $lang ),
			esc_attr( wp_create_nonce( self::ACTION_CATEGORIES . '_' . $lang ) )
		);
		$hint = self::ID . 'category-hint';
		printf(
			'<p><strong>%1$s</strong></p><p class="description" id="%2$s">%3$s</p>' . "\n",
			/* translators: 1: language name, e.g. "English"; 2: language code, e.g. "en". */
			esc_html( sprintf( __( 'Texts for: %1$s (%2$s)', 'creationell-wp-theme' ), $language->names()[ $lang ] ?? $lang, $lang ) ),
			esc_attr( $hint ),
			esc_html__( 'An empty field uses the text of the child theme or of the theme. The title has at most 120 characters, the description at most 2000; the description may contain links, bold and italic text and line breaks.', 'creationell-wp-theme' )
		);
		foreach ( Consent_Categories::all() as $slug ) {
			$id    = self::ID . 'category-' . $slug;
			$error = $slug === $invalid;
			printf( '<fieldset><legend>%s</legend>', esc_html( self::caption( $slug ) ) );
			if ( $error ) {
				printf( '<p id="%1$s"><strong>%2$s</strong></p>', esc_attr( $id . '-error' ), esc_html__( 'Error: check the texts of this category.', 'creationell-wp-theme' ) );
			}
			printf(
				'<p><label for="%1$s-title">%2$s</label><br /><input type="text" class="regular-text" id="%1$s-title" name="%3$s"%4$s aria-describedby="%5$s" value="%6$s" /></p><p><label for="%1$s-description">%7$s</label><br /><textarea class="large-text" rows="3" id="%1$s-description" name="%8$s"%4$s aria-describedby="%5$s">%9$s</textarea></p></fieldset>' . "\n",
				esc_attr( $id ),
				esc_html__( 'Title', 'creationell-wp-theme' ),
				esc_attr( 'categories[' . $slug . '][title]' ),
				$error ? ' aria-invalid="true"' : '',
				esc_attr( $error ? $id . '-error ' . $hint : $hint ),
				esc_attr( $texts[ $slug ]['title'] ?? '' ),
				esc_html__( 'Description', 'creationell-wp-theme' ),
				esc_attr( 'categories[' . $slug . '][description]' ),
				esc_textarea( $texts[ $slug ]['description'] ?? '' )
			);
		}
		printf( '<p><button type="submit" class="button button-primary">%s</button></p></form>' . "\n", esc_html__( 'Save category texts', 'creationell-wp-theme' ) );
	}

	/**
	 * Prints the edit view of a service: the form of the current language, or the service read only with "All languages".
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $query Query arguments: service, error, draft, more.
	 * @return void
	 */
	private static function render_edit( array $query ): void {
		$id       = $query['service'] ?? '';
		$new      = 'new' === $id;
		$stored   = $new ? array() : ( self::stored( $id ) ?? array() );
		$language = Language::instance();
		$lang     = $language->current();
		$primary  = $language->default() === $lang;
		$active   = Language::ALL !== $lang && in_array( $lang, $language->active(), true );

		echo '<div class="wrap">' . "\n";
		printf(
			'<h1>%s</h1>' . "\n",
			/* translators: %s: name of the service. */
			esc_html( $new ? __( 'Add service', 'creationell-wp-theme' ) : sprintf( __( 'Edit service: %s', 'creationell-wp-theme' ), self::string( $stored['name'] ?? null ) ) )
		);
		if ( ! $active || ( $new && ! $primary ) ) {
			printf(
				'<p>%s</p>' . "\n",
				esc_html( $active ? __( 'Create new services in the default language.', 'creationell-wp-theme' ) : __( 'Choose a language in the admin bar to edit the service.', 'creationell-wp-theme' ) )
			);
			if ( ! $new ) {
				self::render_structure( $stored, true );
			}
			self::render_back();
			echo '</div>' . "\n";
			return;
		}

		list( $draft, $missing ) = self::take_draft( $query, $id, $lang );
		$state                   = null === $draft ? self::stored_state( $id, $stored, $lang, $primary ) : self::draft_state( $draft );
		$error                   = in_array( $query['error'] ?? '', self::ERRORS, true ) ? $query['error'] : '';
		self::render_errors( $error, $state['errors'] );
		if ( $missing ) {
			printf( '<div class="notice notice-warning inline"><p>%s</p></div>' . "\n", esc_html__( 'The entered values are no longer available; the stored values are shown.', 'creationell-wp-theme' ) );
		}
		printf(
			'<p><strong>%s</strong></p>' . "\n",
			/* translators: 1: language name, e.g. "English"; 2: language code, e.g. "en". */
			esc_html( sprintf( __( 'Texts for: %1$s (%2$s)', 'creationell-wp-theme' ), $language->names()[ $lang ] ?? $lang, $lang ) )
		);
		$cookies = $primary ? $state['cookies'] : self::cookie_list( $stored );
		printf(
			'<form method="post" action="%1$s" novalidate="novalidate"><input type="hidden" name="action" value="%2$s" /><input type="hidden" name="service" value="%3$s" /><input type="hidden" name="lang" value="%4$s" /><input type="hidden" name="hash" value="%5$s" /><input type="hidden" name="rows" value="%6$d" /><input type="hidden" name="_wpnonce" value="%7$s" />' . "\n",
			esc_url( admin_url( 'admin-post.php' ) ),
			esc_attr( self::ACTION_SAVE ),
			esc_attr( $id ),
			esc_attr( $lang ),
			esc_attr( $state['hash'] ),
			absint( $primary ? $state['rows'] : count( $cookies ) ),
			esc_attr( wp_create_nonce( self::ACTION_SAVE . '_' . $id . '_' . $lang ) )
		);
		$messages = array();
		foreach ( $state['errors'] as $row ) {
			if ( '' !== $row['id'] && ! isset( $messages[ $row['id'] ] ) ) {
				$messages[ $row['id'] ] = $row['message'];
			}
		}
		if ( $primary ) {
			self::render_service_fields( $state['fields'], $messages );
			self::render_cookie_rows( $state, self::cookie_list( $stored ), $messages );
		} else {
			self::render_text_fields( $id, $stored, $state, $messages );
		}
		echo '<input type="hidden" name="_complete" value="1" /></form>' . "\n";
		self::render_back();
		echo '</div>' . "\n";
		$focus = count( $state['cookies'] );
		if ( '' === $error && '1' === ( $query['more'] ?? '' ) && null !== $draft && $focus < $state['rows'] ) {
			wp_print_inline_script_tag( 'document.getElementById(' . wp_json_encode( self::FIELD . 'cookie-' . $focus . '-name' ) . ').focus();' );
		}
	}

	/**
	 * Prints the error summary of the edit view; its heading takes the focus.
	 *
	 * @since 1.0.0
	 *
	 * @param string                                                  $error  Error code of ERRORS, or empty.
	 * @param list<array{id: string, label: string, message: string}> $errors Errors of the draft.
	 * @return void
	 */
	private static function render_errors( string $error, array $errors ): void {
		if ( '' === $error ) {
			return;
		}
		printf(
			'<div class="notice notice-error inline"><h2 id="%1$s" tabindex="-1">%2$s</h2>',
			esc_attr( self::FIELD . 'errors' ),
			esc_html( self::error_heading( $error ) )
		);
		if ( array() !== $errors ) {
			echo '<ul>';
			foreach ( $errors as $row ) {
				if ( '' === $row['id'] ) {
					printf( '<li>%s</li>', esc_html( $row['message'] ) );
					continue;
				}
				printf(
					'<li><a href="%1$s">%2$s</a></li>',
					esc_url( '#' . $row['id'] ),
					/* translators: 1: label of the field, 2: error message. */
					esc_html( sprintf( __( '%1$s: %2$s', 'creationell-wp-theme' ), $row['label'], $row['message'] ) )
				);
			}
			echo '</ul>';
		}
		echo '</div>' . "\n";
		wp_print_inline_script_tag( 'document.getElementById(' . wp_json_encode( self::FIELD . 'errors' ) . ').focus();' );
	}

	/**
	 * Returns the heading of an error of the edit view.
	 *
	 * @since 1.0.0
	 *
	 * @param string $error Error code of ERRORS.
	 * @return string Heading.
	 */
	private static function error_heading( string $error ): string {
		return match ( $error ) {
			'incomplete' => __( 'The form arrived incomplete; check the server limit max_input_vars.', 'creationell-wp-theme' ),
			'changed'    => __( 'The service was changed in the meantime. Please reload.', 'creationell-wp-theme' ),
			default      => __( 'The service was not saved. Check the marked fields.', 'creationell-wp-theme' ),
		};
	}

	/**
	 * Prints the service fields of the default language.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $fields   Values: name, category, provider, privacy_url, purpose.
	 * @param array<string, string> $messages Error messages by element ID.
	 * @return void
	 */
	private static function render_service_fields( array $fields, array $messages ): void {
		$labels = self::labels();
		echo '<table class="form-table" role="presentation"><tbody>' . "\n";
		self::input_row( self::FIELD . 'name', 'name', $labels['name'], $fields['name'], $messages, array() );

		$id = self::FIELD . 'category';
		printf( '<tr><th scope="row"><label for="%1$s">%2$s</label></th><td>', esc_attr( $id ), esc_html( $labels['category'] ) );
		self::open_control( 'select', $id, 'category', $messages, array() );
		$categories = Consent_Categories::all();
		if ( '' === $fields['category'] ) {
			printf( '<option value="" selected="selected">%s</option>', esc_html__( 'Choose a category', 'creationell-wp-theme' ) );
		} elseif ( ! in_array( $fields['category'], $categories, true ) ) {
			$categories[] = $fields['category'];
		}
		foreach ( $categories as $slug ) {
			printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $slug ), $slug === $fields['category'] ? ' selected="selected"' : '', esc_html( self::caption( $slug ) ) );
		}
		echo '</select>';
		self::notes( $id, $messages, array() );
		echo '</td></tr>' . "\n";

		self::input_row( self::FIELD . 'provider', 'provider', $labels['provider'], $fields['provider'], $messages, array( self::FIELD . 'provider-hint' => __( 'Empty: the operator of this website.', 'creationell-wp-theme' ) ) );
		self::input_row( self::FIELD . 'privacy_url', 'privacy_url', $labels['privacy_url'], $fields['privacy_url'], $messages, array( self::FIELD . 'privacy_url-hint' => __( 'Address of the privacy policy of the provider, starting with https://.', 'creationell-wp-theme' ) ), 'url' );
		self::textarea_row( self::FIELD . 'purpose', 'purpose', $labels['purpose'], $fields['purpose'], $messages, array() );
		echo '</tbody></table>' . "\n";
	}

	/**
	 * Prints one group per cookie row of the default language and the buttons.
	 *
	 * @since 1.0.0
	 *
	 * @param array{fields: array<string, string>, cookies: list<array<string, string>>, rows: int, hash: string, errors: list<array{id: string, label: string, message: string}>} $state    Form values.
	 * @param array<string, array<mixed>>                                                                                                                                          $stored   Stored cookies by key.
	 * @param array<string, string>                                                                                                                                                $messages Error messages by element ID.
	 * @return void
	 */
	private static function render_cookie_rows( array $state, array $stored, array $messages ): void {
		$labels = self::labels();
		printf( '<h2>%1$s</h2><p class="description">%2$s</p>' . "\n", esc_html__( 'Cookies', 'creationell-wp-theme' ), esc_html__( 'A group with an empty name, purpose and duration is ignored.', 'creationell-wp-theme' ) );
		for ( $row = 0; $row < $state['rows']; ++$row ) {
			$cookie = $state['cookies'][ $row ] ?? self::empty_cookie();
			$known  = $stored[ $cookie['key'] ] ?? null;
			$id     = self::FIELD . 'cookie-' . $row . '-';
			$name   = 'cookies[' . $row . ']';
			printf( '<fieldset><legend>%s</legend>', esc_html( self::legend( $row, $known ) ) );
			if ( null !== $known ) {
				printf( '<input type="hidden" name="%1$s" value="%2$s" />', esc_attr( $name . '[key]' ), esc_attr( $cookie['key'] ) );
			}
			echo '<table class="form-table" role="presentation"><tbody>' . "\n";
			self::input_row( $id . 'name', $name . '[name]', $labels['cookie'], $cookie['name'], $messages, array() );

			printf( '<tr><th scope="row"><label for="%1$s">%2$s</label></th><td>', esc_attr( $id . 'match' ), esc_html( $labels['match'] ) );
			self::open_control( 'select', $id . 'match', $name . '[match]', $messages, array() );
			foreach ( self::matches() as $mode => $label ) {
				printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $mode ), $mode === $cookie['match'] ? ' selected="selected"' : '', esc_html( $label ) );
			}
			echo '</select>';
			self::notes( $id . 'match', $messages, array() );
			echo '</td></tr>' . "\n";

			self::checkbox_row( $id . 'first_party', $name . '[first_party]', $labels['first_party'], $cookie['first_party'] );
			self::checkbox_row( $id . 'parent_domain', $name . '[parent_domain]', $labels['parent_domain'], $cookie['parent_domain'] );
			self::input_row( $id . 'purpose', $name . '[purpose]', $labels['purpose'], $cookie['purpose'], $messages, array() );
			self::input_row( $id . 'duration', $name . '[duration]', $labels['duration'], $cookie['duration'], $messages, array() );
			if ( null !== $known ) {
				self::checkbox_row( $id . 'remove', $name . '[remove]', $labels['remove'], $cookie['remove'] );
			}
			echo '</tbody></table></fieldset>' . "\n";
		}

		$full = $state['rows'] >= self::MAX_COOKIES;
		$hint = self::FIELD . 'more-limit';
		printf(
			'<p class="submit"><button type="submit" name="do" value="save" class="button button-primary">%1$s</button> <button type="submit" name="do" value="more" class="button"%2$s>%3$s</button>%4$s</p>' . "\n",
			esc_html__( 'Save service', 'creationell-wp-theme' ),
			$full ? ' disabled="disabled" aria-describedby="' . esc_attr( $hint ) . '"' : '',
			esc_html__( 'More cookies', 'creationell-wp-theme' ),
			/* translators: %d: most cookies of a service. */
			$full ? ' <span class="description" id="' . esc_attr( $hint ) . '">' . esc_html( sprintf( __( 'At most %d cookies per service.', 'creationell-wp-theme' ), self::MAX_COOKIES ) ) . '</span>' : ''
		);
	}

	/**
	 * Prints the texts of a language other than the default: the structure as text, the purposes and durations as fields.
	 *
	 * @since 1.0.0
	 *
	 * @param string                                                                                                                                                               $id       Service ID.
	 * @param array<mixed>                                                                                                                                                         $stored   Stored service.
	 * @param array{fields: array<string, string>, cookies: list<array<string, string>>, rows: int, hash: string, errors: list<array{id: string, label: string, message: string}>} $state    Form values.
	 * @param array<string, string>                                                                                                                                                $messages Error messages by element ID.
	 * @return void
	 */
	private static function render_text_fields( string $id, array $stored, array $state, array $messages ): void {
		$labels  = self::labels();
		$default = Language::instance()->default();
		self::render_structure( $stored, false );
		printf( '<p class="description">%s</p>' . "\n", esc_html__( 'Change these fields in the default language.', 'creationell-wp-theme' ) );
		echo '<table class="form-table" role="presentation"><tbody>' . "\n";
		self::textarea_row( self::FIELD . 'purpose', 'purpose', $labels['purpose'], $state['fields']['purpose'], $messages, self::default_hint( self::FIELD . 'purpose', self::text_of( $default, $id, 'purpose' ) ) );
		echo '</tbody></table>' . "\n";

		$values = array();
		foreach ( $state['cookies'] as $cookie ) {
			$values[ $cookie['key'] ] ??= $cookie;
		}
		$row = 0;
		foreach ( self::cookie_list( $stored ) as $key => $cookie ) {
			$value = $values[ $key ] ?? self::empty_cookie();
			$field = self::FIELD . 'cookie-' . $row . '-';
			$name  = 'cookies[' . $row . ']';
			$match = self::string( $cookie['match'] ?? null );
			printf(
				'<fieldset><legend>%1$s</legend><input type="hidden" name="%2$s" value="%3$s" /><dl><dt>%4$s</dt><dd>%5$s</dd><dt>%6$s</dt><dd>%7$s</dd><dt>%8$s</dt><dd>%9$s</dd><dt>%10$s</dt><dd>%11$s</dd></dl>',
				esc_html( self::legend( $row, $cookie ) ),
				esc_attr( $name . '[key]' ),
				esc_attr( $key ),
				esc_html( $labels['cookie'] ),
				esc_html( self::string( $cookie['name'] ?? null ) ),
				esc_html( $labels['match'] ),
				esc_html( self::matches()[ $match ] ?? $match ),
				esc_html( $labels['first_party'] ),
				esc_html( true === ( $cookie['first_party'] ?? null ) ? __( 'Yes', 'creationell-wp-theme' ) : __( 'No', 'creationell-wp-theme' ) ),
				esc_html( $labels['parent_domain'] ),
				esc_html( true === ( $cookie['parent_domain'] ?? null ) ? __( 'Yes', 'creationell-wp-theme' ) : __( 'No', 'creationell-wp-theme' ) )
			);
			echo '<table class="form-table" role="presentation"><tbody>' . "\n";
			self::input_row( $field . 'purpose', $name . '[purpose]', $labels['purpose'], $value['purpose'], $messages, self::default_hint( $field . 'purpose', self::text_of( $default, $id, 'cookies', $key, 'purpose' ) ) );
			self::input_row( $field . 'duration', $name . '[duration]', $labels['duration'], $value['duration'], $messages, self::default_hint( $field . 'duration', self::text_of( $default, $id, 'cookies', $key, 'duration' ) ) );
			echo '</tbody></table></fieldset>' . "\n";
			++$row;
		}
		printf( '<p class="submit"><button type="submit" name="do" value="save" class="button button-primary">%s</button></p>' . "\n", esc_html__( 'Save texts', 'creationell-wp-theme' ) );
	}

	/**
	 * Prints the structure of a service as a description list.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $stored  Stored service.
	 * @param bool         $cookies Whether to list the cookie names too.
	 * @return void
	 */
	private static function render_structure( array $stored, bool $cookies ): void {
		$labels   = self::labels();
		$provider = self::string( $stored['provider'] ?? null );
		$url      = self::string( $stored['privacy_url'] ?? null );
		printf(
			'<dl><dt>%1$s</dt><dd>%2$s</dd><dt>%3$s</dt><dd>%4$s</dd><dt>%5$s</dt><dd>%6$s</dd><dt>%7$s</dt><dd>%8$s</dd>',
			esc_html( $labels['name'] ),
			esc_html( self::string( $stored['name'] ?? null ) ),
			esc_html( $labels['category'] ),
			esc_html( self::caption( self::string( $stored['category'] ?? null ) ) ),
			esc_html( $labels['provider'] ),
			esc_html( '' === $provider ? __( 'Operator of this website', 'creationell-wp-theme' ) : $provider ),
			esc_html( $labels['privacy_url'] ),
			esc_html( '' === $url ? __( 'None', 'creationell-wp-theme' ) : $url )
		);
		if ( $cookies ) {
			$names = array();
			foreach ( self::cookie_list( $stored ) as $cookie ) {
				$names[] = Consent_Cookie_Name::display( self::string( $cookie['name'] ?? null ), self::string( $cookie['match'] ?? null ) );
			}
			printf( '<dt>%1$s</dt><dd>%2$s</dd>', esc_html__( 'Cookies', 'creationell-wp-theme' ), esc_html( array() === $names ? __( 'None', 'creationell-wp-theme' ) : implode( ', ', $names ) ) );
		}
		echo '</dl>' . "\n";
	}

	/**
	 * Prints the link back to the overview.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function render_back(): void {
		printf( '<p><a href="%1$s">%2$s</a></p>' . "\n", esc_url( self::url() ), esc_html__( 'Back to the overview', 'creationell-wp-theme' ) );
	}

	/**
	 * Prints a table row with a label and a text field.
	 *
	 * @since 1.0.0
	 *
	 * @param string                $id       Element ID.
	 * @param string                $name     Field name.
	 * @param string                $label    Label.
	 * @param string                $value    Value.
	 * @param array<string, string> $messages Error messages by element ID.
	 * @param array<string, string> $hints    Hints below the field by element ID.
	 * @param string                $type     Input type.
	 * @return void
	 */
	private static function input_row( string $id, string $name, string $label, string $value, array $messages, array $hints, string $type = 'text' ): void {
		printf( '<tr><th scope="row"><label for="%1$s">%2$s</label></th><td>', esc_attr( $id ), esc_html( $label ) );
		$described = self::described( $id, $messages, $hints );
		printf(
			'<input type="%1$s" class="regular-text" id="%2$s" name="%3$s" value="%4$s"%5$s%6$s />',
			esc_attr( $type ),
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $value ),
			isset( $messages[ $id ] ) ? ' aria-invalid="true"' : '',
			'' === $described ? '' : ' aria-describedby="' . esc_attr( $described ) . '"'
		);
		self::notes( $id, $messages, $hints );
		echo '</td></tr>' . "\n";
	}

	/**
	 * Prints a table row with a label and a textarea.
	 *
	 * @since 1.0.0
	 *
	 * @param string                $id       Element ID.
	 * @param string                $name     Field name.
	 * @param string                $label    Label.
	 * @param string                $value    Value.
	 * @param array<string, string> $messages Error messages by element ID.
	 * @param array<string, string> $hints    Hints below the field by element ID.
	 * @return void
	 */
	private static function textarea_row( string $id, string $name, string $label, string $value, array $messages, array $hints ): void {
		printf( '<tr><th scope="row"><label for="%1$s">%2$s</label></th><td>', esc_attr( $id ), esc_html( $label ) );
		self::open_control( 'textarea', $id, $name, $messages, $hints );
		echo esc_textarea( $value ) . '</textarea>';
		self::notes( $id, $messages, $hints );
		echo '</td></tr>' . "\n";
	}

	/**
	 * Prints a table row with a label and a checkbox.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id    Element ID.
	 * @param string $name  Field name.
	 * @param string $label Label.
	 * @param string $value "1" when checked.
	 * @return void
	 */
	private static function checkbox_row( string $id, string $name, string $label, string $value ): void {
		printf(
			'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input type="checkbox" id="%1$s" name="%3$s" value="1"%4$s /></td></tr>' . "\n",
			esc_attr( $id ),
			esc_html( $label ),
			esc_attr( $name ),
			'1' === $value ? ' checked="checked"' : ''
		);
	}

	/**
	 * Prints the opening tag of a select or textarea with its error and hint references.
	 *
	 * @since 1.0.0
	 *
	 * @param string                $tag      "select" or "textarea".
	 * @param string                $id       Element ID.
	 * @param string                $name     Field name.
	 * @param array<string, string> $messages Error messages by element ID.
	 * @param array<string, string> $hints    Hints below the field by element ID.
	 * @return void
	 */
	private static function open_control( string $tag, string $id, string $name, array $messages, array $hints ): void {
		$described = self::described( $id, $messages, $hints );
		printf(
			'<%1$s%2$s id="%3$s" name="%4$s"%5$s%6$s>',
			'textarea' === $tag ? 'textarea' : 'select',
			'textarea' === $tag ? ' class="large-text" rows="3"' : '',
			esc_attr( $id ),
			esc_attr( $name ),
			isset( $messages[ $id ] ) ? ' aria-invalid="true"' : '',
			'' === $described ? '' : ' aria-describedby="' . esc_attr( $described ) . '"'
		);
	}

	/**
	 * Returns the IDs that describe a field: its error first, then its hints.
	 *
	 * @since 1.0.0
	 *
	 * @param string                $id       Element ID.
	 * @param array<string, string> $messages Error messages by element ID.
	 * @param array<string, string> $hints    Hints by element ID.
	 * @return string IDs separated by spaces.
	 */
	private static function described( string $id, array $messages, array $hints ): string {
		return implode( ' ', array_merge( isset( $messages[ $id ] ) ? array( $id . '-error' ) : array(), array_keys( $hints ) ) );
	}

	/**
	 * Prints the error message and the hints below a field.
	 *
	 * @since 1.0.0
	 *
	 * @param string                $id       Element ID.
	 * @param array<string, string> $messages Error messages by element ID.
	 * @param array<string, string> $hints    Hints by element ID.
	 * @return void
	 */
	private static function notes( string $id, array $messages, array $hints ): void {
		if ( isset( $messages[ $id ] ) ) {
			printf( '<p class="description" id="%1$s"><strong>%2$s</strong></p>', esc_attr( $id . '-error' ), esc_html( $messages[ $id ] ) );
		}
		foreach ( $hints as $hint => $text ) {
			printf( '<p class="description" id="%1$s">%2$s</p>', esc_attr( $hint ), esc_html( $text ) );
		}
	}

	/**
	 * Returns the hint with the text of the default language below a field of another language.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id   Element ID of the field.
	 * @param string $text Text of the default language.
	 * @return array<string, string> Hint by element ID.
	 */
	private static function default_hint( string $id, string $text ): array {
		return array(
			/* translators: %s: text of the default language. */
			$id . '-default' => '' === $text ? __( 'Standard language: no text.', 'creationell-wp-theme' ) : sprintf( __( 'Standard language: %s', 'creationell-wp-theme' ), $text ),
		);
	}

	/**
	 * Returns the legend of a cookie group: number and display name of a stored cookie, or "New cookie".
	 *
	 * @since 1.0.0
	 *
	 * @param int               $row    Row index.
	 * @param array<mixed>|null $cookie Stored cookie; null for a new one.
	 * @return string Legend.
	 */
	private static function legend( int $row, ?array $cookie ): string {
		if ( null === $cookie ) {
			/* translators: %d: number of the cookie group. */
			return sprintf( __( 'New cookie %d', 'creationell-wp-theme' ), $row + 1 );
		}
		/* translators: 1: number of the cookie group, 2: cookie name. */
		return sprintf( __( 'Cookie %1$d: %2$s', 'creationell-wp-theme' ), $row + 1, Consent_Cookie_Name::display( self::string( $cookie['name'] ?? null ), self::string( $cookie['match'] ?? null ) ) );
	}

	/**
	 * Returns the labels of the form fields.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Labels by field.
	 */
	private static function labels(): array {
		return array(
			'name'          => __( 'Name', 'creationell-wp-theme' ),
			'category'      => __( 'Category', 'creationell-wp-theme' ),
			'provider'      => __( 'Provider', 'creationell-wp-theme' ),
			'privacy_url'   => __( 'Privacy policy', 'creationell-wp-theme' ),
			'purpose'       => __( 'Purpose', 'creationell-wp-theme' ),
			'cookie'        => __( 'Cookie name', 'creationell-wp-theme' ),
			'match'         => __( 'Kind of name', 'creationell-wp-theme' ),
			'first_party'   => __( 'Set on this website', 'creationell-wp-theme' ),
			'parent_domain' => __( 'Also delete on the parent domain', 'creationell-wp-theme' ),
			'duration'      => __( 'Duration', 'creationell-wp-theme' ),
			'remove'        => __( 'Remove this cookie', 'creationell-wp-theme' ),
		);
	}

	/**
	 * Returns the labels of the match modes.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Labels by mode of Consent_Cookie_Name::MATCHES.
	 */
	private static function matches(): array {
		return array(
			'exact'  => __( 'Exact name', 'creationell-wp-theme' ),
			'prefix' => __( 'Starts with', 'creationell-wp-theme' ),
			'regex'  => __( 'Regular expression', 'creationell-wp-theme' ),
		);
	}

	/**
	 * Returns the values of an empty cookie group.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Values by field of COOKIE_FIELDS.
	 */
	private static function empty_cookie(): array {
		return array(
			'key'           => '',
			'name'          => '',
			'match'         => 'exact',
			'first_party'   => '1',
			'parent_domain' => '',
			'purpose'       => '',
			'duration'      => '',
			'remove'        => '',
		);
	}

	/**
	 * Returns the form values of a stored service in a language, or of a new service.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $id      Service ID or "new".
	 * @param array<mixed> $stored  Stored service; empty for a new one.
	 * @param string       $lang    Language code.
	 * @param bool         $primary Whether the language is the default language.
	 * @return array{fields: array<string, string>, cookies: list<array<string, string>>, rows: int, hash: string, errors: list<array{id: string, label: string, message: string}>} Form values.
	 */
	private static function stored_state( string $id, array $stored, string $lang, bool $primary ): array {
		$fields = array();
		foreach ( self::SERVICE_FIELDS as $field ) {
			$fields[ $field ] = self::string( $stored[ $field ] ?? null );
		}
		$fields['purpose'] = self::text_of( $lang, $id, 'purpose' );
		$cookies           = array();
		foreach ( self::cookie_list( $stored ) as $key => $cookie ) {
			$cookies[] = array(
				'key'           => (string) $key,
				'name'          => self::string( $cookie['name'] ?? null ),
				'match'         => self::string( $cookie['match'] ?? null ),
				'first_party'   => true === ( $cookie['first_party'] ?? null ) ? '1' : '',
				'parent_domain' => true === ( $cookie['parent_domain'] ?? null ) ? '1' : '',
				'purpose'       => self::text_of( $lang, $id, 'cookies', (string) $key, 'purpose' ),
				'duration'      => self::text_of( $lang, $id, 'cookies', (string) $key, 'duration' ),
				'remove'        => '',
			);
		}
		return array(
			'fields'  => $fields,
			'cookies' => $cookies,
			'rows'    => $primary ? min( self::MAX_COOKIES, count( $cookies ) + self::EMPTY_ROWS ) : count( $cookies ),
			'hash'    => self::hash( $id, $lang ),
			'errors'  => array(),
		);
	}

	/**
	 * Returns the form values of a draft.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $draft Draft as handle_save() stores it.
	 * @return array{fields: array<string, string>, cookies: list<array<string, string>>, rows: int, hash: string, errors: list<array{id: string, label: string, message: string}>} Form values.
	 */
	private static function draft_state( array $draft ): array {
		$raw    = is_array( $draft['fields'] ?? null ) ? $draft['fields'] : array();
		$fields = array();
		foreach ( self::SERVICE_FIELDS as $field ) {
			$fields[ $field ] = self::string( $raw[ $field ] ?? null );
		}
		$cookies = array();
		$list    = is_array( $draft['cookies'] ?? null ) ? array_slice( array_values( $draft['cookies'] ), 0, self::MAX_COOKIES ) : array();
		foreach ( $list as $row ) {
			$cookie = array();
			foreach ( self::COOKIE_FIELDS as $field ) {
				$cookie[ $field ] = is_array( $row ) ? self::string( $row[ $field ] ?? null ) : '';
			}
			$cookies[] = $cookie;
		}
		$errors = array();
		$list   = is_array( $draft['errors'] ?? null ) ? $draft['errors'] : array();
		foreach ( $list as $error ) {
			if ( is_array( $error ) ) {
				$errors[] = array(
					'id'      => self::string( $error['id'] ?? null ),
					'label'   => self::string( $error['label'] ?? null ),
					'message' => self::string( $error['message'] ?? null ),
				);
			}
		}
		$rows = is_int( $draft['rows'] ?? null ) ? $draft['rows'] : 0;
		return array(
			'fields'  => $fields,
			'cookies' => $cookies,
			'rows'    => max( count( $cookies ), min( self::MAX_COOKIES, $rows ) ),
			'hash'    => self::string( $draft['hash'] ?? null ),
			'errors'  => $errors,
		);
	}

	/**
	 * Returns the hash of a service and its texts in a language, as the form carries it.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id   Service ID or "new".
	 * @param string $lang Language code.
	 * @return string MD5 hash; empty for "new".
	 */
	private static function hash( string $id, string $lang ): string {
		if ( 'new' === $id ) {
			return '';
		}
		$data = self::store();
		return md5( (string) wp_json_encode( array( self::stored( $id ), $data['texts'][ $lang ]['services'][ $id ] ?? array() ) ) );
	}

	/**
	 * Returns a text of a service in exactly one language, without fallback.
	 *
	 * @since 1.0.0
	 *
	 * @param string $lang    Language code.
	 * @param string $id      Service ID.
	 * @param string ...$path Keys below texts.<lang>.services.<id>, e.g. "cookies", "c1", "purpose".
	 * @return string Text; empty when missing.
	 */
	private static function text_of( string $lang, string $id, string ...$path ): string {
		$data  = self::store();
		$value = $data['texts'][ $lang ]['services'][ $id ] ?? null;
		foreach ( $path as $key ) {
			$value = is_array( $value ) ? ( $value[ $key ] ?? null ) : null;
		}
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Returns the stored entry of a service as the option holds it.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Service ID.
	 * @return array<mixed>|null Service; null when unknown.
	 */
	private static function stored( string $id ): ?array {
		$index = self::stored_index( $id );
		if ( null === $index ) {
			return null;
		}
		$services = self::store()['services'];
		return is_array( $services ) && is_array( $services[ $index ] ?? null ) ? $services[ $index ] : null;
	}

	/**
	 * Returns the position of a service in the stored list.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Service ID.
	 * @return int|null Index; null when unknown.
	 */
	private static function stored_index( string $id ): ?int {
		$services = self::store()['services'];
		foreach ( is_array( $services ) ? $services : array() as $index => $service ) {
			if ( '' !== $id && is_array( $service ) && ( $service['id'] ?? null ) === $id ) {
				return (int) $index;
			}
		}
		return null;
	}

	/**
	 * Returns the stored cookies of a service by key, in their order; the first of a doubled key wins.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $service Stored service.
	 * @return array<string, array<mixed>> Cookies by key.
	 */
	private static function cookie_list( array $service ): array {
		$cookies = array();
		$list    = is_array( $service['cookies'] ?? null ) ? $service['cookies'] : array();
		foreach ( $list as $cookie ) {
			$key = is_array( $cookie ) ? self::string( $cookie['key'] ?? null ) : '';
			if ( '' !== $key && ! isset( $cookies[ $key ] ) ) {
				$cookies[ $key ] = $cookie;
			}
		}
		return $cookies;
	}

	/**
	 * Returns the stored cookie keys of a service.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $service Stored service.
	 * @return list<string> Keys.
	 */
	private static function cookie_keys( array $service ): array {
		return array_map( 'strval', array_keys( self::cookie_list( $service ) ) );
	}

	/**
	 * Returns a posted value after sanitize_text_field(); an empty string for other types.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value, unslashed.
	 * @return string Clean text.
	 */
	private static function text( mixed $value ): string {
		return is_string( $value ) ? sanitize_text_field( $value ) : '';
	}

	/**
	 * Returns a posted value trimmed only; an empty string for other types.
	 *
	 * For values the validator checks strictly (privacy link, cookie name),
	 * whose percent-encoded octets sanitize_text_field() would delete.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value, unslashed.
	 * @return string Trimmed text.
	 */
	private static function trimmed( mixed $value ): string {
		return is_string( $value ) ? trim( $value ) : '';
	}

	/**
	 * Returns the services of the store in a flat shape.
	 *
	 * @since 1.0.0
	 *
	 * @return list<array{id: string, category: string, name: string, provider: string, preset: string, preset_checked: string, cookies: int, displays: list<string>}> Services.
	 */
	private static function services(): array {
		$data     = self::store();
		$services = array();
		foreach ( is_array( $data['services'] ) ? $data['services'] : array() as $service ) {
			if ( ! is_array( $service ) || '' === self::string( $service['id'] ?? null ) ) {
				continue;
			}
			$cookies  = is_array( $service['cookies'] ?? null ) ? $service['cookies'] : array();
			$displays = array();
			foreach ( $cookies as $cookie ) {
				if ( is_array( $cookie ) ) {
					$displays[] = Consent_Cookie_Name::display( self::string( $cookie['name'] ?? null ), self::string( $cookie['match'] ?? null ) );
				}
			}
			$services[] = array(
				'id'             => self::string( $service['id'] ),
				'category'       => self::string( $service['category'] ?? null ),
				'name'           => self::string( $service['name'] ?? null ),
				'provider'       => self::string( $service['provider'] ?? null ),
				'preset'         => self::string( $service['preset'] ?? null ),
				'preset_checked' => self::string( $service['preset_checked'] ?? null ),
				'cookies'        => count( $cookies ),
				'displays'       => $displays,
			);
		}
		return $services;
	}

	/**
	 * Returns one service of the store by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Service ID.
	 * @return array{id: string, category: string, name: string, provider: string, preset: string, preset_checked: string, cookies: int, displays: list<string>}|null Service; null when unknown.
	 */
	private static function service( string $id ): ?array {
		foreach ( self::services() as $service ) {
			if ( $id === $service['id'] ) {
				return $service;
			}
		}
		return null;
	}

	/**
	 * Tells whether the module consent is installed.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when installed.
	 */
	private static function installed(): bool {
		return isset( Module_State::instance()->catalog()->installed()['consent'] );
	}

	/**
	 * Tells whether the module consent is on.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when active.
	 */
	private static function active(): bool {
		return 'active' === Module_State::instance()->state( 'consent' );
	}

	/**
	 * Returns the current consent revision.
	 *
	 * @since 1.0.0
	 *
	 * @return int Revision.
	 */
	private static function revision(): int {
		$value = Settings::instance()->get( self::REVISION );
		return is_int( $value ) ? $value : 0;
	}

	/**
	 * Returns the write context of the current user on this page.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $lang Language code; null for the default language.
	 * @return Write_Context Context.
	 */
	private static function context( ?string $lang ): Write_Context {
		return new Write_Context( 'admin', get_current_user_id(), $lang ?? Language::instance()->default() );
	}

	/**
	 * Returns the result code of a write: the success code, "unchanged", or the given code for a refusal; stops with 403 when forbidden.
	 *
	 * @since 1.0.0
	 *
	 * @param Set_Result $result  Result of the store.
	 * @param string     $saved   Code for SAVED.
	 * @param string     $invalid Code for every other refusal.
	 * @return string Result code.
	 */
	private static function code( Set_Result $result, string $saved, string $invalid ): string {
		return match ( $result->status ) {
			Set_Status::SAVED     => $saved,
			Set_Status::UNCHANGED => 'unchanged',
			Set_Status::FORBIDDEN => self::deny(),
			default               => $invalid,
		};
	}

	/**
	 * Returns a field of the first error of a result.
	 *
	 * @since 1.0.0
	 *
	 * @param Set_Result $result Result.
	 * @param string     $field  Field of the error, e.g. "code" or "service".
	 * @return string Value; empty without errors.
	 */
	private static function first_error( Set_Result $result, string $field ): string {
		$errors = $result->data['errors'] ?? null;
		$first  = is_array( $errors ) ? reset( $errors ) : null;
		return is_array( $first ) ? self::string( $first[ $field ] ?? null ) : '';
	}

	/**
	 * Ends the request with 403 unless the user may change the cookies and services.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function require_capability(): void {
		if ( ! current_user_can( Capabilities::MANAGE_CONSENT ) ) {
			self::deny();
		}
	}

	/**
	 * Stops the request with 403.
	 *
	 * @since 1.0.0
	 *
	 * @return never
	 */
	private static function deny(): never {
		wp_die( esc_html__( 'You are not allowed to change the cookies and services.', 'creationell-wp-theme' ), '', array( 'response' => 403 ) );
	}

	/**
	 * Stops the request with 403 for a user who may not raise the consent revision.
	 *
	 * @since 1.0.0
	 *
	 * @return never
	 */
	private static function deny_revision(): never {
		wp_die( esc_html__( 'You are not allowed to raise the consent revision.', 'creationell-wp-theme' ), '', array( 'response' => 403 ) );
	}

	/**
	 * Returns the store data of this request, read once.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Data as Consent_Services_Store::read() returns it.
	 */
	private static function store(): array {
		self::$store ??= Consent_Services_Store::read();
		return self::$store;
	}

	/**
	 * Forgets the store data read in this request, so that the next use reads the option again.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function forget(): void {
		self::$store = null;
	}

	/**
	 * Reads the query arguments of the request.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Sanitized values by key; missing keys are left out.
	 */
	private static function query(): array {
		$query = array();
		foreach ( self::QUERY_KEYS as $key ) {
			$value = filter_input( INPUT_GET, $key );
			if ( is_string( $value ) ) {
				$query[ $key ] = sanitize_key( $value );
			}
		}
		return $query;
	}

	/**
	 * Returns a string value, an empty string for other types.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return string String.
	 */
	private static function string( mixed $value ): string {
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Redirects back to the page with result codes and ends the request.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $args Query arguments: view, service, result, key, error, draft, more; empty values are left out.
	 * @return never
	 */
	private static function finish( array $args ): never {
		wp_safe_redirect( self::url( array_filter( $args, static fn( string $value ): bool => '' !== $value ) ) );
		if ( null !== self::$halt ) {
			( self::$halt )();
		}
		exit;
	}
}
