<?php
/**
 * Module page of the theme settings.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Admin;

use Creationell\WpTheme\Core\Capabilities;
use Creationell\WpTheme\Core\Scan_Result;
use Creationell\WpTheme\Modules\Module_Catalog;
use Creationell\WpTheme\Modules\Module_State;
use Creationell\WpTheme\Modules\Module_Switcher;
use Creationell\WpTheme\Modules\Switch_Request;
use Creationell\WpTheme\Modules\Switch_Result;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Shows every module with its state and switches modules through the module switcher.
 *
 * The page is a subpage of the theme menu for users with
 * creationell_wp_theme_view_advanced. It lists the modules of the catalog with
 * their description, their effective state and the reason as text. Users with
 * creationell_wp_theme_manage_modules get a form per installed module: one radio
 * per state, disabled with the reason while a child lock or a constant holds the
 * state. A module that is active and knows "hidden" goes to "hidden" first; "off"
 * is not offered then.
 *
 * Switching a module with blocks or with warn_before_off off leads to the
 * confirmation view first: the consequences, the contents that use its blocks
 * with up to 20 links and, when the switcher asks for it, the checkbox "I
 * understand the consequences". The form sends the number of live contents
 * shown; when more contents use the blocks by then, the view asks again.
 *
 * The handler of the admin-post action creationell_wp_theme_module_switch
 * checks the nonce of the module, stops with 403 without the capability,
 * switches through Module_Switcher (channel "admin") and redirects back with
 * codes only: result, module and warnings. The result shows as a notice whose
 * heading gets the focus and leads the document title.
 *
 * @since 1.0.0
 */
final class Module_Page {

	/**
	 * Slug of the page below the theme menu.
	 *
	 * @since 1.0.0
	 */
	public const PAGE = 'creationell-wp-theme-modules';

	/**
	 * Action of the switch form, for admin-post.php.
	 *
	 * @since 1.0.0
	 */
	public const ACTION = 'creationell_wp_theme_module_switch';

	/**
	 * Prefix of the nonce action; the module slug follows.
	 *
	 * @since 1.0.0
	 */
	public const NONCE_PREFIX = 'creationell_wp_theme_module_switch_';

	/**
	 * Results the page reports after a redirect.
	 *
	 * @since 1.0.0
	 */
	public const RESULTS = array( Switch_Result::SWITCHED, Switch_Result::UNCHANGED, Switch_Result::LOCKED, Switch_Result::INVALID );

	/**
	 * Warning codes the redirect carries.
	 *
	 * @since 1.0.0
	 */
	public const WARNINGS = array( 'dependency_missing', Switch_Result::WARNING_CALLBACK_FAILED, Switch_Result::WARNING_BACKUP_HINT );

	/**
	 * Errors of the confirmation view: the checkbox is missing, or more contents use the blocks than were shown.
	 *
	 * @since 1.0.0
	 */
	public const ERRORS = array( 'confirm', 'changed' );

	/**
	 * ID of the heading of the result notice.
	 *
	 * @since 1.0.0
	 */
	public const RESULT_ID = 'creationell-wp-theme-module-result';

	/**
	 * Prefix of the element IDs of the page.
	 *
	 * @since 1.0.0
	 */
	private const ID = 'creationell-wp-theme-module-';

	/**
	 * Query arguments the page reads.
	 *
	 * @since 1.0.0
	 */
	private const QUERY_KEYS = array( 'view', 'module', 'result', 'warnings', 'error' );

	/**
	 * Registers the page, the handler and the document title.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_action( 'admin_menu', array( self::class, 'add_page' ), 10, 0 );
		add_action( 'admin_post_' . self::ACTION, array( self::class, 'handle' ), 10, 0 );
		add_filter( 'admin_title', array( self::class, 'filter_admin_title' ), 10, 1 );
	}

	/**
	 * Adds the page below the theme menu; runs on admin_menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function add_page(): void {
		add_submenu_page(
			Menu::SLUG,
			__( 'Theme modules', 'creationell-wp-theme' ),
			__( 'Modules', 'creationell-wp-theme' ),
			Capabilities::VIEW_ADVANCED,
			self::PAGE,
			array( self::class, 'render' )
		);
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
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function render(): void {
		self::render_page( self::query() );
	}

	/**
	 * Prints the page: the confirmation view, or the table with the result of the last switch.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $query Query arguments: view, module, result, warnings, error.
	 * @return void
	 */
	public static function render_page( array $query ): void {
		$query    = self::clean( $query );
		$switcher = Module_Switcher::instance();
		$state    = $switcher->state();
		$can      = current_user_can( Capabilities::MANAGE_MODULES );
		$slug     = $query['module'];

		if ( $can && 'confirm' === $query['view'] && self::asks( $state, $slug ) && in_array( 'off', self::offered( $state, $slug ), true ) ) {
			$result = $switcher->switch( new Switch_Request( $slug, 'off', 'admin', false, true ) );
			if ( in_array( $result->status, array( Switch_Result::NEEDS_CONFIRMATION, Switch_Result::DRY_RUN ), true ) ) {
				self::render_confirmation( $state->catalog(), $slug, $result, $query['error'] );
				return;
			}
		}

		echo '<div class="wrap">' . "\n";
		printf( '<h1>%s</h1>' . "\n", esc_html__( 'Theme modules', 'creationell-wp-theme' ) );
		self::render_result( $state, $query );
		if ( ! $can ) {
			printf( '<p id="creationell-wp-theme-modules-read-only">%s</p>' . "\n", esc_html__( 'You can only view the modules.', 'creationell-wp-theme' ) );
		}
		self::render_table( $state, $can );
		echo '</div>' . "\n";
	}

	/**
	 * Switches a module from the form; runs on admin_post_creationell_wp_theme_module_switch.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function handle(): void {
		$slug = isset( $_POST['module'] ) ? sanitize_key( wp_unslash( $_POST['module'] ) ) : '';
		check_admin_referer( self::NONCE_PREFIX . $slug );
		if ( ! current_user_can( Capabilities::MANAGE_MODULES ) ) {
			self::deny();
		}
		$target    = isset( $_POST['state'] ) ? sanitize_key( wp_unslash( $_POST['state'] ) ) : '';
		$confirm   = isset( $_POST['step'] ) && 'confirm' === sanitize_key( wp_unslash( $_POST['step'] ) );
		$confirmed = $confirm && isset( $_POST['confirmed'] );
		// A confirmation without the field counts as nothing seen, so new contents are always shown first.
		$seen_live = $confirm ? absint( isset( $_POST['seen_live'] ) ? wp_unslash( $_POST['seen_live'] ) : 0 ) : null;

		$switcher = Module_Switcher::instance();
		$state    = $switcher->state();
		if ( ! in_array( $target, self::offered( $state, $slug ), true ) ) {
			self::finish( Switch_Result::INVALID, $slug, array() );
		}
		$ask    = ! $confirm && 'off' === $target && self::asks( $state, $slug );
		$result = $switcher->switch( new Switch_Request( $slug, $target, 'admin', $confirmed, $ask, $seen_live ) );
		if ( Switch_Result::DENIED === $result->status ) {
			self::deny();
		}
		if ( Switch_Result::NEEDS_CONFIRMATION === $result->status || ( $ask && Switch_Result::DRY_RUN === $result->status ) ) {
			$args = array(
				'view'   => 'confirm',
				'module' => $slug,
			);
			if ( $confirm && Switch_Result::NEEDS_CONFIRMATION === $result->status ) {
				$args['error'] = $confirmed ? 'changed' : 'confirm';
			}
			self::redirect( self::url( $args ) );
		}
		self::finish( $result->status, $slug, $result->warnings );
	}

	/**
	 * Puts the result of the last switch in front of the document title of the page; runs on admin_title.
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
	 * Returns the document title with the result of the last switch in front.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $admin_title Document title.
	 * @param array<string, mixed> $query       Query arguments.
	 * @return string Document title.
	 */
	public static function admin_title( string $admin_title, array $query ): string {
		$query  = self::clean( $query );
		$result = self::result_message( Module_Switcher::instance()->state(), $query['result'], $query['module'] );
		return null === $result ? $admin_title : $result['text'] . ' &lsaquo; ' . $admin_title;
	}

	/**
	 * Reads the query arguments of the request.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Raw values by key; missing keys are left out.
	 */
	private static function query(): array {
		$query = array();
		foreach ( self::QUERY_KEYS as $key ) {
			$value = filter_input( INPUT_GET, $key );
			if ( is_string( $value ) ) {
				$query[ $key ] = $value;
			}
		}
		return $query;
	}

	/**
	 * Sanitizes the query arguments and keeps only known codes.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $query Raw query arguments.
	 * @return array{view: string, module: string, result: string, warnings: list<string>, error: string} Clean arguments.
	 */
	private static function clean( array $query ): array {
		$value    = static fn( string $key ): string => isset( $query[ $key ] ) && is_string( $query[ $key ] ) ? sanitize_key( $query[ $key ] ) : '';
		$warnings = array();
		foreach ( explode( ',', is_string( $query['warnings'] ?? null ) ? $query['warnings'] : '' ) as $code ) {
			$code = sanitize_key( $code );
			if ( in_array( $code, self::WARNINGS, true ) && ! in_array( $code, $warnings, true ) ) {
				$warnings[] = $code;
			}
		}
		return array(
			'view'     => $value( 'view' ),
			'module'   => $value( 'module' ),
			'result'   => in_array( $value( 'result' ), self::RESULTS, true ) ? $value( 'result' ) : '',
			'warnings' => $warnings,
			'error'    => in_array( $value( 'error' ), self::ERRORS, true ) ? $value( 'error' ) : '',
		);
	}

	/**
	 * Returns the states the page offers for a module.
	 *
	 * Only installed modules with a valid manifest get a form. A module that
	 * is configured active and knows "hidden" goes there first.
	 *
	 * @since 1.0.0
	 *
	 * @param Module_State $state State resolver.
	 * @param string       $slug  Module slug.
	 * @return array<int, string> States in canonical order; empty without a form.
	 */
	private static function offered( Module_State $state, string $slug ): array {
		if ( null === $state->catalog()->manifest( $slug ) ) {
			return array();
		}
		$states = $state->catalog()->states( $slug );
		if ( 'active' === $state->configured( $slug ) && in_array( 'hidden', $states, true ) ) {
			$states = array_diff( $states, array( 'off' ) );
		}
		return array_values( $states );
	}

	/**
	 * Tells whether switching a module off leads to the confirmation view: it has blocks or warn_before_off.
	 *
	 * @since 1.0.0
	 *
	 * @param Module_State $state State resolver.
	 * @param string       $slug  Module slug.
	 * @return bool True for the confirmation view.
	 */
	private static function asks( Module_State $state, string $slug ): bool {
		$manifest = $state->catalog()->manifest( $slug );
		return null !== $manifest && ( array() !== $manifest->blocks || $manifest->warn_before_off );
	}

	/**
	 * Redirects to the table with the status, the module and the warning codes.
	 *
	 * @since 1.0.0
	 *
	 * @param string             $status   Status of the switch.
	 * @param string             $slug     Sanitized module slug.
	 * @param array<int, string> $warnings Warnings of the switch.
	 * @return never
	 */
	private static function finish( string $status, string $slug, array $warnings ): never {
		$codes = array();
		foreach ( $warnings as $warning ) {
			$code = str_starts_with( $warning, Switch_Result::WARNING_DEPENDENCY_MISSING ) ? 'dependency_missing' : $warning;
			if ( in_array( $code, self::WARNINGS, true ) && ! in_array( $code, $codes, true ) ) {
				$codes[] = $code;
			}
		}
		$args = array(
			'result' => $status,
			'module' => $slug,
		);
		if ( array() !== $codes ) {
			$args['warnings'] = implode( ',', $codes );
		}
		self::redirect( self::url( $args ) );
	}

	/**
	 * Redirects and ends the request.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url URL of the page.
	 * @return never
	 */
	private static function redirect( string $url ): never {
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Stops the request with 403.
	 *
	 * @since 1.0.0
	 *
	 * @return never
	 */
	private static function deny(): never {
		wp_die( esc_html__( 'You are not allowed to switch theme modules.', 'creationell-wp-theme' ), '', array( 'response' => 403 ) );
	}

	/**
	 * Prints the table of the modules, with a form per module for users who may switch.
	 *
	 * @since 1.0.0
	 *
	 * @param Module_State $state State resolver.
	 * @param bool         $can   Whether the current user may switch modules.
	 * @return void
	 */
	private static function render_table( Module_State $state, bool $can ): void {
		$catalog = $state->catalog();
		printf(
			'<table class="widefat striped"><caption>%1$s</caption><thead><tr><th scope="col">%2$s</th><th scope="col">%3$s</th><th scope="col">%4$s</th><th scope="col">%5$s</th>',
			esc_html__( 'Theme modules and their state', 'creationell-wp-theme' ),
			esc_html__( 'Module', 'creationell-wp-theme' ),
			esc_html__( 'Description', 'creationell-wp-theme' ),
			esc_html__( 'State', 'creationell-wp-theme' ),
			esc_html__( 'Reason', 'creationell-wp-theme' )
		);
		if ( $can ) {
			printf( '<th scope="col">%s</th>', esc_html__( 'Action', 'creationell-wp-theme' ) );
		}
		echo '</tr></thead><tbody>' . "\n";
		foreach ( $catalog->slugs() as $slug ) {
			printf(
				'<tr id="%1$s"><th scope="row">%2$s</th><td>%6$s</td><td>%3$s</td><td id="%4$s">%5$s</td>',
				esc_attr( self::ID . $slug ),
				esc_html( $catalog->title( $slug ) ),
				esc_html( self::state_label( $state->state( $slug ) ) ),
				esc_attr( self::ID . $slug . '-reason' ),
				esc_html( self::reason_text( $catalog, $slug, $state->reason( $slug ) ) ),
				esc_html( $catalog->description( $slug ) )
			);
			if ( $can ) {
				echo '<td>';
				self::render_form( $state, $slug );
				echo '</td>';
			}
			echo '</tr>' . "\n";
		}
		echo '</tbody></table>' . "\n";
	}

	/**
	 * Prints the form of one module: a fieldset with one radio per offered state and the save button.
	 *
	 * @since 1.0.0
	 *
	 * @param Module_State $state State resolver.
	 * @param string       $slug  Module slug.
	 * @return void
	 */
	private static function render_form( Module_State $state, string $slug ): void {
		$states = self::offered( $state, $slug );
		if ( array() === $states ) {
			return;
		}
		$locked     = $state->is_locked( $slug );
		$configured = $state->configured( $slug );
		self::render_form_start( $slug );
		printf(
			'<fieldset><legend>%s</legend>',
			/* translators: %s: title of the module. */
			esc_html( sprintf( __( 'State of %s', 'creationell-wp-theme' ), $state->catalog()->title( $slug ) ) )
		);
		foreach ( $states as $value ) {
			$id = self::ID . $slug . '-state-' . $value;
			printf(
				'<label for="%1$s"><input type="radio" id="%1$s" name="state" value="%2$s"%3$s%4$s /> %5$s</label><br />',
				esc_attr( $id ),
				esc_attr( $value ),
				$configured === $value ? ' checked' : '',
				$locked ? ' disabled aria-describedby="' . esc_attr( self::ID . $slug . '-reason' ) . '"' : '',
				esc_html( self::state_label( $value ) )
			);
		}
		printf(
			'</fieldset><button type="submit" class="button"%1$s>%2$s</button></form>',
			$locked ? ' disabled' : '',
			esc_html__( 'Save', 'creationell-wp-theme' )
		);
	}

	/**
	 * Prints the opening form tag and the hidden fields of a module.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Module slug.
	 * @return void
	 */
	private static function render_form_start( string $slug ): void {
		printf(
			'<form method="post" action="%1$s"><input type="hidden" name="action" value="%2$s" /><input type="hidden" name="module" value="%3$s" /><input type="hidden" name="_wpnonce" value="%4$s" />',
			esc_url( admin_url( 'admin-post.php' ) ),
			esc_attr( self::ACTION ),
			esc_attr( $slug ),
			esc_attr( wp_create_nonce( self::NONCE_PREFIX . $slug ) )
		);
	}

	/**
	 * Prints the confirmation view of switching a module off.
	 *
	 * @since 1.0.0
	 *
	 * @param Module_Catalog $catalog Catalog.
	 * @param string         $slug    Module slug.
	 * @param Switch_Result  $result  Dry run of the switch: needs_confirmation or dry_run.
	 * @param string         $error   Error code: confirm, changed or empty.
	 * @return void
	 */
	private static function render_confirmation( Module_Catalog $catalog, string $slug, Switch_Result $result, string $error ): void {
		$manifest = $catalog->manifest( $slug );
		$title    = $catalog->title( $slug );
		$ask      = Switch_Result::NEEDS_CONFIRMATION === $result->status;
		$scan     = $result->scan;
		$warning  = null === $manifest || null === $manifest->off_warning ? null : ( $manifest->off_warning )();

		echo '<div class="wrap">' . "\n";
		/* translators: %s: title of the module. */
		printf( '<h1>%s</h1>' . "\n", esc_html( sprintf( __( 'Switch %s off?', 'creationell-wp-theme' ), $title ) ) );
		if ( is_string( $warning ) && '' !== $warning ) {
			printf( '<p id="%1$s">%2$s</p>' . "\n", esc_attr( self::ID . 'off-warning' ), esc_html( $warning ) );
		}
		printf(
			'<p id="%1$s">%2$s</p>' . "\n",
			esc_attr( self::ID . 'consequences' ),
			esc_html(
				null === $manifest || array() === $manifest->blocks
					? __( 'Nothing is deleted; the settings of the module stay stored.', 'creationell-wp-theme' )
					: __( 'While the module is off, its blocks show nothing on the website. Nothing is deleted: the contents keep the blocks and show them again once the module is on.', 'creationell-wp-theme' )
			)
		);
		if ( null !== $scan && array() !== $scan->rows ) {
			self::render_scan( $scan, $title );
		}
		if ( in_array( Switch_Result::WARNING_BACKUP_HINT, $result->warnings, true ) ) {
			printf( '<p id="%1$s">%2$s</p>' . "\n", esc_attr( self::ID . 'backup-hint' ), esc_html( self::backup_hint() ) );
		}

		self::render_form_start( $slug );
		printf(
			'<input type="hidden" name="state" value="off" /><input type="hidden" name="step" value="confirm" /><input type="hidden" name="seen_live" value="%d" />' . "\n",
			null === $scan ? 0 : absint( $scan->total_live() )
		);
		$checkbox = self::ID . 'confirm';
		if ( $ask ) {
			if ( '' !== $error ) {
				printf(
					'<p id="%1$s" class="notice notice-error inline">%2$s</p>' . "\n",
					esc_attr( $checkbox . '-error' ),
					esc_html(
						'changed' === $error
							? __( 'More contents use the blocks of this module now. Check the list and confirm again.', 'creationell-wp-theme' )
							: __( 'Confirm that you understand the consequences.', 'creationell-wp-theme' )
					)
				);
			}
			printf(
				'<p><input type="checkbox" id="%1$s" name="confirmed" value="1"%2$s /> <label for="%1$s">%3$s</label></p>' . "\n",
				esc_attr( $checkbox ),
				'' === $error ? '' : ' aria-invalid="true" aria-describedby="' . esc_attr( $checkbox . '-error' ) . '"',
				esc_html__( 'I understand the consequences', 'creationell-wp-theme' )
			);
		}
		printf(
			'<p class="submit"><button type="submit" class="button button-primary">%1$s</button> <a class="button" href="%2$s">%3$s</a></p></form>' . "\n",
			esc_html__( 'Switch off', 'creationell-wp-theme' ),
			esc_url( self::url() ),
			esc_html__( 'Cancel', 'creationell-wp-theme' )
		);
		if ( $ask && '' !== $error ) {
			wp_print_inline_script_tag( 'document.getElementById(' . wp_json_encode( $checkbox ) . ').focus();' );
		}
		echo '</div>' . "\n";
	}

	/**
	 * Prints the contents that use the blocks of a module: counts per location and up to 20 links.
	 *
	 * @since 1.0.0
	 *
	 * @param Scan_Result $scan  Scan.
	 * @param string      $title Title of the module.
	 * @return void
	 */
	private static function render_scan( Scan_Result $scan, string $title ): void {
		printf(
			'<table class="widefat striped"><caption>%1$s</caption><thead><tr><th scope="col">%2$s</th><th scope="col">%3$s</th><th scope="col">%4$s</th></tr></thead><tbody>' . "\n",
			/* translators: %s: title of the module. */
			esc_html( sprintf( __( 'Contents with blocks of %s', 'creationell-wp-theme' ), $title ) ),
			esc_html__( 'Location', 'creationell-wp-theme' ),
			esc_html__( 'Contents', 'creationell-wp-theme' ),
			esc_html__( 'Revisions and trash', 'creationell-wp-theme' )
		);
		foreach ( $scan->rows as $row ) {
			printf(
				'<tr><th scope="row">%1$s</th><td>%2$s</td><td>%3$s</td></tr>' . "\n",
				esc_html( self::location_label( $row['location'] ) ),
				esc_html( number_format_i18n( $row['live'] ) ),
				esc_html( number_format_i18n( $row['stored'] ) )
			);
		}
		echo '</tbody></table>' . "\n";
		if ( array() === $scan->examples ) {
			return;
		}
		printf( '<p>%s</p>' . "\n", esc_html__( 'Contents that use the blocks, newest first:', 'creationell-wp-theme' ) );
		printf( '<ul id="%s">' . "\n", esc_attr( self::ID . 'examples' ) );
		foreach ( $scan->examples as $example ) {
			/* translators: %d: ID of the content. */
			$name = '' === $example['title'] ? sprintf( __( 'Content %d without title', 'creationell-wp-theme' ), $example['id'] ) : $example['title'];
			printf(
				'<li>%1$s (%2$s)</li>' . "\n",
				'' === $example['edit_url'] ? esc_html( $name ) : sprintf( '<a href="%1$s">%2$s</a>', esc_url( $example['edit_url'] ), esc_html( $name ) ),
				esc_html( self::location_label( $example['location'] ) )
			);
		}
		echo '</ul>' . "\n";
	}

	/**
	 * Prints the result of the last switch as a notice whose heading gets the focus.
	 *
	 * @since 1.0.0
	 *
	 * @param Module_State                                                                               $state State resolver.
	 * @param array{view: string, module: string, result: string, warnings: list<string>, error: string} $query Clean query arguments.
	 * @return void
	 */
	private static function render_result( Module_State $state, array $query ): void {
		$result = self::result_message( $state, $query['result'], $query['module'] );
		if ( null === $result ) {
			return;
		}
		$lines = array();
		$slug  = $query['module'];
		foreach ( $query['warnings'] as $code ) {
			if ( 'dependency_missing' === $code ) {
				if ( 'off' !== $state->configured( $slug ) && 'off' === $state->state( $slug ) ) {
					$lines[] = sprintf(
						/* translators: 1: title of the module, 2: reason, e.g. "The plugin x/x.php is not active". */
						__( '%1$s stays off: %2$s.', 'creationell-wp-theme' ),
						$state->catalog()->title( $slug ),
						self::reason_text( $state->catalog(), $slug, $state->reason( $slug ) )
					);
				}
			} elseif ( Switch_Result::WARNING_CALLBACK_FAILED === $code ) {
				$lines[] = __( 'The module reported an error while switching; the new state applies anyway. The PHP error log names the error.', 'creationell-wp-theme' );
			} else {
				$lines[] = self::backup_hint();
			}
		}
		printf(
			'<div class="notice notice-%1$s inline"><h2 id="%2$s" tabindex="-1">%3$s</h2>',
			esc_attr( $result['type'] ),
			esc_attr( self::RESULT_ID ),
			esc_html( $result['text'] )
		);
		foreach ( $lines as $line ) {
			printf( '<p>%s</p>', esc_html( $line ) );
		}
		echo '</div>' . "\n";
		wp_print_inline_script_tag( 'document.getElementById(' . wp_json_encode( self::RESULT_ID ) . ').focus();' );
	}

	/**
	 * Returns the message and the notice type of a result.
	 *
	 * @since 1.0.0
	 *
	 * @param Module_State $state  State resolver.
	 * @param string       $status Clean status.
	 * @param string       $slug   Clean module slug.
	 * @return array{type: string, text: string}|null Message; null without a known status and module.
	 */
	private static function result_message( Module_State $state, string $status, string $slug ): ?array {
		if ( Switch_Result::INVALID === $status ) {
			return array(
				'type' => 'error',
				'text' => __( 'The module or the state is unknown; nothing changed.', 'creationell-wp-theme' ),
			);
		}
		$catalog = $state->catalog();
		if ( '' === $status || ! $catalog->is_known( $slug ) ) {
			return null;
		}
		$title = $catalog->title( $slug );
		if ( Switch_Result::LOCKED === $status ) {
			return array(
				'type' => 'error',
				/* translators: %s: title of the module. */
				'text' => sprintf( __( '%s is locked; nothing changed.', 'creationell-wp-theme' ), $title ),
			);
		}
		if ( Switch_Result::UNCHANGED === $status ) {
			return array(
				'type' => 'info',
				/* translators: %s: title of the module. */
				'text' => sprintf( __( '%s already has this state; nothing changed.', 'creationell-wp-theme' ), $title ),
			);
		}
		$texts = array(
			/* translators: %s: title of the module. */
			'active' => __( '%s is now active.', 'creationell-wp-theme' ),
			/* translators: %s: title of the module. */
			'hidden' => __( '%s is now hidden.', 'creationell-wp-theme' ),
			/* translators: %s: title of the module. */
			'off'    => __( '%s is now off.', 'creationell-wp-theme' ),
		);
		return array(
			'type' => 'success',
			'text' => sprintf( $texts[ $state->configured( $slug ) ] ?? $texts['off'], $title ),
		);
	}

	/**
	 * Returns the hint that switching a module off makes no backup of the settings.
	 *
	 * @since 1.0.0
	 *
	 * @return string Hint.
	 */
	private static function backup_hint(): string {
		return __( 'No backup of the theme settings is made when a module goes off; export the settings first to keep a copy.', 'creationell-wp-theme' );
	}

	/**
	 * Returns the label of a state.
	 *
	 * @since 1.0.0
	 *
	 * @param string $state State.
	 * @return string Label.
	 */
	private static function state_label( string $state ): string {
		return match ( $state ) {
			'active' => __( 'Active', 'creationell-wp-theme' ),
			'hidden' => __( 'Hidden', 'creationell-wp-theme' ),
			default  => __( 'Off', 'creationell-wp-theme' ),
		};
	}

	/**
	 * Returns the label of a location of the content scanner.
	 *
	 * @since 1.0.0
	 *
	 * @param string $location Location, e.g. post_type:page or wp_block.
	 * @return string Label.
	 */
	private static function location_label( string $location ): string {
		if ( str_starts_with( $location, 'post_type:' ) ) {
			$type   = substr( $location, strlen( 'post_type:' ) );
			$object = get_post_type_object( $type );
			return null !== $object && isset( $object->labels->name ) && is_string( $object->labels->name ) ? $object->labels->name : $type;
		}
		return match ( $location ) {
			'wp_block'         => __( 'Synced patterns', 'creationell-wp-theme' ),
			'wp_template_part' => __( 'Template parts', 'creationell-wp-theme' ),
			'wp_template'      => __( 'Templates', 'creationell-wp-theme' ),
			'wp_navigation'    => __( 'Navigation menus', 'creationell-wp-theme' ),
			'revision'         => __( 'Revisions', 'creationell-wp-theme' ),
			'widget_block'     => __( 'Block widgets', 'creationell-wp-theme' ),
			default            => $location,
		};
	}

	/**
	 * Returns the reason or origin of a state as text.
	 *
	 * @since 1.0.0
	 *
	 * @param Module_Catalog $catalog Catalog.
	 * @param string         $slug    Module slug.
	 * @param string         $reason  Reason of Module_State::reason().
	 * @return string Text without a final period.
	 */
	private static function reason_text( Module_Catalog $catalog, string $slug, string $reason ): string {
		$detail = str_contains( $reason, ':' ) ? substr( $reason, strpos( $reason, ':' ) + 1 ) : '';
		switch ( str_contains( $reason, ':' ) ? strstr( $reason, ':', true ) : $reason ) {
			case 'default':
				return __( 'Default', 'creationell-wp-theme' );
			case 'child':
				return __( 'Set by the child theme', 'creationell-wp-theme' );
			case 'backend':
				return __( 'Set on this page', 'creationell-wp-theme' );
			case 'locked':
				return __( 'Locked by the child theme', 'creationell-wp-theme' );
			case 'constant':
				/* translators: %s: name of the constant, e.g. CREATIONELL_WP_THEME_MODULE_CONSENT. */
				return sprintf( __( 'Set by the constant %s in wp-config.php', 'creationell-wp-theme' ), Module_State::constant_name( $slug ) );
			case 'invalid_state':
				return __( 'The child theme or the constant sets a state the module does not know', 'creationell-wp-theme' );
			case 'invalid_backend':
				return __( 'The stored state is invalid; the default applies', 'creationell-wp-theme' );
			case 'not_installed':
				return __( 'Not installed', 'creationell-wp-theme' );
			case 'invalid_manifest':
				return __( 'The manifest of the module is invalid', 'creationell-wp-theme' );
			case 'missing_blockstudio':
				return __( 'Blockstudio is not active', 'creationell-wp-theme' );
			case 'dependency_cycle':
				return __( 'The required modules lead back to this module', 'creationell-wp-theme' );
			case 'legacy_plugin':
				/* translators: %s: plugin file, e.g. bs-swiper/main.php. */
				return sprintf( __( 'The older plugin %s is active', 'creationell-wp-theme' ), $detail );
			case 'missing_plugin':
				/* translators: %s: plugin file, e.g. contact-form-7/wp-contact-form-7.php. */
				return sprintf( __( 'The plugin %s is not active', 'creationell-wp-theme' ), $detail );
			case 'missing_module':
				/* translators: %s: title of the required module. */
				return sprintf( __( 'The module %s is off', 'creationell-wp-theme' ), $catalog->title( $detail ) );
			default:
				return $reason;
		}
	}
}
