<?php
/**
 * WP-CLI command "wp creationell-theme doctor".
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Cli;

use Closure;
use Creationell\WpTheme\Core\Environment;
use Creationell\WpTheme\Settings\Bootstrap_Line;
use InvalidArgumentException;
use Throwable;
use WP_CLI;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Reports the state of the theme in sections; reads only.
 *
 * Built-in sections: "theme" (slug, version, requirements, update manifest URL)
 * and "bootstrap_line" (active line, available lines, reasons, constant). Other
 * parts of the theme add their own section with add_section(), so they never
 * edit this class.
 *
 * Example:
 *
 *     Doctor_Command::add_section(
 *         'wpml',
 *         static fn(): array => array( 'active' => defined( 'ICL_SITEPRESS_VERSION' ) )
 *     );
 *
 * A collector returns an array of values; a list of strings under "warnings"
 * is also printed as WP-CLI warnings.
 *
 * @api
 * @since 1.0.0
 */
final class Doctor_Command {

	/**
	 * Command name below the theme namespace.
	 *
	 * @since 1.0.0
	 */
	public const NAME = 'creationell-theme doctor';

	/**
	 * Output formats.
	 *
	 * @since 1.0.0
	 */
	public const FORMATS = array( 'table', 'json' );

	/**
	 * Sections added by other parts of the theme, in the order they were added.
	 *
	 * @var array<string, Closure>
	 */
	private static array $sections = array();

	/**
	 * Registers the command; runs on cli_init.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		WP_CLI::add_command( self::NAME, self::class );
	}

	/**
	 * Adds a section to the report.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $key       Section key: lower case letters, digits and underscores.
	 * @param callable $collector Returns the values of the section as an array.
	 * @phpstan-param callable(): array<string, mixed> $collector
	 * @return void
	 * @throws InvalidArgumentException When the key is invalid or taken.
	 */
	public static function add_section( string $key, callable $collector ): void {
		if ( 1 !== preg_match( '~^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$~', $key ) ) {
			throw new InvalidArgumentException( esc_html( sprintf( 'Doctor section key %s is invalid.', $key ) ) );
		}
		if ( in_array( $key, self::section_keys(), true ) ) {
			throw new InvalidArgumentException( esc_html( sprintf( 'Doctor section %s exists.', $key ) ) );
		}
		self::$sections[ $key ] = Closure::fromCallable( $collector );
	}

	/**
	 * Returns the keys of all sections in report order.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Keys.
	 */
	public static function section_keys(): array {
		return array_keys( self::sections() );
	}

	/**
	 * Removes the added sections; the built-in ones stay.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset_sections(): void {
		self::$sections = array();
	}

	/**
	 * Reports the state of the theme.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp creationell-theme doctor --format=json
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments; none.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		unset( $args );
		$format = $assoc_args['format'] ?? 'table';
		if ( ! is_string( $format ) || ! in_array( $format, self::FORMATS, true ) ) {
			WP_CLI::error( 'Unknown format; use --format=table or --format=json.' );
			return;
		}

		$report = array();
		$failed = array();
		foreach ( self::sections() as $key => $collector ) {
			try {
				$values = $collector();
				$values = is_array( $values ) ? $values : array( 'error' => 'The collector returned no array.' );
			} catch ( Throwable $error ) {
				$values = array( 'error' => $error->getMessage() );
			}
			if ( isset( $values['error'] ) && 1 === count( $values ) ) {
				$failed[] = $key;
			}
			$report[ $key ] = $values;
		}

		foreach ( $report as $key => $values ) {
			foreach ( is_array( $values['warnings'] ?? null ) ? $values['warnings'] : array() as $warning ) {
				if ( is_string( $warning ) ) {
					WP_CLI::warning( $key . ': ' . $warning );
				}
			}
		}
		foreach ( $failed as $key ) {
			WP_CLI::warning( sprintf( 'Section %s failed.', $key ) );
		}

		if ( 'json' === $format ) {
			$json = wp_json_encode( $report, JSON_UNESCAPED_SLASHES );
			WP_CLI::line( false === $json ? '{}' : $json );
		} else {
			\WP_CLI\Utils\format_items( 'table', self::rows( $report ), array( 'section', 'check', 'value' ) );
		}

		if ( array() !== $failed ) {
			WP_CLI::halt( 1 );
		}
	}

	/**
	 * Returns the built-in sections followed by the added ones.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, Closure> Collectors by key.
	 */
	private static function sections(): array {
		return array_merge(
			array(
				'theme'          => Closure::fromCallable( array( self::class, 'theme_section' ) ),
				'bootstrap_line' => Closure::fromCallable( array( self::class, 'bootstrap_line_section' ) ),
			),
			self::$sections
		);
	}

	/**
	 * Collects the theme section.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Values.
	 */
	private static function theme_section(): array {
		$unmet = Environment::unmet_requirements();
		return array(
			'slug'                => CREATIONELL_WP_THEME_SLUG,
			'version'             => CREATIONELL_WP_THEME_VERSION,
			'requirements_met'    => array() === $unmet,
			'php'                 => PHP_VERSION,
			'wp'                  => Environment::wp_version(),
			'min_php'             => CREATIONELL_WP_THEME_MIN_PHP,
			'min_wp'              => CREATIONELL_WP_THEME_MIN_WP,
			'manifest_url'        => Environment::manifest_url(),
			'manifest_url_status' => Environment::manifest_url_status(),
		);
	}

	/**
	 * Collects the Bootstrap line section.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Values.
	 */
	private static function bootstrap_line_section(): array {
		$line    = Bootstrap_Line::instance();
		$reasons = array();
		foreach ( $line->available() as $number => $available ) {
			if ( ! $available ) {
				$reasons[ $number ] = $line->unavailable_reason( $number );
			}
		}
		$values    = array(
			'active'                => $line->active(),
			'available'             => $line->available(),
			'reasons'               => $reasons,
			'requested_by_constant' => $line->requested_by_constant(),
		);
		$requested = $line->requested_by_constant();
		if ( null !== $requested && $requested !== $line->active() ) {
			$values['warnings'] = array(
				sprintf( '%1$s asks for line %2$d: %3$s Line %4$d stays active.', Bootstrap_Line::CONSTANT, $requested, $line->unavailable_reason( $requested ), $line->active() ),
			);
		}
		return $values;
	}

	/**
	 * Flattens the report into table rows.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, array<mixed>> $report Values by section.
	 * @return array<int, array{section: string, check: string, value: string}> Rows.
	 */
	private static function rows( array $report ): array {
		$rows = array();
		foreach ( $report as $section => $values ) {
			foreach ( $values as $check => $value ) {
				$rows[] = array(
					'section' => $section,
					'check'   => (string) $check,
					'value'   => self::cell( $value ),
				);
			}
		}
		return $rows;
	}

	/**
	 * Formats a value for a table cell.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return string Text: empty for null, true/false for booleans, JSON for arrays.
	 */
	private static function cell( mixed $value ): string {
		if ( null === $value ) {
			return '';
		}
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}
		if ( is_scalar( $value ) ) {
			return (string) $value;
		}
		$json = wp_json_encode( $value, JSON_UNESCAPED_SLASHES );
		return false === $json ? '' : $json;
	}
}
