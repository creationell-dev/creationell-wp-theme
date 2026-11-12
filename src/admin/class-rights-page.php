<?php
/**
 * Rights page of the theme settings.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Admin;

use Closure;
use Creationell\WpTheme\Core\Capabilities;
use Creationell\WpTheme\Core\Rights_Store;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Lets the agency choose who may use the areas of the theme settings.
 *
 * The page is a subpage of the theme menu for users with
 * creationell_wp_theme_manage_rights (administrators, never restricted
 * administrators). One row per area of "unlock" and "revoke" with two radios
 * (agency only, agency and editors) and the origin of the value; a form for
 * the restricted administrators. The child theme sets the defaults through the
 * filter creationell_wp_theme_capabilities; what it locks shows read-only.
 * Each form saves one entry through the handler of the admin-post action
 * creationell_wp_theme_rights_save, which checks the nonce of the entry,
 * stops with 403 without the capability, writes Rights_Store and redirects
 * back with codes only: result and key. Import and export never carry the
 * rights.
 *
 * @since 1.0.0
 */
final class Rights_Page {

	/**
	 * Slug of the page below the theme menu.
	 *
	 * @since 1.0.0
	 */
	public const PAGE = 'creationell-wp-theme-rights';

	/**
	 * Action of the forms, for admin-post.php.
	 *
	 * @since 1.0.0
	 */
	public const ACTION = 'creationell_wp_theme_rights_save';

	/**
	 * Prefix of the nonce action; the key of the entry follows.
	 *
	 * @since 1.0.0
	 */
	public const NONCE_PREFIX = 'creationell_wp_theme_rights_save_';

	/**
	 * Results the page reports after a redirect.
	 *
	 * @since 1.0.0
	 */
	public const RESULTS = array( 'saved', 'unchanged', 'reset', 'locked', 'invalid', 'self', 'failed' );

	/**
	 * Key of the list of restricted administrators.
	 *
	 * @since 1.0.0
	 */
	public const ADMINS = 'restricted_admins';

	/**
	 * Prefix of the element IDs of the page.
	 */
	private const ID = 'creationell-wp-theme-rights-';

	/**
	 * Source of the administrators of the site, null for get_users().
	 *
	 * @var Closure(): list<array{login: string, name: string}>|null
	 */
	private static ?Closure $administrators = null;

	/**
	 * Registers the page and the handler.
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
			__( 'Rights', 'creationell-wp-theme' ),
			__( 'Rights', 'creationell-wp-theme' ),
			Capabilities::MANAGE_RIGHTS,
			self::PAGE,
			array( self::class, 'render' )
		);
	}

	/**
	 * Replaces the source of the administrators; null goes back to get_users().
	 *
	 * @since 1.0.0
	 *
	 * @param Closure|null $source Source that returns list<array{login: string, name: string}>.
	 * @return void
	 */
	public static function set_administrators( ?Closure $source ): void {
		self::$administrators = $source;
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
	 * Puts the result of the last save in front of the document title of the page; runs on admin_title.
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
	 * Returns the document title with the result of the last save in front.
	 *
	 * @since 1.0.0
	 *
	 * @param string                $admin_title Document title.
	 * @param array<string, string> $query       Query arguments: result, key.
	 * @return string Document title.
	 */
	public static function admin_title( string $admin_title, array $query ): string {
		$result = self::result_message( $query );
		return null === $result ? $admin_title : $result[1] . ' &lsaquo; ' . $admin_title;
	}

	/**
	 * Prints the page: the result of the last save, the table of the areas and the restricted administrators.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $query Query arguments: result, key.
	 * @return void
	 */
	public static function render_page( array $query ): void {
		echo '<div class="wrap">' . "\n";
		printf( '<h1>%s</h1>' . "\n", esc_html__( 'Rights', 'creationell-wp-theme' ) );
		self::render_result( $query );
		printf( '<p>%s</p>' . "\n", esc_html__( 'Choose who may use the areas of the theme settings. The child theme sets the defaults; what it locks cannot be changed here. Import and export never change rights.', 'creationell-wp-theme' ) );
		self::render_table();
		self::render_admins();
		echo '</div>' . "\n";
	}

	/**
	 * Saves one entry from a form; runs on admin_post_creationell_wp_theme_rights_save.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function handle(): void {
		$key = isset( $_POST['key'] ) ? sanitize_key( wp_unslash( $_POST['key'] ) ) : '';
		check_admin_referer( self::NONCE_PREFIX . $key );
		if ( ! current_user_can( Capabilities::MANAGE_RIGHTS ) ) {
			wp_die( esc_html__( 'You are not allowed to change the rights.', 'creationell-wp-theme' ), '', array( 'response' => 403 ) );
		}
		if ( ! in_array( $key, self::keys(), true ) ) {
			self::finish( 'invalid', '' );
		}
		if ( Capabilities::is_locked( $key ) ) {
			self::finish( 'locked', $key );
		}
		$reset  = isset( $_POST['do'] ) && 'reset' === sanitize_key( wp_unslash( $_POST['do'] ) );
		$before = Rights_Store::read();
		if ( $reset ) {
			Rights_Store::forget( $key );
			// The default of the child theme may list the own account; the reset must not lock the user out.
			if ( self::ADMINS === $key && self::restricts_self( Capabilities::config()['restricted_admins'] ) ) {
				Rights_Store::write( $before );
				self::finish( 'self', $key );
			}
			self::finish( 'reset', $key );
		}

		$config = Capabilities::config();
		$values = $before;
		if ( self::ADMINS === $key ) {
			$posted = isset( $_POST['logins'] ) && is_array( $_POST['logins'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['logins'] ) ) : array();
			$known  = array_column( self::administrators(), 'login' );
			$wanted = array_map( 'strtolower', $posted );
			// Logins compare without case, as in Capabilities; the stored spelling is the one of the site.
			$logins = array_values( array_filter( $known, static fn( string $login ): bool => in_array( strtolower( $login ), $wanted, true ) ) );
			if ( self::restricts_self( $logins ) ) {
				self::finish( 'self', $key );
			}
			// Listed logins of users who are no administrators now stay: the form cannot show them.
			$others = array_values( array_filter( $config[ self::ADMINS ], static fn( string $login ): bool => ! in_array( strtolower( $login ), array_map( 'strtolower', $known ), true ) ) );
			$logins = array_merge( $logins, $others );
			if ( $logins === $config[ self::ADMINS ] ) {
				self::finish( 'unchanged', $key );
			}
			$values[ self::ADMINS ] = $logins;
		} else {
			$who = isset( $_POST['who'] ) ? sanitize_key( wp_unslash( $_POST['who'] ) ) : '';
			if ( ! in_array( $who, array( 'agency', 'editors' ), true ) ) {
				self::finish( 'invalid', $key );
			}
			$group  = in_array( $key, Capabilities::UNLOCKABLE, true ) ? 'unlock' : 'revoke';
			$switch = 'unlock' === $group ? 'editors' === $who : 'agency' === $who;
			if ( $switch === $config[ $group ][ $key ] ) {
				self::finish( 'unchanged', $key );
			}
			$values[ $group ][ $key ] = $switch;
		}
		self::finish( Rights_Store::write( $values ) ? 'saved' : 'failed', $key );
	}

	/**
	 * Returns the keys of the page: areas of "unlock" and "revoke" and the restricted administrators.
	 *
	 * @since 1.0.0
	 *
	 * @return list<string> Keys.
	 */
	public static function keys(): array {
		return array_merge( Capabilities::UNLOCKABLE, Capabilities::REVOCABLE, array( self::ADMINS ) );
	}

	/**
	 * Returns the title of an entry.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Key.
	 * @return string Translated title.
	 */
	public static function title( string $key ): string {
		$titles = array(
			'design'         => __( 'Design', 'creationell-wp-theme' ),
			'modules'        => __( 'Modules', 'creationell-wp-theme' ),
			'import_export'  => __( 'Import and export', 'creationell-wp-theme' ),
			'menus'          => __( 'Menus of header and footer', 'creationell-wp-theme' ),
			'consent'        => __( 'Cookies and services', 'creationell-wp-theme' ),
			'basic'          => __( 'Texts', 'creationell-wp-theme' ),
			'template_parts' => __( 'Header and footer', 'creationell-wp-theme' ),
			self::ADMINS     => __( 'Restricted administrators', 'creationell-wp-theme' ),
		);
		return $titles[ $key ] ?? $key;
	}

	/**
	 * Prints the result of the last save as a notice whose heading gets the focus.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $query Query arguments: result, key.
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
	}

	/**
	 * Returns the notice type and the message of a result.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $query Query arguments: result, key.
	 * @return array{0: string, 1: string}|null Type and message; null without a known result.
	 */
	private static function result_message( array $query ): ?array {
		$result = $query['result'] ?? '';
		$key    = $query['key'] ?? '';
		if ( ! in_array( $result, self::RESULTS, true ) ) {
			return null;
		}
		$title    = in_array( $key, self::keys(), true ) ? self::title( $key ) : '';
		$messages = array(
			/* translators: %s: title of the area. */
			'saved'     => array( 'success', sprintf( __( 'Saved: %s.', 'creationell-wp-theme' ), $title ) ),
			/* translators: %s: title of the area. */
			'unchanged' => array( 'info', sprintf( __( 'Unchanged: %s.', 'creationell-wp-theme' ), $title ) ),
			/* translators: %s: title of the area. */
			'reset'     => array( 'success', sprintf( __( 'Back to the default: %s.', 'creationell-wp-theme' ), $title ) ),
			/* translators: %s: title of the area. */
			'locked'    => array( 'error', sprintf( __( 'Not saved: the child theme locks %s.', 'creationell-wp-theme' ), $title ) ),
			'invalid'   => array( 'error', __( 'Not saved: the choice is not valid.', 'creationell-wp-theme' ) ),
			'self'      => array( 'error', __( 'Not saved: you cannot restrict your own account.', 'creationell-wp-theme' ) ),
			'failed'    => array( 'error', __( 'Not saved: the database did not store the value.', 'creationell-wp-theme' ) ),
		);
		return $messages[ $result ];
	}

	/**
	 * Prints the table of the areas with a form per area.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function render_table(): void {
		$config = Capabilities::config();
		printf(
			'<table class="widefat striped"><caption>%1$s</caption><thead><tr><th scope="col">%2$s</th><th scope="col">%3$s</th><th scope="col">%4$s</th><th scope="col">%5$s</th></tr></thead><tbody>' . "\n",
			esc_html__( 'Areas of the theme settings and who may use them', 'creationell-wp-theme' ),
			esc_html__( 'Area', 'creationell-wp-theme' ),
			esc_html__( 'Description', 'creationell-wp-theme' ),
			esc_html__( 'Who may use it', 'creationell-wp-theme' ),
			esc_html__( 'Origin', 'creationell-wp-theme' )
		);
		foreach ( array_merge( Capabilities::UNLOCKABLE, Capabilities::REVOCABLE ) as $key ) {
			$editors = in_array( $key, Capabilities::UNLOCKABLE, true ) ? $config['unlock'][ $key ] : ! $config['revoke'][ $key ];
			printf(
				'<tr id="%1$s"><th scope="row">%2$s</th><td>%3$s</td><td>',
				esc_attr( self::ID . $key ),
				esc_html( self::title( $key ) ),
				esc_html( self::description( $key ) )
			);
			self::render_form_start( $key );
			printf( '<fieldset><legend class="screen-reader-text">%s</legend>', esc_html( sprintf( /* translators: %s: title of the area. */ __( 'Who may use %s', 'creationell-wp-theme' ), self::title( $key ) ) ) );
			foreach ( array(
				'agency'  => __( 'Agency only', 'creationell-wp-theme' ),
				'editors' => __( 'Agency and editors', 'creationell-wp-theme' ),
			) as $value => $label ) {
				printf(
					'<label for="%1$s"><input type="radio" id="%1$s" name="who" value="%2$s"%3$s%4$s /> %5$s</label><br />',
					esc_attr( self::ID . $key . '-' . $value ),
					esc_attr( $value ),
					( 'editors' === $value ) === $editors ? ' checked' : '',
					Capabilities::is_locked( $key ) ? ' disabled aria-describedby="' . esc_attr( self::ID . $key . '-origin' ) . '"' : '',
					esc_html( $label )
				);
			}
			echo '</fieldset>';
			self::render_buttons( $key );
			printf( '</form></td><td id="%1$s">%2$s</td></tr>' . "\n", esc_attr( self::ID . $key . '-origin' ), esc_html( self::origin_label( $key ) ) );
		}
		echo '</tbody></table>' . "\n";
	}

	/**
	 * Prints the form of the restricted administrators.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function render_admins(): void {
		$listed = array_map( 'strtolower', Capabilities::config()['restricted_admins'] );
		$own    = strtolower( self::own_login() );
		printf( '<h2 id="%1$s">%2$s</h2>' . "\n", esc_attr( self::ID . 'admins-title' ), esc_html( self::title( self::ADMINS ) ) );
		printf( '<p>%s</p>' . "\n", esc_html__( 'Administrators marked here cannot use the areas of the agency (design, modules, import and export, menus, cookies and services) unless they are open to editors; they never see this page and never switch the Bootstrap line. They keep the other rights of an administrator in WordPress; the restriction holds firmly only when the child theme locks it.', 'creationell-wp-theme' ) );
		self::render_form_start( self::ADMINS );
		printf( '<fieldset aria-describedby="%1$s"><legend>%2$s</legend>', esc_attr( self::ID . self::ADMINS . '-origin' ), esc_html( self::title( self::ADMINS ) ) );
		foreach ( self::administrators() as $index => $admin ) {
			$self = strtolower( $admin['login'] ) === $own;
			printf(
				'<label for="%1$s"><input type="checkbox" id="%1$s" name="logins[]" value="%2$s"%3$s%4$s /> %5$s</label><br />',
				esc_attr( self::ID . 'admin-' . $index ),
				esc_attr( $admin['login'] ),
				in_array( strtolower( $admin['login'] ), $listed, true ) ? ' checked' : '',
				$self || Capabilities::is_locked( self::ADMINS ) ? ' disabled' : '',
				esc_html(
					$self
						/* translators: 1: display name, 2: user login. */
						? sprintf( __( '%1$s (%2$s, your account cannot be restricted)', 'creationell-wp-theme' ), $admin['name'], $admin['login'] )
						/* translators: 1: display name, 2: user login. */
						: sprintf( __( '%1$s (%2$s)', 'creationell-wp-theme' ), $admin['name'], $admin['login'] )
				)
			);
		}
		echo '</fieldset>';
		printf( '<p id="%1$s">%2$s</p>', esc_attr( self::ID . self::ADMINS . '-origin' ), esc_html( self::origin_label( self::ADMINS ) ) );
		self::render_buttons( self::ADMINS );
		echo '</form>' . "\n";
	}

	/**
	 * Prints the opening form tag and the hidden fields of an entry.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Key.
	 * @return void
	 */
	private static function render_form_start( string $key ): void {
		printf(
			'<form method="post" action="%1$s"><input type="hidden" name="action" value="%2$s" /><input type="hidden" name="key" value="%3$s" />',
			esc_url( admin_url( 'admin-post.php' ) ),
			esc_attr( self::ACTION ),
			esc_attr( $key )
		);
		// A field of its own: the nonce field of WordPress prints id="_wpnonce", once per form of the page.
		printf( '<input type="hidden" name="_wpnonce" value="%s" />', esc_attr( wp_create_nonce( self::NONCE_PREFIX . $key ) ) );
	}

	/**
	 * Prints the save button and, for an entry set on this page, the reset button.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Key.
	 * @return void
	 */
	private static function render_buttons( string $key ): void {
		printf( '<button type="submit" class="button" name="do" value="save"%1$s>%2$s</button>', Capabilities::is_locked( $key ) ? ' disabled' : '', esc_html__( 'Save', 'creationell-wp-theme' ) );
		if ( 'backend' === Capabilities::origin( $key ) ) {
			printf( ' <button type="submit" class="button-link" name="do" value="reset">%s</button>', esc_html__( 'Back to the default', 'creationell-wp-theme' ) );
		}
	}

	/**
	 * Returns the description of an area.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Area.
	 * @return string Translated description.
	 */
	private static function description( string $key ): string {
		$descriptions = array(
			'design'         => __( 'Colors and fonts.', 'creationell-wp-theme' ),
			'modules'        => __( 'Switching theme modules on and off.', 'creationell-wp-theme' ),
			'import_export'  => __( 'Exporting and importing the theme settings.', 'creationell-wp-theme' ),
			'menus'          => __( 'The menus assigned to the header and the footer.', 'creationell-wp-theme' ),
			'consent'        => __( 'Consent services and their cookies in the cookie banner.', 'creationell-wp-theme' ),
			'basic'          => __( 'Texts such as the footer, the contact details and the cookie banner.', 'creationell-wp-theme' ),
			'template_parts' => __( 'Header and footer in the Site Editor.', 'creationell-wp-theme' ),
		);
		return $descriptions[ $key ] ?? '';
	}

	/**
	 * Returns the origin of an entry as text.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Key.
	 * @return string Translated origin.
	 */
	private static function origin_label( string $key ): string {
		$labels = array(
			'theme'   => __( 'Default of the theme', 'creationell-wp-theme' ),
			'child'   => __( 'Default of the child theme', 'creationell-wp-theme' ),
			'backend' => __( 'Set on this page', 'creationell-wp-theme' ),
			'locked'  => __( 'Locked by the child theme', 'creationell-wp-theme' ),
		);
		return $labels[ Capabilities::origin( $key ) ];
	}

	/**
	 * Returns the administrators of the site, ordered by login.
	 *
	 * @since 1.0.0
	 *
	 * @return list<array{login: string, name: string}> Administrators.
	 */
	private static function administrators(): array {
		if ( null !== self::$administrators ) {
			return ( self::$administrators )();
		}
		$admins = array();
		foreach ( get_users(
			array(
				'role'    => 'administrator',
				'orderby' => 'login',
			)
		) as $user ) {
			$admins[] = array(
				'login' => (string) $user->user_login,
				'name'  => (string) $user->display_name,
			);
		}
		return $admins;
	}

	/**
	 * Reads the codes of the redirect from the request.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Result and key.
	 */
	private static function query(): array {
		$query = array();
		foreach ( array( 'result', 'key' ) as $key ) {
			$value = filter_input( INPUT_GET, $key );
			if ( is_string( $value ) ) {
				$query[ $key ] = sanitize_key( $value );
			}
		}
		return $query;
	}

	/**
	 * Tells whether a list of restricted administrators holds the current user; logins compare without case.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $logins Logins.
	 * @return bool True when the current user is listed.
	 */
	private static function restricts_self( array $logins ): bool {
		$own = self::own_login();
		return '' !== $own && in_array( strtolower( $own ), array_map( 'strtolower', $logins ), true );
	}

	/**
	 * Returns the login of the current user.
	 *
	 * @since 1.0.0
	 *
	 * @return string Login, empty when nobody is logged in.
	 */
	private static function own_login(): string {
		$user = get_userdata( get_current_user_id() );
		return false === $user ? '' : (string) $user->user_login;
	}

	/**
	 * Redirects back to the page with the result.
	 *
	 * @since 1.0.0
	 *
	 * @param string $result Result code.
	 * @param string $key    Key of the entry, empty when invalid.
	 * @return never
	 */
	private static function finish( string $result, string $key ): never {
		wp_safe_redirect(
			self::url(
				array_filter(
					array(
						'result' => $result,
						'key'    => $key,
					)
				)
			)
		);
		exit;
	}
}
