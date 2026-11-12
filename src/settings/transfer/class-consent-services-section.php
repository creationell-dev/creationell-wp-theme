<?php
/**
 * Section "consent_services" of a settings transfer file.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

use Creationell\WpTheme\Consent\Consent_Categories;
use Creationell\WpTheme\Consent\Consent_Service_Catalog;
use Creationell\WpTheme\Consent\Consent_Services_Store;
use Creationell\WpTheme\Consent\Consent_Services_Validator;
use Creationell\WpTheme\Core\Capabilities;
use Creationell\WpTheme\Settings\Language;
use Creationell\WpTheme\Settings\Set_Status;
use Creationell\WpTheme\Settings\Write_Context;
use stdClass;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Exports and imports the store of the consent services: the services and their texts per language.
 *
 * The part holds the version "v", the list "services" (structure without
 * language) and "texts" per language code, as in Consent_Services_Store. The
 * plan has a structure row per service of the file (key: service ID, no
 * language) when the default language is imported, a text row per service and
 * language and a row "category:<slug>" per category text and language. The
 * language map applies to the texts as in the section "settings". Each row is
 * checked by Consent_Services_Validator on its own; a service keeps its preset, but its
 * check date only when the preset is in Consent_Service_Catalog, so the page
 * shows the origin "Import" otherwise. A final check of the merged store
 * rejects the rows of the services the validator blames, e.g. two services
 * with the same cookie, and every write when the store gets too large. Services only on the site
 * stay on an import; a restore resets them. A restore also plans a reset row
 * for every text of the site in an active language of the file that the file
 * lacks; texts of other languages stay, except those of a removed service,
 * which the validator drops in every language without a row. apply() writes the merged store
 * with one Consent_Services_Store::save() in the channel "import": all or
 * nothing. Without the capability creationell_wp_theme_manage_consent every
 * row is forbidden.
 *
 * @since 1.0.0
 */
final class Consent_Services_Section implements Section_Interface, Section_Languages {

	/**
	 * ID of the section.
	 *
	 * @since 1.0.0
	 */
	public const ID = 'consent_services';

	/**
	 * Returns the ID of the section.
	 *
	 * @since 1.0.0
	 *
	 * @return string "consent_services".
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
		return __( 'Cookies and services', 'creationell-wp-theme' );
	}

	/**
	 * Returns the stored services and the texts of the requested languages.
	 *
	 * @since 1.0.0
	 *
	 * @param Export_Request $request Request; null languages mean every active language.
	 * @return array{v: int, services: list<mixed>, texts: array<string, mixed>|stdClass} Content; empty texts are an object.
	 */
	public function export( Export_Request $request ): array {
		$store = Consent_Services_Store::read();
		$langs = $request->langs ?? Language::instance()->active();
		$texts = array();
		foreach ( is_array( $store['texts'] ) ? $store['texts'] : array() as $lang => $entry ) {
			if ( in_array( (string) $lang, $langs, true ) ) {
				$texts[ (string) $lang ] = $entry;
			}
		}
		return array(
			'v'        => Consent_Services_Store::VERSION,
			'services' => is_array( $store['services'] ) ? array_values( $store['services'] ) : array(),
			'texts'    => array() === $texts ? new stdClass() : $texts,
		);
	}

	/**
	 * Returns the languages of the texts in the file.
	 *
	 * @since 1.0.0
	 *
	 * @param Transfer_File $file File.
	 * @return array<int, string> Language codes in file order.
	 * @phpstan-return list<string>
	 */
	public function languages( Transfer_File $file ): array {
		$langs = array();
		foreach ( array_keys( self::texts( $file ) ) as $lang ) {
			if ( 1 === preg_match( Transfer_File::LANGUAGE_PATTERN, $lang ) ) {
				$langs[] = $lang;
			}
		}
		return $langs;
	}

	/**
	 * Plans the import of the services and texts without writing.
	 *
	 * @since 1.0.0
	 *
	 * @param Transfer_File  $file    File.
	 * @param Import_Options $options Options.
	 * @return array<int, Plan_Row> Rows: per service of the file its structure and its texts, then the services to reset, the texts of other services and the category texts.
	 * @phpstan-return list<Plan_Row>
	 */
	public function plan( Transfer_File $file, Import_Options $options ): array {
		$part       = self::plain( $file->other_sections[ self::ID ] ?? array() );
		$part       = is_array( $part ) ? $part : array();
		$store      = Consent_Services_Store::read();
		$categories = Consent_Categories::all();
		$incoming   = self::items_list( $part['services'] ?? null );
		$presets    = array() === $incoming ? array() : Consent_Service_Catalog::all();
		$normal     = Consent_Services_Validator::check( $store, $categories )['data'];
		$site       = self::by_id( $store['services'] );
		$site_clean = self::by_id( $normal['services'] );
		$restore    = 'restore' === $options->reason;
		$structure  = $options->wants_lang( Language::instance()->default() );

		$file_ids   = array();
		$structures = array();
		foreach ( $incoming as $index => $raw ) {
			$raw                = self::with_origin( is_array( $raw ) ? $raw : array(), $presets );
			$id                 = is_string( $raw['id'] ?? null ) ? $raw['id'] : '';
			$file_ids[ $index ] = $id;
			if ( $structure ) {
				$structures[ $index ] = self::structure_row( $raw, $id, $site, $site_clean, $categories );
			}
		}

		$resets = array();
		if ( $structure && $restore ) {
			foreach ( $site as $id => $service ) {
				if ( ! in_array( $id, $file_ids, true ) ) {
					$resets[] = new Plan_Row( self::ID, $id, null, $service, null, Row_Status::RESET );
				}
			}
		}

		$targets  = $structure && $restore ? array() : $site_clean;
		$rejected = array();
		foreach ( $structures as $row ) {
			if ( ( Row_Status::SAVE === $row->status || Row_Status::UNCHANGED === $row->status ) && is_array( $row->incoming ) ) {
				$targets[ $row->key ] = $row->incoming;
			} elseif ( isset( $site_clean[ $row->key ] ) ) {
				$targets[ $row->key ] = $site_clean[ $row->key ];
			} else {
				$rejected[ $row->key ] = $row->message;
			}
		}

		$texts  = self::text_rows( $file, $options, $store, $normal, $targets, $rejected, $categories );
		$rows   = array();
		$placed = array();
		foreach ( $file_ids as $index => $id ) {
			if ( isset( $structures[ $index ] ) ) {
				$rows[] = $structures[ $index ];
			}
			if ( ! isset( $placed[ $id ] ) ) {
				$rows          = array_merge( $rows, $texts[ $id ] ?? array() );
				$placed[ $id ] = true;
			}
		}
		$rows = array_merge( $rows, $resets );
		foreach ( array( false, true ) as $category ) {
			foreach ( $texts as $key => $text_rows ) {
				$key = (string) $key;
				if ( ! isset( $placed[ $key ] ) && str_starts_with( $key, 'category:' ) === $category ) {
					$rows = array_merge( $rows, $text_rows );
				}
			}
		}

		if ( ! user_can( $options->user_id, Capabilities::MANAGE_CONSENT ) ) {
			$message = __( 'You are not allowed to change the cookies and services.', 'creationell-wp-theme' );
			return array_map( static fn( Plan_Row $row ): Plan_Row => $row->with_status( Row_Status::FORBIDDEN, $message ), $rows );
		}
		return self::reconcile( $store, $rows, $options, $categories );
	}

	/**
	 * Writes the merged store of the rows with one save.
	 *
	 * Services only on the site stay on an import; on a restore the file
	 * replaces the services and the texts of its languages active on the site:
	 * every text it drops there has a reset row, so a restore writes whenever it
	 * deletes something. Texts of other languages stay, except those of a
	 * removed service: the validator drops them in every language, without a
	 * row. When the store refuses the
	 * data, every write row is rejected with its message.
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
		$writes = array_filter( $rows, static fn( Plan_Row $row ): bool => self::is_write( $row ) );
		if ( array() === $writes ) {
			return $rows;
		}
		$result = Consent_Services_Store::save( self::merge( Consent_Services_Store::read(), $rows, $options ), new Write_Context( 'import', $options->user_id ) );
		if ( Set_Status::SAVED === $result->status || Set_Status::UNCHANGED === $result->status ) {
			return $rows;
		}
		$message = $result->message;
		$errors  = is_array( $result->data['errors'] ?? null ) ? $result->data['errors'] : array();
		$first   = is_array( $errors[0] ?? null ) && is_string( $errors[0]['message'] ?? null ) ? $errors[0]['message'] : '';
		$message = trim( $message . ' ' . $first );
		foreach ( array_keys( $writes ) as $index ) {
			$rows[ $index ] = $rows[ $index ]->with_status( Row_Status::INVALID, $message );
		}
		return array_values( $rows );
	}

	/**
	 * Plans the structure of one service of the file on its own; reconcile() checks it against the other services of the merged store.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed>                $raw        Service of the file with its origin.
	 * @param string                      $id         Service ID; empty when missing.
	 * @param array<string, array<mixed>> $site       Stored services by ID.
	 * @param array<string, array<mixed>> $site_clean Normalised services of the site by ID.
	 * @param array<int, string>          $categories Categories.
	 * @return Plan_Row Row with the normalised service as incoming value.
	 */
	private static function structure_row( array $raw, string $id, array $site, array $site_clean, array $categories ): Plan_Row {
		$checked  = Consent_Services_Validator::check(
			array(
				'services' => array( $raw ),
				'texts'    => array(),
			),
			$categories
		);
		$incoming = $checked['data']['services'][0] ?? array();
		$error    = self::first_error( $checked['errors'], $id, null );
		$current  = $site[ $id ] ?? null;
		if ( null !== $error ) {
			return new Plan_Row( self::ID, $id, null, $current, $incoming, Row_Status::INVALID, $error );
		}
		$before = $site_clean[ $id ] ?? null;
		if ( null !== $before && is_int( $before['cookie_seq'] ?? null ) && is_int( $incoming['cookie_seq'] ?? null ) ) {
			$incoming['cookie_seq'] = max( $before['cookie_seq'], $incoming['cookie_seq'] );
		}
		return new Plan_Row( self::ID, $id, null, $current, $incoming, $before === $incoming ? Row_Status::UNCHANGED : Row_Status::SAVE );
	}

	/**
	 * Plans the texts of the file per target language.
	 *
	 * On a restore, every normalised text of the site in an active, wanted
	 * language of the file (header or texts) that the file does not carry gets
	 * a reset row without incoming value, so the preview lists each deletion.
	 *
	 * @since 1.0.0
	 *
	 * @param Transfer_File               $file       File.
	 * @param Import_Options              $options    Options.
	 * @param array<string, mixed>        $store      Stored data.
	 * @param array<string, mixed>        $normal     Normalised stored data.
	 * @param array<string, array<mixed>> $targets    Services the texts attach to after the import, by ID.
	 * @param array<string, string>       $rejected   Messages of the rejected services of the file that are not on the site, by ID.
	 * @param array<int, string>          $categories Categories.
	 * @return array<string, list<Plan_Row>> Rows by service ID or "category:<slug>", in file order; reset rows last.
	 */
	private static function text_rows( Transfer_File $file, Import_Options $options, array $store, array $normal, array $targets, array $rejected, array $categories ): array {
		$active  = Language::instance()->active();
		$by_key  = array();
		$langs   = $file->languages;
		$carried = array();
		foreach ( Lang_Parts::parts( self::texts( $file ), $options->lang_map ) as $part ) {
			$lang    = $part['lang'];
			$langs[] = $lang;
			if ( ! $options->wants_lang( $lang ) ) {
				continue;
			}
			$rows = array();
			foreach ( self::text_items( $part['values'] ) as $key => $text ) {
				if ( null !== $part['replaced_by'] ) {
					/* translators: %s: language code of the file, e.g. "en". */
					$rows[] = new Plan_Row( self::ID, $key, $lang, null, $text, Row_Status::LANGUAGE_REPLACED, sprintf( __( 'Replaced by the texts of language %s from the file.', 'creationell-wp-theme' ), $part['replaced_by'] ) );
				} elseif ( ! in_array( $lang, $active, true ) ) {
					$rows[] = new Plan_Row( self::ID, $key, $lang, null, $text, Row_Status::LANGUAGE_INACTIVE, __( 'This language is not active on this site.', 'creationell-wp-theme' ) );
				} else {
					$rows[] = self::text_row( $key, $lang, $text, $store, $normal, $targets, $rejected, $categories );
				}
			}
			if ( null === $part['replaced_by'] && in_array( $lang, $active, true ) && $part['from'] !== $lang ) {
				$rows = Lang_Parts::mapped( $rows, $part['from'] );
			}
			foreach ( $rows as $row ) {
				$by_key[ $row->key ][]         = $row;
				$carried[ $lang ][ $row->key ] = true;
			}
		}
		if ( 'restore' !== $options->reason ) {
			return $by_key;
		}
		$texts = is_array( $normal['texts'] ?? null ) ? $normal['texts'] : array();
		foreach ( array_unique( $langs ) as $lang ) {
			if ( ! in_array( $lang, $active, true ) || ! $options->wants_lang( $lang ) || ! is_array( $texts[ $lang ] ?? null ) ) {
				continue;
			}
			foreach ( self::text_items( $texts[ $lang ] ) as $key => $text ) {
				if ( ! isset( $carried[ $lang ][ $key ] ) ) {
					$is_category      = str_starts_with( $key, 'category:' );
					$current          = self::text_at( $store, $lang, $is_category ? 'categories' : 'services', $is_category ? substr( $key, strlen( 'category:' ) ) : $key );
					$by_key[ $key ][] = new Plan_Row( self::ID, $key, $lang, $current ?? $text, null, Row_Status::RESET );
				}
			}
		}
		return $by_key;
	}

	/**
	 * Plans one text of a service or a category in an active language.
	 *
	 * @since 1.0.0
	 *
	 * @param string                      $key        Service ID or "category:<slug>".
	 * @param string                      $lang       Target language.
	 * @param array<mixed>                $text       Text of the file.
	 * @param array<string, mixed>        $store      Stored data.
	 * @param array<string, mixed>        $normal     Normalised stored data.
	 * @param array<string, array<mixed>> $targets    Services the texts attach to after the import, by ID.
	 * @param array<string, string>       $rejected   Messages of the rejected services of the file that are not on the site, by ID.
	 * @param array<int, string>          $categories Categories.
	 * @return Plan_Row Row; its incoming value is the normalised text, null when nothing of it is left.
	 */
	private static function text_row( string $key, string $lang, array $text, array $store, array $normal, array $targets, array $rejected, array $categories ): Plan_Row {
		$is_category = str_starts_with( $key, 'category:' );
		$name        = $is_category ? substr( $key, strlen( 'category:' ) ) : $key;
		$group       = $is_category ? 'categories' : 'services';
		$current     = self::text_at( $store, $lang, $group, $name );
		if ( $is_category && ! in_array( $name, $categories, true ) ) {
			return new Plan_Row( self::ID, $key, $lang, $current, $text, Row_Status::INVALID, __( 'This consent category does not exist.', 'creationell-wp-theme' ) );
		}
		if ( ! $is_category && ! isset( $targets[ $name ] ) ) {
			return new Plan_Row( self::ID, $key, $lang, $current, $text, Row_Status::INVALID, isset( $rejected[ $name ] ) ? self::not_imported( $rejected[ $name ] ) : __( 'Import the default language as well: this service is not on this site.', 'creationell-wp-theme' ) );
		}
		$checked  = Consent_Services_Validator::check(
			array(
				'services' => $is_category ? array() : array( $targets[ $name ] ),
				'texts'    => array( $lang => array( $group => array( $name => $text ) ) ),
			),
			$categories
		);
		$incoming = self::text_at( $checked['data'], $lang, $group, $name );
		$error    = self::first_error( $checked['errors'], $key, 'texts.' . $lang . '.' );
		if ( null !== $error ) {
			return new Plan_Row( self::ID, $key, $lang, $current, $incoming, Row_Status::INVALID, $error );
		}
		return new Plan_Row( self::ID, $key, $lang, $current, $incoming, self::text_at( $normal, $lang, $group, $name ) === $incoming ? Row_Status::UNCHANGED : Row_Status::SAVE );
	}

	/**
	 * Builds the store the rows lead to.
	 *
	 * An import starts from the stored data; a restore of the default language
	 * starts without services. Texts always start from the stored texts: rows
	 * "save" and "unchanged" put their value in place, a text row "reset" of a
	 * restore drops the text of the site, and every other text row (rejected,
	 * skipped) keeps it, so texts of inactive languages stay. A rejected
	 * structure row of a restore keeps the service of the site.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $store   Stored data.
	 * @param array<int, Plan_Row> $rows    Rows.
	 * @param Import_Options       $options Options.
	 * @return array{v: int, services: list<mixed>, texts: array<mixed>} Store data.
	 */
	private static function merge( array $store, array $rows, Import_Options $options ): array {
		$restore  = 'restore' === $options->reason;
		$replace  = $restore && $options->wants_lang( Language::instance()->default() );
		$site     = self::by_id( $store['services'] );
		$services = $replace ? array() : self::items_list( $store['services'] );
		$texts    = is_array( $store['texts'] ) ? $store['texts'] : array();
		foreach ( $rows as $row ) {
			if ( self::ID !== $row->section ) {
				continue;
			}
			$kept = Row_Status::SAVE === $row->status || Row_Status::UNCHANGED === $row->status;
			if ( null === $row->lang ) {
				if ( $kept && is_array( $row->incoming ) ) {
					$services = self::put( $services, $row->incoming )['list'];
				} elseif ( $replace && Row_Status::RESET !== $row->status && isset( $site[ $row->key ] ) ) {
					$services = self::put( $services, $site[ $row->key ] )['list'];
				}
				continue;
			}
			$drop = $restore && Row_Status::RESET === $row->status;
			if ( $kept || $drop ) {
				$is_category = str_starts_with( $row->key, 'category:' );
				$group       = $is_category ? 'categories' : 'services';
				$name        = $is_category ? substr( $row->key, strlen( 'category:' ) ) : $row->key;
				$entry       = is_array( $texts[ $row->lang ] ?? null ) ? $texts[ $row->lang ] : array();
				$list        = is_array( $entry[ $group ] ?? null ) ? $entry[ $group ] : array();
				if ( $drop ) {
					unset( $list[ $name ] );
				} else {
					$list[ $name ] = is_array( $row->incoming ) ? $row->incoming : array();
				}
				$entry[ $group ]     = $list;
				$texts[ $row->lang ] = $entry;
			}
		}
		return array(
			'v'        => Consent_Services_Store::VERSION,
			'services' => $services,
			'texts'    => $texts,
		);
	}

	/**
	 * Checks the store the rows lead to and rejects the rows the validator blames, until the store is valid.
	 *
	 * A size or count limit of the whole store rejects every row "save". An
	 * error of a service rejects its structure row; its text rows too when the
	 * service is not on the site. An error of a text rejects its text row. An
	 * error that names no row of the file, e.g. a cookie of a service on the
	 * site that the file now repeats, rejects every row "save", because the
	 * store would refuse the whole write.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $store      Stored data.
	 * @param array<int, Plan_Row> $rows       Rows.
	 * @param Import_Options       $options    Options.
	 * @param array<int, string>   $categories Categories.
	 * @phpstan-param list<Plan_Row> $rows
	 * @return array<int, Plan_Row> Rows.
	 * @phpstan-return list<Plan_Row>
	 */
	private static function reconcile( array $store, array $rows, Import_Options $options, array $categories ): array {
		$site   = self::by_id( $store['services'] );
		$rounds = count( $rows );
		for ( $round = 0; $round <= $rounds; $round++ ) {
			$errors = Consent_Services_Validator::check( self::merge( $store, $rows, $options ), $categories )['errors'];
			if ( array() === $errors ) {
				return $rows;
			}
			foreach ( $errors as $error ) {
				if ( 'services' === $error['field'] && in_array( $error['code'], array( 'size', 'limit' ), true ) ) {
					return self::reject_saves( $rows, $error['message'] );
				}
			}
			$changed = false;
			foreach ( $errors as $error ) {
				$is_text = str_starts_with( $error['field'], 'texts.' );
				foreach ( $rows as $index => $row ) {
					if ( Row_Status::SAVE !== $row->status || $error['service'] !== $row->key ) {
						continue;
					}
					if ( $is_text ) {
						$message = null !== $row->lang && str_starts_with( $error['field'], 'texts.' . $row->lang . '.' ) ? $error['message'] : null;
					} elseif ( null === $row->lang ) {
						$message = $error['message'];
					} else {
						$message = isset( $site[ $row->key ] ) ? null : self::not_imported( $error['message'] );
					}
					if ( null !== $message ) {
						$rows[ $index ] = $row->with_status( Row_Status::INVALID, $message );
						$changed        = true;
					}
				}
			}
			if ( ! $changed ) {
				return self::reject_saves( $rows, $errors[0]['message'] );
			}
		}
		return $rows;
	}

	/**
	 * Rejects every row "save" with a message.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, Plan_Row> $rows    Rows.
	 * @param string               $message Message.
	 * @phpstan-param list<Plan_Row> $rows
	 * @return array<int, Plan_Row> Rows.
	 * @phpstan-return list<Plan_Row>
	 */
	private static function reject_saves( array $rows, string $message ): array {
		return array_map( static fn( Plan_Row $row ): Plan_Row => Row_Status::SAVE === $row->status ? $row->with_status( Row_Status::INVALID, $message ) : $row, $rows );
	}

	/**
	 * Returns the message of a text whose new service is rejected.
	 *
	 * @since 1.0.0
	 *
	 * @param string $reason Message of the rejected service.
	 * @return string Message.
	 */
	private static function not_imported( string $reason ): string {
		/* translators: %s: why the service was rejected, e.g. "This value is already in use." */
		return sprintf( __( 'The service itself is not imported: %s', 'creationell-wp-theme' ), $reason );
	}

	/**
	 * Returns the first validator message of a service or category.
	 *
	 * @since 1.0.0
	 *
	 * @param list<array{service: string, cookie: ?int, field: string, code: string, message: string}> $errors Errors.
	 * @param string                                                                                   $key    Service ID or "category:<slug>".
	 * @param string|null                                                                              $texts  Field prefix of the texts to look at; null for the structure.
	 * @return string|null Message.
	 */
	private static function first_error( array $errors, string $key, ?string $texts ): ?string {
		foreach ( $errors as $error ) {
			$is_text = str_starts_with( $error['field'], 'texts.' );
			if ( $key === $error['service'] && ( null === $texts ? ! $is_text : str_starts_with( $error['field'], $texts ) ) ) {
				return $error['message'];
			}
		}
		return null;
	}

	/**
	 * Keeps the preset of a service; clears its check date when the preset is not in the catalog.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed>                        $service Service.
	 * @param array<string, array<string, mixed>> $presets Presets by ID.
	 * @return array<mixed> Service.
	 */
	private static function with_origin( array $service, array $presets ): array {
		$preset = is_string( $service['preset'] ?? null ) ? $service['preset'] : '';
		if ( ! isset( $presets[ $preset ] ) ) {
			$service['preset_checked'] = '';
		}
		return $service;
	}

	/**
	 * Replaces the service with the same ID or appends it.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, mixed> $services Services.
	 * @param array<mixed>      $service  Service.
	 * @phpstan-param list<mixed> $services
	 * @return array{list: list<mixed>, index: int} Services and the position of the service.
	 */
	private static function put( array $services, array $service ): array {
		$id = $service['id'] ?? null;
		foreach ( $services as $index => $old ) {
			if ( is_string( $id ) && is_array( $old ) && ( $old['id'] ?? null ) === $id ) {
				$services[ $index ] = $service;
				return array(
					'list'  => $services,
					'index' => $index,
				);
			}
		}
		$services[] = $service;
		return array(
			'list'  => $services,
			'index' => count( $services ) - 1,
		);
	}

	/**
	 * Returns the services with a string ID by ID; the first one of an ID wins.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $services Services.
	 * @return array<string, array<mixed>> Services by ID.
	 */
	private static function by_id( mixed $services ): array {
		$by_id = array();
		foreach ( self::items_list( $services ) as $service ) {
			if ( is_array( $service ) && is_string( $service['id'] ?? null ) && ! isset( $by_id[ $service['id'] ] ) ) {
				$by_id[ $service['id'] ] = $service;
			}
		}
		return $by_id;
	}

	/**
	 * Returns the texts of a part of the file by service ID, then by "category:<slug>".
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $entry Texts of one language.
	 * @return array<string, array<mixed>> Texts by key; entries that are no map fall away.
	 */
	private static function text_items( array $entry ): array {
		$items = array();
		foreach ( self::map( $entry['services'] ?? null ) as $id => $text ) {
			if ( is_array( $text ) ) {
				$items[ $id ] = $text;
			}
		}
		foreach ( self::map( $entry['categories'] ?? null ) as $slug => $text ) {
			if ( is_array( $text ) ) {
				$items[ 'category:' . $slug ] = $text;
			}
		}
		return $items;
	}

	/**
	 * Returns the text of a service or category in a language of store data.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $data  Store data.
	 * @param string       $lang  Language code.
	 * @param string       $group "services" or "categories".
	 * @param string       $name  Service ID or category slug.
	 * @return array<mixed>|null Text, or null without one.
	 */
	private static function text_at( array $data, string $lang, string $group, string $name ): ?array {
		$texts = is_array( $data['texts'] ?? null ) ? $data['texts'] : array();
		$entry = is_array( $texts[ $lang ] ?? null ) ? $texts[ $lang ] : array();
		$list  = is_array( $entry[ $group ] ?? null ) ? $entry[ $group ] : array();
		return is_array( $list[ $name ] ?? null ) ? $list[ $name ] : null;
	}

	/**
	 * Tells whether a row of this section writes.
	 *
	 * @since 1.0.0
	 *
	 * @param Plan_Row $row Row.
	 * @return bool True for "save" and "reset".
	 */
	private static function is_write( Plan_Row $row ): bool {
		return self::ID === $row->section && ( Row_Status::SAVE === $row->status || Row_Status::RESET === $row->status );
	}

	/**
	 * Returns the values of a list or map as list; other values give an empty list.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return list<mixed> Items.
	 */
	private static function items_list( mixed $value ): array {
		return is_array( $value ) ? array_values( $value ) : array();
	}

	/**
	 * Returns the texts of the section in the file by language.
	 *
	 * @since 1.0.0
	 *
	 * @param Transfer_File $file File.
	 * @return array<string, array<string, mixed>> Texts by language code; entries that are no map fall away.
	 */
	private static function texts( Transfer_File $file ): array {
		$part  = self::plain( $file->other_sections[ self::ID ] ?? array() );
		$texts = array();
		foreach ( self::map( is_array( $part ) ? ( $part['texts'] ?? null ) : null ) as $lang => $entry ) {
			if ( is_array( $entry ) ) {
				$texts[ $lang ] = self::map( $entry );
			}
		}
		return $texts;
	}

	/**
	 * Returns a map with string keys; "[]", "{}" and other values give an empty map.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return array<string, mixed> Map.
	 */
	private static function map( mixed $value ): array {
		$map = array();
		if ( ! is_array( $value ) ) {
			return $map;
		}
		foreach ( $value as $key => $item ) {
			if ( is_string( $key ) ) {
				$map[ $key ] = $item;
			}
		}
		return $map;
	}

	/**
	 * Turns every stdClass of a value into an array.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value from a decoded file or an export.
	 * @return mixed Value without objects.
	 */
	private static function plain( mixed $value ): mixed {
		if ( $value instanceof stdClass ) {
			$value = get_object_vars( $value );
		}
		return is_array( $value ) ? array_map( self::plain( ... ), $value ) : $value;
	}
}
