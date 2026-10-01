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
 * Compiles the theme SCSS with the scoped scssphp and minifies and flips it with the scoped CSS parser.
 *
 * Steps: (1) scssphp in expanded style without charset and
 * source map, with the variables of the request (intermediate()); (2)
 * Rtl_Preprocessor puts a placeholder into empty custom properties (`--x: ;`),
 * which the parser would drop, and, right to left, applies the value
 * directives; (3) the parser reads the stylesheet strictly; (4) right to left,
 * Rtl_Port flips the document; the compact rendering follows; (5)
 * Rtl_Postprocessor restores the empty values and puts the charset rule and the
 * license banners in front. compile() builds every requested direction first
 * and then writes <target_basename>.min.css and <target_basename>-rtl.min.css
 * as one change (Atomic_File_Writer::write_all()): an error of the chain or of
 * a write replaces no file.
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
	public const DIRECTIONS = array( 'ltr', 'rtl' );

	/**
	 * Revision of the compiler classes; a change of their output raises it, as it changes the fingerprint.
	 *
	 * @since 1.0.0
	 */
	public const REVISION = '5';

	/**
	 * Placeholder for the value of an empty custom property while the parser holds the stylesheet.
	 *
	 * @since 1.0.0
	 */
	public const EMPTY_VALUE = Rtl_Preprocessor::EMPTY_VALUE;

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
	 * Compiles a request and writes <target_basename>.min.css and, right to left, <target_basename>-rtl.min.css.
	 *
	 * @since 1.0.0
	 *
	 * @param Compile_Request $request What to compile and where to write it.
	 * @return Compile_Result Files by direction, fingerprint and messages, or the error; on an error nothing is written.
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
				$stylesheets = $this->stylesheets( $expanded, $request->directions );
				$files       = array();
				foreach ( $stylesheets as $direction => $css ) {
					$files[ $direction ] = rtrim( $request->target_dir, '/' ) . '/' . $request->target_basename . ( 'rtl' === $direction ? '-rtl' : '' ) . '.min.css';
				}
				Atomic_File_Writer::write_all( array_combine( array_values( $files ), array_values( $stylesheets ) ) );
				return new Compile_Result( true, $files, $fingerprint, $messages, null, microtime( true ) - $start, memory_get_peak_usage( true ) );
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
	 * Runs step 1 alone: the expanded stylesheet of scssphp, the input of Node RTLCSS in the RTL comparison of the repository.
	 *
	 * @since 1.0.0
	 *
	 * @param Compile_Request $request Request; the target is not used.
	 * @return string Expanded stylesheet without charset rule.
	 * @throws RuntimeException When the request cannot be compiled.
	 * @throws SassException    When the SCSS does not compile.
	 */
	public function intermediate( Compile_Request $request ): string {
		$error = $this->request_error( $request );
		if ( null !== $error ) {
			$exception = new RuntimeException( $error );
			throw $exception;
		}
		return self::expanded( $request, new Scss_Logger() );
	}

	/**
	 * Runs steps 2 to 5 on an expanded stylesheet, without writing.
	 *
	 * @since 1.0.0
	 *
	 * @param string             $expanded   Expanded stylesheet of step 1.
	 * @param array<int, string> $directions Directions "ltr" and "rtl".
	 * @return array<string, string> Finished stylesheet by direction, in the order of the directions.
	 * @throws SourceException          When the parser rejects the stylesheet.
	 * @throws \InvalidArgumentException When a direction is unknown.
	 * @throws RuntimeException         When the banner cannot be read.
	 */
	public function stylesheets( string $expanded, array $directions ): array {
		$stylesheets = array();
		foreach ( $directions as $direction ) {
			$document = self::parse( Rtl_Preprocessor::prepare( $expanded, $direction ) );
			if ( 'rtl' === $direction ) {
				( new Rtl_Port( $document ) )->flip();
			}
			$stylesheets[ $direction ] = Rtl_Postprocessor::finish( self::render( $document ), self::LINE );
			unset( $document );
		}
		return $stylesheets;
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
		$document = self::parse( Rtl_Preprocessor::protect_empty( $css ) );
		$roots    = new Document();
		foreach ( $document->getContents() as $item ) {
			if ( $item instanceof DeclarationBlock && self::is_root_rule( $item ) ) {
				$roots->append( $item );
			}
		}
		$rules = Rtl_Postprocessor::restore_empty( self::render( $roots ) );
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
			if ( ! in_array( $direction, self::DIRECTIONS, true ) ) {
				return 'Unknown direction ' . $direction . '.';
			}
		}
		if ( count( array_unique( $request->directions ) ) !== count( $request->directions ) ) {
			return 'A direction is named twice.';
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
