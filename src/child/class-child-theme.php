<?php
/**
 * Active child theme.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Child;

use Creationell\WpTheme\Admin\Notices;
use Creationell\WpTheme\Settings\Bootstrap_Line;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Reads the style.css header of the active child theme: parent slug, Update URI and the declared Bootstrap lines.
 *
 * A child theme declares the Bootstrap lines its project styles support in
 * the header "Bootstrap Lines", e.g. "Bootstrap Lines: 5" or "Bootstrap Lines: 5, 6".
 * The theme compiles child styles only for declared lines. A missing header or
 * a line this theme version does not include gives an admin notice and a
 * warning of the doctor command.
 *
 * Example:
 *
 *     if ( in_array( 5, Child_Theme::declared_lines(), true ) ) {
 *         // The child theme supports line 5.
 *     }
 *
 * @api
 * @since 1.0.0
 */
final class Child_Theme {

	/**
	 * Name of the style.css header with the declared Bootstrap lines.
	 *
	 * @since 1.0.0
	 */
	public const LINES_HEADER = 'Bootstrap Lines';

	/**
	 * Headers read from style.css, by key.
	 *
	 * @since 1.0.0
	 */
	public const HEADERS = array(
		'template'   => 'Template',
		'update_uri' => 'Update URI',
		'lines'      => self::LINES_HEADER,
	);

	/**
	 * ID of the admin notice about the declared lines.
	 *
	 * @since 1.0.0
	 */
	public const NOTICE = 'child-lines';

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Folder of the child theme; null when the parent theme runs without a child.
	 *
	 * @var string|null
	 */
	private ?string $dir;

	/**
	 * Header values, read on first use.
	 *
	 * @var array<string, string>|null
	 */
	private ?array $headers = null;

	/**
	 * Takes the folder of a child theme.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $dir Absolute path of the child theme folder; null for no child theme.
	 */
	public function __construct( ?string $dir ) {
		$this->dir = null === $dir ? null : rtrim( $dir, '/\\' );
	}

	/**
	 * Returns the shared instance for the active theme; its header values stay cached for the request.
	 *
	 * @since 1.0.0
	 *
	 * @return self Instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			$stylesheet     = get_stylesheet_directory(); // creationell-allow-stylesheet: the folder of the active child theme is read, never used as a key.
			self::$instance = new self( get_template_directory() === $stylesheet ? null : $stylesheet );
		}
		return self::$instance;
	}

	/**
	 * Replaces the shared instance; null builds a new one on the next call of instance().
	 *
	 * @since 1.0.0
	 *
	 * @param self|null $child Instance.
	 * @return void
	 */
	public static function set_instance( ?self $child ): void {
		self::$instance = $child;
	}

	/**
	 * Returns the Bootstrap lines the active child theme declares.
	 *
	 * Empty without a child theme and for a missing or invalid header. The
	 * lines may include one this theme version does not include; compare with
	 * Bootstrap_Line before use.
	 *
	 * @since 1.0.0
	 *
	 * @return list<int> Declared lines in header order.
	 */
	public static function declared_lines(): array {
		return self::instance()->lines();
	}

	/**
	 * Returns the folder of the child theme.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null Absolute path, or null without a child theme.
	 */
	public function dir(): ?string {
		return $this->dir;
	}

	/**
	 * Returns the header values of the child theme.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Values by key of HEADERS; empty strings without a child theme.
	 */
	public function headers(): array {
		if ( null === $this->headers ) {
			$this->headers = null === $this->dir ? array_fill_keys( array_keys( self::HEADERS ), '' ) : self::read_headers( $this->dir );
		}
		return $this->headers;
	}

	/**
	 * Returns the declared Bootstrap lines of this child theme.
	 *
	 * @since 1.0.0
	 *
	 * @return list<int> Lines; empty for a missing or invalid header.
	 */
	public function lines(): array {
		return self::parse_lines( $this->headers()['lines'] ) ?? array();
	}

	/**
	 * Explains what is wrong with the declared lines of this child theme.
	 *
	 * @since 1.0.0
	 *
	 * @return string English reason, empty without a child theme or for a valid header.
	 */
	public function problem(): string {
		return null === $this->dir ? '' : self::lines_problem( $this->headers()['lines'] );
	}

	/**
	 * Reads the headers of HEADERS from the style.css of a theme folder.
	 *
	 * @since 1.0.0
	 *
	 * @param string $dir Absolute path of the theme folder.
	 * @return array<string, string> Values by key; empty strings when style.css or a header is missing.
	 */
	public static function read_headers( string $dir ): array {
		$file   = rtrim( $dir, '/\\' ) . '/style.css';
		$values = is_file( $file ) ? get_file_data( $file, self::HEADERS ) : array();
		$result = array();
		foreach ( array_keys( self::HEADERS ) as $key ) {
			$result[ $key ] = is_string( $values[ $key ] ?? null ) ? $values[ $key ] : '';
		}
		return $result;
	}

	/**
	 * Parses the value of the header "Bootstrap Lines".
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Header value, e.g. "5" or "5, 6".
	 * @return list<int>|null Distinct lines in order, or null when the value is no comma-separated list of line numbers.
	 */
	public static function parse_lines( string $value ): ?array {
		if ( 1 !== preg_match( '~^\s*[1-9]\d{0,2}(?:\s*,\s*[1-9]\d{0,2})*\s*$~', $value ) ) {
			return null;
		}
		$lines = array();
		foreach ( explode( ',', $value ) as $part ) {
			$line = (int) trim( $part );
			if ( ! in_array( $line, $lines, true ) ) {
				$lines[] = $line;
			}
		}
		return $lines;
	}

	/**
	 * Explains what is wrong with a value of the header "Bootstrap Lines".
	 *
	 * The text is English and not translated, so tools and the doctor command
	 * can use it before init.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Header value; empty when the header is missing.
	 * @return string Reason, empty for a valid value whose lines this theme version includes.
	 */
	public static function lines_problem( string $value ): string {
		if ( '' === trim( $value ) ) {
			return sprintf( 'The style.css header "%1$s" is missing; declare the Bootstrap lines the child theme supports, e.g. "%1$s: %2$d".', self::LINES_HEADER, Bootstrap_Line::DEFAULT_LINE );
		}
		$lines = self::parse_lines( $value );
		if ( null === $lines ) {
			return sprintf( 'The style.css header "%1$s" must list line numbers separated by commas, e.g. "%2$d"; it is "%3$s".', self::LINES_HEADER, Bootstrap_Line::DEFAULT_LINE, trim( $value ) );
		}
		$line = Bootstrap_Line::instance();
		foreach ( $lines as $number ) {
			$reason = $line->unavailable_reason( $number );
			if ( '' !== $reason ) {
				return sprintf( 'The style.css header "%1$s" names a line this theme version does not include: %2$s', self::LINES_HEADER, $reason );
			}
		}
		return '';
	}

	/**
	 * Queues an admin notice when the active child theme declares no usable lines; runs on admin_init.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function queue_notice(): void {
		$child   = self::instance();
		$problem = $child->problem();
		if ( '' === $problem ) {
			return;
		}
		$folder = basename( (string) $child->dir() );
		Notices::add(
			self::NOTICE,
			static fn(): string => sprintf(
				/* translators: 1: folder name of the child theme, 2: reason in English. */
				__( 'The child theme %1$s needs a fix in its style.css: %2$s The creationell Theme uses the project styles of a child theme only for the Bootstrap lines it declares.', 'creationell-wp-theme' ),
				$folder,
				$problem
			),
			Notices::WARNING
		);
	}

	/**
	 * Collects the doctor section "child".
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Values; "warnings" lists the problem with the declared lines.
	 */
	public static function doctor_section(): array {
		$child   = self::instance();
		$dir     = $child->dir();
		$values  = array(
			'active'         => null !== $dir,
			'folder'         => null === $dir ? '' : basename( $dir ),
			'declared_lines' => $child->lines(),
			'update_uri'     => $child->headers()['update_uri'],
		);
		$problem = $child->problem();
		if ( '' !== $problem ) {
			$values['warnings'] = array( $problem . ' Run wp creationell-theme child check for all rules.' );
		}
		return $values;
	}
}
