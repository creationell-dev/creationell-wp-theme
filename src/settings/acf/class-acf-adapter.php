<?php
/**
 * ACF adapter of the theme settings.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Acf;

use Closure;
use Creationell\WpTheme\Admin\Notices;
use Creationell\WpTheme\Admin\Settings_Notices;
use Creationell\WpTheme\Settings\Definition;
use Creationell\WpTheme\Settings\Language;
use Creationell\WpTheme\Settings\Option_Store;
use Creationell\WpTheme\Settings\Registry;
use Creationell\WpTheme\Settings\Set_Result;
use Creationell\WpTheme\Settings\Set_Status;
use Creationell\WpTheme\Settings\Setter;
use Creationell\WpTheme\Settings\Settings;
use Creationell\WpTheme\Settings\Snapshot;
use Creationell\WpTheme\Settings\Write_Context;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Connects the options pages of ACF Pro with the settings: ACF shows and posts, the theme reads and writes.
 *
 * Only with ACF Pro; without it administrators get a notice and the theme keeps
 * working with the saved values and the defaults. The hooks:
 *
 * - acf/pre_load_value: fields of the theme read Settings::get() in the
 *   language of the post_id, never the rows;
 * - acf/validate_post_id: ACFML 5 appends "_<lang>" to every own options
 *   post_id; the global page gets its plain post_id back, so the untranslated
 *   keys stay the same in every language (FS-20);
 * - acf/prepare_field: effective value and origin, which a select always
 *   offers; read-only when locked, without the capability of the area or
 *   untranslated in a secondary language, then every type but the plain inputs
 *   shows its value as a message; acf/render_field adds the reset button (FS-14);
 * - acf/validate_save_post: a dry run of the setter over the posted values
 *   reports contrast and invalid values at the field;
 * - acf/pre_update_value: fields of the theme never reach the rows through ACF;
 *   during a form save the values are collected and acf/save_post (20) hands
 *   them to Setter::set_many() at once, so the contrast check sees every posted
 *   color; outside a form save a single value goes to Setter::set();
 * - acf/options_page/save: reset, commit, notice with the results.
 *
 * @since 1.0.0
 */
final class Acf_Adapter {

	/**
	 * Name of the reset buttons; the value is the setting key.
	 *
	 * @since 1.0.0
	 */
	public const RESET_FIELD = 'creationell_wp_theme_reset';

	/**
	 * Field setting that marks a field with a reset button.
	 *
	 * @since 1.0.0
	 */
	public const RESET_FLAG = 'creationell_wp_theme_resettable';

	/**
	 * ID of the notice about a missing ACF Pro.
	 *
	 * @since 1.0.0
	 */
	public const NOTICE_ID = 'acf-pro-missing';

	/**
	 * Longest value shown in the field description before it is shortened.
	 *
	 * @since 1.0.0
	 */
	public const MAX_SHOWN = 80;

	/**
	 * ACF types that honor readonly and disabled and post nothing else; other types show their value as a message when read-only.
	 *
	 * The type true_false posts a hidden 0 and has no disabled checkbox, post_object
	 * and a select with ui add a hidden input of the same name (tp5-p3-01).
	 *
	 * @since 1.0.0
	 */
	public const NATIVE_READ_ONLY = array( 'text', 'textarea', 'number', 'email', 'url' );

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Returns the ID of the current user.
	 *
	 * @var Closure
	 * @phpstan-var Closure(): int
	 */
	private Closure $user;

	/**
	 * Values posted during a form save, by language code of the post_id ("" for the default language) and setting key.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $queue = array();

	/**
	 * Results of the writes since the last notice.
	 *
	 * @var array<int, Set_Result>
	 */
	private array $results = array();

	/**
	 * Takes the reader of the current user; without one it asks WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @param Closure|null $user Returns the ID of the current user.
	 * @phpstan-param (Closure(): int)|null $user
	 */
	public function __construct( ?Closure $user = null ) {
		$this->user = $user ?? static fn(): int => get_current_user_id();
	}

	/**
	 * Returns the shared instance.
	 *
	 * @since 1.0.0
	 *
	 * @return self Instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Replaces the shared instance; null builds a new one on the next call of instance().
	 *
	 * @since 1.0.0
	 *
	 * @param self|null $adapter Instance.
	 * @return void
	 */
	public static function set_instance( ?self $adapter ): void {
		self::$instance = $adapter;
	}

	/**
	 * Registers the adapter when ACF Pro is active; runs on after_setup_theme.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function boot(): void {
		self::instance()->register( defined( 'ACF_PRO' ) && function_exists( 'acf_add_options_sub_page' ) );
	}

	/**
	 * Registers the hooks, or without ACF Pro the notice for administrators.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $acf_pro Whether ACF Pro is active.
	 * @return void
	 */
	public function register( bool $acf_pro ): void {
		if ( ! $acf_pro ) {
			Notices::add(
				self::NOTICE_ID,
				static fn(): string => __( 'The theme settings pages "Texts" and "Design" need the plugin ACF Pro. Without it the site keeps the saved settings and the defaults, but they cannot be changed in the admin.', 'creationell-wp-theme' ),
				Notices::WARNING
			);
			return;
		}
		add_action( 'acf/init', array( Options_Pages::class, 'register' ), 10, 0 );
		add_filter( 'acf/pre_load_value', array( $this, 'pre_load_value' ), 10, 3 );
		add_filter( 'acf/validate_post_id', array( $this, 'validate_post_id' ), PHP_INT_MAX - 10, 2 );
		add_filter( 'acf/prepare_field', array( $this, 'prepare_field' ), 10, 1 );
		add_action( 'acf/render_field', array( $this, 'render_field' ), 20, 1 );
		add_action( 'acf/validate_save_post', array( $this, 'validate_save_post' ), 5, 0 );
		add_filter( 'acf/pre_update_value', array( $this, 'pre_update_value' ), 10, 4 );
		add_action( 'acf/save_post', array( $this, 'save_post' ), 20, 1 );
		add_action( 'acf/options_page/save', array( $this, 'options_page_saved' ), 10, 2 );
		add_action( 'acf/input/admin_head', array( $this, 'admin_head' ), 20, 0 );
		add_action( 'admin_enqueue_scripts', array( Options_Pages::class, 'enqueue_styles' ), 10, 0 );
		add_action( 'admin_notices', array( Settings_Notices::class, 'render' ), 10, 0 );
	}

	/**
	 * Returns the ID of the current user.
	 *
	 * @since 1.0.0
	 *
	 * @return int User ID.
	 */
	public function current_user(): int {
		return ( $this->user )();
	}

	/**
	 * Parses an ACF post_id of the theme pages.
	 *
	 * "creationell_wp_theme_global" is global, also with the suffix of an active
	 * language (ACFML, FS-20); "options" is the default language, "options_<lang>"
	 * an active language, "options_all" all languages.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $post_id ACF post_id.
	 * @return array{global: bool, lang: ?string}|null Whether global and the code of a secondary language or "all"; null for other post IDs.
	 */
	public static function post_id_info( mixed $post_id ): ?array {
		if ( ! is_string( $post_id ) ) {
			return null;
		}
		$language = Language::instance();
		if ( Option_Store::GLOBAL_POST_ID === $post_id ) {
			return self::info( true, null );
		}
		if ( str_starts_with( $post_id, Option_Store::GLOBAL_POST_ID . '_' ) ) {
			$suffix = substr( $post_id, strlen( Option_Store::GLOBAL_POST_ID ) + 1 );
			return in_array( $suffix, $language->active(), true ) ? self::info( true, null ) : null;
		}
		if ( Option_Store::OPTIONS_POST_ID === $post_id ) {
			return self::info( false, null );
		}
		if ( str_starts_with( $post_id, Option_Store::OPTIONS_POST_ID . '_' ) ) {
			$code = substr( $post_id, strlen( Option_Store::OPTIONS_POST_ID ) + 1 );
			if ( Language::ALL === $code ) {
				return self::info( false, Language::ALL );
			}
			return in_array( $code, $language->active(), true ) ? self::info( false, $language->secondary( $code ) ) : null;
		}
		return null;
	}

	/**
	 * Removes the language suffix ACFML appended to the global post_id; filter acf/validate_post_id.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $post_id  Post ID after the earlier filters.
	 * @param mixed $original Post ID before the filters.
	 * @return mixed "creationell_wp_theme_global" for the global page, otherwise the given post ID.
	 */
	public function validate_post_id( mixed $post_id, mixed $original ): mixed {
		unset( $original );
		$info = self::post_id_info( $post_id );
		return null !== $info && $info['global'] ? Option_Store::GLOBAL_POST_ID : $post_id;
	}

	/**
	 * Returns the value of a theme field from the settings; filter acf/pre_load_value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value   Value of an earlier filter; null to load.
	 * @param mixed $post_id ACF post_id.
	 * @param mixed $field   ACF field.
	 * @return mixed Effective value in the language of the post_id, or the given value for other fields.
	 */
	public function pre_load_value( mixed $value, mixed $post_id, mixed $field ): mixed {
		if ( null !== $value ) {
			return $value;
		}
		$key  = Field_Factory::setting_key( $field );
		$info = self::post_id_info( $post_id );
		if ( null === $key || null === $info ) {
			return $value;
		}
		return Settings::instance()->get( $key, $info['lang'] ?? Language::instance()->default() );
	}

	/**
	 * Adds effective value, origin, read-only state and reset flag to a theme field; filter acf/prepare_field.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $field ACF field, or false when an earlier filter removed it.
	 * @return mixed The field.
	 */
	public function prepare_field( mixed $field ): mixed {
		$key = Field_Factory::setting_key( $field );
		if ( null === $key || ! is_array( $field ) ) {
			return $field;
		}
		$definition = Registry::instance()->get( $key );
		$info       = self::post_id_info( acf_get_form_data( 'post_id' ) );
		$lang       = null === $info ? null : $info['lang'];
		$effective  = Settings::instance()->effective( $key, $lang ?? Language::instance()->default() );
		$reason     = $this->read_only_reason( $definition, $effective['origin'], $lang );

		$lines = array();
		if ( isset( $field['instructions'] ) && is_string( $field['instructions'] ) && '' !== $field['instructions'] ) {
			$lines[] = esc_html( $field['instructions'] );
		}
		$lines[] = esc_html(
			sprintf(
				/* translators: 1: effective value of the setting, 2: where the value comes from, e.g. "theme default". */
				__( 'Effective: %1$s · Origin: %2$s', 'creationell-wp-theme' ),
				self::shown( $effective['value'] ),
				self::origin_label( $definition, $effective['origin'], $lang )
			)
		);
		if ( null !== $reason ) {
			$lines[] = esc_html( $reason );
		}
		$field['instructions']     = implode( '<br>', $lines );
		$field[ self::RESET_FLAG ] = null === $reason && self::has_row( $key, $lang );
		$field                     = self::with_choice( $field, $effective['value'] );
		if ( null === $reason ) {
			return $field;
		}

		$field['readonly'] = 1;
		$field['disabled'] = 1;
		if ( in_array( $field['type'] ?? null, self::NATIVE_READ_ONLY, true ) ) {
			return $field;
		}
		$field['message']   = self::read_only_view( $field, $definition, $effective['value'] );
		$field['type']      = 'message';
		$field['esc_html']  = 0;
		$field['new_lines'] = '';
		return $field;
	}

	/**
	 * Adds the effective value to the choices of a select that lacks it.
	 *
	 * Without it the browser selects the first choice, and an unchanged save
	 * would overwrite the value silently (tp5-p3-02). The label is the value
	 * itself; the setter still judges it on save.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $field ACF field.
	 * @param mixed        $value Effective value.
	 * @return array<mixed> The field.
	 */
	private static function with_choice( array $field, mixed $value ): array {
		if ( 'select' !== ( $field['type'] ?? null ) || ! is_array( $field['choices'] ?? null ) || ! ( is_string( $value ) || is_int( $value ) ) || '' === $value ) {
			return $field;
		}
		if ( ! array_key_exists( $value, $field['choices'] ) ) {
			$field['choices'][ $value ] = (string) $value;
		}
		return $field;
	}

	/**
	 * Returns the escaped HTML that shows the value of a read-only field.
	 *
	 * A color shows as swatch with hex, a choice by its label, a page by its
	 * title and ID, a switch as yes or no, anything else as short text.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $field      ACF field.
	 * @param Definition   $definition Definition.
	 * @param mixed        $value      Effective value.
	 * @return string Escaped HTML.
	 */
	private static function read_only_view( array $field, Definition $definition, mixed $value ): string {
		if ( 'color' === $definition->type && is_string( $value ) ) {
			return sprintf(
				'<span class="creationell-wp-theme-swatch" style="background-color: %1$s;" aria-hidden="true"></span> <code>%2$s</code>',
				esc_attr( $value ),
				esc_html( $value )
			);
		}
		if ( 'page' === $definition->reference_type && is_int( $value ) ) {
			if ( $value <= 0 ) {
				return esc_html__( '(none)', 'creationell-wp-theme' );
			}
			$title = get_the_title( $value );
			return esc_html(
				sprintf(
					/* translators: 1: title of a page, 2: ID of the page. */
					__( '%1$s (ID %2$d)', 'creationell-wp-theme' ),
					'' !== $title ? $title : __( '(no title)', 'creationell-wp-theme' ),
					$value
				)
			);
		}
		$choices = $field['choices'] ?? null;
		if ( is_array( $choices ) && ( is_string( $value ) || is_int( $value ) ) && isset( $choices[ $value ] ) && is_string( $choices[ $value ] ) ) {
			return esc_html( $choices[ $value ] );
		}
		return esc_html( self::shown( $value ) );
	}

	/**
	 * Prints the reset button after a theme field with a backend value; action acf/render_field.
	 *
	 * The button submits the form (FS-14: no writing GET); the options page runs
	 * the reset after its save.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $field Prepared ACF field.
	 * @return void
	 */
	public function render_field( mixed $field ): void {
		$key = Field_Factory::setting_key( $field );
		if ( null === $key || ! is_array( $field ) || true !== ( $field[ self::RESET_FLAG ] ?? false ) ) {
			return;
		}
		printf(
			'<p class="creationell-wp-theme-reset"><button type="submit" class="button" name="%1$s" value="%2$s">%3$s</button></p>' . "\n",
			esc_attr( self::RESET_FIELD ),
			esc_attr( $key ),
			esc_html(
				sprintf(
					/* translators: %s: label of the setting. */
					__( 'Reset to default: %s', 'creationell-wp-theme' ),
					self::label( $key )
				)
			)
		);
	}

	/**
	 * Reports contrast and invalid values at their fields before ACF saves; action acf/validate_save_post.
	 *
	 * ACF verified its nonce before this action. The setter runs as a dry run
	 * over the posted theme fields, so the check equals the one of the save.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function validate_save_post(): void {
		$posted = acf_maybe_get_POST( 'acf' );
		$info   = self::post_id_info( wp_unslash( acf_maybe_get_POST( '_acf_post_id' ) ) );
		if ( ! is_array( $posted ) || null === $info ) {
			return;
		}
		$values = array();
		foreach ( $posted as $field_key => $value ) {
			$key = Field_Factory::setting_key( array( 'key' => $field_key ) );
			if ( null !== $key ) {
				$values[ $key ] = wp_unslash( $value );
			}
		}
		if ( array() === $values ) {
			return;
		}
		foreach ( Setter::instance()->set_many( $values, $this->context( $info['lang'], true ) ) as $result ) {
			if ( in_array( $result->status, array( Set_Status::CONTRAST, Set_Status::INVALID ), true ) ) {
				acf_add_validation_error( 'acf[' . Option_Store::field_key( $result->key ) . ']', $result->message );
			}
		}
	}

	/**
	 * Takes over the write of a theme field; filter acf/pre_update_value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $check   Result of an earlier filter; null to write.
	 * @param mixed $value   Posted value, slashed.
	 * @param mixed $post_id ACF post_id.
	 * @param mixed $field   ACF field.
	 * @return mixed True while a form save collects the value; outside a form save whether the setter took it; the given result for other fields.
	 */
	public function pre_update_value( mixed $check, mixed $value, mixed $post_id, mixed $field ): mixed {
		if ( null !== $check ) {
			return $check;
		}
		$key  = Field_Factory::setting_key( $field );
		$info = self::post_id_info( $post_id );
		if ( null === $key || null === $info ) {
			return $check;
		}
		$value = wp_unslash( $value );
		if ( doing_action( 'acf/save_post' ) ) {
			$this->queue[ $info['lang'] ?? '' ][ $key ] = $value;
			return true;
		}
		$setter = Setter::instance();
		$result = $setter->set( $key, $value, $this->context( $info['lang'] ) );
		$setter->commit();
		return $result->succeeded();
	}

	/**
	 * Writes the values collected during a form save and commits; action acf/save_post, priority 20.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $post_id ACF post_id of the save.
	 * @return void
	 */
	public function save_post( mixed $post_id ): void {
		unset( $post_id );
		if ( array() === $this->queue ) {
			return;
		}
		$queue       = $this->queue;
		$this->queue = array();
		$setter      = Setter::instance();
		foreach ( $queue as $lang => $values ) {
			$lang          = (string) $lang;
			$this->results = array_merge( $this->results, $setter->set_many( $values, $this->context( '' === $lang ? null : $lang ) ) );
		}
		$setter->commit();
	}

	/**
	 * Runs the reset of the pressed button, commits and keeps the results for the notice; action acf/options_page/save.
	 *
	 * ACF verified its nonce before this action.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $post_id   ACF post_id of the page.
	 * @param mixed $menu_slug Menu slug of the page.
	 * @return void
	 */
	public function options_page_saved( mixed $post_id, mixed $menu_slug ): void {
		if ( ! is_string( $menu_slug ) || ! isset( Options_Pages::pages()[ $menu_slug ] ) ) {
			return;
		}
		$info   = self::post_id_info( $post_id );
		$setter = Setter::instance();
		$reset  = wp_unslash( acf_maybe_get_POST( self::RESET_FIELD ) );
		if ( is_string( $reset ) && '' !== $reset ) {
			$this->results[] = $setter->reset( sanitize_key( $reset ), $this->context( null === $info ? null : $info['lang'] ) );
		}
		$setter->commit();
		Settings_Notices::store( $this->current_user(), $this->results );
		$this->results = array();
	}

	/**
	 * Removes the success notice of ACF and, for a user who may change nothing there, the save box of a theme page; action acf/input/admin_head, priority 20.
	 *
	 * ACF adds both on the same action with priority 10. After a save the result
	 * list of the theme (Settings_Notices) is the only success notice. The setter
	 * refuses writes without the right anyway ("forbidden").
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function admin_head(): void {
		$page = Options_Pages::current();
		if ( null === $page ) {
			return;
		}
		Options_Pages::remove_acf_updated_notice();
		if ( ! Options_Pages::writable( $page, $this->current_user() ) ) {
			remove_meta_box( 'submitdiv', 'acf_options_page', 'side' );
		}
	}

	/**
	 * Returns the results of the writes since the last notice.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, Set_Result> Results in write order.
	 */
	public function results(): array {
		return $this->results;
	}

	/**
	 * Builds the write context of the current user.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $lang    Code of a secondary language, "all" or null for the default language.
	 * @param bool        $dry_run Whether to check only.
	 * @return Write_Context Context of the channel "acf".
	 */
	private function context( ?string $lang, bool $dry_run = false ): Write_Context {
		return new Write_Context( channel: 'acf', user_id: $this->current_user(), lang: $lang, dry_run: $dry_run );
	}

	/**
	 * Tells why the current user sees a field read-only.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition  $definition Definition.
	 * @param string      $origin     Origin of the effective value.
	 * @param string|null $lang       Code of a secondary language, "all" or null.
	 * @return string|null Translated reason, or null when the field is writable.
	 */
	private function read_only_reason( Definition $definition, string $origin, ?string $lang ): ?string {
		if ( Settings::ORIGIN_CONSTANT === $origin ) {
			return sprintf(
				/* translators: %s: name of a PHP constant. */
				__( 'Set by the constant %s.', 'creationell-wp-theme' ),
				Settings::constant_name( $definition->key )
			);
		}
		if ( Settings::ORIGIN_LOCKED === $origin ) {
			return __( 'Locked by the child theme.', 'creationell-wp-theme' );
		}
		if ( Language::ALL === $lang ) {
			return __( 'Choose one language; settings cannot be saved for all languages at once.', 'creationell-wp-theme' );
		}
		if ( null !== $lang && ! $definition->translatable ) {
			return __( 'The same in every language; change it in the default language.', 'creationell-wp-theme' );
		}
		if ( ! user_can( $this->current_user(), $definition->capability ) ) {
			return __( 'You may view this setting but not change it.', 'creationell-wp-theme' );
		}
		return null;
	}

	/**
	 * Returns the label of an origin.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition  $definition Definition.
	 * @param string      $origin     Origin.
	 * @param string|null $lang       Code of a secondary language, "all" or null.
	 * @return string Translated label.
	 */
	private static function origin_label( Definition $definition, string $origin, ?string $lang ): string {
		if ( Settings::ORIGIN_BACKEND === $origin && null !== $lang && $definition->translatable && ! self::has_row( $definition->key, $lang ) ) {
			return __( 'default language', 'creationell-wp-theme' );
		}
		return match ( $origin ) {
			Settings::ORIGIN_CHILD    => __( 'child theme default', 'creationell-wp-theme' ),
			Settings::ORIGIN_BACKEND  => __( 'saved value', 'creationell-wp-theme' ),
			Settings::ORIGIN_LOCKED   => __( 'child theme lock', 'creationell-wp-theme' ),
			Settings::ORIGIN_CONSTANT => __( 'site constant', 'creationell-wp-theme' ),
			default                   => __( 'theme default', 'creationell-wp-theme' ),
		};
	}

	/**
	 * Tells whether the snapshot of a language holds a backend value of a key.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $key  Setting key.
	 * @param string|null $lang Code of a secondary language, "all" or null for the default language.
	 * @return bool True with a backend row in that language.
	 */
	private static function has_row( string $key, ?string $lang ): bool {
		return array_key_exists( $key, Snapshot::instance()->values( $lang ) );
	}

	/**
	 * Returns a value as short plain text for the field description.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return string Text; "(empty)" for an empty string.
	 */
	private static function shown( mixed $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? __( 'yes', 'creationell-wp-theme' ) : __( 'no', 'creationell-wp-theme' );
		}
		if ( ! is_scalar( $value ) ) {
			return '';
		}
		$text = (string) $value;
		if ( '' === $text ) {
			return __( '(empty)', 'creationell-wp-theme' );
		}
		return mb_strlen( $text, 'UTF-8' ) > self::MAX_SHOWN ? mb_substr( $text, 0, self::MAX_SHOWN, 'UTF-8' ) . '…' : $text;
	}

	/**
	 * Returns the label of a setting.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Setting key.
	 * @return string Translated label, or the key without one.
	 */
	private static function label( string $key ): string {
		$registry = Registry::instance();
		$label    = $registry->has( $key ) ? $registry->get( $key )->label_text() : '';
		return '' !== $label ? $label : $key;
	}

	/**
	 * Builds a parsed post_id.
	 *
	 * @since 1.0.0
	 *
	 * @param bool        $is_global Whether the global page.
	 * @param string|null $lang      Code of a secondary language, "all" or null.
	 * @return array{global: bool, lang: ?string} Parsed post_id.
	 */
	private static function info( bool $is_global, ?string $lang ): array {
		return array(
			'global' => $is_global,
			'lang'   => $lang,
		);
	}
}
