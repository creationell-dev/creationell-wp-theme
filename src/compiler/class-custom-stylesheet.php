<?php
/**
 * Individual stylesheet of the site, compiled from the design settings.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Compiler;

use Closure;
use Creationell\WpTheme\Child\Child_Theme;
use Creationell\WpTheme\Core\Capabilities;
use Creationell\WpTheme\Assets\Stylesheet_Locator;
use Creationell\WpTheme\Settings\Bootstrap_Line;
use Creationell\WpTheme\Settings\Font_Catalog;
use Creationell\WpTheme\Settings\Language;
use Creationell\WpTheme\Settings\Registry;
use Creationell\WpTheme\Settings\Settings;
use Creationell\WpTheme\Settings\Token_Map;
use Creationell\WpTheme\Settings\Write_Context;
use RuntimeException;
use Throwable;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Compiles the theme SCSS with the design settings into uploads/creationell-wp-theme/css/.
 *
 * Steps of build(): (1) the variables come from Token_Map for the active
 * Bootstrap line, as Scss_Value colors and font stacks; (2) the import paths are
 * the child assets/scss/ (only when the child declares the line), the parent
 * assets/scss/ and assets/scss/child-defaults/ (empty partials the child
 * replaces) and assets/vendor/; (3) with every value at its default and no child
 * SCSS the package stylesheet applies: state "package", own files deleted;
 * (4) the same fingerprint with the files in place builds nothing; (5) the memory
 * limit is raised (context creationell_wp_theme_compile) and the compiler writes
 * theme-<fp12>.min.css and, while a language written right to left is active
 * (Language::rtl()), theme-<fp12>-rtl.min.css; the directions are part of the
 * fingerprint, so a new right-to-left language makes the stylesheet stale;
 * (6) root-vars-<fp12>.min.css follows
 * for the editor screens; (7) the state option is written and older files except
 * the previous build are deleted. A failure writes the state "failed" with the
 * error, keeps the previous files active and shows an admin notice; it never
 * stops the request. The cleanup keeps own files younger than CLEANUP_GRACE,
 * which may belong to a build running at the same time, and schedules a
 * follow-up for them: the cron event CRON_HOOK with the arguments "cleanup" and
 * their names deletes them after the grace period unless a build made them
 * active meanwhile.
 *
 * Triggers: a commit of the setter that changed a token setting, WP-CLI, the
 * button of the admin notice and a cron event 60 seconds after an update of the
 * theme or a switch to it or to one of its child themes. The admin screens check
 * once per hour whether the stylesheet is stale; the front end never computes
 * the fingerprint.
 *
 * Example:
 *
 *     $state = Custom_Stylesheet::instance()->build( 'cli', true );
 *     if ( 'failed' === $state['status'] ) {
 *         WP_CLI::error( (string) $state['error'] );
 *     }
 *
 * @api
 * @since 1.0.0
 */
final class Custom_Stylesheet {

	/**
	 * Option with the state of the last build (autoloaded).
	 *
	 * @since 1.0.0
	 */
	public const STATE_OPTION = 'creationell_wp_theme_css_state';

	/**
	 * Schema version of the state.
	 *
	 * @since 1.0.0
	 */
	public const SCHEMA = 1;

	/**
	 * Cron hook of the build after a theme update.
	 *
	 * @since 1.0.0
	 */
	public const CRON_HOOK = 'creationell_wp_theme_build_css';

	/**
	 * Action of the admin-post request and of its nonce.
	 *
	 * @since 1.0.0
	 */
	public const ADMIN_ACTION = 'creationell_wp_theme_build_css';

	/**
	 * Transient with the result of the stale check ("stale" or "fresh").
	 *
	 * @since 1.0.0
	 */
	public const CHECK_TRANSIENT = 'creationell_wp_theme_css_check';

	/**
	 * Seconds the stale check holds.
	 *
	 * @since 1.0.0
	 */
	public const CHECK_SECONDS = 3600;

	/**
	 * Seconds between a theme update and the build.
	 *
	 * @since 1.0.0
	 */
	public const CRON_DELAY = 60;

	/**
	 * Seconds an own file in the target folder is safe from the cleanup.
	 *
	 * Two builds may run at the same time (cron and a save): one of them must not
	 * delete the file the other one wrote but has not stored in its state yet.
	 * A follow-up of the cleanup, one second after the grace period, removes
	 * the file once it is older and still not active.
	 *
	 * @since 1.0.0
	 */
	public const CLEANUP_GRACE = 300;

	/**
	 * Context of wp_raise_memory_limit(); the filter creationell_wp_theme_compile_memory_limit changes the limit.
	 *
	 * @since 1.0.0
	 */
	public const MEMORY_CONTEXT = 'creationell_wp_theme_compile';

	/**
	 * Target folder below the uploads folder.
	 *
	 * @since 1.0.0
	 */
	public const UPLOADS_SUBDIR = 'creationell-wp-theme/css';

	/**
	 * Folder with the empty project partials of the parent, relative to the theme folder.
	 *
	 * Sass resolves an import next to the importing file before the import paths,
	 * so these partials must not lie next to theme.scss, or they would hide the
	 * ones of the child theme.
	 *
	 * @since 1.0.0
	 */
	public const CHILD_DEFAULTS_DIR = 'assets/scss/child-defaults';

	/**
	 * Form of the names of own files in the target folder.
	 *
	 * @since 1.0.0
	 */
	public const FILE_PATTERN = '~^(?:theme-[0-9a-f]{12}(?:-rtl)?|root-vars-[0-9a-f]{12})\.min\.css$~D';

	/**
	 * Absolute path that the admin notice shortens (Custom_Stylesheet::short_path()).
	 *
	 * Start: not inside a word, a relative path or a URL (no letter, digit, dot,
	 * hyphen, tilde, slash or backslash before it), or right after "file://".
	 * Root: a drive (C:\ or C:/), a share (two backslashes) or a slash without a
	 * second slash after it. Then folders, each followed by a slash or backslash;
	 * a folder may hold single spaces, apostrophes and a group in brackets before the separator
	 * ("Program Files (x86)"). A word that looks like a file name (a known extension
	 * such as scss, css, php, json, map or js in any case, perhaps ":line" and up to
	 * two of : , ; ') followed by a space ends the path, so the text after
	 * "a.scss: ..." stays text; a folder like "v1.5 beta" goes on. The
	 * last part has no spaces. The quantifiers are possessive, so a long path does
	 * not backtrack. In the pattern \x5c is the backslash, \x22 and \x27 the quotes.
	 *
	 * @since 1.0.0
	 */
	private const SERVER_PATH = '~(?:(?<![\w.\~/\x5c-])|(?<=file://))(?:[A-Za-z]:[/\x5c]|\x5c\x5c(?!\x5c)|/(?!/))(?:[^\s\x22()\[\]<>/\x5c]++[/\x5c]|(?!(?:[^\s\x22()\[\]<>/\x5c.]*+\.)++(?i:scss|sass|css|php|phar|inc|json|map|js|mjs|txt|log|tmp)(?::\d++)*+[:,;\x27]{0,2}+\x20)[^\s\x22()\[\]<>/\x5c]++(?:\x20(?!(?:[^\s\x22()\[\]<>/\x5c.]*+\.)++(?i:scss|sass|css|php|phar|inc|json|map|js|mjs|txt|log|tmp)(?::\d++)*+[:,;\x27]{0,2}+\x20)[^\s\x22()\[\]<>/\x5c]++|\x20?+\([^\s\x22()\[\]<>/\x5c]++(?:\x20[^\s\x22()\[\]<>/\x5c]++)*+\)(?=[/\x5c]))*+[/\x5c])*+[^\s\x22()\[\]<>/\x5c]++~';

	/**
	 * Directions of the individual stylesheet; right to left only while a language written right to left is active.
	 *
	 * @since 1.0.0
	 */
	public const DIRECTIONS = array( 'ltr', 'rtl' );

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Returns the compiler of a Bootstrap line.
	 *
	 * @var Closure
	 * @phpstan-var Closure(int): Stylesheet_Compiler_Interface
	 */
	private Closure $compilers;

	/**
	 * Takes the compiler source; without one it uses Compiler_Factory.
	 *
	 * @since 1.0.0
	 *
	 * @param Closure|null $compilers Returns the compiler of a line.
	 * @phpstan-param (Closure(int): Stylesheet_Compiler_Interface)|null $compilers
	 */
	public function __construct( ?Closure $compilers = null ) {
		$this->compilers = $compilers ?? static fn( int $line ): Stylesheet_Compiler_Interface => Compiler_Factory::for_line( $line );
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
	 * @param self|null $stylesheet Instance.
	 * @return void
	 */
	public static function set_instance( ?self $stylesheet ): void {
		self::$instance = $stylesheet;
	}

	/**
	 * Registers the triggers, the stale check and the notice.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_action( 'creationell_wp_theme_settings_saved', array( self::class, 'on_settings_saved' ), 10, 2 );
		add_action( self::CRON_HOOK, array( self::class, 'on_cron' ), 10, 2 );
		add_action( 'upgrader_process_complete', array( self::class, 'on_upgrade' ), 10, 2 );
		add_action( 'after_switch_theme', array( self::class, 'on_switch_theme' ), 10, 0 );
		add_action( 'admin_post_' . self::ADMIN_ACTION, array( self::class, 'handle_admin_post' ), 10, 0 );
		add_action( 'admin_init', array( self::class, 'check_stale' ), 10, 0 );
		add_action( 'admin_notices', array( self::class, 'render_notice' ), 10, 0 );
	}

	/**
	 * Builds the stylesheet when a commit of the setter changed a token setting of the active line.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $keys Keys written by the commit.
	 * @param mixed $ctx  Write context.
	 * @return void
	 */
	public static function on_settings_saved( mixed $keys, mixed $ctx ): void {
		if ( ! is_array( $keys ) || ( $ctx instanceof Write_Context && $ctx->dry_run ) ) {
			return;
		}
		$tokens = array_keys( Token_Map::for_line( Bootstrap_Line::instance()->active() ) );
		if ( array() !== array_intersect( $keys, $tokens ) ) {
			self::instance()->build( 'settings' );
		}
	}

	/**
	 * Builds the stylesheet from the cron event, or runs the follow-up of a cleanup.
	 *
	 * The follow-up is the same event with the arguments "cleanup" and the
	 * names of the files a cleanup spared because they were young.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $task  "cleanup" for the follow-up; nothing for a build.
	 * @param mixed $names File names the cleanup spared.
	 * @return void
	 */
	public static function on_cron( mixed $task = null, mixed $names = null ): void {
		if ( 'cleanup' === $task ) {
			self::instance()->follow_up( is_array( $names ) ? $names : array() );
			return;
		}
		self::instance()->build( 'cron' );
	}

	/**
	 * Schedules one build 60 seconds after an update of the parent or the active child theme.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $upgrader   Upgrader, unused.
	 * @param mixed $hook_extra Details of the update: type, action, theme or themes.
	 * @return void
	 */
	public static function on_upgrade( mixed $upgrader, mixed $hook_extra ): void {
		unset( $upgrader );
		if ( ! is_array( $hook_extra ) || 'theme' !== ( $hook_extra['type'] ?? null ) ) {
			return;
		}
		$themes = is_array( $hook_extra['themes'] ?? null ) ? $hook_extra['themes'] : array();
		if ( is_string( $hook_extra['theme'] ?? null ) ) {
			$themes[] = $hook_extra['theme'];
		}
		$own   = array( get_template() );
		$child = Child_Theme::instance()->dir();
		if ( null !== $child ) {
			$own[] = basename( $child );
		}
		if ( array() !== array_intersect( $themes, $own ) ) {
			self::schedule();
		}
	}

	/**
	 * Schedules one build 60 seconds after a switch to this theme or to one of its child themes.
	 *
	 * Another child theme brings other SCSS, fonts and palette defaults, and the
	 * locator serves the stored build until a new one. WordPress fires
	 * after_switch_theme on the first request after the switch, which may be a
	 * front-end request: this only schedules the event and forgets the stale
	 * check of the admin, it neither compiles nor computes the fingerprint (FS-8).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function on_switch_theme(): void {
		delete_transient( self::CHECK_TRANSIENT );
		self::schedule();
	}

	/**
	 * Schedules the build event 60 seconds from now unless one is waiting.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function schedule(): void {
		if ( false === wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_single_event( time() + self::CRON_DELAY, self::CRON_HOOK );
		}
	}

	/**
	 * Builds the stylesheet from the button of the admin notice and returns to the page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function handle_admin_post(): void {
		check_admin_referer( self::ADMIN_ACTION );
		if ( ! current_user_can( Capabilities::MANAGE_DESIGN ) ) {
			wp_die( esc_html__( 'You are not allowed to rebuild the stylesheet.', 'creationell-wp-theme' ), '', array( 'response' => 403 ) );
		}
		self::instance()->build( 'notice', true );
		$referer = wp_get_referer();
		wp_safe_redirect( is_string( $referer ) && '' !== $referer ? $referer : admin_url() );
		exit;
	}

	/**
	 * Checks on admin screens, once per hour, whether the stylesheet is stale.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function check_stale(): void {
		if ( ! current_user_can( Capabilities::MANAGE_DESIGN ) || false !== get_transient( self::CHECK_TRANSIENT ) ) {
			return;
		}
		set_transient( self::CHECK_TRANSIENT, self::instance()->is_stale() ? 'stale' : 'fresh', self::CHECK_SECONDS );
	}

	/**
	 * Prints a notice with a rebuild button when the last build failed or the stylesheet is stale.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function render_notice(): void {
		if ( ! current_user_can( Capabilities::MANAGE_DESIGN ) ) {
			return;
		}
		$state = self::instance()->state();
		if ( 'failed' === $state['status'] ) {
			$type    = 'error';
			$message = sprintf(
				/* translators: %s: error message of the compiler, in English. */
				__( 'The stylesheet of the design settings could not be built; the previous stylesheet stays active. Error: %s', 'creationell-wp-theme' ),
				self::without_server_paths( (string) $state['error'] )
			);
		} elseif ( 'stale' === get_transient( self::CHECK_TRANSIENT ) ) {
			$type    = 'warning';
			$message = __( 'The stylesheet does not match the current design settings, theme files or child theme files.', 'creationell-wp-theme' );
		} else {
			return;
		}
		printf(
			'<div id="creationell-wp-theme-notice-css" class="notice notice-%1$s"><p>%2$s</p><form method="post" action="%3$s"><input type="hidden" name="action" value="%4$s" />',
			esc_attr( $type ),
			esc_html( $message ),
			esc_url( admin_url( 'admin-post.php' ) ),
			esc_attr( self::ADMIN_ACTION )
		);
		wp_nonce_field( self::ADMIN_ACTION );
		printf( '<p><button type="submit" class="button">%s</button></p></form></div>' . "\n", esc_html__( 'Rebuild the stylesheet', 'creationell-wp-theme' ) );
	}

	/**
	 * Shortens the server paths in an error message for the admin notice.
	 *
	 * The notice reaches every user with the design capability, editors of an
	 * unlocked design area included. The theme root becomes "themes", the content
	 * folder "wp-content", and the WordPress folder is removed, each also in its
	 * resolved form (a mounted theme, a symlinked content folder). Any other
	 * absolute path keeps only its last two parts after ".../" (or "...\"):
	 * after any character but a letter, digit, dot, hyphen, tilde, slash or
	 * backslash (so also after "=", ":" or ","), in POSIX, Windows (C:\, C:/) and
	 * share form, with spaces inside its folders, and after "file://". URLs,
	 * relative paths and CSS values such as "12px/1.5" stay. The stored state keeps the full message
	 * for WP-CLI and doctor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $error Error message.
	 * @return string Message with relative paths.
	 */
	private static function without_server_paths( string $error ): string {
		$roots = array(
			dirname( get_template_directory() ) => 'themes/',
			ABSPATH                             => '',
		);
		if ( defined( 'WP_CONTENT_DIR' ) && is_string( constant( 'WP_CONTENT_DIR' ) ) ) {
			$roots[ constant( 'WP_CONTENT_DIR' ) ] = 'wp-content/';
		}
		$map = array();
		foreach ( $roots as $root => $short ) {
			foreach ( array( (string) $root, (string) realpath( (string) $root ) ) as $path ) {
				$path = rtrim( $path, '/\\' );
				if ( '' !== $path && ! isset( $map[ $path . '/' ] ) ) {
					$map[ $path . '/' ] = $short;
				}
			}
		}
		// The longest root first, so the theme root wins over the folders that hold it.
		uksort( $map, static fn( string $a, string $b ): int => strlen( $b ) <=> strlen( $a ) );
		// Other absolute paths (a library of the compiler, a folder outside WordPress) keep their last two parts.
		// Should the pattern fail, the notice shows no message rather than a path.
		return (string) preg_replace_callback( self::SERVER_PATH, self::short_path( ... ), strtr( $error, $map ) );
	}

	/**
	 * Shortens one absolute path found by SERVER_PATH to its last two parts.
	 *
	 * A path of one part stays.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int|string, string> $path Match; key 0 is the path.
	 * @return string ".../" (or "...\") with the last two parts.
	 */
	private static function short_path( array $path ): string {
		$parts = preg_split( '~([/\x5c])~', $path[0], -1, PREG_SPLIT_DELIM_CAPTURE );
		// Root, then part and separator in turn: [root..., part, sep, part, sep, part].
		if ( false === $parts || count( $parts ) < 5 ) {
			return $path[0];
		}
		$last   = (string) array_pop( $parts );
		$sep    = (string) array_pop( $parts );
		$part   = (string) array_pop( $parts );
		$before = (string) array_pop( $parts );
		return '' === $part ? $path[0] : '...' . $before . $part . $sep . $last;
	}

	/**
	 * Returns the stored state, normalized.
	 *
	 * @since 1.0.0
	 *
	 * @return array{schema: int, status: string, line: int, fingerprint: string, files: array{ltr: string, rtl: string, root_vars: string}, theme_version: string, built_at: int, seconds: float, peak_bytes: int, error: string|null} State; "package" without a stored one.
	 */
	public function state(): array {
		return self::stored() ?? self::package_state( Bootstrap_Line::instance()->active() );
	}

	/**
	 * Returns the active individual file of a kind for the locator.
	 *
	 * Only a stored build of the active line with its file in place counts;
	 * otherwise the package applies. A right-to-left request also needs the
	 * right-to-left file of the build: the main stylesheet is then still the
	 * left-to-right file, which WordPress replaces with its "-rtl" file
	 * (wp_style_add_data( ..., 'rtl', 'replace' ) in Assets); without it the
	 * package applies to both kinds.
	 *
	 * @since 1.0.0
	 *
	 * @param string $kind "ltr" for the main stylesheet, "root_vars" for the root custom properties.
	 * @return array{url: string, path: string, version: string}|null URL, path and 12 hex digits of the fingerprint as version; null for the package.
	 */
	public static function active( string $kind ): ?array {
		$state = self::stored();
		if ( null === $state || 'package' === $state['status'] || Bootstrap_Line::instance()->active() !== $state['line'] ) {
			return null;
		}
		$name   = 'root_vars' === $kind ? $state['files']['root_vars'] : $state['files']['ltr'];
		$target = self::target();
		if ( '' === $name || null === $target || ! is_file( $target['dir'] . '/' . $name ) ) {
			return null;
		}
		if ( is_rtl() && ( '' === $state['files']['rtl'] || ! is_file( $target['dir'] . '/' . $state['files']['rtl'] ) ) ) {
			return null;
		}
		return array(
			'url'     => $target['url'] . '/' . $name,
			'path'    => $target['dir'] . '/' . $name,
			'version' => substr( $state['fingerprint'], 0, Stylesheet_Locator::FINGERPRINT_LENGTH ),
		);
	}

	/**
	 * Tells whether the active stylesheet differs from what the settings and files would build now.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when a build would change the stylesheet.
	 */
	public function is_stale(): bool {
		$state = $this->state();
		try {
			$plan = $this->plan();
			if ( ! $plan['custom'] ) {
				return 'package' !== $state['status'] && '' !== $state['files']['ltr'];
			}
			$fingerprint = $this->fingerprint( $plan );
		} catch ( Throwable $error ) {
			unset( $error );
			return true;
		}
		return 'ok' !== $state['status'] || ! $this->current( $state, $plan['line'], $fingerprint );
	}

	/**
	 * Builds the stylesheet when needed and returns the new state.
	 *
	 * @since 1.0.0
	 *
	 * @param string $reason Trigger, e.g. "settings", "cli", "cron", "notice".
	 * @param bool   $force  Whether to compile even with the same fingerprint.
	 * @return array{schema: int, status: string, line: int, fingerprint: string, files: array{ltr: string, rtl: string, root_vars: string}, theme_version: string, built_at: int, seconds: float, peak_bytes: int, error: string|null} State.
	 */
	public function build( string $reason, bool $force = false ): array {
		unset( $reason );
		$previous = $this->state();
		$line     = Bootstrap_Line::instance()->active();
		try {
			$plan = $this->plan();
			if ( ! $plan['custom'] ) {
				$this->delete_files( array() );
				return $this->save( self::package_state( $line ) );
			}
			$target = self::target();
			if ( null === $target ) {
				return $this->fail( $previous, 'The uploads folder is not available.', 0.0, 0 );
			}
			$compiler    = ( $this->compilers )( $line );
			$fingerprint = $this->fingerprint( $plan, $compiler );
			if ( ! $force && $this->current( $previous, $line, $fingerprint ) ) {
				if ( 'ok' === $previous['status'] ) {
					return $previous;
				}
				return $this->save(
					array_merge(
						$previous,
						array(
							'status' => 'ok',
							'error'  => null,
						)
					)
				);
			}
			return $this->compile( $compiler, $plan, $fingerprint, $target, $previous );
		} catch ( Throwable $error ) {
			// An Error of the compiler (TypeError, ValueError) must not stop the request that saved the settings.
			return $this->fail( $previous, $error->getMessage(), 0.0, 0 );
		}
	}

	/**
	 * Compiles the stylesheet and the root custom properties and stores the state.
	 *
	 * @since 1.0.0
	 *
	 * @param Stylesheet_Compiler_Interface                                                                                                                                                                                            $compiler    Compiler of the line.
	 * @param array{line: int, custom: bool, variables: array<string, Scss_Value>, import_paths: array<int, string>, child_version: string|null, directions: array<int, string>}                                                       $plan Plan.
	 * @param string                                                                                                                                                                                                                   $fingerprint Fingerprint.
	 * @param array{dir: string, url: string}                                                                                                                                                                                          $target    Target folder and URL.
	 * @param array{schema: int, status: string, line: int, fingerprint: string, files: array{ltr: string, rtl: string, root_vars: string}, theme_version: string, built_at: int, seconds: float, peak_bytes: int, error: string|null} $previous Previous state.
	 * @return array{schema: int, status: string, line: int, fingerprint: string, files: array{ltr: string, rtl: string, root_vars: string}, theme_version: string, built_at: int, seconds: float, peak_bytes: int, error: string|null} State.
	 * @throws RuntimeException When the compiler cannot extract the root custom properties.
	 * @throws Throwable        When extracting or writing the root custom properties fails; the new files are removed first.
	 */
	private function compile( Stylesheet_Compiler_Interface $compiler, array $plan, string $fingerprint, array $target, array $previous ): array {
		if ( ! method_exists( $compiler, 'root_vars' ) ) {
			throw new RuntimeException( 'The compiler of this line cannot extract the root custom properties.' );
		}
		$fp12 = substr( $fingerprint, 0, Stylesheet_Locator::FINGERPRINT_LENGTH );
		wp_raise_memory_limit( self::MEMORY_CONTEXT );
		$result = $compiler->compile(
			new Compile_Request(
				get_template_directory() . '/assets/scss/theme.scss',
				$plan['import_paths'],
				$plan['variables'],
				$target['dir'],
				'theme-' . $fp12,
				$plan['directions']
			)
		);
		$rtl    = in_array( 'rtl', $plan['directions'], true );
		if ( ! $result->ok || ! isset( $result->files['ltr'] ) || ( $rtl && ! isset( $result->files['rtl'] ) ) ) {
			return $this->fail( $previous, $result->error ?? 'The compiler wrote no stylesheet.', $result->seconds, $result->peak_bytes );
		}
		$main  = 'theme-' . $fp12 . '.min.css';
		$right = $rtl ? 'theme-' . $fp12 . '-rtl.min.css' : '';
		$vars  = 'root-vars-' . $fp12 . '.min.css';
		try {
			$root = $compiler->root_vars( self::read( $result->files['ltr'] ) );
			Atomic_File_Writer::write( $target['dir'] . '/' . $vars, is_string( $root ) ? $root : '' );
		} catch ( Throwable $error ) {
			// The previous files stay active; the new stylesheets of this build would be orphans until the next cleanup.
			$active = 'package' === $previous['status'] ? array() : array_values( $previous['files'] );
			foreach ( array_filter( array( $main, $right, $vars ) ) as $name ) {
				if ( ! in_array( $name, $active, true ) ) {
					wp_delete_file( $target['dir'] . '/' . $name );
				}
			}
			throw $error;
		}

		$state = array(
			'schema'        => self::SCHEMA,
			'status'        => 'ok',
			'line'          => $plan['line'],
			'fingerprint'   => $fingerprint,
			'files'         => array(
				'ltr'       => $main,
				'rtl'       => $right,
				'root_vars' => $vars,
			),
			'theme_version' => Stylesheet_Locator::theme_version(),
			'built_at'      => time(),
			'seconds'       => $result->seconds,
			'peak_bytes'    => $result->peak_bytes,
			'error'         => null,
		);
		$this->save( $state );
		$keep = array( $main, $right, $vars );
		if ( 'package' !== $previous['status'] ) {
			$keep = array_merge( $keep, array_values( $previous['files'] ) );
		}
		$this->delete_files( array_values( array_filter( $keep ) ) );
		return $state;
	}

	/**
	 * Collects what a build compiles: line, variables, import paths, directions and whether anything differs from the package.
	 *
	 * @since 1.0.0
	 *
	 * @return array{line: int, custom: bool, variables: array<string, Scss_Value>, import_paths: array<int, string>, child_version: string|null, directions: array<int, string>} Plan.
	 * @throws \InvalidArgumentException When a value is no valid SCSS value.
	 */
	private function plan(): array {
		$line      = Bootstrap_Line::instance()->active();
		$registry  = Registry::instance();
		$settings  = Settings::instance();
		$catalog   = Font_Catalog::instance();
		$variables = array();
		$custom    = false;
		foreach ( Token_Map::for_line( $line ) as $key => $token ) {
			$definition = $registry->get( $key );
			if ( 'font' === $definition->type ) {
				$family = $catalog->for_setting( $key );
				$custom = $custom || ( null === $family ? 'inherit' : $family->slug ) !== $definition->default;
				if ( null !== $family ) {
					$variables[ $token['variable'] ] = Scss_Value::font_stack( $family->stack );
				}
				continue;
			}
			$value                           = $settings->get( $key );
			$custom                          = $custom || $value !== $definition->default;
			$variables[ $token['variable'] ] = Scss_Value::color( is_string( $value ) ? $value : '' );
		}

		$theme = get_template_directory();
		$child = $this->child_scss_dir( $line );
		$paths = array( $theme . '/assets/scss', $theme . '/' . self::CHILD_DEFAULTS_DIR, $theme . '/assets/vendor' );
		if ( null !== $child ) {
			array_unshift( $paths, $child );
		}
		return array(
			'line'          => $line,
			'custom'        => $custom || ( null !== $child && self::has_styles( $child ) ),
			'variables'     => $variables,
			'import_paths'  => $paths,
			'child_version' => null === $child ? null : self::child_version( dirname( $child, 2 ) ),
			'directions'    => array() === Language::instance()->rtl() ? array( 'ltr' ) : self::DIRECTIONS,
		);
	}

	/**
	 * Computes the fingerprint of a plan.
	 *
	 * @since 1.0.0
	 *
	 * @param array{line: int, custom: bool, variables: array<string, Scss_Value>, import_paths: array<int, string>, child_version: string|null, directions: array<int, string>} $plan Plan.
	 * @param Stylesheet_Compiler_Interface|null                                                                                                                                 $compiler Compiler; null gets the one of the line.
	 * @return string Fingerprint, 64 hex digits.
	 * @throws \InvalidArgumentException When a source file cannot be read.
	 */
	private function fingerprint( array $plan, ?Stylesheet_Compiler_Interface $compiler = null ): string {
		$compiler = $compiler ?? ( $this->compilers )( $plan['line'] );
		$entry    = get_template_directory() . '/assets/scss/theme.scss';
		$sources  = Fingerprint::scss_files( $plan['import_paths'] );
		if ( is_file( $entry ) && ! in_array( $entry, $sources, true ) ) {
			$sources[] = $entry;
		}
		return Fingerprint::compute( $plan['line'], $compiler->versions(), $sources, $plan['variables'], $plan['directions'], Stylesheet_Locator::theme_version(), $plan['child_version'] );
	}

	/**
	 * Tells whether a state holds the build of a fingerprint with its stylesheets in place.
	 *
	 * @since 1.0.0
	 *
	 * @param array{schema: int, status: string, line: int, fingerprint: string, files: array{ltr: string, rtl: string, root_vars: string}, theme_version: string, built_at: int, seconds: float, peak_bytes: int, error: string|null} $state State.
	 * @param int                                                                                                                                                                                                                      $line        Active line.
	 * @param string                                                                                                                                                                                                                   $fingerprint Fingerprint.
	 * @return bool True when nothing needs to be built.
	 */
	private function current( array $state, int $line, string $fingerprint ): bool {
		$target = self::target();
		return 'package' !== $state['status'] && $state['line'] === $line && $state['fingerprint'] === $fingerprint
			&& '' !== $state['files']['ltr'] && null !== $target && is_file( $target['dir'] . '/' . $state['files']['ltr'] )
			&& ( '' === $state['files']['rtl'] || is_file( $target['dir'] . '/' . $state['files']['rtl'] ) );
	}

	/**
	 * Returns the child SCSS folder when the child theme declares the line.
	 *
	 * @since 1.0.0
	 *
	 * @param int $line Active line.
	 * @return string|null Absolute path, or null without a child, without the line or without the folder.
	 */
	private function child_scss_dir( int $line ): ?string {
		$child = Child_Theme::instance();
		$dir   = $child->dir();
		if ( null === $dir || ! in_array( $line, $child->lines(), true ) || ! is_dir( $dir . '/assets/scss' ) ) {
			return null;
		}
		return $dir . '/assets/scss';
	}

	/**
	 * Tells whether a folder holds SCSS with more than comments.
	 *
	 * @since 1.0.0
	 *
	 * @param string $dir Folder.
	 * @return bool True when one file has code.
	 */
	private static function has_styles( string $dir ): bool {
		foreach ( Fingerprint::scss_files( array( $dir ) ) as $file ) {
			$code = preg_replace( array( '~/\*.*?\*/~s', '~//[^\n]*~' ), '', self::read( $file ) );
			if ( '' !== trim( (string) $code ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Reads a local file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file Absolute path.
	 * @return string Content; empty when the file cannot be read.
	 */
	private static function read( string $file ): string {
		$lines = is_file( $file ) ? file( $file ) : false;
		return false === $lines ? '' : implode( '', $lines );
	}

	/**
	 * Returns the Version header of the child theme.
	 *
	 * @since 1.0.0
	 *
	 * @param string $dir Child theme folder.
	 * @return string Version; empty when missing.
	 */
	private static function child_version( string $dir ): string {
		$file   = $dir . '/style.css';
		$values = is_file( $file ) ? get_file_data( $file, array( 'version' => 'Version' ) ) : array();
		return is_string( $values['version'] ?? null ) ? $values['version'] : '';
	}

	/**
	 * Returns the target folder and its URL.
	 *
	 * @since 1.0.0
	 *
	 * @return array{dir: string, url: string}|null Folder and URL without trailing slash; null when the uploads folder is not available.
	 */
	private static function target(): ?array {
		$uploads = wp_upload_dir( null, false );
		if ( false !== $uploads['error'] || '' === $uploads['basedir'] ) {
			return null;
		}
		return array(
			'dir' => rtrim( $uploads['basedir'], '/' ) . '/' . self::UPLOADS_SUBDIR,
			'url' => rtrim( $uploads['baseurl'], '/' ) . '/' . self::UPLOADS_SUBDIR,
		);
	}

	/**
	 * Deletes own stylesheets in the target folder, except those to keep and those younger than CLEANUP_GRACE; schedules a follow-up for the young ones.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>      $keep  File names to keep.
	 * @param array<int, string>|null $names File names to look at; null for every own file in the folder.
	 * @return void
	 */
	private function delete_files( array $keep, ?array $names = null ): void {
		$target = self::target();
		if ( null === $target ) {
			return;
		}
		if ( null === $names ) {
			$files = glob( $target['dir'] . '/*.min.css' );
			$names = array_map( 'basename', false === $files ? array() : $files );
		}
		$young  = time() - self::CLEANUP_GRACE;
		$spared = array();
		foreach ( $names as $name ) {
			$file = $target['dir'] . '/' . $name;
			if ( 1 !== preg_match( self::FILE_PATTERN, $name ) || in_array( $name, $keep, true ) || ! is_file( $file ) ) {
				continue;
			}
			$modified = filemtime( $file );
			if ( false === $modified || $modified <= $young ) {
				wp_delete_file( $file );
			} else {
				$spared[] = $name;
			}
		}
		if ( array() !== $spared ) {
			sort( $spared );
			$args = array( 'cleanup', $spared );
			if ( false === wp_next_scheduled( self::CRON_HOOK, $args ) ) {
				wp_schedule_single_event( time() + self::CLEANUP_GRACE + 1, self::CRON_HOOK, $args );
			}
		}
	}

	/**
	 * Follow-up of a cleanup: deletes the spared files that are no longer young, unless the stored state uses them.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $names File names the cleanup spared.
	 * @return void
	 */
	private function follow_up( array $names ): void {
		$state = $this->state();
		$keep  = 'package' === $state['status'] ? array() : array_values( $state['files'] );
		$this->delete_files( $keep, array_values( array_filter( $names, 'is_string' ) ) );
	}

	/**
	 * Stores a failure: the previous files stay active.
	 *
	 * @since 1.0.0
	 *
	 * @param array{schema: int, status: string, line: int, fingerprint: string, files: array{ltr: string, rtl: string, root_vars: string}, theme_version: string, built_at: int, seconds: float, peak_bytes: int, error: string|null} $previous Previous state.
	 * @param string                                                                                                                                                                                                                   $error      Error message.
	 * @param float                                                                                                                                                                                                                    $seconds    Seconds of the attempt.
	 * @param int                                                                                                                                                                                                                      $peak_bytes Peak memory of the attempt.
	 * @return array{schema: int, status: string, line: int, fingerprint: string, files: array{ltr: string, rtl: string, root_vars: string}, theme_version: string, built_at: int, seconds: float, peak_bytes: int, error: string|null} State.
	 */
	private function fail( array $previous, string $error, float $seconds, int $peak_bytes ): array {
		return $this->save(
			array_merge(
				$previous,
				array(
					'status'     => 'failed',
					'built_at'   => time(),
					'seconds'    => $seconds,
					'peak_bytes' => $peak_bytes,
					'error'      => '' === $error ? 'Unknown error.' : $error,
				)
			)
		);
	}

	/**
	 * Writes the state, clears the stale check and fires creationell_wp_theme_css_built.
	 *
	 * @since 1.0.0
	 *
	 * @param array{schema: int, status: string, line: int, fingerprint: string, files: array{ltr: string, rtl: string, root_vars: string}, theme_version: string, built_at: int, seconds: float, peak_bytes: int, error: string|null} $state State.
	 * @return array{schema: int, status: string, line: int, fingerprint: string, files: array{ltr: string, rtl: string, root_vars: string}, theme_version: string, built_at: int, seconds: float, peak_bytes: int, error: string|null} The same state.
	 */
	private function save( array $state ): array {
		update_option( self::STATE_OPTION, $state, true );
		delete_transient( self::CHECK_TRANSIENT );

		/**
		 * Fires after a build of the individual stylesheet wrote its state.
		 *
		 * The status is "ok", "package" (the package stylesheet applies) or
		 * "failed" (the previous files stay active, "error" says why).
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $state State as stored in the option creationell_wp_theme_css_state.
		 */
		do_action( 'creationell_wp_theme_css_built', $state );
		return $state;
	}

	/**
	 * Returns the state of the package stylesheet.
	 *
	 * @since 1.0.0
	 *
	 * @param int $line Active line.
	 * @return array{schema: int, status: string, line: int, fingerprint: string, files: array{ltr: string, rtl: string, root_vars: string}, theme_version: string, built_at: int, seconds: float, peak_bytes: int, error: string|null} State.
	 */
	private static function package_state( int $line ): array {
		return array(
			'schema'        => self::SCHEMA,
			'status'        => 'package',
			'line'          => $line,
			'fingerprint'   => '',
			'files'         => array(
				'ltr'       => '',
				'rtl'       => '',
				'root_vars' => '',
			),
			'theme_version' => Stylesheet_Locator::theme_version(),
			'built_at'      => time(),
			'seconds'       => 0.0,
			'peak_bytes'    => 0,
			'error'         => null,
		);
	}

	/**
	 * Checks a stored file name.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $name File name.
	 * @return string The name, or an empty string when it is no own file name.
	 */
	private static function file_name( mixed $name ): string {
		return is_string( $name ) && 1 === preg_match( self::FILE_PATTERN, $name ) ? $name : '';
	}

	/**
	 * Reads and checks the stored state.
	 *
	 * @since 1.0.0
	 *
	 * @return array{schema: int, status: string, line: int, fingerprint: string, files: array{ltr: string, rtl: string, root_vars: string}, theme_version: string, built_at: int, seconds: float, peak_bytes: int, error: string|null}|null State, or null when missing or of another form.
	 */
	private static function stored(): ?array {
		$state = get_option( self::STATE_OPTION, null );
		if ( ! is_array( $state ) || self::SCHEMA !== ( $state['schema'] ?? null ) || ! in_array( $state['status'] ?? null, array( 'ok', 'package', 'failed' ), true ) ) {
			return null;
		}
		$files = is_array( $state['files'] ?? null ) ? $state['files'] : array();
		$names = array(
			'ltr'       => self::file_name( $files['ltr'] ?? null ),
			'rtl'       => self::file_name( $files['rtl'] ?? null ),
			'root_vars' => self::file_name( $files['root_vars'] ?? null ),
		);
		$error = $state['error'] ?? null;
		return array(
			'schema'        => self::SCHEMA,
			'status'        => (string) $state['status'],
			'line'          => is_int( $state['line'] ?? null ) ? $state['line'] : 0,
			'fingerprint'   => is_string( $state['fingerprint'] ?? null ) ? $state['fingerprint'] : '',
			'files'         => $names,
			'theme_version' => is_string( $state['theme_version'] ?? null ) ? $state['theme_version'] : '',
			'built_at'      => is_int( $state['built_at'] ?? null ) ? $state['built_at'] : 0,
			'seconds'       => is_float( $state['seconds'] ?? null ) || is_int( $state['seconds'] ?? null ) ? (float) $state['seconds'] : 0.0,
			'peak_bytes'    => is_int( $state['peak_bytes'] ?? null ) ? $state['peak_bytes'] : 0,
			'error'         => is_string( $error ) ? $error : null,
		);
	}
}
