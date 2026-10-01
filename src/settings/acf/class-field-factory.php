<?php
/**
 * ACF fields of the theme settings.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Acf;

use Creationell\WpTheme\Settings\Definition;
use Creationell\WpTheme\Settings\Option_Store;
use Creationell\WpTheme\Settings\Registry;
use Creationell\WpTheme\Settings\Sanitizer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Builds the ACF field of a setting definition, plus the tabs and notes of the settings pages.
 *
 * Field key "field_creationell_wp_theme_<key>" and field name follow Option_Store,
 * so ACF finds the rows the setter writes. Types: color → color_picker,
 * font and enum → select, text and html_inline → textarea, string → textarea
 * above 200 characters and text otherwise, tel → text, email and url
 * keep their name, int → number (post_object with the page ID for the reference
 * type "page"), bool → true_false. Translated keys get the WPML preference 2
 * (translate), all others 0 (do nothing).
 *
 * Tabs and notes use keys with a double underscore after the prefix; no setting
 * key can take that form, so the adapter never mistakes them for a setting.
 *
 * @since 1.0.0
 */
final class Field_Factory {

	/**
	 * Prefix of the field keys of settings.
	 *
	 * @since 1.0.0
	 */
	public const KEY_PREFIX = 'field_' . Option_Store::PREFIX;

	/**
	 * Longest string that stays a single-line text field.
	 *
	 * @since 1.0.0
	 */
	public const MAX_SINGLE_LINE = 200;

	/**
	 * Returns the ACF field of a setting.
	 *
	 * Call it after init: the label and the description are translated here.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition $definition Definition.
	 * @return array<string, mixed> ACF field.
	 */
	public static function field( Definition $definition ): array {
		$label = $definition->label_text();
		$field = array(
			'key'                 => Option_Store::field_key( $definition->key ),
			'name'                => Option_Store::field_name( $definition ),
			'label'               => '' !== $label ? $label : $definition->key,
			'instructions'        => $definition->description_text(),
			'required'            => 0,
			'wpml_cf_preferences' => $definition->translatable ? 2 : 0,
		);
		if ( is_string( $definition->default ) || is_int( $definition->default ) ) {
			$field['default_value'] = $definition->default;
		}
		return array_merge( $field, self::type_settings( $definition ) );
	}

	/**
	 * Returns the ACF type of a setting with the settings of that type.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition $definition Definition.
	 * @return array<string, mixed> Type and type settings.
	 */
	private static function type_settings( Definition $definition ): array {
		$length = null === $definition->max_length ? array() : array( 'maxlength' => $definition->max_length );
		switch ( $definition->type ) {
			case 'color':
				return array(
					'type'           => 'color_picker',
					'enable_opacity' => 0,
					'return_format'  => 'string',
				);
			case 'font':
			case 'enum':
				return array(
					'type'          => 'select',
					'choices'       => 'font' === $definition->type ? self::font_choices( $definition ) : self::enum_choices( $definition ),
					'multiple'      => 0,
					'allow_null'    => 0,
					'ui'            => 0,
					'return_format' => 'value',
				);
			case 'text':
			case 'html_inline':
				return self::textarea( $length );
			case 'string':
				return null !== $definition->max_length && $definition->max_length > self::MAX_SINGLE_LINE ? self::textarea( $length ) : array_merge( array( 'type' => 'text' ), $length );
			case 'tel':
				return array_merge( array( 'type' => 'text' ), $length );
			case 'int':
				if ( 'page' === $definition->reference_type ) {
					return array(
						'type'          => 'post_object',
						'post_type'     => array( 'page' ),
						'return_format' => 'id',
						'allow_null'    => 1,
						'multiple'      => 0,
					);
				}
				return array(
					'type' => 'number',
					'step' => 1,
				);
			case 'bool':
				return array(
					'type' => 'true_false',
					'ui'   => 1,
				);
			default:
				return array( 'type' => $definition->type );
		}
	}

	/**
	 * Returns the settings of a textarea that keeps the line breaks as typed.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, int> $length Maximum length, if any.
	 * @return array<string, mixed> Type and type settings.
	 */
	private static function textarea( array $length ): array {
		return array_merge(
			array(
				'type'      => 'textarea',
				'rows'      => 4,
				'new_lines' => '',
			),
			$length
		);
	}

	/**
	 * Returns the available enum values as choices.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition $definition Definition.
	 * @return array<string, string> Labels by value.
	 */
	private static function enum_choices( Definition $definition ): array {
		$choices = array();
		foreach ( $definition->choices as $value => $available ) {
			if ( true === $available ) {
				$choices[ (string) $value ] = (string) $value;
			}
		}
		return $choices;
	}

	/**
	 * Returns the fonts a font setting may take.
	 *
	 * The system fonts of the sanitizer; "inherit" only where it is the default,
	 * as the sanitizer accepts it. An effective value missing here, such as a
	 * font of the child theme, is added by Acf_Adapter::prepare_field(). After
	 * the merge of the font catalog this list comes from Font_Catalog::all().
	 *
	 * @since 1.0.0
	 *
	 * @param Definition $definition Definition.
	 * @return array<string, string> Labels by font slug.
	 */
	private static function font_choices( Definition $definition ): array {
		$labels  = array(
			'system-sans'      => __( 'System sans-serif', 'creationell-wp-theme' ),
			'system-serif'     => __( 'System serif', 'creationell-wp-theme' ),
			Sanitizer::INHERIT => __( 'Same as body font', 'creationell-wp-theme' ),
		);
		$slugs   = Sanitizer::FONTS;
		$slugs[] = Sanitizer::INHERIT === $definition->default ? Sanitizer::INHERIT : null;
		$choices = array();
		foreach ( array_filter( $slugs, 'is_string' ) as $slug ) {
			$choices[ $slug ] = $labels[ $slug ] ?? $slug;
		}
		return $choices;
	}

	/**
	 * Returns a tab of a settings page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $group Group: colors, fonts, footer, contact, social or a module slug.
	 * @param string $label Translated label.
	 * @return array<string, mixed> ACF tab field.
	 */
	public static function tab( string $group, string $label ): array {
		return array(
			'key'       => self::KEY_PREFIX . '_tab_' . $group,
			'name'      => '',
			'label'     => $label,
			'type'      => 'tab',
			'placement' => 'top',
			'endpoint'  => 0,
		);
	}

	/**
	 * Returns a note of a settings page: a message field without a value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id      Lower case ID of the note.
	 * @param string $label   Translated label.
	 * @param string $message Translated plain text.
	 * @return array<string, mixed> ACF message field.
	 */
	public static function message( string $id, string $label, string $message ): array {
		return array(
			'key'       => self::KEY_PREFIX . '_note_' . $id,
			'name'      => '',
			'label'     => $label,
			'type'      => 'message',
			'message'   => $message,
			'new_lines' => 'wpautop',
			'esc_html'  => 1,
		);
	}

	/**
	 * Returns the setting key of an ACF field of the theme.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $field ACF field.
	 * @return string|null Registered setting key, or null for any other field, tab or note.
	 */
	public static function setting_key( mixed $field ): ?string {
		if ( ! is_array( $field ) || ! isset( $field['key'] ) || ! is_string( $field['key'] ) || ! str_starts_with( $field['key'], self::KEY_PREFIX ) ) {
			return null;
		}
		$key = substr( $field['key'], strlen( self::KEY_PREFIX ) );
		return Registry::instance()->has( $key ) ? $key : null;
	}
}
