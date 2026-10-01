<?php
/**
 * Gate of the theme modules.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules;

use Closure;
use Creationell\WpTheme\Admin\Notices;
use Creationell\WpTheme\Settings\Snapshot;
use Throwable;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Loads the modules that are effectively active or hidden, and only those.
 *
 * On after_setup_theme 20 the gate requires modules/<slug>/bootstrap.php of
 * each such module once, required modules first; the file sees $slug, $state
 * and $manifest. A module that is off loads and registers nothing; only its
 * settings.php, which the settings registry reads for every installed module.
 * The blocks of a hidden module leave the block inserter, and every loading
 * module with blocks gets its block texts translated. On init 10 the gate
 * starts a Blockstudio instance for the blocks/ folder of each loaded module,
 * when Blockstudio is loaded. The filter theme_block_pattern_files drops the
 * patterns below patterns/<slug>/ of every module that is not active, in the
 * parent and the child theme alike. An active older plugin that a module
 * replaces keeps the module off and queues a notice for administrators.
 *
 * A switch takes effect with the next request: a loaded bootstrap.php cannot
 * be unloaded. The module state is flushed when the settings snapshot is
 * rebuilt.
 *
 * @since 1.0.0
 */
final class Module_Gate {

	/**
	 * Priority on after_setup_theme: after the theme setup on 2, before init.
	 *
	 * @since 1.0.0
	 */
	public const SETUP_PRIORITY = 20;

	/**
	 * Namespace of the blocks of the theme modules.
	 *
	 * @since 1.0.0
	 */
	public const BLOCK_NAMESPACE = 'creationell-theme';

	/**
	 * Class that starts a Blockstudio instance.
	 *
	 * @since 1.0.0
	 */
	public const BLOCKSTUDIO_CLASS = 'Blockstudio\\Build';

	/**
	 * Shared gate.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Module state.
	 *
	 * @var Module_State
	 */
	private Module_State $state;

	/**
	 * Tells whether Blockstudio is loaded.
	 *
	 * @var Closure
	 * @phpstan-var Closure(): bool
	 */
	private Closure $blockstudio;

	/**
	 * Starts a Blockstudio instance for a blocks folder.
	 *
	 * @var Closure
	 * @phpstan-var Closure(string): void
	 */
	private Closure $blockstudio_init;

	/**
	 * Whether load_modules() ran.
	 *
	 * @var bool
	 */
	private bool $modules_loaded = false;

	/**
	 * Whether init_blocks() ran.
	 *
	 * @var bool
	 */
	private bool $blocks_started = false;

	/**
	 * Loaded modules: effective state by slug, in load order.
	 *
	 * @var array<string, string>
	 */
	private array $loaded = array();

	/**
	 * Takes the module state and the Blockstudio probe and starter; tests pass doubles.
	 *
	 * @since 1.0.0
	 *
	 * @param Module_State $state            Module state.
	 * @param Closure|null $blockstudio      Tells whether Blockstudio is loaded; null checks the class Blockstudio\Build.
	 * @param Closure|null $blockstudio_init Starts a Blockstudio instance for a folder; null calls Blockstudio\Build::init().
	 * @phpstan-param (Closure(): bool)|null $blockstudio
	 * @phpstan-param (Closure(string): void)|null $blockstudio_init
	 */
	public function __construct( Module_State $state, ?Closure $blockstudio = null, ?Closure $blockstudio_init = null ) {
		$this->state            = $state;
		$this->blockstudio      = $blockstudio ?? static fn(): bool => class_exists( self::BLOCKSTUDIO_CLASS );
		$this->blockstudio_init = $blockstudio_init ?? static function ( string $dir ): void {
			$init = array( self::BLOCKSTUDIO_CLASS, 'init' );
			if ( is_callable( $init ) ) {
				call_user_func( $init, array( 'dir' => $dir ) );
			}
		};
	}

	/**
	 * Returns the shared gate over the shared module state.
	 *
	 * @since 1.0.0
	 *
	 * @return self Gate.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self( Module_State::instance() );
		}
		return self::$instance;
	}

	/**
	 * Replaces the shared gate; null builds a new one on the next call of instance().
	 *
	 * @since 1.0.0
	 *
	 * @param self|null $gate Gate.
	 * @return void
	 */
	public static function set_instance( ?self $gate ): void {
		self::$instance = $gate;
	}

	/**
	 * Registers the hooks of the gate; Theme::boot() calls it.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_action( 'after_setup_theme', array( self::class, 'setup' ), self::SETUP_PRIORITY, 0 );
		add_action( 'init', array( self::class, 'init' ), 10, 0 );
		add_filter( 'theme_block_pattern_files', array( self::class, 'filter_pattern_files' ), 10, 1 );
		add_action( Snapshot::REBUILT_ACTION, array( self::class, 'flush' ), 10, 0 );
		Inserter_Visibility::register_hooks();
	}

	/**
	 * Loads the modules through the shared gate; runs on after_setup_theme.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function setup(): void {
		self::instance()->load_modules();
	}

	/**
	 * Starts the Blockstudio instances through the shared gate; runs on init.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function init(): void {
		self::instance()->init_blocks();
	}

	/**
	 * Drops the pattern files of modules that are not active; runs on theme_block_pattern_files.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $files Pattern files by path relative to the patterns folder.
	 * @return mixed Remaining files.
	 */
	public static function filter_pattern_files( mixed $files ): mixed {
		return self::instance()->pattern_files( $files );
	}

	/**
	 * Forgets the cached module states; runs when the settings snapshot is rebuilt.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function flush(): void {
		self::instance()->state->flush();
	}

	/**
	 * Returns the module state of the gate.
	 *
	 * @since 1.0.0
	 *
	 * @return Module_State Module state.
	 */
	public function state(): Module_State {
		return $this->state;
	}

	/**
	 * Returns the loaded modules.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Effective state by slug, in load order.
	 */
	public function loaded(): array {
		return $this->loaded;
	}

	/**
	 * Loads every installed module that is effectively active or hidden, once per gate.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function load_modules(): void {
		if ( $this->modules_loaded ) {
			return;
		}
		$this->modules_loaded = true;
		$catalog              = $this->state->catalog();
		foreach ( array_keys( $catalog->installed() ) as $slug ) {
			$this->queue_legacy_notice( $slug );
		}
		$done = array();
		foreach ( array_keys( $catalog->installed() ) as $slug ) {
			$this->load( $slug, $done );
		}
	}

	/**
	 * Starts a Blockstudio instance for the blocks/ folder of each loaded module, once per gate.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Blocks folders passed to Blockstudio; empty without Blockstudio or on a repeated call.
	 */
	public function init_blocks(): array {
		if ( $this->blocks_started ) {
			return array();
		}
		$this->blocks_started = true;
		if ( ! ( $this->blockstudio )() ) {
			return array();
		}
		$started = array();
		foreach ( array_keys( $this->loaded ) as $slug ) {
			$manifest = $this->state->catalog()->manifest( $slug );
			$dir      = null === $manifest ? '' : $manifest->dir . '/blocks';
			if ( '' !== $dir && is_dir( $dir ) ) {
				( $this->blockstudio_init )( $dir );
				$started[] = $dir;
			}
		}
		return $started;
	}

	/**
	 * Drops the pattern files below <slug>/ of every known module that is not effectively active.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $files Pattern files by path relative to the patterns folder.
	 * @return mixed Remaining files; values other than an array unchanged.
	 */
	public function pattern_files( mixed $files ): mixed {
		if ( ! is_array( $files ) || array() === $files ) {
			return $files;
		}
		$prefixes = array();
		foreach ( $this->state->catalog()->slugs() as $slug ) {
			if ( 'active' !== $this->state->state( $slug ) ) {
				$prefixes[] = $slug . '/';
			}
		}
		foreach ( array_keys( $files ) as $path ) {
			foreach ( $prefixes as $prefix ) {
				if ( str_starts_with( (string) $path, $prefix ) ) {
					unset( $files[ $path ] );
					break;
				}
			}
		}
		return $files;
	}

	/**
	 * Loads one module after its required modules.
	 *
	 * The effective state is "off" for a module whose required modules are off
	 * or lead back to it, so the recursion follows only modules that load and
	 * ends.
	 *
	 * @since 1.0.0
	 *
	 * @param string              $slug Slug.
	 * @param array<string, true> $done Slugs already handled; the slug is added.
	 * @return void
	 */
	private function load( string $slug, array &$done ): void {
		if ( isset( $done[ $slug ] ) ) {
			return;
		}
		$done[ $slug ] = true;
		$manifest      = $this->state->catalog()->manifest( $slug );
		$state         = $this->state->state( $slug );
		if ( null === $manifest || 'off' === $state ) {
			return;
		}
		foreach ( $manifest->requires['modules'] as $required ) {
			$this->load( $required, $done );
		}
		if ( 'hidden' === $state ) {
			foreach ( $manifest->blocks as $block ) {
				Inserter_Visibility::hide( $block );
			}
		}
		if ( array() !== $manifest->blocks ) {
			Block_I18n::register_namespace( self::BLOCK_NAMESPACE );
		}
		// The bootstrap file sees $slug, $state and $manifest.
		$file = $manifest->dir . '/bootstrap.php';
		if ( is_file( $file ) ) {
			try {
				( static function () use ( $file, $slug, $state, $manifest ): void {
					require $file;
				} )();
			} catch ( Throwable $error ) {
				$this->queue_failure_notice( $slug, $error->getMessage() );
				return;
			}
		}
		$this->loaded[ $slug ] = $state;
	}

	/**
	 * Queues a notice when an active older plugin keeps a module off.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Slug.
	 * @return void
	 */
	private function queue_legacy_notice( string $slug ): void {
		$reason = $this->state->reason( $slug );
		if ( ! str_starts_with( $reason, 'legacy_plugin:' ) ) {
			return;
		}
		$plugin  = substr( $reason, strlen( 'legacy_plugin:' ) );
		$catalog = $this->state->catalog();
		Notices::add(
			'module-legacy-' . $slug,
			static fn(): string => sprintf(
				/* translators: 1: module title, 2: plugin file such as bs-grid/main.php. */
				__( 'The theme module %1$s stays off while the plugin %2$s is active, which the module replaces. Deactivate the plugin to use the module.', 'creationell-wp-theme' ),
				$catalog->title( $slug ),
				$plugin
			),
			Notices::WARNING
		);
	}

	/**
	 * Queues an error notice for a module whose bootstrap.php failed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug    Slug.
	 * @param string $message Message of the error.
	 * @return void
	 */
	private function queue_failure_notice( string $slug, string $message ): void {
		$catalog = $this->state->catalog();
		Notices::add(
			'module-failed-' . $slug,
			static fn(): string => sprintf(
				/* translators: 1: module title, 2: error message in English. */
				__( 'The theme module %1$s failed to load and stays off for this request: %2$s', 'creationell-wp-theme' ),
				$catalog->title( $slug ),
				$message
			),
			Notices::ERROR
		);
	}
}
