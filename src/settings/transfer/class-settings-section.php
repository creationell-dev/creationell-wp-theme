<?php
/**
 * Section "settings" of a settings transfer file.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

use Creationell\WpTheme\Settings\Bootstrap_Line;
use Creationell\WpTheme\Settings\Definition;
use Creationell\WpTheme\Settings\Language;
use Creationell\WpTheme\Settings\Registry;
use Creationell\WpTheme\Settings\Set_Result;
use Creationell\WpTheme\Settings\Setter;
use Creationell\WpTheme\Settings\Settings;
use Creationell\WpTheme\Settings\Write_Context;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Exports and imports the setting values: the ones that are the same in every language and the translated ones per language.
 *
 * The section covers every registry key outside the sections "modules" and
 * "system" that is not sensitive. A value is the backend value (null: no
 * deviation, the lower layers apply), with references encoded by
 * Reference_Codec. Sensitive keys are left out and named in the header.
 *
 * The plan goes through the rules of the import in this order: the Bootstrap
 * line is never imported; keys of another Bootstrap line go through the filter
 * creationell_wp_theme_import_line_map; renamed keys go to their new name
 * (the new name in the file wins); removed and unknown keys are skipped;
 * sensitive keys are skipped; translated values of inactive languages are
 * skipped; references are resolved on this site. Everything else goes to the
 * setter in a dry run, per language one set_many() for the values and one
 * reset_many() for the null values, so the contrast rules see all colors of the
 * file together. Registry keys without a value in the file stay as they are
 * ("absent"). apply() writes through the setter in the channel "import" and
 * does not commit; the importer commits once after all sections.
 *
 * @since 1.0.0
 */
final class Settings_Section implements Section_Interface {

	/**
	 * ID of the section.
	 *
	 * @since 1.0.0
	 */
	public const ID = 'settings';

	/**
	 * Filter that maps keys of another Bootstrap line to keys of the active one.
	 *
	 * @since 1.0.0
	 */
	public const LINE_MAP_FILTER = 'creationell_wp_theme_import_line_map';

	/**
	 * Registry sections that belong to other transfer sections or are never transferred.
	 *
	 * @since 1.0.0
	 */
	public const OTHER_SECTIONS = array( 'modules', 'system' );

	/**
	 * Setter; null for the shared one at the time of use.
	 *
	 * @var Setter|null
	 */
	private ?Setter $setter;

	/**
	 * Removed keys with the theme version that removed them.
	 *
	 * @var array<string, string>
	 */
	private array $removed;

	/**
	 * Takes the setter and the removed keys.
	 *
	 * @since 1.0.0
	 *
	 * @param Setter|null                $setter  Setter; without one the shared setter.
	 * @param array<string, string>|null $removed Theme version that removed a key, by key; without them Removed_Keys::all().
	 */
	public function __construct( ?Setter $setter = null, ?array $removed = null ) {
		$this->setter  = $setter;
		$this->removed = $removed ?? Removed_Keys::all();
	}

	/**
	 * Returns the ID of the section.
	 *
	 * @since 1.0.0
	 *
	 * @return string "settings".
	 */
	public function id(): string {
		return self::ID;
	}

	/**
	 * Returns the translated name of the section.
	 *
	 * @since 1.0.0
	 *
	 * @return string Label.
	 */
	public function label(): string {
		return __( 'Settings', 'creationell-wp-theme' );
	}

	/**
	 * Returns the definitions the section transfers.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, Definition> Definitions by key in registry order: not sensitive, not in the sections "modules" and "system".
	 */
	public static function definitions(): array {
		return array_filter(
			Registry::instance()->all(),
			static fn( Definition $definition ): bool => ! $definition->sensitive && ! in_array( $definition->section, self::OTHER_SECTIONS, true )
		);
	}

	/**
	 * Returns the sensitive keys the section leaves out.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Keys in registry order.
	 * @phpstan-return list<string>
	 */
	public static function omitted(): array {
		$keys = array();
		foreach ( Registry::instance()->all() as $key => $definition ) {
			if ( $definition->sensitive && ! in_array( $definition->section, self::OTHER_SECTIONS, true ) ) {
				$keys[] = $key;
			}
		}
		return $keys;
	}

	/**
	 * Returns the values and the translated values per language.
	 *
	 * @since 1.0.0
	 *
	 * @param Export_Request $request Request; null languages mean every active language.
	 * @return array{values: array<string, mixed>, translatable: array<string, array<string, mixed>>} Content.
	 */
	public function export( Export_Request $request ): array {
		$settings     = Settings::instance();
		$default      = Language::instance()->default();
		$langs        = $request->langs ?? Language::instance()->active();
		$values       = array();
		$translatable = array_fill_keys( $langs, array() );
		foreach ( self::definitions() as $key => $definition ) {
			if ( ! $definition->translatable ) {
				$values[ $key ] = Reference_Codec::encode( $definition, $settings->backend_value( $key, $default ) );
				continue;
			}
			foreach ( $langs as $lang ) {
				$translatable[ $lang ][ $key ] = Reference_Codec::encode( $definition, $settings->backend_value( $key, $lang ) );
			}
		}
		return array(
			'values'       => $values,
			'translatable' => $translatable,
		);
	}

	/**
	 * Plans the import of the values without writing.
	 *
	 * @since 1.0.0
	 *
	 * @param Transfer_File  $file    File.
	 * @param Import_Options $options Options.
	 * @return array<int, Plan_Row> Rows: the keys the export left out, the values, then the translated values per language.
	 * @phpstan-return list<Plan_Row>
	 */
	public function plan( Transfer_File $file, Import_Options $options ): array {
		$rows = array();
		foreach ( $file->omitted_sensitive as $key ) {
			$rows[] = self::row( $key, null, null, null, Row_Status::SENSITIVE_SKIPPED, __( 'Sensitive settings are left out of every export and never imported.', 'creationell-wp-theme' ) );
		}
		$map    = $this->line_map( $file );
		$active = Language::instance()->active();
		$rows   = array_merge( $rows, $this->plan_part( $file->values, null, $file, $options, $map ) );
		foreach ( $file->translatable as $lang => $values ) {
			if ( ! $options->wants_lang( $lang ) ) {
				continue;
			}
			if ( in_array( $lang, $active, true ) ) {
				$rows = array_merge( $rows, $this->plan_part( $values, $lang, $file, $options, $map ) );
				continue;
			}
			foreach ( $values as $key => $value ) {
				$rows[] = self::row( $key, $lang, null, $value, Row_Status::LANGUAGE_INACTIVE, __( 'This language is not active on this site.', 'creationell-wp-theme' ) );
			}
		}
		return $rows;
	}

	/**
	 * Writes the rows "save" and "reset" through the setter, per language: first the values, then the default language, then the others.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, Plan_Row> $rows    Rows of this section from a fresh plan.
	 * @param Import_Options       $options Options.
	 * @phpstan-param list<Plan_Row> $rows
	 * @return array<int, Plan_Row> Rows with the status of the write.
	 * @phpstan-return list<Plan_Row>
	 */
	public function apply( array $rows, Import_Options $options ): array {
		$groups = array();
		foreach ( $rows as $index => $row ) {
			if ( self::ID !== $row->section || ( Row_Status::SAVE !== $row->status && Row_Status::RESET !== $row->status ) ) {
				continue;
			}
			$groups[ $row->lang ?? '' ][ $index ] = $row;
		}
		$default_lang = Language::instance()->default();
		uksort(
			$groups,
			static fn( int|string $a, int|string $b ): int => self::lang_rank( (string) $a, $default_lang ) <=> self::lang_rank( (string) $b, $default_lang )
		);
		foreach ( $groups as $lang => $group ) {
			$lang    = '' === $lang ? null : (string) $lang;
			$sets    = array();
			$resets  = array();
			$indexes = array();
			foreach ( $group as $index => $row ) {
				$indexes[ $row->key ] = $index;
				if ( Row_Status::RESET === $row->status ) {
					$resets[] = $row->key;
					continue;
				}
				$sets[ $row->key ] = $row->incoming;
			}
			foreach ( $this->run( $sets, $resets, new Write_Context( 'import', $options->user_id, $lang ) ) as $key => $result ) {
				$index          = $indexes[ $key ];
				$rows[ $index ] = $rows[ $index ]->with_status( Row_Status::from_set_status( $result->status ), $result->message );
			}
		}
		return array_values( $rows );
	}

	/**
	 * Plans one part of the file: the values (no language) or the translated values of one active language.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed>             $values  Values by key.
	 * @param string|null                      $lang    Language code, or null for the values.
	 * @param Transfer_File                    $file    File.
	 * @param Import_Options                   $options Options.
	 * @param array<string, string|false>|null $map     Key map of another Bootstrap line, or null.
	 * @return array<int, Plan_Row> Rows in file order, then the absent keys.
	 * @phpstan-return list<Plan_Row>
	 */
	private function plan_part( array $values, ?string $lang, Transfer_File $file, Import_Options $options, ?array $map ): array {
		$registry = Registry::instance();
		$settings = Settings::instance();
		$target   = $lang ?? Language::instance()->default();
		$slots    = array();
		$planned  = array();
		$targets  = array();
		$covered  = array();
		foreach ( $values as $key => $value ) {
			$key             = (string) $key;
			$covered[ $key ] = true;
			$resolved        = $this->target_key( $key, $values, $map, $registry );
			if ( $resolved instanceof Plan_Row ) {
				$slots[] = self::localized( $resolved, $lang, $value );
				continue;
			}
			$new             = $resolved['key'];
			$covered[ $new ] = true;
			if ( isset( $targets[ $new ] ) ) {
				/* translators: %s: setting key. */
				$slots[] = self::row( $key, $lang, null, $value, Row_Status::REMOVED_KEY, sprintf( __( 'The file has another value for %s; that value is imported.', 'creationell-wp-theme' ), $new ) );
				continue;
			}
			$targets[ $new ] = true;
			if ( null !== $resolved['renamed'] ) {
				$slots[] = self::row( $key, $lang, null, $value, Row_Status::RENAMED, $resolved['renamed'] );
			}
			$definition = $registry->get( $new );
			$refusal    = self::refusal( $definition, $lang );
			if ( null !== $refusal ) {
				$slots[] = self::row( $new, $lang, null, $value, $refusal[0], $refusal[1] );
				continue;
			}
			$current  = $settings->backend_value( $new, $target );
			$incoming = $value;
			if ( null !== $value && null !== $definition->reference_type ) {
				$incoming = Reference_Codec::resolve( $definition, $value, $file->source, $target );
				if ( null === $incoming ) {
					$slots[] = self::row( $new, $lang, $current, $value, Row_Status::REFERENCE_UNRESOLVED, __( 'The page, file or term this setting points to does not exist on this site.', 'creationell-wp-theme' ) );
					continue;
				}
			}
			$planned[ $new ] = array(
				'current'  => $current,
				'incoming' => $incoming,
				'note'     => $resolved['note'],
			);
			$slots[]         = $new;
		}

		$sets   = array();
		$resets = array();
		foreach ( $planned as $key => $entry ) {
			if ( null === $entry['incoming'] ) {
				$resets[] = $key;
				continue;
			}
			$sets[ $key ] = $entry['incoming'];
		}
		$results = $this->run( $sets, $resets, new Write_Context( 'import', $options->user_id, $lang, true ) );

		$rows = array();
		foreach ( $slots as $slot ) {
			if ( $slot instanceof Plan_Row ) {
				$rows[] = $slot;
				continue;
			}
			$entry   = $planned[ $slot ];
			$result  = $results[ $slot ];
			$message = trim( $entry['note'] . ' ' . $result->message );
			$rows[]  = new Plan_Row( self::ID, $slot, $lang, $entry['current'], $entry['incoming'], Row_Status::from_set_status( $result->status ), $message );
		}
		foreach ( self::definitions() as $key => $definition ) {
			if ( ! isset( $covered[ $key ] ) && ( null !== $lang ) === $definition->translatable ) {
				$rows[] = self::row( $key, $lang, $settings->backend_value( $key, $target ), null, Row_Status::ABSENT, __( 'The file has no value for this setting; it stays as it is.', 'creationell-wp-theme' ) );
			}
		}
		return $rows;
	}

	/**
	 * Finds the key a file key is imported as.
	 *
	 * @since 1.0.0
	 *
	 * @param string                           $key      Key in the file.
	 * @param array<string, mixed>             $values   All values of the part, to find the new name of a renamed key.
	 * @param array<string, string|false>|null $map      Key map of another Bootstrap line, or null.
	 * @param Registry                         $registry Registry.
	 * @return Plan_Row|array{key: string, note: string, renamed: string|null} Skipped row (key, status and message; language and value follow), or the registry key with a note and the message of a "renamed" row.
	 */
	private function target_key( string $key, array $values, ?array $map, Registry $registry ): Plan_Row|array {
		if ( 'bootstrap_line' === $key ) {
			return self::row( $key, null, null, null, Row_Status::LINE_NEVER_IMPORTED, __( 'The Bootstrap line is never imported; it is part of the code of the site.', 'creationell-wp-theme' ) );
		}
		$new  = $key;
		$note = '';
		if ( null !== $map && array_key_exists( $key, $map ) ) {
			if ( false === $map[ $key ] ) {
				return self::row( $key, null, null, null, Row_Status::LINE_UNMAPPED, __( 'This setting has no counterpart in the Bootstrap line of this site.', 'creationell-wp-theme' ) );
			}
			$new = $map[ $key ];
			/* translators: %s: setting key in the file. */
			$note = sprintf( __( 'Mapped from %s of the other Bootstrap line.', 'creationell-wp-theme' ), $key );
		}
		if ( $registry->has( $new ) ) {
			return array(
				'key'     => $new,
				'note'    => $note,
				'renamed' => null,
			);
		}
		$renamed = self::renamed_to( $new, $registry );
		if ( null !== $renamed ) {
			if ( array_key_exists( $renamed, $values ) ) {
				/* translators: %s: new setting key. */
				return self::row( $key, null, null, null, Row_Status::REMOVED_KEY, sprintf( __( 'This setting is now called %s; the value under the new name is imported.', 'creationell-wp-theme' ), $renamed ) );
			}
			return array(
				'key'     => $renamed,
				/* translators: %s: former setting key. */
				'note'    => sprintf( __( 'Renamed from %s.', 'creationell-wp-theme' ), $key ),
				/* translators: %s: new setting key. */
				'renamed' => sprintf( __( 'This setting is now called %s and is imported under that name.', 'creationell-wp-theme' ), $renamed ),
			);
		}
		if ( isset( $this->removed[ $new ] ) ) {
			/* translators: %s: theme version. */
			return self::row( $key, null, null, null, Row_Status::REMOVED_KEY, sprintf( __( 'This setting was removed in theme version %s.', 'creationell-wp-theme' ), $this->removed[ $new ] ) );
		}
		return self::row( $key, null, null, null, Row_Status::UNKNOWN_KEY, __( 'This theme version does not know this setting.', 'creationell-wp-theme' ) );
	}

	/**
	 * Returns why a registry key is skipped before the setter sees it.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition  $definition Definition.
	 * @param string|null $lang       Language code of the part, or null for the values.
	 * @return array{0: Row_Status, 1: string}|null Status and message, or null when the setter decides.
	 */
	private static function refusal( Definition $definition, ?string $lang ): ?array {
		if ( $definition->sensitive ) {
			return array( Row_Status::SENSITIVE_SKIPPED, __( 'Sensitive settings are never imported.', 'creationell-wp-theme' ) );
		}
		if ( in_array( $definition->section, self::OTHER_SECTIONS, true ) ) {
			$message = 'modules' === $definition->section
				? __( 'Module switches are imported in the section Modules.', 'creationell-wp-theme' )
				: __( 'This setting is never imported.', 'creationell-wp-theme' );
			return array( Row_Status::SECTION_UNKNOWN, $message );
		}
		if ( $definition->translatable && null === $lang ) {
			return array( Row_Status::NOT_TRANSLATABLE, __( 'This setting differs per language; the file must list it per language.', 'creationell-wp-theme' ) );
		}
		if ( ! $definition->translatable && null !== $lang ) {
			return array( Row_Status::NOT_TRANSLATABLE, __( 'This setting is the same in every language; the file must list it without a language.', 'creationell-wp-theme' ) );
		}
		return null;
	}

	/**
	 * Returns the key whose former name is the given key.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $former   Former key.
	 * @param Registry $registry Registry.
	 * @return string|null New key, or null when no definition was renamed from it.
	 */
	private static function renamed_to( string $former, Registry $registry ): ?string {
		foreach ( $registry->all() as $key => $definition ) {
			if ( $former === $definition->renamed_from ) {
				return $key;
			}
		}
		return null;
	}

	/**
	 * Returns the key map of the Bootstrap line of the file when it is not the active line.
	 *
	 * @since 1.0.0
	 *
	 * @param Transfer_File $file File.
	 * @return array<string, string|false>|null Map from file key to key of this site or false; null for the same line.
	 */
	private function line_map( Transfer_File $file ): ?array {
		$active = Bootstrap_Line::instance()->active();
		if ( $file->bootstrap_line === $active ) {
			return null;
		}
		/**
		 * Filters how the keys of a file from another Bootstrap line are imported.
		 *
		 * A key maps to the key of this site that takes its value, or to false
		 * when this line has no counterpart ("line_unmapped"). Keys that are not
		 * in the map are imported under their own name. The Bootstrap line
		 * itself is never imported.
		 *
		 * Example:
		 *
		 *     add_filter(
		 *         'creationell_wp_theme_import_line_map',
		 *         static function ( mixed $map, int $from, int $to ): mixed {
		 *             $map = is_array( $map ) ? $map : array();
		 *             if ( 6 === $from && 5 === $to ) {
		 *                 $map['color_tertiary'] = false;
		 *             }
		 *             return $map;
		 *         },
		 *         10,
		 *         3
		 *     );
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, string|false> $map  Map from file key to key of this site or false; empty: every key keeps its name.
		 * @param int                         $from Bootstrap line of the file.
		 * @param int                         $to   Bootstrap line of this site.
		 */
		$filtered = apply_filters( 'creationell_wp_theme_import_line_map', array(), $file->bootstrap_line, $active );
		$map      = array();
		if ( ! is_array( $filtered ) ) {
			return $map;
		}
		foreach ( $filtered as $key => $target ) {
			if ( is_string( $key ) && ( false === $target || ( is_string( $target ) && '' !== $target ) ) ) {
				$map[ $key ] = $target;
			}
		}
		return $map;
	}

	/**
	 * Runs the sets and the resets of one language through the setter.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $sets   Values by key.
	 * @param array<int, string>   $resets Keys to reset.
	 * @param Write_Context        $ctx    Context.
	 * @return array<string, Set_Result> Results by key.
	 */
	private function run( array $sets, array $resets, Write_Context $ctx ): array {
		$setter  = $this->setter ?? Setter::instance();
		$results = array();
		$done    = array_merge(
			array() === $sets ? array() : $setter->set_many( $sets, $ctx ),
			array() === $resets ? array() : $setter->reset_many( $resets, $ctx )
		);
		foreach ( $done as $result ) {
			$results[ $result->key ] = $result;
		}
		return $results;
	}

	/**
	 * Returns a skipped row with the language and the file value of its part.
	 *
	 * @since 1.0.0
	 *
	 * @param Plan_Row    $row   Row from target_key().
	 * @param string|null $lang  Language code of the part.
	 * @param mixed       $value File value.
	 * @return Plan_Row Row.
	 */
	private static function localized( Plan_Row $row, ?string $lang, mixed $value ): Plan_Row {
		return new Plan_Row( self::ID, $row->key, $lang, null, $value, $row->status, $row->message );
	}

	/**
	 * Builds a row of this section.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $key      Key.
	 * @param string|null $lang     Language code or null.
	 * @param mixed       $current  Backend value or null.
	 * @param mixed       $incoming Value.
	 * @param Row_Status  $status   Status.
	 * @param string      $message  Message.
	 * @return Plan_Row Row.
	 */
	private static function row( string $key, ?string $lang, mixed $current, mixed $incoming, Row_Status $status, string $message ): Plan_Row {
		return new Plan_Row( self::ID, $key, $lang, $current, $incoming, $status, $message );
	}

	/**
	 * Returns the write order of a language group: the values, the default language, the others.
	 *
	 * @since 1.0.0
	 *
	 * @param string $lang         Language code, empty for the values.
	 * @param string $default_lang Default language code.
	 * @return int Rank.
	 */
	private static function lang_rank( string $lang, string $default_lang ): int {
		if ( '' === $lang ) {
			return 0;
		}
		return $default_lang === $lang ? 1 : 2;
	}
}
