<?php
/**
 * Language parts of a settings transfer file under a language map.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Splits translated content of a file into parts per target language and notes the file language on mapped rows.
 *
 * The sections "settings" and "consent_services" plan their texts with the
 * same language map rules.
 *
 * @since 1.0.0
 */
final class Lang_Parts {

	/**
	 * Returns the translated parts of the file under their target language.
	 *
	 * A file language with a map entry goes to its target. A file language that
	 * another file language is mapped to keeps its own code and names the
	 * language that replaces it.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, array<string, mixed>> $translatable Translated values per file language.
	 * @param array<string, string>               $lang_map     Source => target.
	 * @return list<array{lang: string, from: string, values: array<string, mixed>, replaced_by: string|null}> Parts in file order.
	 */
	public static function parts( array $translatable, array $lang_map ): array {
		$sources = array();
		foreach ( $lang_map as $source => $target ) {
			if ( $source !== $target ) {
				$sources[ $target ] = $source;
			}
		}
		$parts = array();
		foreach ( $translatable as $lang => $values ) {
			$lang    = (string) $lang;
			$target  = $lang_map[ $lang ] ?? $lang;
			$parts[] = array(
				'lang'        => $target,
				'from'        => $lang,
				'values'      => $values,
				'replaced_by' => $target === $lang && isset( $sources[ $lang ] ) ? $sources[ $lang ] : null,
			);
		}
		return $parts;
	}

	/**
	 * Adds the file language to the messages of rows planned under another language.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, Plan_Row> $rows Rows.
	 * @param string               $from Language code in the file.
	 * @return array<int, Plan_Row> Rows.
	 * @phpstan-param list<Plan_Row> $rows
	 * @phpstan-return list<Plan_Row>
	 */
	public static function mapped( array $rows, string $from ): array {
		/* translators: %s: language code of the file, e.g. "en". */
		$note = sprintf( __( 'From language %s of the file.', 'creationell-wp-theme' ), $from );
		return array_map(
			static fn( Plan_Row $row ): Plan_Row => new Plan_Row( $row->section, $row->key, $row->lang, $row->current, $row->incoming, $row->status, '' === $row->message ? $note : $row->message . ' ' . $note, $row->switch_result ),
			$rows
		);
	}
}
