<?php
/**
 * Form markup of the module contact-form-7.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\ContactForm7;

use WP_HTML_Tag_Processor;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Adds the Bootstrap classes to the form markup of Contact Form 7.
 *
 * One pass of WP_HTML_Tag_Processor over the form elements, no regular
 * expressions: the attribute order, checked and tabindex do not matter. The
 * class only adds classes and never removes one, so a second pass changes
 * nothing. Elements with the class creationell-theme-cf7-raw stay unchanged; on
 * the wrapper of a checkbox, radio or acceptance field (where Contact Form 7
 * puts the class option) the whole group stays unchanged. Hidden inputs, the
 * captcha widget and everything without a class of Contact Form 7 are left
 * alone. The submit input stays an input, because other plugins look for
 * "<input ... wpcf7-submit".
 *
 * @since 1.0.0
 */
final class Form_Markup {

	/**
	 * Filter with the classes per role.
	 *
	 * @since 1.0.0
	 */
	public const FILTER_CLASSES = 'creationell_wp_theme_cf7_classes';

	/**
	 * Filter that puts the choices of checkbox and radio fields side by side.
	 *
	 * @since 1.0.0
	 */
	public const FILTER_INLINE_CHOICES = 'creationell_wp_theme_cf7_inline_choices';

	/**
	 * Class that keeps a field of Contact Form 7 unchanged (CF7 option class:creationell-theme-cf7-raw).
	 *
	 * @since 1.0.0
	 */
	public const RAW_CLASS = 'creationell-theme-cf7-raw';

	/**
	 * Class added to every choice when the choices stand side by side.
	 *
	 * @since 1.0.0
	 */
	public const INLINE_CLASS = 'form-check-inline';

	/**
	 * Classes per role: text-like inputs and text areas, selects, range inputs,
	 * the submit (the first class always, the others only without an own btn-
	 * class), the list item of a choice, its checkbox or radio input and its label.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, array<int, string>>
	 */
	public const DEFAULT_CLASSES = array(
		'control'      => array( 'form-control' ),
		'select'       => array( 'form-select' ),
		'range'        => array( 'form-range' ),
		'submit'       => array( 'btn', 'btn-primary' ),
		'choice'       => array( 'form-check' ),
		'choice_input' => array( 'form-check-input' ),
		'choice_label' => array( 'form-check-label' ),
	);

	/**
	 * Input types that get the classes of the role control.
	 *
	 * @var array<int, string>
	 */
	private const CONTROL_TYPES = array( 'text', 'email', 'url', 'tel', 'number', 'date', 'password', 'file' );

	/**
	 * Filters the form elements of Contact Form 7; runs on wpcf7_form_elements with priority 20.
	 *
	 * Reads the filters creationell_wp_theme_cf7_classes and
	 * creationell_wp_theme_cf7_inline_choices on every call. Values that are no
	 * string come back unchanged.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $html Form markup from Contact Form 7.
	 * @return mixed The markup with the Bootstrap classes, or the value unchanged when it is no string.
	 */
	public static function filter( mixed $html ): mixed {
		if ( ! is_string( $html ) ) {
			return $html;
		}
		$options = self::options();
		return self::enhance( $html, $options['classes'], $options['inline'] );
	}

	/**
	 * Reads and checks the filters of the form markup.
	 *
	 * @since 1.0.0
	 *
	 * @return array{classes: array<string, array<int, string>>, inline: bool} Classes per role and whether choices stand side by side.
	 */
	public static function options(): array {
		/**
		 * Filters the classes the theme adds to the form elements of Contact Form 7.
		 *
		 * Keys are the roles control (text-like inputs and text areas), select,
		 * range, submit, choice (list item of a checkbox, radio or acceptance
		 * field), choice_input and choice_label; values are lists of class names.
		 * Missing roles keep their defaults, an empty list adds no class. The
		 * first submit class is always added, the others only when the submit
		 * has no class starting with "btn-" (CF7 option class:btn-outline-secondary).
		 * Invalid entries fall back to their defaults and give a notice.
		 *
		 * Example, in the functions.php of a child theme:
		 *
		 *     add_filter(
		 *         'creationell_wp_theme_cf7_classes',
		 *         static fn( array $classes ): array => array_merge( $classes, array( 'submit' => array( 'btn', 'btn-dark' ) ) )
		 *     );
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, array<int, string>> $classes Classes per role; Form_Markup::DEFAULT_CLASSES by default.
		 */
		$classes = self::classes( apply_filters( 'creationell_wp_theme_cf7_classes', self::DEFAULT_CLASSES ) );

		/**
		 * Filters whether the choices of checkbox and radio fields stand side by side.
		 *
		 * True adds form-check-inline to every choice. The theme stacks them by
		 * default: a list is easier to read and to hit. Values other than true
		 * or false give false and a notice.
		 *
		 * Example, in the functions.php of a child theme:
		 *
		 *     add_filter( 'creationell_wp_theme_cf7_inline_choices', '__return_true' );
		 *
		 * @since 1.0.0
		 *
		 * @param bool $inline Whether the choices stand side by side; false by default.
		 */
		$inline = self::inline_choices( apply_filters( 'creationell_wp_theme_cf7_inline_choices', false ) );

		return array(
			'classes' => $classes,
			'inline'  => $inline,
		);
	}

	/**
	 * Checks the value of the filter creationell_wp_theme_cf7_classes.
	 *
	 * Known roles with a list of strings replace their defaults; each string may
	 * hold several classes separated by white space, each class passes
	 * sanitize_html_class(), empty and repeated ones are dropped. Missing roles
	 * keep their defaults. A value that is no array, an unknown role or a role
	 * without a list of strings falls back to the defaults and gives one notice
	 * that names every invalid entry.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Filter value.
	 * @return array<string, array<int, string>> Classes per role, in the order of the defaults.
	 */
	public static function classes( mixed $value ): array {
		$classes = self::DEFAULT_CLASSES;
		if ( ! is_array( $value ) ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html( sprintf( 'The filter %s must return an array of class lists; the defaults are used.', self::FILTER_CLASSES ) ),
				'1.0.0'
			);
			return $classes;
		}
		$invalid = array();
		foreach ( $value as $role => $names ) {
			$sanitized = is_string( $role ) && isset( self::DEFAULT_CLASSES[ $role ] ) ? self::class_names( $names ) : null;
			if ( null === $sanitized ) {
				$invalid[] = $role;
				continue;
			}
			$classes[ $role ] = $sanitized;
		}
		if ( array() !== $invalid ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html( sprintf( 'The filter %1$s returned invalid entries for the keys %2$s; their defaults are used.', self::FILTER_CLASSES, implode( ', ', $invalid ) ) ),
				'1.0.0'
			);
		}
		return $classes;
	}

	/**
	 * Checks the value of the filter creationell_wp_theme_cf7_inline_choices.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Filter value.
	 * @return bool The value, or false and a notice when it is no boolean.
	 */
	public static function inline_choices( mixed $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}
		_doing_it_wrong(
			__METHOD__,
			esc_html( sprintf( 'The filter %s must return true or false; false is used.', self::FILTER_INLINE_CHOICES ) ),
			'1.0.0'
		);
		return false;
	}

	/**
	 * Returns the classes of a choice: the role choice, plus form-check-inline when the choices stand side by side.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, array<int, string>> $classes Classes per role.
	 * @param bool                              $inline  Whether the choices stand side by side.
	 * @return array<int, string> Class names.
	 */
	public static function choice_classes( array $classes, bool $inline ): array {
		$names = $classes['choice'] ?? array();
		if ( $inline && ! in_array( self::INLINE_CLASS, $names, true ) ) {
			$names[] = self::INLINE_CLASS;
		}
		return $names;
	}

	/**
	 * Adds the classes per role to the form markup of Contact Form 7.
	 *
	 * Rules (Contact Form 7 6.x):
	 * - INPUT with wpcf7-form-control and type text, email, url, tel, number,
	 *   date, password or file, TEXTAREA with wpcf7-form-control, and
	 *   INPUT.wpcf7-free-text: control;
	 * - SELECT.wpcf7-form-control: select; INPUT[type=range].wpcf7-form-control: range;
	 * - INPUT or BUTTON with wpcf7-submit: the first submit class, the others only
	 *   when no class starts with "btn-";
	 * - SPAN.wpcf7-list-item: choice (see choice_classes()); the next checkbox or
	 *   radio input after it: choice_input; SPAN.wpcf7-list-item-label: choice_label.
	 *
	 * Returns the input unchanged when nothing is added or when the processor
	 * stops at an incomplete tag.
	 *
	 * @since 1.0.0
	 *
	 * @param string                            $html    Form markup.
	 * @param array<string, array<int, string>> $classes Classes per role, as returned by classes().
	 * @param bool                              $inline  Whether the choices stand side by side.
	 * @return string Markup with the added classes.
	 */
	public static function enhance( string $html, array $classes, bool $inline ): string {
		if ( '' === $html ) {
			return $html;
		}
		$processor = new WP_HTML_Tag_Processor( $html );
		$choice    = false;
		$changed   = false;
		$raw_depth = 0;
		while ( $processor->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
			$tag = $processor->get_tag();
			if ( $raw_depth > 0 ) {
				if ( 'SPAN' === $tag ) {
					$raw_depth += $processor->is_tag_closer() ? -1 : 1;
				}
				continue;
			}
			if ( $processor->is_tag_closer() ) {
				continue;
			}
			if ( true === $processor->has_class( self::RAW_CLASS ) ) {
				$raw_depth = 'SPAN' === $tag ? 1 : 0;
				continue;
			}
			foreach ( self::classes_for( $processor, $classes, $inline, $choice ) as $name ) {
				if ( true !== $processor->has_class( $name ) ) {
					$processor->add_class( $name );
					$changed = true;
				}
			}
		}
		if ( ! $changed || $processor->paused_at_incomplete_token() ) {
			return $html;
		}
		return $processor->get_updated_html();
	}

	/**
	 * Returns the classes for the tag the processor stands on.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_HTML_Tag_Processor             $processor Processor on an opening tag.
	 * @param array<string, array<int, string>> $classes   Classes per role.
	 * @param bool                              $inline    Whether the choices stand side by side.
	 * @param bool                              $choice    Whether a list item waits for its input; updated.
	 * @return array<int, string> Class names, empty for tags the module leaves alone.
	 */
	private static function classes_for( WP_HTML_Tag_Processor $processor, array $classes, bool $inline, bool &$choice ): array {
		switch ( $processor->get_tag() ) {
			case 'INPUT':
				return self::input_classes( $processor, $classes, $choice );
			case 'TEXTAREA':
				return true === $processor->has_class( 'wpcf7-form-control' ) ? $classes['control'] ?? array() : array();
			case 'SELECT':
				return true === $processor->has_class( 'wpcf7-form-control' ) ? $classes['select'] ?? array() : array();
			case 'BUTTON':
				return true === $processor->has_class( 'wpcf7-submit' ) ? self::submit_classes( $processor, $classes ) : array();
			case 'SPAN':
				if ( true === $processor->has_class( 'wpcf7-list-item' ) ) {
					$choice = true;
					return self::choice_classes( $classes, $inline );
				}
				return true === $processor->has_class( 'wpcf7-list-item-label' ) ? $classes['choice_label'] ?? array() : array();
		}
		return array();
	}

	/**
	 * Returns the classes for an INPUT tag.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_HTML_Tag_Processor             $processor Processor on an INPUT tag.
	 * @param array<string, array<int, string>> $classes   Classes per role.
	 * @param bool                              $choice    Whether a list item waits for its input; updated.
	 * @return array<int, string> Class names.
	 */
	private static function input_classes( WP_HTML_Tag_Processor $processor, array $classes, bool &$choice ): array {
		$type = $processor->get_attribute( 'type' );
		$type = is_string( $type ) ? strtolower( $type ) : 'text';
		if ( true === $processor->has_class( 'wpcf7-submit' ) ) {
			return self::submit_classes( $processor, $classes );
		}
		if ( $choice && ( 'checkbox' === $type || 'radio' === $type ) ) {
			$choice = false;
			return $classes['choice_input'] ?? array();
		}
		if ( true === $processor->has_class( 'wpcf7-free-text' ) ) {
			return $classes['control'] ?? array();
		}
		if ( true !== $processor->has_class( 'wpcf7-form-control' ) ) {
			return array();
		}
		if ( 'range' === $type ) {
			return $classes['range'] ?? array();
		}
		return in_array( $type, self::CONTROL_TYPES, true ) ? $classes['control'] ?? array() : array();
	}

	/**
	 * Returns the classes for a submit: the first one always, the others only without an own btn- class.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_HTML_Tag_Processor             $processor Processor on the submit tag.
	 * @param array<string, array<int, string>> $classes   Classes per role.
	 * @return array<int, string> Class names.
	 */
	private static function submit_classes( WP_HTML_Tag_Processor $processor, array $classes ): array {
		$names = $classes['submit'] ?? array();
		foreach ( $processor->class_list() as $name ) {
			if ( str_starts_with( $name, 'btn-' ) ) {
				return array_slice( $names, 0, 1 );
			}
		}
		return $names;
	}

	/**
	 * Splits, sanitizes and deduplicates a list of class names.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $names Class names; strings may hold several names separated by white space.
	 * @return array<int, string>|null Class names, or null when the value is no list of strings.
	 */
	private static function class_names( mixed $names ): ?array {
		if ( ! is_array( $names ) ) {
			return null;
		}
		$sanitized = array();
		foreach ( $names as $name ) {
			if ( ! is_string( $name ) ) {
				return null;
			}
			$parts = preg_split( '/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY );
			foreach ( false === $parts ? array() : $parts as $part ) {
				$part = sanitize_html_class( $part );
				if ( '' !== $part && ! in_array( $part, $sanitized, true ) ) {
					$sanitized[] = $part;
				}
			}
		}
		return $sanitized;
	}
}
