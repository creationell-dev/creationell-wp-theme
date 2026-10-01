<?php
/**
 * Manifest of a theme module.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules;

use Closure;
use InvalidArgumentException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Reads and validates modules/<slug>/module.php, which returns the manifest as an array.
 *
 * Fields:
 * - slug (required): lower case letters, digits and hyphens; equals the folder name.
 * - title (required): closure that returns the translated title.
 * - description: closure that returns a translated sentence about what the module does.
 * - type (required): "block" (brings blocks) or "integration".
 * - states (required): "active" and "off", optionally "hidden".
 * - since (required): theme version X.Y.Z that added the module.
 * - default: "off", the only allowed default of a module.
 * - requires: plugins (plugin files such as "dir/file.php"), modules (slugs),
 *   blockstudio (minimum version as a string, or false).
 * - blocks: full block names "creationell-theme/<name>"; required for the type block, empty otherwise.
 * - outposts: paths outside the module, the folders "patterns/<slug>/" and
 *   "template-parts/<slug>/" or template part files "parts/<part>.html", which
 *   WordPress reads from the parts folder of the theme only.
 * - legacy_plugins: plugin files of older plugins the module replaces.
 * - warn_before_off: whether switching off asks for confirmation.
 * - off_warning: closure that returns the translated warning shown before switching off.
 * - on_change: closure called with the old and the new state after a switch.
 *
 * Reading a manifest translates nothing: title, description and texts stay closures.
 *
 * @since 1.0.0
 */
final class Module_Manifest {

	/**
	 * Module states in canonical order.
	 *
	 * @since 1.0.0
	 */
	public const STATES = array( 'active', 'hidden', 'off' );

	/**
	 * Module types.
	 *
	 * @since 1.0.0
	 */
	public const TYPES = array( 'block', 'integration' );

	/**
	 * Allowed fields of a manifest.
	 *
	 * @since 1.0.0
	 */
	public const FIELDS = array( 'slug', 'title', 'description', 'type', 'states', 'default', 'requires', 'blocks', 'outposts', 'since', 'legacy_plugins', 'warn_before_off', 'off_warning', 'on_change' );

	/**
	 * Pattern of a module slug.
	 *
	 * @since 1.0.0
	 */
	public const SLUG_PATTERN = '~^[a-z0-9]+(?:-[a-z0-9]+)*$~';

	/**
	 * Pattern of a plugin file relative to the plugins folder.
	 *
	 * @since 1.0.0
	 */
	private const PLUGIN_PATTERN = '~^[A-Za-z0-9._-]+/[A-Za-z0-9._-]+\.php$~';

	/**
	 * Default state of the module, always "off".
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public readonly string $default;

	/**
	 * Builds a validated manifest; use from_file() or from_array().
	 *
	 * @since 1.0.0
	 *
	 * @param string                                                                                     $slug            Slug.
	 * @param Closure                                                                                    $title           Returns the translated title.
	 * @param string                                                                                     $type            Type.
	 * @param array<int, string>                                                                         $states          States in canonical order.
	 * @param string                                                                                     $default_state   Default state, "off".
	 * @param array{plugins: array<int, string>, modules: array<int, string>, blockstudio: string|false} $requires Requirements.
	 * @param array<int, string>                                                                         $blocks          Block names.
	 * @param array<int, string>                                                                         $outposts        Folders and template part files outside the module.
	 * @param string                                                                                     $since           Theme version that added the module.
	 * @param array<int, string>                                                                         $legacy_plugins  Plugin files the module replaces.
	 * @param bool                                                                                       $warn_before_off Whether switching off asks first.
	 * @param Closure|null                                                                               $off_warning     Returns the warning before switching off.
	 * @param Closure|null                                                                               $on_change       Called with the old and the new state.
	 * @param string                                                                                     $dir             Absolute path of the module folder.
	 * @param Closure|null                                                                               $description     Returns the translated description.
	 * @phpstan-param Closure(): string $title
	 */
	private function __construct(
		public readonly string $slug,
		public readonly Closure $title,
		public readonly string $type,
		public readonly array $states,
		string $default_state,
		public readonly array $requires,
		public readonly array $blocks,
		public readonly array $outposts,
		public readonly string $since,
		public readonly array $legacy_plugins,
		public readonly bool $warn_before_off,
		public readonly ?Closure $off_warning,
		public readonly ?Closure $on_change,
		public readonly string $dir,
		public readonly ?Closure $description = null,
	) {
		$this->default = $default_state;
	}

	/**
	 * Reads module.php and validates it; the folder name is the expected slug.
	 *
	 * The file runs in its own scope and must return an array.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Absolute path of modules/<slug>/module.php.
	 * @return self Manifest.
	 * @throws InvalidArgumentException When the file returns no array or a field is invalid.
	 */
	public static function from_file( string $path ): self {
		$dir   = dirname( $path );
		$shown = 'modules/' . basename( $dir ) . '/module.php';
		$data  = is_file( $path ) ? ( static fn( string $manifest_file ): mixed => include $manifest_file )( $path ) : null;
		if ( ! is_array( $data ) ) {
			self::fail( sprintf( '%s must return an array.', $shown ) );
		}
		return self::from_array( $data, $dir );
	}

	/**
	 * Validates manifest data; missing optional fields get their defaults.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $data Manifest data.
	 * @param string       $dir  Absolute path of the module folder; its name is the expected slug.
	 * @return self Manifest.
	 * @throws InvalidArgumentException When a field is invalid; the message names the field.
	 */
	public static function from_array( array $data, string $dir ): self {
		$dir   = rtrim( $dir, '/' );
		$shown = 'modules/' . basename( $dir ) . '/module.php';
		$fail  = static function ( string $field, string $rule ) use ( $shown ): never {
			self::fail( sprintf( '%1$s: field %2$s %3$s.', $shown, $field, $rule ) );
		};

		foreach ( array_keys( $data ) as $field ) {
			if ( ! in_array( $field, self::FIELDS, true ) ) {
				$fail( (string) $field, 'is unknown' );
			}
		}

		$slug = $data['slug'] ?? null;
		if ( ! is_string( $slug ) || 1 !== preg_match( self::SLUG_PATTERN, $slug ) || basename( $dir ) !== $slug ) {
			$fail( 'slug', 'must equal the folder name and use lower case letters, digits and hyphens' );
		}
		$title = $data['title'] ?? null;
		if ( ! $title instanceof Closure ) {
			$fail( 'title', 'must be a closure that returns the translated title' );
		}
		$description = $data['description'] ?? null;
		if ( null !== $description && ! $description instanceof Closure ) {
			$fail( 'description', 'must be a closure that returns the translated description' );
		}
		$type = $data['type'] ?? null;
		if ( ! is_string( $type ) || ! in_array( $type, self::TYPES, true ) ) {
			$fail( 'type', 'must be block or integration' );
		}
		$states = self::states( $data['states'] ?? null );
		if ( null === $states ) {
			$fail( 'states', 'must list active and off once each, optionally hidden' );
		}
		$default = $data['default'] ?? 'off';
		if ( 'off' !== $default ) {
			$fail( 'default', 'must be off' );
		}
		$requires = self::requires( $data['requires'] ?? array() );
		if ( null === $requires ) {
			$fail( 'requires', 'allows only plugins (plugin files), modules (slugs) and blockstudio (version or false)' );
		}
		$blocks = self::strings( $data['blocks'] ?? array(), '~^creationell-theme/[a-z0-9]+(?:-[a-z0-9]+)*$~' );
		if ( null === $blocks || ( 'block' === $type ) === ( array() === $blocks ) ) {
			$fail( 'blocks', 'must name blocks of the namespace creationell-theme for the type block and stay empty otherwise' );
		}
		$outposts = self::strings( $data['outposts'] ?? array(), '~^(?:(?:patterns|template-parts)/' . preg_quote( $slug, '~' ) . '/|parts/[a-z0-9]+(?:-[a-z0-9]+)*\.html)$~' );
		if ( null === $outposts ) {
			$fail( 'outposts', 'may name only patterns/' . $slug . '/, template-parts/' . $slug . '/ and template part files parts/<part>.html' );
		}
		$since = $data['since'] ?? null;
		if ( ! is_string( $since ) || 1 !== preg_match( '~^\d+\.\d+\.\d+$~', $since ) ) {
			$fail( 'since', 'must be a version X.Y.Z' );
		}
		$legacy_plugins = self::strings( $data['legacy_plugins'] ?? array(), self::PLUGIN_PATTERN );
		if ( null === $legacy_plugins ) {
			$fail( 'legacy_plugins', 'must list plugin files such as folder/file.php' );
		}
		$warn_before_off = $data['warn_before_off'] ?? false;
		if ( ! is_bool( $warn_before_off ) ) {
			$fail( 'warn_before_off', 'must be true or false' );
		}
		$off_warning = $data['off_warning'] ?? null;
		if ( null !== $off_warning && ! $off_warning instanceof Closure ) {
			$fail( 'off_warning', 'must be a closure' );
		}
		$on_change = $data['on_change'] ?? null;
		if ( null !== $on_change && ! $on_change instanceof Closure ) {
			$fail( 'on_change', 'must be a closure' );
		}

		return new self( $slug, $title, $type, $states, 'off', $requires, $blocks, $outposts, $since, $legacy_plugins, $warn_before_off, $off_warning, $on_change, $dir, $description );
	}

	/**
	 * Returns the translated title; call it after init.
	 *
	 * @since 1.0.0
	 *
	 * @return string Title, the slug when the closure returns no string.
	 */
	public function title_text(): string {
		$title = ( $this->title )();
		return is_string( $title ) && '' !== $title ? $title : $this->slug;
	}

	/**
	 * Returns the translated description; call it after init.
	 *
	 * @since 1.0.0
	 *
	 * @return string Description; empty without description or when the closure returns no string.
	 */
	public function description_text(): string {
		if ( null === $this->description ) {
			return '';
		}
		$description = ( $this->description )();
		return is_string( $description ) ? $description : '';
	}

	/**
	 * Throws the exception for an invalid manifest.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Message in English, naming the file and the field.
	 * @return never
	 * @throws InvalidArgumentException Always.
	 */
	private static function fail( string $message ): never {
		throw new InvalidArgumentException( esc_html( $message ) );
	}

	/**
	 * Validates the states and puts them in canonical order.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $states Field value.
	 * @return array<int, string>|null States, or null when invalid.
	 */
	private static function states( mixed $states ): ?array {
		if ( ! is_array( $states ) || ! array_is_list( $states ) || count( $states ) !== count( array_unique( $states, SORT_REGULAR ) ) ) {
			return null;
		}
		foreach ( $states as $state ) {
			if ( ! is_string( $state ) || ! in_array( $state, self::STATES, true ) ) {
				return null;
			}
		}
		if ( ! in_array( 'active', $states, true ) || ! in_array( 'off', $states, true ) ) {
			return null;
		}
		return array_values( array_intersect( self::STATES, $states ) );
	}

	/**
	 * Validates the requirements and fills missing keys.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $requires Field value.
	 * @return array{plugins: array<int, string>, modules: array<int, string>, blockstudio: string|false}|null Requirements, or null when invalid.
	 */
	private static function requires( mixed $requires ): ?array {
		if ( ! is_array( $requires ) || array() !== array_diff( array_keys( $requires ), array( 'plugins', 'modules', 'blockstudio' ) ) ) {
			return null;
		}
		$plugins     = self::strings( $requires['plugins'] ?? array(), self::PLUGIN_PATTERN );
		$modules     = self::strings( $requires['modules'] ?? array(), self::SLUG_PATTERN );
		$blockstudio = $requires['blockstudio'] ?? false;
		if ( null === $plugins || null === $modules ) {
			return null;
		}
		if ( false !== $blockstudio && ( ! is_string( $blockstudio ) || 1 !== preg_match( '~^\d+(?:\.\d+){0,2}$~', $blockstudio ) ) ) {
			return null;
		}
		return array(
			'plugins'     => $plugins,
			'modules'     => $modules,
			'blockstudio' => $blockstudio,
		);
	}

	/**
	 * Validates a list of unique strings against a pattern.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $values  Field value.
	 * @param string $pattern Pattern each value must match.
	 * @return array<int, string>|null Values, or null when invalid.
	 */
	private static function strings( mixed $values, string $pattern ): ?array {
		if ( ! is_array( $values ) || ! array_is_list( $values ) ) {
			return null;
		}
		$strings = array();
		foreach ( $values as $value ) {
			if ( ! is_string( $value ) || 1 !== preg_match( $pattern, $value ) || in_array( $value, $strings, true ) ) {
				return null;
			}
			$strings[] = $value;
		}
		return $strings;
	}
}
