<?php
/**
 * Rules for child themes of the creationell Theme.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Child;

use FilesystemIterator;
use InvalidArgumentException;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use SplFileObject;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Checks a child theme folder against the rules of its README: what a child theme may and may not do.
 *
 * The check reads files only; it runs no code of the child theme. PHP files
 * are scanned token by token, so comments never count. The command
 * "wp creationell-theme child check" prints the findings.
 *
 * Example:
 *
 *     foreach ( Child_Rules::check( get_stylesheet_directory() ) as $finding ) {
 *         printf( "%s %s: %s\n", $finding['rule'], $finding['file'], $finding['message'] );
 *     }
 *
 * @api
 * @since 1.0.0
 */
final class Child_Rules {

	/**
	 * Rule names in report order.
	 *
	 * @since 1.0.0
	 */
	public const RULES = array(
		'update-uri-false',
		'template-slug',
		'lines-declared',
		'no-block-theme',
		'no-token-presets',
		'no-new-settings',
		'no-updater',
		'no-blocks',
		'no-roles',
		'allowed-paths',
	);

	/**
	 * Slug of the parent theme that the header "Template" must name.
	 *
	 * @since 1.0.0
	 */
	public const PARENT_SLUG = 'creationell-wp-theme';

	/**
	 * Paths a child theme may hold besides template overrides, as patterns on the path relative to the child folder.
	 *
	 * Font files are .woff2 files directly in assets/fonts/ with the names that
	 * Font_Catalog::SRC_PATTERN accepts as sources of child fonts.
	 *
	 * @since 1.0.0
	 */
	public const ALLOWED_PATHS = array(
		'~^style\.css$~',
		'~^functions\.php$~',
		'~^README\.md$~',
		'~^LICENSE$~',
		'~^screenshot\.(?:png|jpe?g|webp)$~',
		'~^theme\.json$~',
		'~^assets/img/logo/[^/]+\.(?:svg|png|jpe?g|webp)$~',
		'~^assets/scss/(?:[^/]+/)*[^/]+\.scss$~',
		'~^assets/fonts/[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)*\.woff2$~D',
		'~^assets/fonts/README\.md$~',
		'~^parts/(?:header|footer)\.html$~',
	);

	/**
	 * Template paths a child theme may override when the parent theme has a file of the same path.
	 *
	 * @since 1.0.0
	 */
	public const TEMPLATE_OVERRIDES = '~^(?:[^/]+|(?:template-parts|page-templates|single-templates)/.+)\.php$~';

	/**
	 * Files that make WordPress treat a theme as a block theme.
	 *
	 * @since 1.0.0
	 */
	public const BLOCK_THEME_FILES = array( 'block-templates/index.html', 'templates/index.html' );

	/**
	 * Presets below "settings" (and "settings.blocks.<block>") of theme.json that only the parent theme sets.
	 *
	 * @since 1.0.0
	 */
	public const TOKEN_PRESETS = array( 'color.palette', 'typography.fontFamilies', 'typography.fontSizes' );

	/**
	 * Called functions and methods (lower case) by rule.
	 *
	 * @since 1.0.0
	 */
	public const CALLS = array(
		'no-new-settings' => array( 'acf_add_options_page', 'acf_add_local_field_group', 'register_setting', 'add_option' ),
		'no-blocks'       => array( 'register_block_type', 'register_block_type_from_metadata' ),
		'no-roles'        => array( 'add_role', 'add_cap', 'remove_cap' ),
	);

	/**
	 * Parts of hook names that belong to theme updates.
	 *
	 * @since 1.0.0
	 */
	public const UPDATER_HOOKS = array( 'upgrader_pre_download', 'update_themes_', 'site_transient_update_' );

	/**
	 * Checks a child theme folder.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $child_dir  Absolute or relative path of the child theme folder.
	 * @param string|null $parent_dir Folder of the parent theme for template overrides; default: this theme.
	 * @return list<array{rule: string, file: string, message: string}> Findings in the order of RULES, then by file; empty when the child follows all rules.
	 * @throws InvalidArgumentException When the folder does not exist.
	 */
	public static function check( string $child_dir, ?string $parent_dir = null ): array {
		$child_dir = rtrim( $child_dir, '/\\' );
		if ( '' === $child_dir || ! is_dir( $child_dir ) ) {
			throw new InvalidArgumentException( esc_html( sprintf( 'The child theme folder %s does not exist.', $child_dir ) ) );
		}
		$parent_dir = rtrim( $parent_dir ?? CREATIONELL_WP_THEME_DIR, '/\\' );
		$files      = self::files( $child_dir );
		$findings   = array_fill_keys( self::RULES, array() );

		self::check_headers( $child_dir, $findings );
		$reported = array();
		foreach ( $files as $file ) {
			if ( in_array( $file, self::BLOCK_THEME_FILES, true ) ) {
				$findings['no-block-theme'][] = self::finding( 'no-block-theme', $file, sprintf( '%s makes WordPress treat the child theme as a block theme; the creationell Theme is a classic theme with PHP templates.', $file ) );
				$reported[]                   = $file;
			} elseif ( 1 === preg_match( '~^blocks/|(?:^|/)block\.json$~', $file ) ) {
				$findings['no-blocks'][] = self::finding( 'no-blocks', $file, 'Blocks belong in a plugin, not in a child theme.' );
				$reported[]              = $file;
			}
		}
		if ( in_array( 'theme.json', $files, true ) ) {
			self::check_theme_json( $child_dir . '/theme.json', $findings );
		}
		foreach ( $files as $file ) {
			if ( str_ends_with( $file, '.php' ) ) {
				self::check_php( $file, self::read( $child_dir . '/' . $file ), $findings );
			}
		}
		foreach ( $files as $file ) {
			if ( ! in_array( $file, $reported, true ) && ! self::is_allowed_path( $file, $parent_dir ) ) {
				$findings['allowed-paths'][] = self::finding( 'allowed-paths', $file, 'The path is not on the list of allowed paths in the README of the child theme; a template override needs a template of the same path in the parent theme.' );
			}
		}

		$result = array();
		foreach ( $findings as $rule_findings ) {
			usort( $rule_findings, static fn( array $a, array $b ): int => strcmp( $a['file'], $b['file'] ) );
			$result = array_merge( $result, $rule_findings );
		}
		return $result;
	}

	/**
	 * Tells whether a child theme may hold a path.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path       Path relative to the child folder, with forward slashes.
	 * @param string $parent_dir Folder of the parent theme.
	 * @return bool True for a path of ALLOWED_PATHS and for a template the parent theme has at the same path.
	 */
	public static function is_allowed_path( string $path, string $parent_dir ): bool {
		foreach ( self::ALLOWED_PATHS as $pattern ) {
			if ( 1 === preg_match( $pattern, $path ) ) {
				return true;
			}
		}
		return 'functions.php' !== $path
			&& 1 === preg_match( self::TEMPLATE_OVERRIDES, $path )
			&& is_file( rtrim( $parent_dir, '/\\' ) . '/' . $path );
	}

	/**
	 * Checks the style.css headers: Update URI, Template and Bootstrap Lines.
	 *
	 * @since 1.0.0
	 *
	 * @param string                                                                  $child_dir Child folder.
	 * @param array<string, list<array{rule: string, file: string, message: string}>> $findings  Findings by rule.
	 * @return void
	 */
	private static function check_headers( string $child_dir, array &$findings ): void {
		if ( ! is_file( $child_dir . '/style.css' ) ) {
			foreach ( array( 'update-uri-false', 'template-slug', 'lines-declared' ) as $rule ) {
				$findings[ $rule ][] = self::finding( $rule, 'style.css', 'style.css is missing; a child theme needs its header.' );
			}
			return;
		}
		$headers = Child_Theme::read_headers( $child_dir );
		if ( 'false' !== $headers['update_uri'] ) {
			$findings['update-uri-false'][] = self::finding( 'update-uri-false', 'style.css', sprintf( 'The header needs "Update URI: false", so WordPress never offers an update of another theme for the child theme; it is %s.', self::shown( $headers['update_uri'] ) ) );
		}
		if ( self::PARENT_SLUG !== $headers['template'] ) {
			$findings['template-slug'][] = self::finding( 'template-slug', 'style.css', sprintf( 'The header needs "Template: %1$s"; it is %2$s.', self::PARENT_SLUG, self::shown( $headers['template'] ) ) );
		}
		$problem = Child_Theme::lines_problem( $headers['lines'] );
		if ( '' !== $problem ) {
			$findings['lines-declared'][] = self::finding( 'lines-declared', 'style.css', $problem );
		}
	}

	/**
	 * Checks theme.json for token presets, also per block.
	 *
	 * @since 1.0.0
	 *
	 * @param string                                                                  $file     Absolute path of theme.json.
	 * @param array<string, list<array{rule: string, file: string, message: string}>> $findings Findings by rule.
	 * @return void
	 */
	private static function check_theme_json( string $file, array &$findings ): void {
		$json = wp_json_file_decode( $file, array( 'associative' => true ) );
		if ( ! is_array( $json ) ) {
			$findings['no-token-presets'][] = self::finding( 'no-token-presets', 'theme.json', 'theme.json is no valid JSON object, so the check for token presets cannot run.' );
			return;
		}
		$settings = is_array( $json['settings'] ?? null ) ? $json['settings'] : array();
		$scopes   = array( 'settings' => $settings );
		foreach ( is_array( $settings['blocks'] ?? null ) ? $settings['blocks'] : array() as $block => $block_settings ) {
			if ( is_array( $block_settings ) ) {
				$scopes[ 'settings.blocks.' . $block ] = $block_settings;
			}
		}
		foreach ( $scopes as $prefix => $scope ) {
			foreach ( self::TOKEN_PRESETS as $preset ) {
				list( $group, $key ) = explode( '.', $preset );
				if ( is_array( $scope[ $group ] ?? null ) && array_key_exists( $key, $scope[ $group ] ) ) {
					$findings['no-token-presets'][] = self::finding( 'no-token-presets', 'theme.json', sprintf( '%s.%s: colors, font families and font sizes come from the parent theme and its settings; remove the preset.', $prefix, $preset ) );
				}
			}
		}
	}

	/**
	 * Scans a PHP file for calls of CALLS and for hook names of UPDATER_HOOKS; comments do not count.
	 *
	 * @since 1.0.0
	 *
	 * @param string                                                                  $file     Path relative to the child folder.
	 * @param string                                                                  $code     PHP source.
	 * @param array<string, list<array{rule: string, file: string, message: string}>> $findings Findings by rule.
	 * @return void
	 */
	private static function check_php( string $file, string $code, array &$findings ): void {
		$tokens = array_values(
			array_filter(
				token_get_all( $code ),
				static fn( mixed $token ): bool => ! is_array( $token ) || ! in_array( $token[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true )
			)
		);
		foreach ( $tokens as $index => $token ) {
			if ( ! is_array( $token ) ) {
				continue;
			}
			if ( in_array( $token[0], array( T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE ), true ) ) {
				self::check_string( $file, $token, $findings );
				continue;
			}
			if ( ! in_array( $token[0], array( T_STRING, T_NAME_FULLY_QUALIFIED ), true ) || '(' !== ( $tokens[ $index + 1 ] ?? null ) ) {
				continue;
			}
			$previous = $tokens[ $index - 1 ] ?? null;
			if ( is_array( $previous ) && in_array( $previous[0], array( T_FUNCTION, T_NEW, T_CONST ), true ) ) {
				continue;
			}
			$name = strtolower( ltrim( $token[1], '\\' ) );
			foreach ( self::CALLS as $rule => $names ) {
				if ( in_array( $name, $names, true ) ) {
					$findings[ $rule ][] = self::finding( $rule, $file, sprintf( 'Line %1$d: %2$s() %3$s', $token[2], $name, self::call_reason( $rule ) ) );
				}
			}
		}
	}

	/**
	 * Reports a string that names a hook of theme updates.
	 *
	 * @since 1.0.0
	 *
	 * @param string                                                                  $file     Path relative to the child folder.
	 * @param array{0: int, 1: string, 2: int}                                        $token    String token.
	 * @param array<string, list<array{rule: string, file: string, message: string}>> $findings Findings by rule.
	 * @return void
	 */
	private static function check_string( string $file, array $token, array &$findings ): void {
		foreach ( self::UPDATER_HOOKS as $hook ) {
			if ( str_contains( $token[1], $hook ) ) {
				$findings['no-updater'][] = self::finding( 'no-updater', $file, sprintf( 'Line %1$d: the hook %2$s belongs to theme updates; a child theme keeps "Update URI: false" and changes no updates.', $token[2], trim( $token[1], "'\"\n" ) ) );
				return;
			}
		}
	}

	/**
	 * Returns why a call of a rule is forbidden.
	 *
	 * @since 1.0.0
	 *
	 * @param string $rule Rule of CALLS.
	 * @return string Reason.
	 */
	private static function call_reason( string $rule ): string {
		switch ( $rule ) {
			case 'no-new-settings':
				return 'adds a setting or an options page; settings belong to the theme, a child theme sets defaults through the theme filters.';
			case 'no-blocks':
				return 'registers a block; blocks belong in a plugin, not in a child theme.';
			default:
				return 'changes roles or capabilities, which stay in the database after a theme switch.';
		}
	}

	/**
	 * Lists the files of a folder, without the version control folder .git.
	 *
	 * @since 1.0.0
	 *
	 * @param string $dir Folder.
	 * @return list<string> Paths relative to the folder with forward slashes, sorted.
	 */
	private static function files( string $dir ): array {
		$iterator = new RecursiveIteratorIterator(
			new RecursiveCallbackFilterIterator(
				new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
				static fn( SplFileInfo $file ): bool => '.git' !== $file->getFilename()
			)
		);
		$files    = array();
		foreach ( $iterator as $file ) {
			if ( $file instanceof SplFileInfo && $file->isFile() ) {
				$files[] = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $dir ) + 1 ) );
			}
		}
		sort( $files );
		return $files;
	}

	/**
	 * Reads a local file of the child theme.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Absolute path.
	 * @return string Content; empty for an empty or unreadable file.
	 */
	private static function read( string $path ): string {
		$size = is_readable( $path ) ? filesize( $path ) : false;
		if ( false === $size || 0 === $size ) {
			return '';
		}
		$content = ( new SplFileObject( $path, 'rb' ) )->fread( $size );
		return false === $content ? '' : $content;
	}

	/**
	 * Builds a finding.
	 *
	 * @since 1.0.0
	 *
	 * @param string $rule    Rule.
	 * @param string $file    Path relative to the child folder.
	 * @param string $message English message.
	 * @return array{rule: string, file: string, message: string} Finding.
	 */
	private static function finding( string $rule, string $file, string $message ): array {
		return array(
			'rule'    => $rule,
			'file'    => $file,
			'message' => $message,
		);
	}

	/**
	 * Quotes a header value for a message.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Header value.
	 * @return string The value in quotes, or "missing".
	 */
	private static function shown( string $value ): string {
		return '' === $value ? 'missing' : '"' . $value . '"';
	}
}
