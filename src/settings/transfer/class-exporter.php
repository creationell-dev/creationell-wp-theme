<?php
/**
 * Export of the theme settings.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

use Closure;
use Creationell\WpTheme\Core\Capabilities;
use Creationell\WpTheme\Settings\Bootstrap_Line;
use Creationell\WpTheme\Settings\Language;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Builds the transfer file of the chosen sections and languages and logs the export.
 *
 * The export checks the capability creationell_wp_theme_import_export of the
 * user, refuses "All languages" and the multilingual mode of ACF Extended and
 * needs at least one known section. The languages are the chosen ones that
 * are active; the header names the languages of the site. The file goes
 * through Transfer_File::from_array(), so every export can be imported again.
 * The snapshot builds the same file in every active language without a check
 * and without a log entry; Backup_Store uses it for backups.
 *
 * Example:
 *
 *     $file = Exporter::instance()->export( new Export_Request( array( 'settings' ), null, get_current_user_id(), 'cli' ) );
 *     $json = Json_Codec::encode( $file->to_array() );
 *
 * @since 1.0.0
 */
final class Exporter {

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Sections by ID; null for the ones of Sections::all().
	 *
	 * @var array<string, Section_Interface>|null
	 */
	private ?array $sections;

	/**
	 * Log; null for the shared one at the time of writing.
	 *
	 * @var Transfer_Log|null
	 */
	private ?Transfer_Log $log;

	/**
	 * Returns the current Unix time.
	 *
	 * @var Closure
	 * @phpstan-var Closure(): int
	 */
	private Closure $clock;

	/**
	 * Returns the active child theme.
	 *
	 * @var Closure
	 * @phpstan-var Closure(): (array{slug: string, name: string}|null)
	 */
	private Closure $child;

	/**
	 * Takes the sections, the log, the clock and the child theme reader.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, Section_Interface>|null $sections Sections by ID; without them the ones of Sections::all().
	 * @param Transfer_Log|null                     $log      Log; without one the shared log.
	 * @param Closure|null                          $clock    Returns the current Unix time; without one time().
	 * @param Closure|null                          $child    Returns slug and name of the active child theme or null; without one it asks WordPress.
	 * @phpstan-param (Closure(): int)|null $clock
	 * @phpstan-param (Closure(): (array{slug: string, name: string}|null))|null $child
	 */
	public function __construct( ?array $sections = null, ?Transfer_Log $log = null, ?Closure $clock = null, ?Closure $child = null ) {
		$this->sections = $sections;
		$this->log      = $log;
		$this->clock    = $clock ?? static fn(): int => time();
		$this->child    = $child ?? self::child_theme( ... );
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
	 * @param self|null $exporter Instance.
	 * @return void
	 */
	public static function set_instance( ?self $exporter ): void {
		self::$instance = $exporter;
	}

	/**
	 * Exports the chosen sections and languages and logs the export.
	 *
	 * @since 1.0.0
	 *
	 * @param Export_Request $request Request.
	 * @return Transfer_File File.
	 * @throws Transfer_Exception With forbidden, language_all, acfe_multilang or nothing_selected; nothing is logged then.
	 */
	public function export( Export_Request $request ): Transfer_File {
		if ( ! user_can( $request->user_id, Capabilities::IMPORT_EXPORT ) ) {
			Transfer_Exception::raise( Transfer_Error::FORBIDDEN );
		}
		$language = Language::instance();
		if ( Language::ALL === $language->current() ) {
			Transfer_Exception::raise( Transfer_Error::LANGUAGE_ALL );
		}
		if ( $language->acfe_multilang_active() ) {
			Transfer_Exception::raise( Transfer_Error::ACFE_MULTILANG );
		}
		$active = $this->languages();
		$langs  = null === $request->langs ? $active : array_values( array_intersect( $active, $request->langs ) );
		$file   = $this->build( $request->sections, new Export_Request( $request->sections, $langs, $request->user_id, $request->channel ) );
		$data   = Json_Codec::encode( $file->to_array() );
		( $this->log ?? Transfer_Log::instance() )->add( 'export', $request->channel, $request->user_id, $file->sections, $langs, array(), null, hash( 'sha256', $data ) );
		return $file;
	}

	/**
	 * Builds the file of the given sections in every active language, without a check and without a log entry.
	 *
	 * The caller checks the capability; Backup_Store uses it as snapshot source.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $sections Section IDs.
	 * @phpstan-param list<string> $sections
	 * @return Transfer_File File.
	 * @throws Transfer_Exception With nothing_selected when no known section is given.
	 */
	public function snapshot( array $sections ): Transfer_File {
		return $this->build( $sections, new Export_Request( $sections, $this->languages(), 0, Transfer_Log::channel() ) );
	}

	/**
	 * Builds and checks the file.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $wanted  Section IDs that were asked for.
	 * @param Export_Request     $request Request with the languages to export.
	 * @phpstan-param list<string> $wanted
	 * @return Transfer_File File.
	 * @throws Transfer_Exception With nothing_selected when no known section is given, schema_invalid when a value of the site does not fit the format.
	 */
	private function build( array $wanted, Export_Request $request ): Transfer_File {
		$sections = array();
		foreach ( $this->sections() as $id => $section ) {
			if ( in_array( $id, $wanted, true ) ) {
				$sections[ $id ] = $section;
			}
		}
		if ( array() === $sections ) {
			Transfer_Exception::raise( Transfer_Error::NOTHING_SELECTED );
		}
		$language = Language::instance();
		$data     = array(
			'format'            => Transfer_File::FORMAT,
			'schema_version'    => Migrator::CURRENT,
			'theme_version'     => CREATIONELL_WP_THEME_VERSION,
			'bootstrap_line'    => Bootstrap_Line::instance()->active(),
			'child'             => ( $this->child )(),
			'exported_at'       => gmdate( 'Y-m-d\TH:i:s\Z', ( $this->clock )() ),
			'source'            => home_url(),
			'wp_version'        => get_bloginfo( 'version' ),
			'languages'         => $this->languages(),
			'default_language'  => $language->default(),
			'sections'          => array_keys( $sections ),
			'omitted_sensitive' => isset( $sections[ Settings_Section::ID ] ) ? Settings_Section::omitted() : array(),
		);
		foreach ( $sections as $id => $section ) {
			$data[ $id ] = $section->export( $request );
		}
		return Transfer_File::from_array( $data );
	}

	/**
	 * Returns the languages of the site: the active ones, with the default language.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Language codes in WPML order.
	 * @phpstan-return list<string>
	 */
	private function languages(): array {
		$language = Language::instance();
		$active   = array_values( $language->active() );
		if ( ! in_array( $language->default(), $active, true ) ) {
			array_unshift( $active, $language->default() );
		}
		return $active;
	}

	/**
	 * Returns the sections.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, Section_Interface> Sections by ID.
	 */
	private function sections(): array {
		return $this->sections ?? Sections::all();
	}

	/**
	 * Returns slug and name of the active child theme.
	 *
	 * @since 1.0.0
	 *
	 * @return array{slug: string, name: string}|null Child theme, or null without one.
	 */
	private static function child_theme(): ?array {
		if ( ! is_child_theme() ) {
			return null;
		}
		$slug = get_stylesheet(); // creationell-allow-stylesheet: the folder of the active child theme is written to the file header for information, never used as a key.
		$name = wp_get_theme()->get( 'Name' );
		return array(
			'slug' => $slug,
			'name' => is_string( $name ) && '' !== $name ? $name : $slug,
		);
	}
}
