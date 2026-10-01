<?php
/**
 * Definition of one theme setting.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings;

use Closure;
use InvalidArgumentException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Describes one key of the settings registry: type, default, accepted values and metadata.
 *
 * Built with named arguments; every field except key, type and default has a
 * default, so later fields can be added without breaking existing definitions.
 * Labels and descriptions are closures around a literal __() call and run only
 * when a text is needed, so building a definition translates nothing and may
 * happen before init.
 *
 * A module lists its settings in modules/<slug>/settings.php.
 *
 * Example:
 *
 *     return array(
 *         new Definition(
 *             key: 'module_post_lists_columns',
 *             type: 'int',
 *             default_value: 3,
 *             section: 'design',
 *             label: static fn(): string => __( 'Columns', 'creationell-wp-theme' ),
 *         ),
 *     );
 *
 * @api
 * @since 1.0.0
 */
final class Definition {

	/**
	 * Value types.
	 *
	 * "color" is #rrggbb in lower case, "font" a slug of the font catalog, "text"
	 * plain text with line breaks, "html_inline" text with the inline tags of
	 * Sanitizer::INLINE_HTML, "email", "url" (http or https) and "tel" may be empty.
	 *
	 * @since 1.0.0
	 */
	public const TYPES = array( 'int', 'string', 'bool', 'enum', 'color', 'font', 'text', 'html_inline', 'email', 'url', 'tel' );

	/**
	 * Types that hold free text and may have a maximum length.
	 *
	 * @since 1.0.0
	 */
	public const TEXT_TYPES = array( 'string', 'text', 'html_inline', 'email', 'url', 'tel' );

	/**
	 * Types that feed SCSS or theme.json tokens and therefore never differ per language.
	 *
	 * @since 1.0.0
	 */
	public const TOKEN_TYPES = array( 'color', 'font' );

	/**
	 * Accessibility rules; "contrast" is for colors only.
	 *
	 * @since 1.0.0
	 */
	public const A11Y_RULES = array( 'contrast' );

	/**
	 * Longest key: the longest option name, "_options_<lang>_creationell_wp_theme_<key>" with a
	 * language code of ten characters, then stays within the 191 characters of an option name.
	 *
	 * @since 1.0.0
	 */
	public const MAX_KEY_LENGTH = 150;

	/**
	 * Levels: basic settings for everyone with the capability, advanced ones for experienced users.
	 *
	 * @since 1.0.0
	 */
	public const LEVELS = array( 'basic', 'advanced' );

	/**
	 * Sections of the settings; "modules" holds only the module switches module_<slug>.
	 *
	 * @since 1.0.0
	 */
	public const SECTIONS = array( 'system', 'modules', 'design', 'texts' );

	/**
	 * Keys that would clash with the constants the theme defines itself.
	 *
	 * @since 1.0.0
	 */
	public const RESERVED_KEYS = array( 'version', 'slug', 'dir', 'url', 'min_php', 'min_wp', 'manifest_url', 'allow_auto_update' );

	/**
	 * Default value; for an enum an available choice.
	 *
	 * @since 1.0.0
	 * @var mixed
	 */
	public readonly mixed $default;

	/**
	 * Section of the setting, derived from the key unless given.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public readonly string $section;

	/**
	 * Validates the arguments and derives the section.
	 *
	 * @since 1.0.0
	 *
	 * @param string             $key            Key: lower case letters, digits and underscores, starting with a letter.
	 * @param string             $type           One of TYPES.
	 * @param mixed              $default_value  Default value; for an enum an available choice.
	 * @param array<mixed, bool> $choices        Enum values mapped to their availability; empty for other types.
	 * @param string             $level          One of LEVELS.
	 * @param string             $capability     Capability needed to change the setting.
	 * @param bool               $translatable   Whether the value differs per language.
	 * @param bool               $sensitive      Whether the value must stay out of exports and logs.
	 * @param string             $since          Theme version that added the key (X.Y.Z).
	 * @param string|null        $renamed_from   Former key, if the key was renamed.
	 * @param string|null        $reference_type Kind of object the value points to, e.g. "page".
	 * @param string|null        $a11y_rule      Accessibility rule the value must meet, e.g. "contrast".
	 * @param Closure|null       $label          Returns the translated label.
	 * @param Closure|null       $description    Returns the translated description.
	 * @param Closure|null       $sanitize       Cleans a raw value.
	 * @param Closure|null       $validate       Tells whether a clean value is valid.
	 * @param string|null        $section        One of SECTIONS; null derives it from the key.
	 * @param string|null        $group          Group on the settings page: colors, fonts, footer, contact, social or a module slug.
	 * @param int|null           $max_length     Longest value in characters, for the types of TEXT_TYPES only.
	 * @phpstan-param Closure(): string|null $label
	 * @phpstan-param Closure(): string|null $description
	 * @throws InvalidArgumentException When an argument is invalid; the message names the field.
	 */
	public function __construct(
		public readonly string $key,
		public readonly string $type,
		mixed $default_value,
		public readonly array $choices = array(),
		public readonly string $level = 'basic',
		public readonly string $capability = 'manage_options',
		public readonly bool $translatable = false,
		public readonly bool $sensitive = false,
		public readonly string $since = '1.0.0',
		public readonly ?string $renamed_from = null,
		public readonly ?string $reference_type = null,
		public readonly ?string $a11y_rule = null,
		public readonly ?Closure $label = null,
		public readonly ?Closure $description = null,
		public readonly ?Closure $sanitize = null,
		public readonly ?Closure $validate = null,
		?string $section = null,
		public readonly ?string $group = null,
		public readonly ?int $max_length = null,
	) {
		if ( 1 !== preg_match( '~^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$~', $key ) || strlen( $key ) > self::MAX_KEY_LENGTH || in_array( $key, self::RESERVED_KEYS, true ) ) {
			self::fail( sprintf( 'Setting key %s is invalid.', $key ) );
		}
		if ( ! in_array( $type, self::TYPES, true ) ) {
			self::fail( sprintf( 'Setting %1$s: type %2$s is unknown.', $key, $type ) );
		}
		if ( ! in_array( $level, self::LEVELS, true ) ) {
			self::fail( sprintf( 'Setting %1$s: level %2$s is unknown.', $key, $level ) );
		}
		if ( '' === $capability ) {
			self::fail( sprintf( 'Setting %s: capability is empty.', $key ) );
		}
		if ( 1 !== preg_match( '~^\d+\.\d+\.\d+$~', $since ) ) {
			self::fail( sprintf( 'Setting %1$s: since %2$s is no version X.Y.Z.', $key, $since ) );
		}
		$this->section = self::resolve_section( $key, $section );
		self::check_choices( $key, $type, $choices );
		self::check_extras( $key, $type, $translatable, $a11y_rule, $group, $max_length );
		$this->default = $default_value;
		if ( ! $this->accepts( $default_value ) ) {
			self::fail( sprintf( 'Setting %s: default does not match the type or is no available choice.', $key ) );
		}
	}

	/**
	 * Tells whether a value has the type of the setting; an enum value must be an available choice.
	 *
	 * Values are compared strictly: "5" is no int.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return bool True when the value fits.
	 */
	public function accepts( mixed $value ): bool {
		if ( in_array( $this->type, self::TEXT_TYPES, true ) || in_array( $this->type, self::TOKEN_TYPES, true ) ) {
			return is_string( $value ) && $this->fits( $value );
		}
		return match ( $this->type ) {
			'int'   => is_int( $value ),
			'bool'  => is_bool( $value ),
			default => ( is_int( $value ) || is_string( $value ) ) && true === ( $this->choices[ $value ] ?? null ) && in_array( $value, $this->values(), true ),
		};
	}

	/**
	 * Tells whether a string has the form of the type and fits the maximum length.
	 *
	 * The format checks need no WordPress function; Sanitizer cleans raw input
	 * into this form.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Value.
	 * @return bool True when the string fits.
	 */
	private function fits( string $value ): bool {
		if ( null !== $this->max_length && mb_strlen( $value, 'UTF-8' ) > $this->max_length ) {
			return false;
		}
		$pattern = match ( $this->type ) {
			'color' => '~^#[0-9a-f]{6}$~D',
			'font'  => '~^[a-z0-9]+(?:-[a-z0-9]+)*$~D',
			'email' => '~^(?:|[^@\s]+@[^@\s]+\.[^@\s]+)$~D',
			'url'   => '~^(?:|https?://[^\s<>"]+)$~iD',
			'tel'   => '~^[0-9 +()./-]*$~D',
			default => null,
		};
		return null === $pattern || 1 === preg_match( $pattern, $value );
	}

	/**
	 * Returns the enum values, available or not, in their order.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, mixed> Values; empty for other types.
	 */
	public function values(): array {
		return array_keys( $this->choices );
	}

	/**
	 * Returns the translated label; call it after init.
	 *
	 * @since 1.0.0
	 *
	 * @return string Label, empty without a label closure.
	 */
	public function label_text(): string {
		return self::text( $this->label );
	}

	/**
	 * Returns the translated description; call it after init.
	 *
	 * @since 1.0.0
	 *
	 * @return string Description, empty without a description closure.
	 */
	public function description_text(): string {
		return self::text( $this->description );
	}

	/**
	 * Runs a text closure.
	 *
	 * @since 1.0.0
	 *
	 * @param Closure|null $text Closure that returns a string.
	 * @return string Text, empty when there is no closure or it returns no string.
	 */
	private static function text( ?Closure $text ): string {
		if ( null === $text ) {
			return '';
		}
		$value = $text();
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Returns the section: "modules" for keys starting with module_, "system" for other keys without a section.
	 *
	 * Settings of a module live in modules/<slug>/settings.php and name their
	 * section ("design" or "texts"); the registry keeps "modules" for the switch.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $key     Key.
	 * @param string|null $section Given section.
	 * @return string Section.
	 * @throws InvalidArgumentException When the section is unknown or "modules" is given for another key.
	 */
	private static function resolve_section( string $key, ?string $section ): string {
		$is_switch = str_starts_with( $key, 'module_' );
		if ( null === $section ) {
			return $is_switch ? 'modules' : 'system';
		}
		if ( ! in_array( $section, self::SECTIONS, true ) ) {
			self::fail( sprintf( 'Setting %1$s: section %2$s is unknown.', $key, $section ) );
		}
		if ( 'modules' === $section && ! $is_switch ) {
			self::fail( sprintf( 'Setting %s: the section modules is only for module switches.', $key ) );
		}
		return $section;
	}

	/**
	 * Throws the exception for an invalid argument.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Message in English, naming the field.
	 * @return never
	 * @throws InvalidArgumentException Always.
	 */
	private static function fail( string $message ): never {
		throw new InvalidArgumentException( esc_html( $message ) );
	}

	/**
	 * Checks the language coupling, the accessibility rule, the group and the maximum length.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $key          Key.
	 * @param string      $type         Type.
	 * @param bool        $translatable Whether translatable.
	 * @param string|null $a11y_rule    Accessibility rule.
	 * @param string|null $group        Group.
	 * @param int|null    $max_length   Maximum length.
	 * @return void
	 * @throws InvalidArgumentException When a field does not fit the type.
	 */
	private static function check_extras( string $key, string $type, bool $translatable, ?string $a11y_rule, ?string $group, ?int $max_length ): void {
		if ( $translatable && in_array( $type, self::TOKEN_TYPES, true ) ) {
			self::fail( sprintf( 'Setting %1$s: translatable is not allowed for the type %2$s, which feeds SCSS and theme.json.', $key, $type ) );
		}
		if ( null !== $a11y_rule && ( ! in_array( $a11y_rule, self::A11Y_RULES, true ) || 'color' !== $type ) ) {
			self::fail( sprintf( 'Setting %1$s: a11y_rule %2$s is unknown or does not fit the type %3$s.', $key, $a11y_rule, $type ) );
		}
		if ( null !== $group && 1 !== preg_match( '~^[a-z0-9]+(?:[-_][a-z0-9]+)*$~D', $group ) ) {
			self::fail( sprintf( 'Setting %1$s: group %2$s is invalid.', $key, $group ) );
		}
		if ( null !== $max_length && ( $max_length < 1 || ! in_array( $type, self::TEXT_TYPES, true ) ) ) {
			self::fail( sprintf( 'Setting %1$s: max_length must be positive and is only for the types %2$s.', $key, implode( ', ', self::TEXT_TYPES ) ) );
		}
	}

	/**
	 * Checks the choices against the type.
	 *
	 * @since 1.0.0
	 *
	 * @param string             $key     Key.
	 * @param string             $type    Type.
	 * @param array<mixed, bool> $choices Choices.
	 * @return void
	 * @throws InvalidArgumentException When an enum has no choices, a flag is no bool or another type has choices.
	 */
	private static function check_choices( string $key, string $type, array $choices ): void {
		if ( 'enum' !== $type ) {
			if ( array() !== $choices ) {
				self::fail( sprintf( 'Setting %s: choices are only for the type enum.', $key ) );
			}
			return;
		}
		if ( array() === $choices ) {
			self::fail( sprintf( 'Setting %s: an enum needs choices.', $key ) );
		}
		foreach ( $choices as $available ) {
			if ( ! is_bool( $available ) ) {
				self::fail( sprintf( 'Setting %s: choices map each value to true or false.', $key ) );
			}
		}
	}
}
