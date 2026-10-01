<?php
/**
 * Admin page "Import/Export" of the theme settings.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Admin;

use Closure;
use Creationell\WpTheme\Core\Capabilities;
use Creationell\WpTheme\Modules\Module_Catalog;
use Creationell\WpTheme\Settings\Language;
use Creationell\WpTheme\Settings\Registry;
use Creationell\WpTheme\Settings\Transfer\Backup_Store;
use Creationell\WpTheme\Settings\Transfer\Export_Request;
use Creationell\WpTheme\Settings\Transfer\Exporter;
use Creationell\WpTheme\Settings\Transfer\Import_Options;
use Creationell\WpTheme\Settings\Transfer\Import_Plan;
use Creationell\WpTheme\Settings\Transfer\Import_Report;
use Creationell\WpTheme\Settings\Transfer\Import_Session;
use Creationell\WpTheme\Settings\Transfer\Importer;
use Creationell\WpTheme\Settings\Transfer\Json_Codec;
use Creationell\WpTheme\Settings\Transfer\Plan_Row;
use Creationell\WpTheme\Settings\Transfer\Row_Status;
use Creationell\WpTheme\Settings\Transfer\Sections;
use Creationell\WpTheme\Settings\Transfer\Transfer_Error;
use Creationell\WpTheme\Settings\Transfer\Transfer_Exception;
use Creationell\WpTheme\Settings\Transfer\Transfer_File;
use Creationell\WpTheme\Settings\Transfer\Transfer_Log;
use Creationell\WpTheme\Settings\Transfer\Upload_Validator;
use Exception;
use InvalidArgumentException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Subpage "Import/Export" below the theme settings: export, import with preview, backups and log.
 *
 * The page needs creationell_wp_theme_view_advanced. Without
 * creationell_wp_theme_import_export it only lists the backups. Every form
 * posts to admin-post.php; each handler checks its own nonce (named like its
 * action) and the capability creationell_wp_theme_import_export and ends with
 * 403 without it. Handlers redirect back to the page and pass only codes in
 * the URL: "error=<code of Transfer_Error>", "message=<code>", "view" and
 * "token".
 *
 * Flow of an import: the upload is checked (Upload_Validator, then decoded,
 * migrated and checked like every file) and kept in an import session bound
 * to the user; the preview (GET) plans the import and shows every row; the
 * apply handler imports after a fresh plan, keeps the report for five minutes
 * and redirects to the result (Post/Redirect/Get). A restore puts a backup
 * into a session and takes the same way, so it is backed up first and can be
 * undone as well.
 *
 * @since 1.0.0
 */
final class Transfer_Page {

	/**
	 * Slug of the page, below the main menu of the theme.
	 *
	 * @since 1.0.0
	 */
	public const SLUG = 'creationell-wp-theme-import-export';

	/**
	 * Priority on admin_menu, after the main menu (9) and the settings pages.
	 *
	 * @since 1.0.0
	 */
	public const PRIORITY = 100;

	/**
	 * Handle of the stylesheet of the page.
	 *
	 * @since 1.0.0
	 */
	public const STYLE_HANDLE = 'creationell-wp-theme-admin-transfer';

	/**
	 * Action and nonce of the export.
	 *
	 * @since 1.0.0
	 */
	public const ACTION_EXPORT = 'creationell_wp_theme_export';

	/**
	 * Action and nonce of the upload.
	 *
	 * @since 1.0.0
	 */
	public const ACTION_UPLOAD = 'creationell_wp_theme_import_upload';

	/**
	 * Action and nonce of the import after the preview.
	 *
	 * @since 1.0.0
	 */
	public const ACTION_APPLY = 'creationell_wp_theme_import_apply';

	/**
	 * Action and nonce of cancelling the preview.
	 *
	 * @since 1.0.0
	 */
	public const ACTION_CANCEL = 'creationell_wp_theme_import_cancel';

	/**
	 * Action and nonce of restoring a backup (leads to the preview).
	 *
	 * @since 1.0.0
	 */
	public const ACTION_RESTORE = 'creationell_wp_theme_backup_restore';

	/**
	 * Action and nonce of "Back up now".
	 *
	 * @since 1.0.0
	 */
	public const ACTION_BACKUP = 'creationell_wp_theme_backup_create';

	/**
	 * Prefix of the transient with the report of the last import of a user; the user ID follows.
	 *
	 * @since 1.0.0
	 */
	public const RESULT_PREFIX = 'creationell_wp_theme_import_result_';

	/**
	 * Lifetime of the stored report in seconds.
	 *
	 * @since 1.0.0
	 */
	public const RESULT_TTL = 300;

	/**
	 * Number of log entries on the page.
	 *
	 * @since 1.0.0
	 */
	public const LOG_ROWS = 10;

	/**
	 * Codes of the notices after a successful action.
	 *
	 * @since 1.0.0
	 */
	public const MESSAGES = array( 'backup_created', 'import_cancelled' );

	/**
	 * Errors that belong to the file field.
	 *
	 * @var list<Transfer_Error>
	 */
	private const FILE_ERRORS = array(
		Transfer_Error::UPLOAD_FAILED,
		Transfer_Error::TOO_LARGE,
		Transfer_Error::WRONG_EXTENSION,
		Transfer_Error::INVALID_JSON,
		Transfer_Error::WRONG_FORMAT,
		Transfer_Error::SCHEMA_INVALID,
		Transfer_Error::SCHEMA_NEWER,
		Transfer_Error::NO_MIGRATION,
	);

	/**
	 * Errors of the import session; the apply handler sends them to the start page.
	 *
	 * @var list<Transfer_Error>
	 */
	private const TOKEN_ERRORS = array( Transfer_Error::TOKEN_INVALID, Transfer_Error::TOKEN_EXPIRED, Transfer_Error::TOKEN_USER_MISMATCH );

	/**
	 * Prefix of the IDs and classes of the page.
	 *
	 * @var string
	 */
	private const ID = 'creationell-wp-theme-transfer';

	/**
	 * ID of the heading of the result, which takes the focus.
	 *
	 * @var string
	 */
	private const RESULT_ID = self::ID . '-result';

	/**
	 * ID of the checkbox that confirms the module switches.
	 *
	 * @var string
	 */
	private const CONFIRM_ID = self::ID . '-confirm';

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Query arguments of the request that page() prints.
	 *
	 * @var array<string, mixed>
	 */
	private array $query = array();

	/**
	 * Hook suffix of the page, set by add_page().
	 *
	 * @var string
	 */
	private static string $hook = '';

	/**
	 * Exporter; null for the shared one.
	 *
	 * @var Exporter|null
	 */
	private ?Exporter $exporter;

	/**
	 * Importer; null for one over the sections of the theme.
	 *
	 * @var Importer|null
	 */
	private ?Importer $importer;

	/**
	 * Backup store; null for the shared one.
	 *
	 * @var Backup_Store|null
	 */
	private ?Backup_Store $store;

	/**
	 * Log; null for the shared one.
	 *
	 * @var Transfer_Log|null
	 */
	private ?Transfer_Log $log;

	/**
	 * Checks uploads.
	 *
	 * @var Upload_Validator
	 */
	private Upload_Validator $validator;

	/**
	 * Sends a download: headers by name and the file as body.
	 *
	 * @var Closure
	 * @phpstan-var Closure(array<string, string>, Transfer_File): void
	 */
	private Closure $send;

	/**
	 * Ends the request after a redirect or a download.
	 *
	 * @var Closure
	 * @phpstan-var Closure(): never
	 */
	private Closure $halt;

	/**
	 * Returns the current Unix time.
	 *
	 * @var Closure
	 * @phpstan-var Closure(): int
	 */
	private Closure $clock;

	/**
	 * Takes the transfer services and the ways to answer the request.
	 *
	 * @since 1.0.0
	 *
	 * @param Exporter|null         $exporter  Exporter; without one the shared exporter.
	 * @param Importer|null         $importer  Importer; without one an importer over the sections of the theme.
	 * @param Backup_Store|null     $store     Backup store; without one the shared store.
	 * @param Transfer_Log|null     $log       Log; without one the shared log.
	 * @param Upload_Validator|null $validator Upload check; without one the check of real uploads.
	 * @param Closure|null          $send      Sends the headers and the file of a download; without one header() and echo.
	 * @param Closure|null          $halt      Ends the request; without one exit.
	 * @param Closure|null          $clock     Returns the current Unix time; without one time().
	 * @phpstan-param (Closure(array<string, string>, Transfer_File): void)|null $send
	 * @phpstan-param (Closure(): never)|null $halt
	 * @phpstan-param (Closure(): int)|null $clock
	 */
	public function __construct(
		?Exporter $exporter = null,
		?Importer $importer = null,
		?Backup_Store $store = null,
		?Transfer_Log $log = null,
		?Upload_Validator $validator = null,
		?Closure $send = null,
		?Closure $halt = null,
		?Closure $clock = null
	) {
		$this->exporter  = $exporter;
		$this->importer  = $importer;
		$this->store     = $store;
		$this->log       = $log;
		$this->validator = $validator ?? new Upload_Validator();
		$this->send      = $send ?? self::send_download( ... );
		$this->halt      = $halt ?? static function (): never {
			exit;
		};
		$this->clock     = $clock ?? static fn(): int => time();
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
	 * @param self|null $page Instance.
	 * @return void
	 */
	public static function set_instance( ?self $page ): void {
		self::$instance = $page;
		self::$hook     = '';
	}

	/**
	 * Registers the hooks of the page; runs from Theme::boot().
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_action( 'admin_menu', array( self::class, 'add_page' ), self::PRIORITY, 0 );
		add_action( 'admin_post_' . self::ACTION_EXPORT, array( self::class, 'handle_export' ), 10, 0 );
		add_action( 'admin_post_' . self::ACTION_UPLOAD, array( self::class, 'handle_upload' ), 10, 0 );
		add_action( 'admin_post_' . self::ACTION_APPLY, array( self::class, 'handle_apply' ), 10, 0 );
		add_action( 'admin_post_' . self::ACTION_CANCEL, array( self::class, 'handle_cancel' ), 10, 0 );
		add_action( 'admin_post_' . self::ACTION_RESTORE, array( self::class, 'handle_restore' ), 10, 0 );
		add_action( 'admin_post_' . self::ACTION_BACKUP, array( self::class, 'handle_backup' ), 10, 0 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ), 10, 1 );
		add_filter( 'admin_title', array( self::class, 'admin_title' ), 10, 2 );
	}

	/**
	 * Adds the subpage below the main menu of the theme; runs on admin_menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function add_page(): void {
		$hook       = add_submenu_page(
			Menu::SLUG,
			__( 'Import/Export', 'creationell-wp-theme' ),
			__( 'Import/Export', 'creationell-wp-theme' ),
			Capabilities::VIEW_ADVANCED,
			self::SLUG,
			array( self::class, 'render' )
		);
		self::$hook = is_string( $hook ) ? $hook : '';
	}

	/**
	 * Loads the stylesheet on the page only; runs on admin_enqueue_scripts.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $hook_suffix Hook suffix of the admin screen.
	 * @return void
	 */
	public static function enqueue( mixed $hook_suffix ): void {
		if ( '' === self::$hook || self::$hook !== $hook_suffix ) {
			return;
		}
		wp_enqueue_style( self::STYLE_HANDLE, get_template_directory_uri() . '/assets/css/admin-transfer.css', array(), CREATIONELL_WP_THEME_VERSION );
	}

	/**
	 * Puts the result of an import in front of the document title of the result view.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $admin_title Document title.
	 * @param mixed $title       Page title.
	 * @return string Document title.
	 */
	public static function admin_title( mixed $admin_title, mixed $title ): string {
		unset( $title );
		return self::title_for( is_string( $admin_title ) ? $admin_title : '', self::request_query() );
	}

	/**
	 * Returns the document title for the query of a request to the page.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $admin_title Document title.
	 * @param array<string, mixed> $query       Query arguments of the request.
	 * @return string Document title, with the result of the import in front on the result view.
	 */
	public static function title_for( string $admin_title, array $query ): string {
		if ( self::SLUG !== ( $GLOBALS['plugin_page'] ?? null ) || 'result' !== self::arg( $query, 'view' ) ) {
			return $admin_title;
		}
		$report = self::stored_report( get_current_user_id() );
		if ( null === $report ) {
			return $admin_title;
		}
		/* translators: 1: result of the import, 2: title of the admin page. */
		return sprintf( __( '%1$s – %2$s', 'creationell-wp-theme' ), self::result_heading( $report ), $admin_title );
	}

	/**
	 * Prints the page; callback of add_submenu_page().
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function render(): void {
		self::instance()->page( self::request_query() );
	}

	/**
	 * Sends the export as download; admin-post handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function handle_export(): void {
		self::instance()->export();
	}

	/**
	 * Checks an upload and opens its preview; admin-post handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function handle_upload(): void {
		self::instance()->upload();
	}

	/**
	 * Imports the file of the preview; admin-post handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function handle_apply(): void {
		self::instance()->apply();
	}

	/**
	 * Cancels the preview; admin-post handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function handle_cancel(): void {
		self::instance()->cancel();
	}

	/**
	 * Opens the preview of a backup; admin-post handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function handle_restore(): void {
		self::instance()->restore();
	}

	/**
	 * Backs up every section; admin-post handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function handle_backup(): void {
		self::instance()->backup();
	}

	/**
	 * Exports the posted sections and languages and sends the file.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function export(): void {
		check_admin_referer( self::ACTION_EXPORT );
		self::require_capability();
		$post     = (array) wp_unslash( $_POST );
		$sections = array_values( array_intersect( self::arg_list( $post, 'sections' ), Sections::ids() ) );
		$langs    = array_values( array_intersect( self::arg_list( $post, 'langs' ), self::languages() ) );
		try {
			$file = $this->exporter()->export( new Export_Request( $sections, $langs, get_current_user_id(), 'admin' ) );
			$json = Json_Codec::encode( $file->to_array() );
		} catch ( Transfer_Exception $exception ) {
			$this->redirect( array( 'error' => $exception->error->value ) );
		} catch ( Exception $exception ) {
			self::fail( $exception );
		}
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		$name = Transfer_File::file_name( is_string( $host ) ? $host : '', ( $this->clock )() );
		( $this->send )(
			array(
				'Content-Type'           => 'application/json; charset=utf-8',
				'Content-Disposition'    => 'attachment; filename="' . $name . '"',
				'Content-Length'         => (string) strlen( $json ),
				'X-Content-Type-Options' => 'nosniff',
				'Cache-Control'          => 'no-store',
			),
			$file
		);
		( $this->halt )();
	}

	/**
	 * Checks the uploaded file, keeps it in an import session and opens the preview.
	 *
	 * The file is decoded, migrated and checked here already, so a broken file
	 * is reported at the file field and never reaches the preview.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function upload(): void {
		check_admin_referer( self::ACTION_UPLOAD );
		self::require_capability();
		// Upload_Validator checks every field of the entry; $_FILES is not slashed.
		$files = $_FILES;
		$file  = is_array( $files[ Upload_Validator::FIELD ] ?? null ) ? $files[ Upload_Validator::FIELD ] : array();
		try {
			$json = $this->validator->read( $file );
			Importer::parse( $json );
		} catch ( Transfer_Exception $exception ) {
			$this->redirect( array( 'error' => $exception->error->value ) );
		}
		$name  = sanitize_text_field( is_string( $file['name'] ?? null ) ? $file['name'] : '' );
		$token = Import_Session::store( $json, $name, 'file', get_current_user_id() );
		$this->redirect(
			array(
				'view'  => 'preview',
				'token' => $token,
			)
		);
	}

	/**
	 * Imports the file of the session after a fresh plan and redirects to the result.
	 *
	 * A missing or too small confirmation of the module switches leads back to
	 * the preview; errors of the session lead to the start page. Other errors
	 * of the transfer lead back to the preview, which names them.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function apply(): void {
		check_admin_referer( self::ACTION_APPLY );
		self::require_capability();
		$post      = (array) wp_unslash( $_POST );
		$user_id   = get_current_user_id();
		$token     = self::arg( $post, 'token' );
		$confirmed = '1' === self::arg( $post, 'confirm_modules' );
		try {
			$session  = Import_Session::load( $token, $user_id );
			$parsed   = Importer::parse( $session['data'] );
			$options  = new Import_Options(
				sections: $parsed['file']->sections,
				langs: null,
				user_id: $user_id,
				channel: 'admin',
				confirm_modules: $confirmed,
				seen_live: self::arg_counts( $post, 'seen_live' ),
				reason: 'backup' === $session['source'] ? 'restore' : 'import',
			);
			$importer = $this->importer();
			$report   = $importer->apply( $importer->plan( $parsed['file'], $options, $parsed['migrated'] ), $options );
		} catch ( Transfer_Exception $exception ) {
			if ( in_array( $exception->error, self::TOKEN_ERRORS, true ) ) {
				$this->redirect( array( 'error' => $exception->error->value ) );
			}
			$this->redirect(
				array(
					'view'  => 'preview',
					'token' => $token,
					'error' => $exception->error->value,
				)
			);
		} catch ( Exception $exception ) {
			self::fail( $exception );
		}
		Import_Session::delete( $token );
		set_transient( self::RESULT_PREFIX . $user_id, $report->to_array(), self::RESULT_TTL );
		$this->redirect( array( 'view' => 'result' ) );
	}

	/**
	 * Removes the session of the preview of the current user and goes back to the start.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function cancel(): void {
		check_admin_referer( self::ACTION_CANCEL );
		self::require_capability();
		$token = self::arg( (array) wp_unslash( $_POST ), 'token' );
		if ( self::owns_session( $token, get_current_user_id() ) ) {
			Import_Session::delete( $token );
		}
		$this->redirect( array( 'message' => 'import_cancelled' ) );
	}

	/**
	 * Tells whether a token names a live session of the user.
	 *
	 * @since 1.0.0
	 *
	 * @param string $token   Token.
	 * @param int    $user_id User ID.
	 * @return bool True for a session the user started; false for a malformed, expired or foreign one.
	 */
	private static function owns_session( string $token, int $user_id ): bool {
		try {
			Import_Session::load( $token, $user_id );
		} catch ( Transfer_Exception ) {
			return false;
		}
		return true;
	}

	/**
	 * Puts the posted backup into an import session and opens its preview.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function restore(): void {
		check_admin_referer( self::ACTION_RESTORE );
		self::require_capability();
		$id = self::arg( (array) wp_unslash( $_POST ), 'backup_id' );
		try {
			$json = $this->store()->data( $id );
		} catch ( Transfer_Exception $exception ) {
			$this->redirect( array( 'error' => $exception->error->value ) );
		}
		$token = Import_Session::store( $json, $id, 'backup', get_current_user_id() );
		$this->redirect(
			array(
				'view'  => 'preview',
				'token' => $token,
			)
		);
	}

	/**
	 * Backs up every section with the reason "manual".
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function backup(): void {
		check_admin_referer( self::ACTION_BACKUP );
		self::require_capability();
		try {
			$this->store()->create( Sections::ids(), 'manual', get_current_user_id(), 'admin' );
		} catch ( Transfer_Exception $exception ) {
			$this->redirect( array( 'error' => $exception->error->value ) );
		} catch ( Exception $exception ) {
			self::fail( $exception );
		}
		$this->redirect( array( 'message' => 'backup_created' ) );
	}

	/**
	 * Prints the view the query asks for.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $query Query arguments of the request: view, token, error, message.
	 * @return void
	 */
	public function page( array $query ): void {
		if ( ! current_user_can( Capabilities::VIEW_ADVANCED ) ) {
			wp_die( esc_html__( 'You are not allowed to open this page.', 'creationell-wp-theme' ), '', array( 'response' => 403 ) );
		}
		$this->query = $query;
		$can_act     = current_user_can( Capabilities::IMPORT_EXPORT );
		$error       = Transfer_Error::tryFrom( self::arg( $query, 'error' ) );
		$view        = self::arg( $query, 'view' );

		echo '<div class="wrap ' . esc_attr( self::ID ) . '">' . "\n";
		echo '<h1>' . esc_html__( 'Import/Export', 'creationell-wp-theme' ) . "</h1>\n";
		echo '<hr class="wp-header-end">' . "\n";
		if ( $can_act && 'preview' === $view ) {
			$this->render_preview( get_current_user_id(), $error );
		} elseif ( $can_act && 'result' === $view ) {
			$this->render_result( get_current_user_id() );
		} else {
			$this->render_start( $can_act, $error );
		}
		echo "</div>\n";
	}

	/**
	 * Prints the start view: export, import, backups and log, or the backups only.
	 *
	 * @since 1.0.0
	 *
	 * @param bool                $can_act Whether the user may import and export.
	 * @param Transfer_Error|null $error   Error to show, or null.
	 * @return void
	 */
	private function render_start( bool $can_act, ?Transfer_Error $error ): void {
		if ( ! $can_act ) {
			echo '<p class="' . esc_attr( self::ID . '-read-only' ) . '">' . esc_html__( 'You can only view import and export.', 'creationell-wp-theme' ) . "</p>\n";
			$this->render_backups( false );
			return;
		}
		if ( null !== $error ) {
			self::error_notice( $error );
		}
		$message = self::arg( $this->query, 'message' );
		if ( 'backup_created' === $message ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Backup created.', 'creationell-wp-theme' ) . "</p></div>\n";
		} elseif ( 'import_cancelled' === $message ) {
			echo '<div class="notice notice-info"><p>' . esc_html__( 'Import cancelled. Nothing was changed.', 'creationell-wp-theme' ) . "</p></div>\n";
		}
		self::render_export( $error );
		self::render_import( $error );
		$this->render_backups( true );
		$this->render_log();
	}

	/**
	 * Prints the export form.
	 *
	 * @since 1.0.0
	 *
	 * @param Transfer_Error|null $error Error to show, or null.
	 * @return void
	 */
	private static function render_export( ?Transfer_Error $error ): void {
		$invalid  = Transfer_Error::NOTHING_SELECTED === $error;
		$error_id = self::ID . '-sections-error';
		echo '<h2>' . esc_html__( 'Export', 'creationell-wp-theme' ) . "</h2>\n";
		echo '<p>' . esc_html__( 'Downloads the settings as a JSON file. Sensitive values are never exported.', 'creationell-wp-theme' ) . "</p>\n";
		self::form_open( self::ACTION_EXPORT );
		echo '<fieldset class="' . esc_attr( self::ID . '-choices' ) . '"' . ( $invalid ? ' aria-describedby="' . esc_attr( $error_id ) . '"' : '' ) . '>';
		echo '<legend>' . esc_html__( 'Sections', 'creationell-wp-theme' ) . "</legend>\n";
		if ( $invalid ) {
			self::field_error( $error_id, $error );
		}
		foreach ( Sections::all() as $id => $section ) {
			self::checkbox( 'sections[]', $id, self::ID . '-section-' . $id, $section->label(), $invalid );
		}
		echo "</fieldset>\n";
		echo '<fieldset class="' . esc_attr( self::ID . '-choices' ) . '">';
		echo '<legend>' . esc_html__( 'Languages', 'creationell-wp-theme' ) . "</legend>\n";
		$default = Language::instance()->default();
		foreach ( self::languages() as $lang ) {
			/* translators: %s: language code, e.g. "de". */
			$label = $lang === $default ? sprintf( __( '%s (default language)', 'creationell-wp-theme' ), $lang ) : $lang;
			self::checkbox( 'langs[]', $lang, self::ID . '-lang-' . $lang, $label, false );
		}
		echo "</fieldset>\n";
		echo '<p><button type="submit" class="button button-primary">' . esc_html__( 'Download file', 'creationell-wp-theme' ) . "</button></p>\n";
		echo "</form>\n";
	}

	/**
	 * Prints the upload form.
	 *
	 * @since 1.0.0
	 *
	 * @param Transfer_Error|null $error Error to show, or null.
	 * @return void
	 */
	private static function render_import( ?Transfer_Error $error ): void {
		$invalid     = null !== $error && in_array( $error, self::FILE_ERRORS, true );
		$hint_id     = self::ID . '-file-hint';
		$error_id    = self::ID . '-file-error';
		$describedby = $hint_id . ( $invalid ? ' ' . $error_id : '' );
		echo '<h2>' . esc_html__( 'Import', 'creationell-wp-theme' ) . "</h2>\n";
		echo '<p>' . esc_html__( 'The import shows a preview first; nothing changes before you confirm it. The current settings are backed up before the import.', 'creationell-wp-theme' ) . "</p>\n";
		self::form_open( self::ACTION_UPLOAD, true );
		printf(
			'<p><label for="%1$s">%2$s</label><br>' . "\n" . '<input type="file" id="%1$s" name="%1$s" accept=".json,application/json" required aria-describedby="%3$s"%4$s></p>' . "\n",
			esc_attr( Upload_Validator::FIELD ),
			esc_html__( 'Settings file', 'creationell-wp-theme' ),
			esc_attr( $describedby ),
			$invalid ? ' aria-invalid="true"' : ''
		);
		echo '<p id="' . esc_attr( $hint_id ) . '" class="description">' . esc_html__( 'JSON, at most 1 MB', 'creationell-wp-theme' ) . "</p>\n";
		if ( $invalid ) {
			self::field_error( $error_id, $error );
		}
		echo '<p><button type="submit" class="button">' . esc_html__( 'Show preview', 'creationell-wp-theme' ) . "</button></p>\n";
		echo "</form>\n";
	}

	/**
	 * Prints the backups, with the restore buttons and "Back up now" for users who may import.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $can_act Whether the user may import and export.
	 * @return void
	 */
	private function render_backups( bool $can_act ): void {
		echo '<h2>' . esc_html__( 'Backups', 'creationell-wp-theme' ) . "</h2>\n";
		echo '<p>' . esc_html(
			sprintf(
				/* translators: %d: number of backups the theme keeps. */
				__( 'The theme keeps the last %d backups. It makes one before every import, restore and module switch.', 'creationell-wp-theme' ),
				Backup_Store::MAX_ITEMS
			)
		) . "</p>\n";
		$backups = $this->store()->all();
		if ( array() === $backups ) {
			echo '<p>' . esc_html__( 'No backups yet.', 'creationell-wp-theme' ) . "</p>\n";
		} else {
			$headers = array(
				__( 'Time', 'creationell-wp-theme' ),
				__( 'Reason', 'creationell-wp-theme' ),
				__( 'Sections', 'creationell-wp-theme' ),
				__( 'Person', 'creationell-wp-theme' ),
			);
			if ( $can_act ) {
				$headers[] = __( 'Action', 'creationell-wp-theme' );
			}
			self::table_open( 'backups', __( 'Backups, newest first', 'creationell-wp-theme' ), $headers );
			foreach ( $backups as $backup ) {
				$row_id = self::ID . '-backup-' . $backup['id'];
				$time   = strtotime( $backup['created_at'] );
				echo '<tr><th scope="row" id="' . esc_attr( $row_id ) . '">';
				self::print_time( false === $time ? 0 : $time );
				echo '</th>';
				echo '<td>' . esc_html( self::reason_label( $backup['reason'] ) ) . '</td>';
				echo '<td>' . esc_html( self::section_labels( $backup['sections'] ) ) . '</td>';
				echo '<td>' . esc_html( self::person( $backup['user_id'] ) ) . '</td>';
				if ( $can_act ) {
					echo '<td>';
					self::restore_form( $backup['id'], __( 'Restore …', 'creationell-wp-theme' ), $row_id );
					echo '</td>';
				}
				echo "</tr>\n";
			}
			self::table_close();
		}
		if ( $can_act ) {
			self::form_open( self::ACTION_BACKUP );
			echo '<p><button type="submit" class="button">' . esc_html__( 'Back up now', 'creationell-wp-theme' ) . "</button></p>\n";
			echo "</form>\n";
		}
	}

	/**
	 * Prints the last entries of the log as a part of the backups area.
	 *
	 * Spec 5.8 names three h2 areas and puts the last log entries below the
	 * backups, so the log has an h3 right after them.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function render_log(): void {
		echo '<h3>' . esc_html__( 'Log', 'creationell-wp-theme' ) . "</h3>\n";
		echo '<p>' . esc_html__( 'The log keeps who exported, imported, restored or backed up which sections, for 90 days. It stores no values.', 'creationell-wp-theme' ) . "</p>\n";
		$entries = array_slice( ( $this->log ?? Transfer_Log::instance() )->all(), 0, self::LOG_ROWS );
		if ( array() === $entries ) {
			echo '<p>' . esc_html__( 'No log entries yet.', 'creationell-wp-theme' ) . "</p>\n";
			return;
		}
		self::table_open(
			'log',
			/* translators: %d: number of entries. */
			sprintf( __( 'Last %d log entries', 'creationell-wp-theme' ), self::LOG_ROWS ),
			array(
				__( 'Time', 'creationell-wp-theme' ),
				__( 'Action', 'creationell-wp-theme' ),
				__( 'Channel', 'creationell-wp-theme' ),
				__( 'Sections', 'creationell-wp-theme' ),
				__( 'Languages', 'creationell-wp-theme' ),
				__( 'Person', 'creationell-wp-theme' ),
			)
		);
		foreach ( $entries as $entry ) {
			echo '<tr><th scope="row">';
			self::print_time( $entry['time'] );
			echo '</th>';
			echo '<td>' . esc_html( self::action_label( $entry['action'] ) ) . '</td>';
			echo '<td>' . esc_html( 'cli' === $entry['channel'] ? 'WP-CLI' : __( 'Admin', 'creationell-wp-theme' ) ) . '</td>';
			echo '<td>' . esc_html( self::section_labels( $entry['sections'] ) ) . '</td>';
			echo '<td>' . esc_html( implode( ', ', $entry['langs'] ) ) . '</td>';
			echo '<td>' . esc_html( self::person( $entry['user_id'] ) ) . "</td></tr>\n";
		}
		self::table_close();
	}

	/**
	 * Prints the preview of the session in the query, or the start view with the error of the session or the plan.
	 *
	 * @since 1.0.0
	 *
	 * @param int                 $user_id Current user.
	 * @param Transfer_Error|null $error   Error of the last import attempt, or null.
	 * @return void
	 */
	private function render_preview( int $user_id, ?Transfer_Error $error ): void {
		$token = self::arg( $this->query, 'token' );
		try {
			$session = Import_Session::load( $token, $user_id );
			$parsed  = Importer::parse( $session['data'] );
			$options = new Import_Options( $parsed['file']->sections, null, $user_id, 'admin', false, array(), false, true, 'backup' === $session['source'] ? 'restore' : 'import' );
			$plan    = $this->importer()->plan( $parsed['file'], $options, $parsed['migrated'] );
		} catch ( Transfer_Exception $exception ) {
			$this->render_start( true, $exception->error );
			return;
		}

		if ( null !== $error ) {
			self::error_notice( $error );
		}
		echo '<h2>' . esc_html__( 'Preview', 'creationell-wp-theme' ) . "</h2>\n";
		echo '<p>' . esc_html__( 'Check what the import changes. Nothing has been changed yet.', 'creationell-wp-theme' ) . "</p>\n";
		self::render_header( $plan->file, $session['name'], 'backup' === $session['source'] );
		self::render_warnings( $plan->warnings );
		self::render_rows( $plan->rows, true );

		self::form_open( self::ACTION_APPLY );
		echo '<input type="hidden" name="token" value="' . esc_attr( $token ) . '">' . "\n";
		if ( $plan->needs_confirmation ) {
			self::render_confirmation( $plan, Transfer_Error::CONFIRMATION_REQUIRED === $error );
		}
		echo '<p class="' . esc_attr( self::ID . '-buttons' ) . '"><button type="submit" class="button button-primary">' . esc_html_x( 'Import', 'button', 'creationell-wp-theme' ) . "</button></p>\n";
		echo "</form>\n";
		self::form_open( self::ACTION_CANCEL );
		echo '<input type="hidden" name="token" value="' . esc_attr( $token ) . '">' . "\n";
		echo '<p class="' . esc_attr( self::ID . '-buttons' ) . '"><button type="submit" class="button">' . esc_html__( 'Cancel', 'creationell-wp-theme' ) . "</button></p>\n";
		echo "</form>\n";
	}

	/**
	 * Prints the header of the file as definition list.
	 *
	 * @since 1.0.0
	 *
	 * @param Transfer_File $file   File.
	 * @param string        $name   File name or backup ID.
	 * @param bool          $backup Whether the file is a backup.
	 * @return void
	 */
	private static function render_header( Transfer_File $file, string $name, bool $backup ): void {
		$items = array(
			$backup ? __( 'Backup', 'creationell-wp-theme' ) : __( 'File', 'creationell-wp-theme' ) => $name,
			__( 'Exported from', 'creationell-wp-theme' )  => $file->source,
			__( 'Exported at', 'creationell-wp-theme' )    => $file->exported_at,
			__( 'Theme version', 'creationell-wp-theme' )  => $file->theme_version,
			__( 'Bootstrap line', 'creationell-wp-theme' ) => (string) $file->bootstrap_line,
			__( 'Languages', 'creationell-wp-theme' )      => implode( ', ', $file->languages ),
			__( 'Sections', 'creationell-wp-theme' )       => self::section_labels( $file->sections ),
		);
		if ( array() !== $file->omitted_sensitive ) {
			$items[ __( 'Not in the file (sensitive)', 'creationell-wp-theme' ) ] = implode( ', ', array_map( self::key_label( ... ), $file->omitted_sensitive ) );
		}
		echo '<dl class="' . esc_attr( self::ID . '-header' ) . '">' . "\n";
		foreach ( $items as $term => $description ) {
			echo '<dt>' . esc_html( (string) $term ) . '</dt><dd>' . esc_html( $description ) . "</dd>\n";
		}
		echo "</dl>\n";
	}

	/**
	 * Prints the warnings as text.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $warnings Warning codes.
	 * @return void
	 */
	private static function render_warnings( array $warnings ): void {
		if ( array() === $warnings ) {
			return;
		}
		echo '<div class="notice notice-warning inline"><p><strong>' . esc_html__( 'Warnings', 'creationell-wp-theme' ) . '</strong></p><ul>';
		foreach ( $warnings as $warning ) {
			echo '<li>' . esc_html( self::warning_text( $warning ) ) . '</li>';
		}
		echo "</ul></div>\n";
	}

	/**
	 * Prints one table per section.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, Plan_Row> $rows    Rows.
	 * @param bool                 $preview Whether the tables belong to the preview; it only changes their IDs.
	 * @return void
	 */
	private static function render_rows( array $rows, bool $preview ): void {
		$labels = self::section_label_map();
		$groups = array();
		foreach ( $rows as $row ) {
			$groups[ $row->section ][] = $row;
		}
		foreach ( $groups as $section => $section_rows ) {
			$first = Transfer_File::SECTION_MODULES === $section ? __( 'Module', 'creationell-wp-theme' ) : __( 'Setting', 'creationell-wp-theme' );
			self::table_open(
				( $preview ? 'preview-' : 'result-' ) . $section,
				$labels[ $section ] ?? $section,
				array( $first, __( 'Language', 'creationell-wp-theme' ), __( 'Current', 'creationell-wp-theme' ), __( 'New', 'creationell-wp-theme' ), __( 'Result', 'creationell-wp-theme' ) )
			);
			foreach ( $section_rows as $row ) {
				self::row_html( $row );
			}
			self::table_close();
		}
	}

	/**
	 * Prints one row of a plan or report.
	 *
	 * @since 1.0.0
	 *
	 * @param Plan_Row $row Row.
	 * @return void
	 */
	private static function row_html( Plan_Row $row ): void {
		$module = Transfer_File::SECTION_MODULES === $row->section && str_starts_with( $row->key, 'module_' );
		$label  = $module ? Module_Catalog::instance()->title( Transfer_File::module_slug( $row->key ) ) : self::key_label( $row->key );
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th>';
		echo '<td>' . esc_html( $row->lang ?? __( 'All languages', 'creationell-wp-theme' ) ) . '</td>';
		echo '<td>';
		self::print_value( $row->current, $module );
		echo '</td><td>';
		self::print_value( $row->incoming, $module );
		echo '</td><td>';
		self::print_status( $row );
		echo "</td></tr>\n";
	}

	/**
	 * Prints the confirmation of the module switches with the counted live contents.
	 *
	 * @since 1.0.0
	 *
	 * @param Import_Plan $plan    Plan.
	 * @param bool        $invalid Whether the last attempt lacked the confirmation.
	 * @return void
	 */
	private static function render_confirmation( Import_Plan $plan, bool $invalid ): void {
		$error_id = self::CONFIRM_ID . '-error';
		echo '<fieldset class="' . esc_attr( self::ID . '-confirm' ) . '"><legend>' . esc_html__( 'Module changes', 'creationell-wp-theme' ) . "</legend>\n";
		echo '<p>' . esc_html__( 'The import switches modules. Published contents that use them may look different or lose functions:', 'creationell-wp-theme' ) . "</p>\n<ul>\n";
		foreach ( $plan->seen_live as $slug => $live ) {
			echo '<li>' . esc_html(
				sprintf(
					/* translators: 1: module title, 2: number of published contents. */
					_n( '%1$s: used in %2$d published content', '%1$s: used in %2$d published contents', $live, 'creationell-wp-theme' ),
					Module_Catalog::instance()->title( $slug ),
					$live
				)
			) . "</li>\n";
			echo '<input type="hidden" name="' . esc_attr( 'seen_live[' . $slug . ']' ) . '" value="' . esc_attr( (string) $live ) . '">' . "\n";
		}
		echo "</ul>\n";
		if ( $invalid ) {
			self::field_error( $error_id, Transfer_Error::CONFIRMATION_REQUIRED );
		}
		printf(
			'<p><input type="checkbox" id="%1$s" name="confirm_modules" value="1"%2$s> <label for="%1$s">%3$s</label></p>' . "\n",
			esc_attr( self::CONFIRM_ID ),
			$invalid ? ' aria-invalid="true" aria-describedby="' . esc_attr( $error_id ) . '"' : '',
			esc_html__( 'I understand the consequences of the module changes', 'creationell-wp-theme' )
		);
		echo "</fieldset>\n";
	}

	/**
	 * Prints the result of the last import of the user, or the start view when none is stored.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id Current user.
	 * @return void
	 */
	private function render_result( int $user_id ): void {
		$report = self::stored_report( $user_id );
		if ( null === $report ) {
			echo '<div class="notice notice-info"><p>' . esc_html__( 'No import result is available anymore.', 'creationell-wp-theme' ) . "</p></div>\n";
			$this->render_start( true, null );
			return;
		}
		echo '<h2 id="' . esc_attr( self::RESULT_ID ) . '" tabindex="-1">' . esc_html( self::result_heading( $report ) ) . "</h2>\n";
		// The ID in the script is the literal value of RESULT_ID.
		echo '<script>document.getElementById("creationell-wp-theme-transfer-result").focus();</script>' . "\n";
		echo '<p class="' . esc_attr( self::ID . '-counts' ) . '">' . esc_html(
			sprintf(
				/* translators: 1: number of changed values, 2: number of skipped values, 3: number of rejected values. */
				__( '%1$d changed, %2$d skipped, %3$d rejected.', 'creationell-wp-theme' ),
				$report->counts['write'],
				$report->counts['skip'],
				$report->counts['reject']
			)
		) . "</p>\n";
		if ( null !== $report->backup_id ) {
			echo '<p>' . esc_html__( 'Backup made before the import:', 'creationell-wp-theme' ) . ' <code>' . esc_html( $report->backup_id ) . "</code></p>\n";
			self::restore_form( $report->backup_id, __( 'Restore this backup …', 'creationell-wp-theme' ), null );
		}
		self::render_warnings( $report->warnings );
		foreach ( array( 'reject', 'skip' ) as $group ) {
			$rows = array_values( array_filter( $report->rows, static fn( Plan_Row $row ): bool => $group === $row->group() ) );
			if ( array() === $rows ) {
				continue;
			}
			self::table_open(
				'result-' . $group,
				'reject' === $group ? __( 'Rejected', 'creationell-wp-theme' ) : __( 'Skipped', 'creationell-wp-theme' ),
				array( __( 'Setting', 'creationell-wp-theme' ), __( 'Language', 'creationell-wp-theme' ), __( 'Current', 'creationell-wp-theme' ), __( 'New', 'creationell-wp-theme' ), __( 'Result', 'creationell-wp-theme' ) )
			);
			foreach ( $rows as $row ) {
				self::row_html( $row );
			}
			self::table_close();
		}
		echo '<p><a href="' . esc_url( self::url( array() ) ) . '">' . esc_html__( 'Back to Import/Export', 'creationell-wp-theme' ) . "</a></p>\n";
	}

	/**
	 * Returns the heading of a result.
	 *
	 * @since 1.0.0
	 *
	 * @param Import_Report $report Report.
	 * @return string Heading.
	 */
	private static function result_heading( Import_Report $report ): string {
		return $report->written ? __( 'Import finished', 'creationell-wp-theme' ) : __( 'Nothing was imported', 'creationell-wp-theme' );
	}

	/**
	 * Returns the stored report of the last import of a user.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID.
	 * @return Import_Report|null Report, or null when none is stored or it is broken.
	 */
	private static function stored_report( int $user_id ): ?Import_Report {
		$stored = get_transient( self::RESULT_PREFIX . $user_id );
		if ( ! is_array( $stored ) ) {
			return null;
		}
		try {
			return Import_Report::from_array( $stored );
		} catch ( InvalidArgumentException ) {
			return null;
		}
	}

	/**
	 * Prints an error notice with a link to the field it belongs to.
	 *
	 * @since 1.0.0
	 *
	 * @param Transfer_Error $error Error.
	 * @return void
	 */
	private static function error_notice( Transfer_Error $error ): void {
		$target = '';
		if ( in_array( $error, self::FILE_ERRORS, true ) ) {
			$target = Upload_Validator::FIELD;
		} elseif ( Transfer_Error::NOTHING_SELECTED === $error ) {
			$ids    = Sections::ids();
			$target = self::ID . '-section-' . ( $ids[0] ?? '' );
		} elseif ( Transfer_Error::CONFIRMATION_REQUIRED === $error ) {
			$target = self::CONFIRM_ID;
		}
		echo '<div class="notice notice-error"><p>' . esc_html( $error->message() );
		if ( '' !== $target ) {
			echo ' <a href="#' . esc_attr( $target ) . '">' . esc_html__( 'Go to the field', 'creationell-wp-theme' ) . '</a>';
		}
		echo "</p></div>\n";
	}

	/**
	 * Prints the error text next to a field.
	 *
	 * @since 1.0.0
	 *
	 * @param string         $id    ID the field refers to.
	 * @param Transfer_Error $error Error.
	 * @return void
	 */
	private static function field_error( string $id, Transfer_Error $error ): void {
		echo '<p id="' . esc_attr( $id ) . '" class="' . esc_attr( self::ID . '-field-error' ) . '">' . esc_html( $error->message() ) . "</p>\n";
	}

	/**
	 * Prints the start of a form to admin-post.php with its action and nonce.
	 *
	 * The nonce field has no ID, so several forms on the page keep their IDs unique.
	 *
	 * @since 1.0.0
	 *
	 * @param string $action    Action and nonce action.
	 * @param bool   $multipart Whether the form uploads a file.
	 * @return void
	 */
	private static function form_open( string $action, bool $multipart = false ): void {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"' . ( $multipart ? ' enctype="multipart/form-data"' : '' ) . ">\n";
		echo '<input type="hidden" name="action" value="' . esc_attr( $action ) . '">' . "\n";
		echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( wp_create_nonce( $action ) ) . '">' . "\n";
	}

	/**
	 * Prints a checked checkbox with its label.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name    Field name.
	 * @param string $value   Value.
	 * @param string $id      ID.
	 * @param string $label   Label.
	 * @param bool   $invalid Whether the group has an error.
	 * @return void
	 */
	private static function checkbox( string $name, string $value, string $id, string $label, bool $invalid ): void {
		printf(
			'<p><input type="checkbox" id="%1$s" name="%2$s" value="%3$s" checked%4$s> <label for="%1$s">%5$s</label></p>' . "\n",
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $value ),
			$invalid ? ' aria-invalid="true"' : '',
			esc_html( $label )
		);
	}

	/**
	 * Prints the form that opens the preview of a backup.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $backup_id   Backup ID.
	 * @param string      $label       Text of the button.
	 * @param string|null $describedby ID of the element that names the backup, or null.
	 * @return void
	 */
	private static function restore_form( string $backup_id, string $label, ?string $describedby ): void {
		self::form_open( self::ACTION_RESTORE );
		echo '<input type="hidden" name="backup_id" value="' . esc_attr( $backup_id ) . '">' . "\n";
		echo '<button type="submit" class="button"' . ( null === $describedby ? '' : ' aria-describedby="' . esc_attr( $describedby ) . '"' ) . '>' . esc_html( $label ) . "</button>\n";
		echo "</form>\n";
	}

	/**
	 * Prints the start of a table in a scrollable region named by its caption.
	 *
	 * @since 1.0.0
	 *
	 * @param string             $name    Name that makes the IDs unique.
	 * @param string             $caption Caption.
	 * @param array<int, string> $headers Column headers.
	 * @return void
	 */
	private static function table_open( string $name, string $caption, array $headers ): void {
		$caption_id = self::ID . '-' . $name . '-caption';
		echo '<div class="' . esc_attr( self::ID . '-scroll' ) . '" role="region" tabindex="0" aria-labelledby="' . esc_attr( $caption_id ) . '">' . "\n";
		echo '<table class="widefat striped ' . esc_attr( self::ID . '-table' ) . '">' . "\n";
		echo '<caption id="' . esc_attr( $caption_id ) . '">' . esc_html( $caption ) . "</caption>\n<thead><tr>";
		foreach ( $headers as $header ) {
			echo '<th scope="col">' . esc_html( $header ) . '</th>';
		}
		echo "</tr></thead>\n<tbody>\n";
	}

	/**
	 * Prints the end of a table and its region.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function table_close(): void {
		echo "</tbody>\n</table>\n</div>\n";
	}

	/**
	 * Prints a value: colors as hex with a swatch, null as default, other values as text.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value  Value.
	 * @param bool  $module Whether the value is a module state.
	 * @return void
	 */
	private static function print_value( mixed $value, bool $module ): void {
		if ( null === $value ) {
			echo '<span class="' . esc_attr( self::ID . '-default' ) . '">' . esc_html__( 'Default', 'creationell-wp-theme' ) . '</span>';
		} elseif ( $module && is_string( $value ) ) {
			echo esc_html( self::state_label( $value ) );
		} elseif ( is_bool( $value ) ) {
			echo esc_html( $value ? __( 'Yes', 'creationell-wp-theme' ) : __( 'No', 'creationell-wp-theme' ) );
		} elseif ( is_string( $value ) && 1 === preg_match( '~^#[0-9a-fA-F]{6}$~D', $value ) ) {
			echo '<span class="' . esc_attr( self::ID . '-swatch' ) . '" style="background-color: ' . esc_attr( $value ) . '" aria-hidden="true"></span><code>' . esc_html( $value ) . '</code>';
		} elseif ( is_scalar( $value ) ) {
			echo esc_html( (string) $value );
		} else {
			$json = wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			echo '<code>' . esc_html( is_string( $json ) ? $json : '' ) . '</code>';
		}
	}

	/**
	 * Prints the result of a row: group, status and message as text.
	 *
	 * @since 1.0.0
	 *
	 * @param Plan_Row $row Row.
	 * @return void
	 */
	private static function print_status( Plan_Row $row ): void {
		$group = $row->group();
		$names = array(
			'write'  => __( 'Changes', 'creationell-wp-theme' ),
			'skip'   => __( 'Skipped', 'creationell-wp-theme' ),
			'reject' => __( 'Rejected', 'creationell-wp-theme' ),
		);
		echo '<strong class="' . esc_attr( self::ID . '-status ' . self::ID . '-status-' . $group ) . '">' . esc_html( $names[ $group ] ?? $group ) . '</strong>: ' . esc_html( self::status_label( $row->status ) );
		if ( '' !== $row->message ) {
			echo '<br>' . esc_html( $row->message );
		}
		$scan = $row->switch_result?->scan;
		if ( null !== $scan ) {
			$live = $scan->total_live();
			echo '<br>' . esc_html(
				sprintf(
					/* translators: %d: number of published contents. */
					_n( 'Used in %d published content.', 'Used in %d published contents.', $live, 'creationell-wp-theme' ),
					$live
				)
			);
		}
	}

	/**
	 * Returns the label of a row status.
	 *
	 * @since 1.0.0
	 *
	 * @param Row_Status $status Status.
	 * @return string Label.
	 */
	private static function status_label( Row_Status $status ): string {
		return match ( $status ) {
			Row_Status::SAVE                      => __( 'Save', 'creationell-wp-theme' ),
			Row_Status::RESET                     => __( 'Reset to the default', 'creationell-wp-theme' ),
			Row_Status::MODULE_SWITCH             => __( 'Switch the module', 'creationell-wp-theme' ),
			Row_Status::UNCHANGED                 => __( 'Unchanged', 'creationell-wp-theme' ),
			Row_Status::ABSENT                    => __( 'Not in the file', 'creationell-wp-theme' ),
			Row_Status::LOCKED                    => __( 'Locked on this site', 'creationell-wp-theme' ),
			Row_Status::NOT_TRANSLATABLE          => __( 'Not translatable', 'creationell-wp-theme' ),
			Row_Status::UNKNOWN_KEY               => __( 'Unknown setting', 'creationell-wp-theme' ),
			Row_Status::REMOVED_KEY               => __( 'Setting removed in a theme update', 'creationell-wp-theme' ),
			Row_Status::RENAMED                   => __( 'Setting renamed in a theme update', 'creationell-wp-theme' ),
			Row_Status::SENSITIVE_SKIPPED         => __( 'Sensitive, never imported', 'creationell-wp-theme' ),
			Row_Status::LANGUAGE_INACTIVE         => __( 'Language not active on this site', 'creationell-wp-theme' ),
			Row_Status::LINE_UNMAPPED             => __( 'Not available in the Bootstrap line of this site', 'creationell-wp-theme' ),
			Row_Status::LINE_NEVER_IMPORTED       => __( 'The Bootstrap line is never imported', 'creationell-wp-theme' ),
			Row_Status::SECTION_UNKNOWN           => __( 'Unknown part of the file', 'creationell-wp-theme' ),
			Row_Status::MODULE_UNCHANGED          => __( 'Module unchanged', 'creationell-wp-theme' ),
			Row_Status::MODULE_LOCKED             => __( 'Module locked on this site', 'creationell-wp-theme' ),
			Row_Status::FORBIDDEN                 => __( 'You may not change this setting', 'creationell-wp-theme' ),
			Row_Status::INVALID                   => __( 'Invalid value', 'creationell-wp-theme' ),
			Row_Status::CONTRAST                  => __( 'Contrast too low', 'creationell-wp-theme' ),
			Row_Status::REFERENCE_UNRESOLVED      => __( 'Referenced content not found on this site', 'creationell-wp-theme' ),
			Row_Status::MODULE_DENIED             => __( 'You may not switch this module', 'creationell-wp-theme' ),
			Row_Status::MODULE_INVALID            => __( 'Invalid module state', 'creationell-wp-theme' ),
			Row_Status::MODULE_NEEDS_CONFIRMATION => __( 'Needs your confirmation', 'creationell-wp-theme' ),
		};
	}

	/**
	 * Returns the label of a module state.
	 *
	 * @since 1.0.0
	 *
	 * @param string $state State.
	 * @return string Label; unknown states as they are.
	 */
	private static function state_label( string $state ): string {
		return match ( $state ) {
			'active' => __( 'On', 'creationell-wp-theme' ),
			'off'    => __( 'Off', 'creationell-wp-theme' ),
			'hidden' => __( 'Hidden', 'creationell-wp-theme' ),
			default  => $state,
		};
	}

	/**
	 * Returns the text of a warning code of a plan.
	 *
	 * @since 1.0.0
	 *
	 * @param string $warning Warning code, e.g. "line_differs" or "migrated:1->2".
	 * @return string Text.
	 */
	private static function warning_text( string $warning ): string {
		if ( 'line_differs' === $warning ) {
			return __( 'The file comes from another Bootstrap line. The import never switches the line; settings of the other line are skipped. Switch the line first if you need them.', 'creationell-wp-theme' );
		}
		if ( 'theme_newer' === $warning ) {
			return __( 'The file comes from a newer theme version. Settings this version does not know are skipped.', 'creationell-wp-theme' );
		}
		if ( str_starts_with( $warning, 'migrated:' ) ) {
			$steps = explode( '->', substr( $warning, strlen( 'migrated:' ) ) );
			/* translators: 1: old format version, 2: new format version. */
			return sprintf( __( 'The file was converted from format version %1$s to %2$s.', 'creationell-wp-theme' ), $steps[0], $steps[1] ?? '' );
		}
		return $warning;
	}

	/**
	 * Returns the label of a backup reason.
	 *
	 * @since 1.0.0
	 *
	 * @param string $reason Reason.
	 * @return string Label.
	 */
	private static function reason_label( string $reason ): string {
		return match ( $reason ) {
			'import'        => __( 'Before an import', 'creationell-wp-theme' ),
			'restore'       => __( 'Before a restore', 'creationell-wp-theme' ),
			'module_switch' => __( 'Before a module switch', 'creationell-wp-theme' ),
			'manual'        => __( 'Manual', 'creationell-wp-theme' ),
			'line_switch'   => __( 'Before a Bootstrap line switch', 'creationell-wp-theme' ),
			default         => $reason,
		};
	}

	/**
	 * Returns the label of a log action.
	 *
	 * @since 1.0.0
	 *
	 * @param string $action Action.
	 * @return string Label.
	 */
	private static function action_label( string $action ): string {
		return match ( $action ) {
			'export'  => __( 'Export', 'creationell-wp-theme' ),
			'import'  => __( 'Import', 'creationell-wp-theme' ),
			'restore' => __( 'Restore', 'creationell-wp-theme' ),
			'backup'  => __( 'Backup', 'creationell-wp-theme' ),
			default   => $action,
		};
	}

	/**
	 * Returns the labels of the sections by ID.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Labels.
	 */
	private static function section_label_map(): array {
		$labels = array();
		foreach ( Sections::all() as $id => $section ) {
			$labels[ $id ] = $section->label();
		}
		return $labels;
	}

	/**
	 * Returns the labels of section IDs as a list.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $sections Section IDs.
	 * @return string Labels separated by commas; unknown IDs as they are.
	 */
	private static function section_labels( array $sections ): string {
		$labels = self::section_label_map();
		return implode( ', ', array_map( static fn( string $id ): string => $labels[ $id ] ?? $id, $sections ) );
	}

	/**
	 * Returns the label of a setting key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Setting key.
	 * @return string Label; the key without a label.
	 */
	private static function key_label( string $key ): string {
		$registry = Registry::instance();
		if ( ! $registry->has( $key ) ) {
			return $key;
		}
		$label = $registry->get( $key )->label_text();
		return '' === $label ? $key : $label;
	}

	/**
	 * Returns the name of a user.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID; 0 for none or a deleted user.
	 * @return string Display name.
	 */
	private static function person( int $user_id ): string {
		$user = $user_id > 0 ? get_userdata( $user_id ) : false;
		if ( false === $user ) {
			return __( 'Unknown or deleted user', 'creationell-wp-theme' );
		}
		return '' === $user->display_name ? $user->user_login : $user->display_name;
	}

	/**
	 * Prints a time element in the time zone of the site.
	 *
	 * @since 1.0.0
	 *
	 * @param int $timestamp Unix time.
	 * @return void
	 */
	private static function print_time( int $timestamp ): void {
		$shown = wp_date( 'Y-m-d H:i', $timestamp );
		echo '<time datetime="' . esc_attr( gmdate( 'Y-m-d\TH:i:s\Z', $timestamp ) ) . '">' . esc_html( is_string( $shown ) ? $shown : '' ) . '</time>';
	}

	/**
	 * Returns the languages of the site: the active ones, with the default language first when WPML does not list it.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Language codes.
	 * @phpstan-return list<string>
	 */
	private static function languages(): array {
		$language = Language::instance();
		$active   = array_values( $language->active() );
		if ( ! in_array( $language->default(), $active, true ) ) {
			array_unshift( $active, $language->default() );
		}
		return $active;
	}

	/**
	 * Ends the request with 403 unless the user may import and export; the handlers call it right after their nonce check.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function require_capability(): void {
		if ( ! current_user_can( Capabilities::IMPORT_EXPORT ) ) {
			wp_die( esc_html( Transfer_Error::FORBIDDEN->message() ), '', array( 'response' => 403 ) );
		}
	}

	/**
	 * Redirects to the page with the given query arguments and ends the request.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $args Query arguments; codes only.
	 * @return never
	 */
	private function redirect( array $args ): never {
		wp_safe_redirect( self::url( $args ) );
		( $this->halt )();
	}

	/**
	 * Stops a request whose backup, log or file could not be written.
	 *
	 * @since 1.0.0
	 *
	 * @param Exception $exception Cause; its message is not shown.
	 * @return never
	 */
	private static function fail( Exception $exception ): never {
		unset( $exception );
		wp_die( esc_html__( 'The backup or the log could not be stored. Nothing was changed.', 'creationell-wp-theme' ), '', array( 'response' => 500 ) );
	}

	/**
	 * Returns the URL of the page with query arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $args Query arguments.
	 * @return string URL.
	 */
	private static function url( array $args ): string {
		return add_query_arg( array_map( 'rawurlencode', $args ), admin_url( 'admin.php?page=' . self::SLUG ) );
	}

	/**
	 * Returns the query arguments of the request that the page reads.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Raw values by name; missing ones are left out.
	 */
	private static function request_query(): array {
		$query = array();
		foreach ( array( 'view', 'token', 'error', 'message' ) as $name ) {
			$value = filter_input( INPUT_GET, $name );
			if ( is_string( $value ) ) {
				$query[ $name ] = $value;
			}
		}
		return $query;
	}

	/**
	 * Returns a key-like argument of a query or of the posted fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $args Arguments, unslashed.
	 * @param string       $name Name.
	 * @return string Value after sanitize_key(); empty when missing or no string.
	 */
	private static function arg( array $args, string $name ): string {
		return isset( $args[ $name ] ) && is_string( $args[ $name ] ) ? sanitize_key( $args[ $name ] ) : '';
	}

	/**
	 * Returns a list of key-like posted values.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $post Posted fields, unslashed.
	 * @param string       $name Name without the brackets.
	 * @return array<int, string> Values after sanitize_key(), without empty ones.
	 * @phpstan-return list<string>
	 */
	private static function arg_list( array $post, string $name ): array {
		$values = $post[ $name ] ?? array();
		$list   = array();
		foreach ( is_array( $values ) ? $values : array() as $value ) {
			$key = is_string( $value ) ? sanitize_key( $value ) : '';
			if ( '' !== $key ) {
				$list[] = $key;
			}
		}
		return array_values( array_unique( $list ) );
	}

	/**
	 * Returns posted counts by module slug.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $post Posted fields, unslashed.
	 * @param string       $name Name without the brackets.
	 * @return array<string, int> Counts of at least 0 by slug.
	 */
	private static function arg_counts( array $post, string $name ): array {
		$values = $post[ $name ] ?? array();
		$counts = array();
		foreach ( is_array( $values ) ? $values : array() as $slug => $count ) {
			$slug = sanitize_key( (string) $slug );
			if ( '' !== $slug && is_string( $count ) && ctype_digit( $count ) ) {
				$counts[ $slug ] = (int) $count;
			}
		}
		return $counts;
	}

	/**
	 * Returns the exporter.
	 *
	 * @since 1.0.0
	 *
	 * @return Exporter Exporter.
	 */
	private function exporter(): Exporter {
		return $this->exporter ?? Exporter::instance();
	}

	/**
	 * Returns the importer.
	 *
	 * @since 1.0.0
	 *
	 * @return Importer Importer.
	 */
	private function importer(): Importer {
		if ( null === $this->importer ) {
			$this->importer = new Importer();
		}
		return $this->importer;
	}

	/**
	 * Returns the backup store.
	 *
	 * @since 1.0.0
	 *
	 * @return Backup_Store Store.
	 */
	private function store(): Backup_Store {
		return $this->store ?? Backup_Store::instance();
	}

	/**
	 * Sends the headers and the file of a download, encoded as Json_Codec::encode() does.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $headers Headers by name.
	 * @param Transfer_File         $file    File.
	 * @return void
	 */
	private static function send_download( array $headers, Transfer_File $file ): void {
		foreach ( $headers as $name => $value ) {
			header( $name . ': ' . $value );
		}
		echo wp_json_encode( Json_Codec::normalize( $file->to_array() ), Json_Codec::ENCODE_FLAGS, Json_Codec::DEPTH ) . "\n";
	}
}
