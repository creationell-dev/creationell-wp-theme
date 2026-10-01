<?php
/**
 * WP-CLI command "wp creationell-theme settings".
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Cli;

use Creationell\WpTheme\Compiler\Custom_Stylesheet;
use Creationell\WpTheme\Settings\Contrast_Rules;
use Creationell\WpTheme\Settings\Definition;
use Creationell\WpTheme\Settings\Font_Catalog;
use Creationell\WpTheme\Settings\Font_Coverage;
use Creationell\WpTheme\Settings\Language;
use Creationell\WpTheme\Settings\Registry;
use Creationell\WpTheme\Settings\Sanitizer;
use Creationell\WpTheme\Settings\Set_Result;
use Creationell\WpTheme\Settings\Set_Status;
use Creationell\WpTheme\Settings\Setter;
use Creationell\WpTheme\Settings\Settings;
use Creationell\WpTheme\Settings\Snapshot;
use Creationell\WpTheme\Settings\Write_Context;
use WP_CLI;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Lists, reads, sets and resets the theme settings and rebuilds their snapshots.
 *
 * Writes go through the setter with the channel "cli" and the user of the
 * global parameter --user; without a user with the capability of the setting
 * the setter refuses. "set" and "reset" end with exit code 0 for
 * saved, removed and unchanged, otherwise with 1 and the message of the setter.
 * Without --lang the commands work in the default language. The class also
 * collects the section "settings" of "wp creationell-theme doctor".
 *
 * Example:
 *
 *     wp --user=admin creationell-theme settings set color_primary '#6f2da8'
 *     wp creationell-theme settings get footer_text --lang=en
 *
 * @since 1.0.0
 */
final class Settings_Command {

	/**
	 * Command name below the theme namespace.
	 *
	 * @since 1.0.0
	 */
	public const NAME = 'creationell-theme settings';

	/**
	 * Key of the doctor section.
	 *
	 * @since 1.0.0
	 */
	public const DOCTOR_SECTION = 'settings';

	/**
	 * Fields of the list.
	 *
	 * @since 1.0.0
	 */
	public const LIST_FIELDS = array( 'key', 'section', 'group', 'type', 'value', 'origin', 'locked' );

	/**
	 * Formats of the list.
	 *
	 * @since 1.0.0
	 */
	public const LIST_FORMATS = array( 'table', 'json' );

	/**
	 * Formats of get: the value alone, or the value with origin and lock as JSON.
	 *
	 * @since 1.0.0
	 */
	public const GET_FORMATS = array( 'plain', 'json' );

	/**
	 * Sections the doctor reports; module switches and the Bootstrap line have their own sections.
	 *
	 * @since 1.0.0
	 */
	public const DOCTOR_SETTING_SECTIONS = array( 'design', 'texts' );

	/**
	 * Registers the command and the doctor section; runs on cli_init.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		WP_CLI::add_command( self::NAME, self::class );
		if ( ! in_array( self::DOCTOR_SECTION, Doctor_Command::section_keys(), true ) ) {
			Doctor_Command::add_section( self::DOCTOR_SECTION, array( self::class, 'doctor_section' ) );
		}
	}

	/**
	 * Lists the settings with value, origin and lock.
	 *
	 * ## OPTIONS
	 *
	 * [--lang=<code>]
	 * : Language of the values; default: the default language.
	 *
	 * [--section=<section>]
	 * : Only settings of one section: system, modules, design or texts.
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
	 *     wp creationell-theme settings list --section=design --format=json
	 *
	 * @subcommand list
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments; none.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function list_( array $args, array $assoc_args ): void {
		unset( $args );
		$format = self::choice( $assoc_args, 'format', self::LIST_FORMATS, 'table' );
		$lang   = self::lang( $assoc_args );
		$filter = $assoc_args['section'] ?? null;
		if ( null !== $filter && ( ! is_string( $filter ) || ! in_array( $filter, Definition::SECTIONS, true ) ) ) {
			WP_CLI::error( sprintf( 'Unknown section; use one of: %s.', implode( ', ', Definition::SECTIONS ) ) );
			return;
		}
		$settings = Settings::instance();
		$items    = array();
		foreach ( Registry::instance()->all() as $key => $definition ) {
			if ( null !== $filter && $filter !== $definition->section ) {
				continue;
			}
			$effective = $settings->effective( $key, $lang );
			$items[]   = array(
				'key'     => $key,
				'section' => $definition->section,
				'group'   => $definition->group,
				'type'    => $definition->type,
				'value'   => 'table' === $format ? self::cell( $effective['value'] ) : $effective['value'],
				'origin'  => $effective['origin'],
				'locked'  => 'table' === $format ? self::cell( $effective['locked'] ) : $effective['locked'],
			);
		}
		if ( 'json' === $format ) {
			$json = wp_json_encode( $items, JSON_UNESCAPED_SLASHES );
			WP_CLI::line( false === $json ? '[]' : $json );
			return;
		}
		\WP_CLI\Utils\format_items( 'table', $items, self::LIST_FIELDS );
	}

	/**
	 * Prints the value of a setting.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : Setting key, e.g. color_primary.
	 *
	 * [--lang=<code>]
	 * : Language; default: the default language. A translated key falls back to the default language.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: plain
	 * options:
	 *   - plain
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp creationell-theme settings get color_primary
	 *     wp creationell-theme settings get footer_text --lang=en --format=json
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments: the key.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function get( array $args, array $assoc_args ): void {
		$format = self::choice( $assoc_args, 'format', self::GET_FORMATS, 'plain' );
		$key    = self::key( $args, 'get <key>' );
		$lang   = self::lang( $assoc_args );

		$effective = Settings::instance()->effective( $key, $lang );
		if ( 'plain' === $format ) {
			WP_CLI::line( self::cell( $effective['value'] ) );
			return;
		}
		$json = wp_json_encode(
			array(
				'key'    => $key,
				'lang'   => $lang,
				'value'  => $effective['value'],
				'origin' => $effective['origin'],
				'locked' => $effective['locked'],
			),
			JSON_UNESCAPED_SLASHES
		);
		WP_CLI::line( false === $json ? '{}' : $json );
	}

	/**
	 * Sets a setting through the setter; needs --user with the capability of the setting.
	 *
	 * Exit code 0 for saved, removed (the value equals the next lower layer) and
	 * unchanged; 1 with the message of the setter otherwise, e.g. for a color
	 * below the contrast minimum.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : Setting key, e.g. color_primary.
	 *
	 * <value>
	 * : New value, e.g. '#6f2da8'.
	 *
	 * [--lang=<code>]
	 * : Language of a translated text; default: the default language.
	 *
	 * ## EXAMPLES
	 *
	 *     wp --user=admin creationell-theme settings set color_primary '#6f2da8'
	 *     wp --user=editor creationell-theme settings set footer_text 'Hello' --lang=en
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments: key and value.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function set( array $args, array $assoc_args ): void {
		if ( ! isset( $args[0], $args[1] ) || '' === $args[0] ) {
			WP_CLI::error( 'Usage: wp creationell-theme settings set <key> <value> [--lang=<code>].' );
			return;
		}
		$ctx    = self::context( $assoc_args );
		$setter = Setter::instance();
		self::finish( $setter, $setter->set( $args[0], $args[1], $ctx ), $ctx );
	}

	/**
	 * Removes the backend value of a setting, so the next lower layer applies; needs --user with the capability of the setting.
	 *
	 * Exit code 0 for removed and unchanged (no backend value), 1 with the
	 * message of the setter otherwise.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : Setting key, e.g. color_primary.
	 *
	 * [--lang=<code>]
	 * : Language of a translated text; default: the default language.
	 *
	 * ## EXAMPLES
	 *
	 *     wp --user=admin creationell-theme settings reset color_primary
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments: the key.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function reset( array $args, array $assoc_args ): void {
		$key    = self::key( $args, 'reset <key>' );
		$ctx    = self::context( $assoc_args );
		$setter = Setter::instance();
		self::finish( $setter, $setter->reset( $key, $ctx ), $ctx );
	}

	/**
	 * Rebuilds the settings snapshots of all active languages from the stored rows.
	 *
	 * Needed after a theme update or rows written without the theme; the admin
	 * does it on the next admin page. Changes no setting and needs no --user.
	 *
	 * ## EXAMPLES
	 *
	 *     wp creationell-theme settings rebuild
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string>   $args       Positional arguments; none.
	 * @param array<string, mixed> $assoc_args Associative arguments; none.
	 * @return void
	 */
	public function rebuild( array $args, array $assoc_args ): void {
		unset( $args, $assoc_args );
		$changed = Snapshot::instance()->rebuild();
		if ( array() === $changed ) {
			WP_CLI::success( 'The settings snapshots were up to date.' );
			return;
		}
		WP_CLI::success( sprintf( 'Rebuilt the settings snapshots of: %s.', implode( ', ', $changed ) ) );
	}

	/**
	 * Collects the doctor section "settings".
	 *
	 * Values: per design and text setting the effective value with origin and
	 * lock, the registry default and the backend row (also when a lock hides it),
	 * for translated keys the values of the other languages; the findings of the
	 * PHP layers and of the font catalog; the font catalog with the coverage of
	 * the site locales; the contrast rules the effective colors break (only the
	 * PHP layers can hold such colors); whether the snapshots are stale;
	 * whether the multilang module of ACF Extended runs.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Values; "warnings" when there is something to fix.
	 */
	public static function doctor_section(): array {
		$settings = Settings::instance();
		$language = Language::instance();
		$default  = $language->default();
		$others   = array_values( array_filter( $language->active(), static fn( string $code ): bool => $code !== $default ) );

		$values = array();
		foreach ( Registry::instance()->all() as $key => $definition ) {
			if ( ! in_array( $definition->section, self::DOCTOR_SETTING_SECTIONS, true ) ) {
				continue;
			}
			$effective      = $settings->effective( $key, $default );
			$values[ $key ] = array(
				'value'   => $effective['value'],
				'origin'  => $effective['origin'],
				'locked'  => $effective['locked'],
				'default' => $definition->default,
				'backend' => $settings->backend_value( $key, $default ),
			);
			if ( $definition->translatable && array() !== $others ) {
				$values[ $key ]['languages'] = array();
				foreach ( $others as $code ) {
					$values[ $key ]['languages'][ $code ] = $settings->get( $key, $code );
				}
			}
		}

		$fonts    = self::fonts();
		$contrast = self::contrast( $default );
		$findings = array();
		foreach ( $settings->findings() as $finding ) {
			$findings[] = array(
				'key'     => $finding['key'],
				'source'  => $finding['layer'],
				'message' => $finding['message'],
			);
		}
		foreach ( Font_Catalog::instance()->findings() as $finding ) {
			$findings[] = array(
				'key'     => '' === $finding['key'] ? $finding['slug'] : $finding['key'],
				'source'  => 'font_catalog',
				'message' => $finding['message'],
			);
		}
		$stale = Snapshot::instance()->is_stale();
		$acfe  = $language->acfe_multilang_active();

		$section  = array(
			'values'         => $values,
			'findings'       => $findings,
			'fonts'          => $fonts,
			'contrast'       => $contrast,
			'snapshot_stale' => $stale,
			'acfe_multilang' => $acfe,
		);
		$warnings = self::warnings( $findings, $fonts, $contrast, $stale, $acfe );
		if ( array() !== $warnings ) {
			$section['warnings'] = $warnings;
		}
		return $section;
	}

	/**
	 * Writes the result of the setter: commits, reports a rebuilt stylesheet and ends with the exit code.
	 *
	 * @since 1.0.0
	 *
	 * @param Setter        $setter Setter.
	 * @param Set_Result    $result Result.
	 * @param Write_Context $ctx    Context.
	 * @return void
	 */
	private static function finish( Setter $setter, Set_Result $result, Write_Context $ctx ): void {
		$before = get_option( Custom_Stylesheet::STATE_OPTION, null );
		$setter->commit();
		$after = get_option( Custom_Stylesheet::STATE_OPTION, null );

		if ( ! $result->succeeded() ) {
			WP_CLI::error( self::refusal( $result, $ctx ) );
			return;
		}
		$shown = $result->key . ( isset( $result->data['lang'] ) && is_string( $result->data['lang'] ) ? ' (' . $result->data['lang'] . ')' : '' );
		if ( Set_Status::SAVED === $result->status ) {
			WP_CLI::success( sprintf( 'Saved %s.', $shown ) );
		} elseif ( Set_Status::REMOVED === $result->status ) {
			WP_CLI::success( sprintf( '%s is back at its default; the backend value was removed.', $shown ) );
		} else {
			WP_CLI::success( sprintf( '%s is unchanged.', $shown ) );
		}
		if ( $before !== $after && is_array( $after ) ) {
			self::report_build( $after );
		}
	}

	/**
	 * Reports the stylesheet build that a commit started.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $state Stored state of the build.
	 * @return void
	 */
	private static function report_build( array $state ): void {
		$status = $state['status'] ?? null;
		if ( 'ok' === $status ) {
			$files = is_array( $state['files'] ?? null ) ? $state['files'] : array();
			WP_CLI::log( sprintf( 'Rebuilt the stylesheet: %s.', is_string( $files['ltr'] ?? null ) ? $files['ltr'] : '' ) );
		} elseif ( 'package' === $status ) {
			WP_CLI::log( 'The package stylesheet applies again.' );
		} elseif ( 'failed' === $status ) {
			WP_CLI::warning( sprintf( 'The stylesheet could not be built; the previous one stays active. Error: %s Run wp creationell-theme css build after the fix.', is_string( $state['error'] ?? null ) ? $state['error'] : '' ) );
		}
	}

	/**
	 * Builds the error message of a refused write.
	 *
	 * @since 1.0.0
	 *
	 * @param Set_Result    $result Result.
	 * @param Write_Context $ctx    Context.
	 * @return string Message: key, message of the setter and status.
	 */
	private static function refusal( Set_Result $result, Write_Context $ctx ): string {
		$message = '' === $result->message ? 'The setting was not changed.' : $result->message;
		if ( Set_Status::FORBIDDEN === $result->status && 0 === $ctx->user_id ) {
			$registry   = Registry::instance();
			$capability = $registry->has( $result->key ) ? $registry->get( $result->key )->capability : '';
			$message   .= sprintf( ' Run the command with --user=<login> of a user with the capability %s.', $capability );
		}
		return sprintf( '%1$s: %2$s (%3$s)', $result->key, $message, $result->status->value );
	}

	/**
	 * Builds the write context of the current WP-CLI user.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return Write_Context Context with channel "cli".
	 */
	private static function context( array $assoc_args ): Write_Context {
		$lang = self::lang( $assoc_args, true );
		return new Write_Context( channel: 'cli', user_id: get_current_user_id(), lang: $lang );
	}

	/**
	 * Reads and checks --lang.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @param bool                 $allow_all  Whether "all" passes through (the setter refuses it with its own status).
	 * @return string Language code; the default language without --lang.
	 */
	private static function lang( array $assoc_args, bool $allow_all = false ): string {
		$language = Language::instance();
		$lang     = $assoc_args['lang'] ?? null;
		if ( null === $lang ) {
			return $language->default();
		}
		$active = $language->active();
		if ( is_string( $lang ) && ( in_array( $lang, $active, true ) || ( $allow_all && Language::ALL === $lang ) ) ) {
			return $lang;
		}
		WP_CLI::error( sprintf( 'Unknown language %1$s; active languages: %2$s.', is_string( $lang ) ? $lang : '?', implode( ', ', $active ) ) );
		return $language->default();
	}

	/**
	 * Reads the key argument and checks that it exists.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $args  Positional arguments.
	 * @param string             $usage Usage after "settings".
	 * @return string Key.
	 */
	private static function key( array $args, string $usage ): string {
		$key = $args[0] ?? '';
		if ( '' === $key ) {
			WP_CLI::error( sprintf( 'Usage: wp creationell-theme settings %s [--lang=<code>].', $usage ) );
		}
		if ( ! Registry::instance()->has( $key ) ) {
			WP_CLI::error( sprintf( 'Unknown setting: %s. See wp creationell-theme settings list.', $key ) );
		}
		return $key;
	}

	/**
	 * Reads an argument with a closed set of values; stops with an error for another value.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @param string               $name       Argument name.
	 * @param array<int, string>   $choices    Allowed values.
	 * @param string               $fallback   Value without the argument.
	 * @return string Value.
	 */
	private static function choice( array $assoc_args, string $name, array $choices, string $fallback ): string {
		$value = $assoc_args[ $name ] ?? $fallback;
		if ( is_string( $value ) && in_array( $value, $choices, true ) ) {
			return $value;
		}
		WP_CLI::error( sprintf( 'Unknown %1$s; use one of: %2$s.', $name, implode( ', ', $choices ) ) );
		return $fallback;
	}

	/**
	 * Collects the font catalog and the coverage of the site locales per font setting.
	 *
	 * @since 1.0.0
	 *
	 * @return array{catalog: array<string, array{name: string, scripts: array<int, string>, faces: int}>, locales: array<int, string>, coverage: array<string, array{font: string, missing: array<int, string>}>} Fonts.
	 */
	private static function fonts(): array {
		$catalog = Font_Catalog::instance();
		$fonts   = array();
		foreach ( $catalog->all() as $slug => $family ) {
			$fonts[ $slug ] = array(
				'name'    => $family->name(),
				'scripts' => $family->scripts,
				'faces'   => count( $family->faces ),
			);
		}
		$locales  = Font_Coverage::site_locales();
		$coverage = array();
		foreach ( Registry::instance()->all() as $key => $definition ) {
			if ( 'font' !== $definition->type ) {
				continue;
			}
			$family           = $catalog->for_setting( $key );
			$coverage[ $key ] = array(
				'font'    => null === $family ? Sanitizer::INHERIT : $family->slug,
				'missing' => null === $family ? array() : Font_Coverage::missing( $family, $locales ),
			);
		}
		return array(
			'catalog'  => $fonts,
			'locales'  => $locales,
			'coverage' => $coverage,
		);
	}

	/**
	 * Returns the contrast rules the effective colors break, with the origin of both sides.
	 *
	 * @since 1.0.0
	 *
	 * @param string $lang Default language.
	 * @return array<int, array{fg: string, bg: string, ratio: float, min: float, reason: string, origins: array{fg: string, bg: string}}> Failures in rule order.
	 */
	private static function contrast( string $lang ): array {
		$settings = Settings::instance();
		$colors   = array();
		$origins  = array( Contrast_Rules::BLACK_OR_WHITE => 'rule' );
		foreach ( Registry::instance()->all() as $key => $definition ) {
			if ( 'contrast' === $definition->a11y_rule ) {
				$effective       = $settings->effective( $key, $lang );
				$colors[ $key ]  = $effective['value'];
				$origins[ $key ] = $effective['origin'];
			}
		}
		$failures = array();
		foreach ( Contrast_Rules::check( $colors ) as $failure ) {
			$fg         = $failure['pair']['fg'];
			$bg         = $failure['pair']['bg'];
			$failures[] = array(
				'fg'      => $fg,
				'bg'      => $bg,
				'ratio'   => floor( $failure['ratio'] * 100 ) / 100,
				'min'     => $failure['min'],
				'reason'  => $failure['reason'],
				'origins' => array(
					'fg' => $origins[ $fg ] ?? '',
					'bg' => $origins[ $bg ] ?? '',
				),
			);
		}
		return $failures;
	}

	/**
	 * Builds the warnings of the section.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array{key: string, source: string, message: string}>                            $findings Findings.
	 * @param array{coverage: array<string, array{font: string, missing: array<int, string>}>}           $fonts    Fonts.
	 * @param array<int, array{fg: string, bg: string, ratio: float, min: float, origins: array<mixed>}> $contrast Contrast failures.
	 * @param bool                                                                                       $stale    Whether the snapshots are stale.
	 * @param bool                                                                                       $acfe     Whether the multilang module of ACF Extended runs.
	 * @return array<int, string> Warnings in English.
	 */
	private static function warnings( array $findings, array $fonts, array $contrast, bool $stale, bool $acfe ): array {
		$warnings = array_column( $findings, 'message' );
		foreach ( $fonts['coverage'] as $key => $coverage ) {
			if ( array() !== $coverage['missing'] ) {
				$warnings[] = sprintf( 'The font %1$s of %2$s declares no glyphs for %3$s; browsers fall back to another font there.', $coverage['font'], $key, implode( ', ', $coverage['missing'] ) );
			}
		}
		foreach ( $contrast as $failure ) {
			$warnings[] = sprintf(
				'%1$s on %2$s has a contrast of %3$s:1, at least %4$s:1 is needed (set by %5$s). Only a child theme lock or a constant can set such a color.',
				$failure['fg'],
				$failure['bg'],
				number_format( $failure['ratio'], 2, '.', '' ),
				rtrim( rtrim( number_format( $failure['min'], 2, '.', '' ), '0' ), '.' ),
				is_string( $failure['origins']['fg'] ?? null ) ? $failure['origins']['fg'] : ''
			);
		}
		if ( $stale ) {
			$warnings[] = 'The settings snapshots are stale; run wp creationell-theme settings rebuild or open an admin page.';
		}
		if ( $acfe ) {
			$warnings[] = 'The multilang module of ACF Extended is active; keep the theme settings pages out of it, or it saves global settings per language.';
		}
		return $warnings;
	}

	/**
	 * Formats a value for plain output and table cells.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return string Empty for null, true/false for booleans, JSON for arrays.
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
