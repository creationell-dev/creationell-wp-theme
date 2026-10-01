<?php
/**
 * Bootstrap line of the theme.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings;

use Closure;
use Creationell\WpTheme\Admin\Notices;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Tells which Bootstrap line (major version) the theme uses and which lines this version includes.
 *
 * This theme version includes line 5 only. A site may hold a line with the
 * constant CREATIONELL_WP_THEME_BOOTSTRAP_LINE; a request for a line the theme
 * does not include keeps line 5, and administrators and the doctor command see
 * why. Assets and the stylesheet fingerprint read the line here.
 *
 * @since 1.0.0
 */
final class Bootstrap_Line {

	/**
	 * Name of the site constant that holds a line.
	 *
	 * @since 1.0.0
	 */
	public const CONSTANT = 'CREATIONELL_WP_THEME_BOOTSTRAP_LINE';

	/**
	 * Line used when no available line is requested.
	 *
	 * @since 1.0.0
	 */
	public const DEFAULT_LINE = 5;

	/**
	 * Known lines mapped to their availability in this theme version.
	 *
	 * @since 1.0.0
	 */
	public const LINES = array(
		5 => true,
		6 => false,
	);

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Reads a constant by name; returns null for an undefined one.
	 *
	 * @var Closure
	 * @phpstan-var Closure(string): mixed
	 */
	private Closure $constants;

	/**
	 * Takes the constant reader; without one it reads the real constants.
	 *
	 * @since 1.0.0
	 *
	 * @param Closure|null $constants Returns the value of a constant by name, null when undefined.
	 * @phpstan-param (Closure(string): mixed)|null $constants
	 */
	public function __construct( ?Closure $constants = null ) {
		$this->constants = $constants ?? static fn( string $name ): mixed => defined( $name ) ? constant( $name ) : null;
	}

	/**
	 * Returns the shared instance, which reads the real constants.
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
	 * @param self|null $line Instance.
	 * @return void
	 */
	public static function set_instance( ?self $line ): void {
		self::$instance = $line;
	}

	/**
	 * Returns the active line: the requested line when this version includes it, otherwise 5.
	 *
	 * @since 1.0.0
	 *
	 * @return int Line.
	 */
	public function active(): int {
		$requested = $this->requested_by_constant();
		return null !== $requested && true === ( self::LINES[ $requested ] ?? false ) ? $requested : self::DEFAULT_LINE;
	}

	/**
	 * Returns the known lines mapped to their availability.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, bool> Availability by line, e.g. array( 5 => true, 6 => false ).
	 */
	public function available(): array {
		return self::LINES;
	}

	/**
	 * Returns why a line cannot be used.
	 *
	 * The text is English and not translated, so it may be read before init;
	 * the admin notice translates its own wording.
	 *
	 * @since 1.0.0
	 *
	 * @param int $line Line.
	 * @return string Reason, empty for an available line.
	 */
	public function unavailable_reason( int $line ): string {
		if ( true === ( self::LINES[ $line ] ?? null ) ) {
			return '';
		}
		if ( isset( self::LINES[ $line ] ) ) {
			return sprintf( 'Line %1$d (Bootstrap %1$d) is not included in this theme version.', $line );
		}
		return sprintf( 'Line %d does not exist.', $line );
	}

	/**
	 * Returns the line the site constant asks for.
	 *
	 * An int or a string of digits counts; other values are ignored.
	 *
	 * @since 1.0.0
	 *
	 * @return int|null Requested line, or null without a usable constant.
	 */
	public function requested_by_constant(): ?int {
		$value = ( $this->constants )( self::CONSTANT );
		if ( is_int( $value ) ) {
			return $value;
		}
		if ( is_string( $value ) && 1 === preg_match( '~^\d{1,3}$~', $value ) ) {
			return (int) $value;
		}
		return null;
	}

	/**
	 * Queues an admin notice when the constant asks for a line this version does not include.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function queue_notice(): void {
		$requested = $this->requested_by_constant();
		if ( null === $requested || $requested === $this->active() ) {
			return;
		}
		$reason = $this->unavailable_reason( $requested );
		$active = $this->active();
		Notices::add(
			'bootstrap-line',
			static fn(): string => sprintf(
				/* translators: 1: name of the constant, 2: requested line, 3: reason in English, 4: line in use. */
				__( '%1$s asks for Bootstrap line %2$d: %3$s The creationell Theme keeps line %4$d.', 'creationell-wp-theme' ),
				self::CONSTANT,
				$requested,
				$reason,
				$active
			),
			Notices::WARNING
		);
	}
}
