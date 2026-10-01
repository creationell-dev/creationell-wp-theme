<?php
/**
 * Catalog of the theme modules.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules;

use Closure;
use InvalidArgumentException;
use Throwable;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Knows the reserved module slugs and finds the installed modules in a modules folder.
 *
 * A module is installed when modules/<slug>/module.php returns a valid manifest.
 * Folders without module.php do not count; folders with an invalid manifest are
 * listed as errors. The reserved slugs of the catalog are known without a folder.
 * A second folder, the test source, may add modules; on a slug collision the
 * modules folder of the theme wins. The shared catalog takes the test source
 * from the constant CREATIONELL_WP_THEME_TEST_MODULES_DIR, only in the local
 * environment and only when the folder exists. The folders are scanned once per
 * catalog, when first needed.
 *
 * @since 1.0.0
 */
final class Module_Catalog {

	/**
	 * Reserved modules of the catalog with their states, in catalog order.
	 *
	 * @since 1.0.0
	 */
	public const RESERVED = array(
		'post-lists'     => array( 'active', 'hidden', 'off' ),
		'post-slider'    => array( 'active', 'hidden', 'off' ),
		'related-posts'  => array( 'active', 'hidden', 'off' ),
		'contact-form-7' => array( 'active', 'off' ),
		'consent'        => array( 'active', 'off' ),
		'header-footer'  => array( 'active', 'off' ),
		'woocommerce'    => array( 'active', 'off' ),
	);

	/**
	 * Constant that names the folder of the test modules; read only in the local environment.
	 *
	 * @since 1.0.0
	 */
	public const TEST_DIR_CONSTANT = 'CREATIONELL_WP_THEME_TEST_MODULES_DIR';

	/**
	 * Shared catalog of the theme folder.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Absolute path of the modules folder, without trailing slash.
	 *
	 * @var string
	 */
	private string $dir;

	/**
	 * Absolute path of the folder with test modules, without trailing slash; null without test source.
	 *
	 * @var string|null
	 */
	private ?string $test_dir;

	/**
	 * Installed modules by slug, sorted by slug; null before the scan.
	 *
	 * @var array<string, Module_Manifest>|null
	 */
	private ?array $installed = null;

	/**
	 * Messages of invalid manifests by slug, sorted by slug.
	 *
	 * @var array<string, string>
	 */
	private array $errors = array();

	/**
	 * Takes the modules folder and the test source; tests pass fixture folders.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $modules_dir Absolute path of the modules folder.
	 * @param string|null $test_dir    Absolute path of the folder with test modules; null for none.
	 */
	public function __construct( string $modules_dir, ?string $test_dir = null ) {
		$this->dir      = rtrim( $modules_dir, '/' );
		$this->test_dir = null === $test_dir ? null : rtrim( $test_dir, '/' );
	}

	/**
	 * Returns the shared catalog of modules/ in the parent theme, with the test source in the local environment.
	 *
	 * @since 1.0.0
	 *
	 * @return self Catalog.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			$test_dir = null;
			if ( defined( self::TEST_DIR_CONSTANT ) ) {
				$test_dir = self::test_source( constant( self::TEST_DIR_CONSTANT ), wp_get_environment_type() );
			}
			self::$instance = new self( CREATIONELL_WP_THEME_DIR . '/modules', $test_dir );
		}
		return self::$instance;
	}

	/**
	 * Returns the folder of the test modules, if it may be used.
	 *
	 * The test source counts only in the environment type "local" and only for
	 * an existing folder, so a release never reads it.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $constant    Value of CREATIONELL_WP_THEME_TEST_MODULES_DIR; null when undefined.
	 * @param string $environment Environment type as wp_get_environment_type() returns it.
	 * @return string|null Absolute path without trailing slash, or null.
	 */
	public static function test_source( mixed $constant, string $environment ): ?string {
		if ( 'local' !== $environment || ! is_string( $constant ) || '' === $constant ) {
			return null;
		}
		$dir = rtrim( $constant, '/' );
		return '' !== $dir && is_dir( $dir ) ? $dir : null;
	}

	/**
	 * Replaces the shared catalog; null builds a new one on the next call of instance().
	 *
	 * @since 1.0.0
	 *
	 * @param self|null $catalog Catalog.
	 * @return void
	 */
	public static function set_instance( ?self $catalog ): void {
		self::$instance = $catalog;
	}

	/**
	 * Returns the title closure of a reserved module.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Reserved slug.
	 * @return Closure Returns the translated title; the slug for other slugs.
	 * @phpstan-return Closure(): string
	 */
	public static function reserved_title( string $slug ): Closure {
		return match ( $slug ) {
			'post-lists'     => static fn(): string => __( 'Post lists', 'creationell-wp-theme' ),
			'post-slider'    => static fn(): string => __( 'Post slider', 'creationell-wp-theme' ),
			'related-posts'  => static fn(): string => __( 'Related posts', 'creationell-wp-theme' ),
			'contact-form-7' => static fn(): string => __( 'Contact Form 7', 'creationell-wp-theme' ),
			'consent'        => static fn(): string => __( 'Consent', 'creationell-wp-theme' ),
			'header-footer'  => static fn(): string => __( 'Header and footer', 'creationell-wp-theme' ),
			'woocommerce'    => static fn(): string => __( 'WooCommerce', 'creationell-wp-theme' ),
			default          => static fn(): string => $slug,
		};
	}

	/**
	 * Returns the modules folder.
	 *
	 * @since 1.0.0
	 *
	 * @return string Absolute path without trailing slash.
	 */
	public function dir(): string {
		return $this->dir;
	}

	/**
	 * Returns the folder of the test modules.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null Absolute path without trailing slash; null without test source.
	 */
	public function test_dir(): ?string {
		return $this->test_dir;
	}

	/**
	 * Returns the installed modules.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, Module_Manifest> Manifests by slug, sorted by slug.
	 */
	public function installed(): array {
		if ( null === $this->installed ) {
			$this->scan();
		}
		return $this->installed ?? array();
	}

	/**
	 * Returns the messages of the invalid manifests.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Messages by folder name, sorted.
	 */
	public function errors(): array {
		$this->installed();
		return $this->errors;
	}

	/**
	 * Returns all known slugs: the reserved ones in catalog order, then the others sorted.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Slugs.
	 */
	public function slugs(): array {
		$others = array_diff( array_merge( array_keys( $this->installed() ), array_keys( $this->errors ) ), array_keys( self::RESERVED ) );
		sort( $others, SORT_STRING );
		return array_merge( array_keys( self::RESERVED ), array_values( array_unique( $others ) ) );
	}

	/**
	 * Returns the manifest of an installed module.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Slug.
	 * @return Module_Manifest|null Manifest, or null when the module is not installed.
	 */
	public function manifest( string $slug ): ?Module_Manifest {
		return $this->installed()[ $slug ] ?? null;
	}

	/**
	 * Tells whether a module is installed with a valid manifest.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Slug.
	 * @return bool True when installed.
	 */
	public function is_installed( string $slug ): bool {
		return isset( $this->installed()[ $slug ] );
	}

	/**
	 * Tells whether a slug is reserved by the catalog.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Slug.
	 * @return bool True when reserved.
	 */
	public function is_reserved( string $slug ): bool {
		return isset( self::RESERVED[ $slug ] );
	}

	/**
	 * Tells whether a slug is reserved, installed or has a folder with an invalid manifest.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Slug.
	 * @return bool True when known.
	 */
	public function is_known( string $slug ): bool {
		return $this->is_reserved( $slug ) || $this->is_installed( $slug ) || isset( $this->errors[ $slug ] );
	}

	/**
	 * Returns the states a module knows.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Slug.
	 * @return array<int, string> States in canonical order; empty for unknown slugs and invalid manifests.
	 */
	public function states( string $slug ): array {
		$manifest = $this->manifest( $slug );
		if ( null !== $manifest ) {
			return $manifest->states;
		}
		return self::RESERVED[ $slug ] ?? array();
	}

	/**
	 * Returns the translated title of a module; call it after init.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Slug.
	 * @return string Title; the slug for modules without title.
	 */
	public function title( string $slug ): string {
		$manifest = $this->manifest( $slug );
		if ( null !== $manifest ) {
			return $manifest->title_text();
		}
		$title = ( self::reserved_title( $slug ) )();
		return is_string( $title ) && '' !== $title ? $title : $slug;
	}

	/**
	 * Returns the translated description of a module; call it after init.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Slug.
	 * @return string Description; empty for a module without description and for one that is not installed.
	 */
	public function description( string $slug ): string {
		return $this->manifest( $slug )?->description_text() ?? '';
	}

	/**
	 * Reads module.php of every subfolder, first in the modules folder, then in the test source.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function scan(): void {
		$installed = array();
		$errors    = array();
		self::scan_dir( $this->dir, $installed, $errors );
		if ( null !== $this->test_dir ) {
			self::scan_dir( $this->test_dir, $installed, $errors );
		}
		ksort( $installed, SORT_STRING );
		ksort( $errors, SORT_STRING );
		$this->installed = $installed;
		$this->errors    = $errors;
	}

	/**
	 * Reads module.php of every subfolder of one folder; slugs found before are skipped.
	 *
	 * @since 1.0.0
	 *
	 * @param string                         $dir       Absolute path of the folder.
	 * @param array<string, Module_Manifest> $installed Installed modules by slug; found modules are added.
	 * @param array<string, string>          $errors    Messages of invalid manifests by slug; found errors are added.
	 * @return void
	 */
	private static function scan_dir( string $dir, array &$installed, array &$errors ): void {
		$files = is_dir( $dir ) ? glob( $dir . '/*/module.php' ) : array();
		foreach ( false === $files ? array() : $files as $file ) {
			$slug = basename( dirname( $file ) );
			if ( isset( $installed[ $slug ] ) || isset( $errors[ $slug ] ) ) {
				continue;
			}
			try {
				$installed[ $slug ] = Module_Manifest::from_file( $file );
			} catch ( InvalidArgumentException $exception ) {
				$errors[ $slug ] = $exception->getMessage();
			} catch ( Throwable $error ) {
				$errors[ $slug ] = sprintf( 'modules/%1$s/module.php failed: %2$s', $slug, $error->getMessage() );
			}
		}
	}
}
