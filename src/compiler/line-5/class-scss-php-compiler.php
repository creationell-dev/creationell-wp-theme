<?php
/**
 * Stylesheet compiler of Bootstrap line 5.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Compiler\Line5;

use Creationell\WpTheme\Compiler\Atomic_File_Writer;
use Creationell\WpTheme\Compiler\Compile_Request;
use Creationell\WpTheme\Compiler\Compile_Result;
use Creationell\WpTheme\Compiler\Fingerprint;
use Creationell\WpTheme\Compiler\License_Banner;
use Creationell\WpTheme\Compiler\Scss_Value;
use Creationell\WpTheme\Compiler\Stylesheet_Compiler_Interface;
use Creationell\WpTheme\Vendor\Sabberworm\CSS\CSSList\Document;
use Creationell\WpTheme\Vendor\Sabberworm\CSS\OutputFormat;
use Creationell\WpTheme\Vendor\Sabberworm\CSS\Parser;
use Creationell\WpTheme\Vendor\Sabberworm\CSS\Parsing\SourceException;
use Creationell\WpTheme\Vendor\Sabberworm\CSS\RuleSet\DeclarationBlock;
use Creationell\WpTheme\Vendor\Sabberworm\CSS\Settings;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Compiler;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Exception\SassException;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\OutputStyle;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\ValueConverter;
use Exception;
use RuntimeException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Compiles the theme SCSS with the scoped scssphp and minifies it with the scoped CSS parser.
 *
 * Steps: scssphp in expanded style without charset and source map, with the
 * variables of the request; empty custom properties (`--x: ;`) get a placeholder,
 * because the parser would drop them; the parser renders the compact stylesheet;
 * the placeholder becomes a space again; the charset rule and the license banners
 * of License_Banner go in front. This theme version builds the left-to-right
 * stylesheet only.
 *
 * @since 1.0.0
 */
final class Scss_Php_Compiler implements Stylesheet_Compiler_Interface {

	/**
	 * Bootstrap line of this compiler.
	 *
	 * @since 1.0.0
	 */
	public const LINE = 5;

	/**
	 * Directions this compiler builds.
	 *
	 * @since 1.0.0
	 */
	public const DIRECTIONS = array( 'ltr' );

	/**
	 * Revision of the compiler classes; a change of their output raises it, as it changes the fingerprint.
	 *
	 * @since 1.0.0
	 */
	public const REVISION = '2';

	/**
	 * Placeholder for the value of an empty custom property while the parser holds the stylesheet.
	 *
	 * @since 1.0.0
	 */
	public const EMPTY_VALUE = '__creationell_empty__';

	/**
	 * Selectors of the rules that root_vars() keeps.
	 *
	 * @since 1.0.0
	 */
	public const ROOT_SELECTORS = array( ':root', '[data-bs-theme=light]' );

	/**
	 * Package name of the CSS parser in lib/UPSTREAM.
	 */
	private const PARSER = 'sabberworm/php-css-parser';

	/**
	 * Major version of the CSS parser the chain and the adapted RTL port are written for.
	 */
	private const PARSER_MAJOR = '9';

	/**
	 * Scoped libraries whose versions shape the output.
	 */
	private const LIBRARIES = array( 'scssphp/scssphp', self::PARSER, 'moodlehq/rtlcss-php' );

	/**
	 * Theme folder with lib/UPSTREAM, style.css and the vendored Bootstrap.
	 *
	 * @var string
	 */
	private readonly string $theme_dir;

	/**
	 * Versions, read once.
	 *
	 * @var array<string, string>|null
	 */
	private ?array $versions = null;

	/**
	 * Sets the theme folder whose versions and style.css count for the fingerprint.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $theme_dir Theme folder, null for the folder of this theme.
	 */
	public function __construct( ?string $theme_dir = null ) {
		$this->theme_dir = null === $theme_dir ? dirname( __DIR__, 3 ) : rtrim( $theme_dir, '/' );
	}

	/**
	 * Returns the Bootstrap lines this compiler builds.
	 *
	 * @since 1.0.0
	 *
	 * @return list<int> Line 5.
	 */
	public function supported_lines(): array {
		return array( self::LINE );
	}

	/**
	 * Returns the versions of Bootstrap, scssphp, the parser, the RTL port and this compiler.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Component name and exact version.
	 * @throws RuntimeException When VERSION or lib/UPSTREAM is missing or incomplete.
	 */
	public function versions(): array {
		if ( null === $this->versions ) {
			$versions = array( 'bootstrap' => self::first_line( $this->theme_dir . '/assets/vendor/bootstrap-5/VERSION' ) );
			$upstream = self::upstream( $this->theme_dir . '/lib/UPSTREAM' );
			foreach ( self::LIBRARIES as $library ) {
				if ( ! isset( $upstream[ $library ] ) ) {
					$error = new RuntimeException( 'lib/UPSTREAM names no version of ' . $library . '.' );
					throw $error;
				}
				$versions[ $library ] = $upstream[ $library ];
			}
			$versions['creationell/line-5'] = self::REVISION;
			$this->versions                 = $versions;
		}
		return $this->versions;
	}

	/**
	 * Returns the fingerprint of a request without compiling it.
	 *
	 * Inputs: the versions, every SCSS file below the import paths and the entry file,
	 * the variables, the directions and the Version header of the theme style.css.
	 *
	 * @since 1.0.0
	 *
	 * @param Compile_Request $request Request.
	 * @return string Fingerprint, 64 hex digits.
	 * @throws RuntimeException When a version or the theme version cannot be read.
	 */
	public function fingerprint( Compile_Request $request ): string {
		$sources = Fingerprint::scss_files( $request->import_paths );
		if ( is_file( $request->entry_file ) && ! in_array( $request->entry_file, $sources, true ) ) {
			$sources[] = $request->entry_file;
		}
		return Fingerprint::compute( self::LINE, $this->versions(), $sources, $request->variables, $request->directions, $this->theme_version(), null );
	}

	/**
	 * Compiles a request and writes <target_basename>.min.css.
	 *
	 * @since 1.0.0
	 *
	 * @param Compile_Request $request What to compile and where to write it.
	 * @return Compile_Result Files, fingerprint and messages, or the error; on an error nothing is written.
	 */
	public function compile( Compile_Request $request ): Compile_Result {
		$start       = microtime( true );
		$fingerprint = '';
		$messages    = array();
		try {
			$error = $this->request_error( $request );
			if ( null === $error ) {
				$fingerprint = $this->fingerprint( $request );
				$logger      = new Scss_Logger();
				$expanded    = self::expanded( $request, $logger );
				$messages    = $logger->messages();
				$target      = rtrim( $request->target_dir, '/' ) . '/' . $request->target_basename . '.min.css';
				Atomic_File_Writer::write( $target, License_Banner::for_line( self::LINE ) . self::minify( $expanded ) );
				return new Compile_Result( true, array( 'ltr' => $target ), $fingerprint, $messages, null, microtime( true ) - $start, memory_get_peak_usage( true ) );
			}
		} catch ( SassException $exception ) {
			$error = 'SCSS error: ' . $exception->getMessage();
		} catch ( SourceException $exception ) {
			$error = 'CSS parser error: ' . $exception->getMessage();
		} catch ( Exception $exception ) {
			$error = $exception->getMessage();
		}
		return new Compile_Result( false, array(), $fingerprint, $messages, $error, microtime( true ) - $start, memory_get_peak_usage( true ) );
	}

	/**
	 * Extracts the root custom properties from a compiled stylesheet.
	 *
	 * Keeps the top-level rules whose selectors are all ":root" or
	 * "[data-bs-theme=light]"; rules inside at-rules and the dark theme stay out.
	 * The editor screens outside the content canvas load the result, so the
	 * var(--bs-*) colors of theme.json resolve there.
	 *
	 * @since 1.0.0
	 *
	 * @param string $css Compiled stylesheet.
	 * @return string Charset rule, banners and the root rules; empty when there is no root rule.
	 * @throws SourceException When the stylesheet cannot be parsed.
	 */
	public function root_vars( string $css ): string {
		$document = self::parse( self::protect_empty( $css ) );
		$roots    = new Document();
		foreach ( $document->getContents() as $item ) {
			if ( $item instanceof DeclarationBlock && self::is_root_rule( $item ) ) {
				$roots->append( $item );
			}
		}
		$rules = self::restore_empty( self::render( $roots ) );
		return '' === $rules ? '' : License_Banner::for_line( self::LINE ) . $rules;
	}

	/**
	 * Returns why a request cannot be compiled.
	 *
	 * @since 1.0.0
	 *
	 * @param Compile_Request $request Request.
	 * @return string|null Reason, null when the request is fine.
	 * @throws RuntimeException When lib/UPSTREAM cannot be read.
	 */
	private function request_error( Compile_Request $request ): ?string {
		$parser = $this->versions()[ self::PARSER ];
		if ( ! str_starts_with( $parser, self::PARSER_MAJOR . '.' ) ) {
			return self::PARSER . ' ' . $parser . ' is not supported: the line 5 chain needs version ' . self::PARSER_MAJOR . '.';
		}
		if ( array() === $request->directions ) {
			return 'The request has no direction.';
		}
		foreach ( $request->directions as $direction ) {
			if ( 'rtl' === $direction ) {
				return 'Right-to-left output (rtl) is not available in this theme version.';
			}
			if ( ! in_array( $direction, self::DIRECTIONS, true ) ) {
				return 'Unknown direction ' . $direction . '.';
			}
		}
		if ( 1 !== preg_match( '~^[a-z0-9][a-z0-9-]*$~iD', $request->target_basename ) ) {
			return 'Invalid target base name: use letters, digits and hyphens.';
		}
		if ( ! is_file( $request->entry_file ) ) {
			return 'The entry file ' . $request->entry_file . ' does not exist.';
		}
		foreach ( $request->variables as $name => $value ) {
			if ( 1 !== preg_match( '~^[A-Za-z_][A-Za-z0-9_-]*$~D', (string) $name ) || ! $value instanceof Scss_Value ) {
				return 'Invalid variable name or value.';
			}
		}
		return null;
	}

	/**
	 * Compiles the request with scssphp in expanded style.
	 *
	 * @since 1.0.0
	 *
	 * @param Compile_Request $request Request.
	 * @param Scss_Logger     $logger  Logger for warnings and deprecations.
	 * @return string Expanded stylesheet without charset rule.
	 * @throws SassException When the SCSS does not compile.
	 */
	private static function expanded( Compile_Request $request, Scss_Logger $logger ): string {
		$compiler = new Compiler();
		$compiler->setLogger( $logger );
		$compiler->setImportPaths( array_values( $request->import_paths ) );
		$compiler->setOutputStyle( OutputStyle::EXPANDED );
		$compiler->setCharset( false );
		$variables = array();
		foreach ( $request->variables as $name => $value ) {
			$variables[ (string) $name ] = ValueConverter::parseValue( $value->value );
		}
		$compiler->addVariables( $variables );
		return $compiler->compileFile( $request->entry_file )->getCss();
	}

	/**
	 * Minifies a stylesheet with the CSS parser.
	 *
	 * @since 1.0.0
	 *
	 * @param string $css Stylesheet.
	 * @return string Compact stylesheet without comments.
	 * @throws SourceException When the stylesheet cannot be parsed.
	 */
	private static function minify( string $css ): string {
		return self::restore_empty( self::render( self::parse( self::protect_empty( $css ) ) ) );
	}

	/**
	 * Parses a stylesheet strictly: an unknown construct fails instead of being dropped.
	 *
	 * @since 1.0.0
	 *
	 * @param string $css Stylesheet.
	 * @return Document Parsed stylesheet.
	 * @throws SourceException When the stylesheet cannot be parsed.
	 */
	private static function parse( string $css ): Document {
		return ( new Parser( $css, Settings::create()->beStrict() ) )->parse();
	}

	/**
	 * Renders a document in the compact format, which omits the last semicolon of a block.
	 *
	 * @since 1.0.0
	 *
	 * @param Document $document Document.
	 * @return string Stylesheet.
	 */
	private static function render( Document $document ): string {
		return $document->render( OutputFormat::createCompact() );
	}

	/**
	 * Replaces the empty value of custom properties with the placeholder.
	 *
	 * @since 1.0.0
	 *
	 * @param string $css Stylesheet.
	 * @return string Stylesheet with placeholders.
	 */
	private static function protect_empty( string $css ): string {
		return (string) preg_replace( '~(--[A-Za-z0-9_-]+)\s*:\s*(?=[;}])~', '$1:' . self::EMPTY_VALUE, $css );
	}

	/**
	 * Turns the placeholder back into the empty value " ".
	 *
	 * @since 1.0.0
	 *
	 * @param string $css Stylesheet with placeholders.
	 * @return string Stylesheet.
	 */
	private static function restore_empty( string $css ): string {
		return str_replace( self::EMPTY_VALUE, ' ', $css );
	}

	/**
	 * Tells whether all selectors of a rule are root selectors.
	 *
	 * @since 1.0.0
	 *
	 * @param DeclarationBlock $block Rule.
	 * @return bool True for a root rule.
	 */
	private static function is_root_rule( DeclarationBlock $block ): bool {
		$selectors = $block->getSelectors();
		$format    = OutputFormat::createCompact();
		foreach ( $selectors as $selector ) {
			if ( ! in_array( trim( $selector->render( $format ) ), self::ROOT_SELECTORS, true ) ) {
				return false;
			}
		}
		return array() !== $selectors;
	}

	/**
	 * Returns the Version header of the theme style.css.
	 *
	 * @since 1.0.0
	 *
	 * @return string Theme version.
	 * @throws RuntimeException When style.css or its Version header is missing.
	 */
	private function theme_version(): string {
		$file  = $this->theme_dir . '/style.css';
		$lines = is_file( $file ) ? file( $file, FILE_IGNORE_NEW_LINES ) : false;
		foreach ( false === $lines ? array() : array_slice( $lines, 0, 40 ) as $text ) {
			if ( 1 === preg_match( '~^[ \t/*#@]*Version:(.*)$~i', $text, $matches ) && '' !== trim( $matches[1] ) ) {
				return trim( $matches[1] );
			}
		}
		$error = new RuntimeException( 'The theme style.css has no Version header.' );
		throw $error;
	}

	/**
	 * Returns the first line of a file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file File.
	 * @return string First line without white space.
	 * @throws RuntimeException When the file is missing or empty.
	 */
	private static function first_line( string $file ): string {
		$lines = is_file( $file ) ? file( $file, FILE_IGNORE_NEW_LINES ) : false;
		$first = false === $lines ? '' : trim( $lines[0] ?? '' );
		if ( '' === $first ) {
			$error = new RuntimeException( 'Cannot read the version in ' . basename( dirname( $file ) ) . '/' . basename( $file ) . '.' );
			throw $error;
		}
		return $first;
	}

	/**
	 * Reads the package versions of lib/UPSTREAM.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file Path of UPSTREAM.
	 * @return array<string, string> Package name and version.
	 * @throws RuntimeException When the file is missing.
	 */
	private static function upstream( string $file ): array {
		$lines = is_file( $file ) ? file( $file, FILE_IGNORE_NEW_LINES ) : false;
		if ( false === $lines ) {
			$error = new RuntimeException( 'The scoped libraries have no lib/UPSTREAM.' );
			throw $error;
		}
		$versions = array();
		$package  = '';
		foreach ( $lines as $text ) {
			if ( 1 === preg_match( '~^\[([^\]]+)\]$~', trim( $text ), $matches ) ) {
				$package = $matches[1];
			} elseif ( '' !== $package && 1 === preg_match( '~^version\s*=\s*(\S+)$~', trim( $text ), $matches ) ) {
				$versions[ $package ] = $matches[1];
			}
		}
		return $versions;
	}
}
