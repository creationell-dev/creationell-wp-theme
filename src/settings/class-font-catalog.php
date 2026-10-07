<?php
/**
 * Catalog of the fonts the design settings offer.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings;

use Creationell\WpTheme\Compiler\Scss_Value;
use InvalidArgumentException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Lists the fonts: the two system stacks of the theme plus the entries of the child filter.
 *
 * A child entry counts only when it is complete and safe: a slug of lower case
 * letters, digits and hyphens that no other font uses, a name, a stack that
 * Scss_Value::font_stack() accepts, scripts from SCRIPTS and, optionally, faces
 * whose files lie in assets/fonts/ of the child theme as .woff2. Any other entry
 * is dropped, reported with _doing_it_wrong() and kept as a finding. A setting
 * whose saved slug the catalog no longer knows falls back to its default font,
 * also with a finding. The catalog is built once per request.
 *
 * @since 1.0.0
 */
final class Font_Catalog {

	/**
	 * Filter for child fonts.
	 *
	 * @since 1.0.0
	 */
	public const FILTER = 'creationell_wp_theme_font_catalog';

	/**
	 * Scripts a font can declare; Font_Coverage maps languages to them.
	 *
	 * @since 1.0.0
	 */
	public const SCRIPTS = array( 'latin', 'latin-ext', 'vietnamese', 'cyrillic', 'arabic', 'cjk' );

	/**
	 * Stack of "system-sans": the $font-family-sans-serif default of Bootstrap 5.
	 *
	 * @since 1.0.0
	 */
	public const SANS_STACK = 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", "Noto Sans", "Liberation Sans", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"';

	/**
	 * Stack of "system-serif".
	 *
	 * @since 1.0.0
	 */
	public const SERIF_STACK = 'Georgia, "Times New Roman", Times, serif';

	/**
	 * Allowed form of a font file of a child entry.
	 *
	 * @since 1.0.0
	 */
	public const SRC_PATTERN = '~^file:\./assets/fonts/[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)*\.woff2$~D';

	/**
	 * Generic font families of CSS; one of them as first name of a stack hides the font files.
	 *
	 * @since 1.0.0
	 */
	public const GENERIC_FAMILIES = array( 'serif', 'sans-serif', 'monospace', 'cursive', 'fantasy', 'system-ui', 'ui-serif', 'ui-sans-serif', 'ui-monospace', 'ui-rounded', 'math', 'emoji', 'fangsong' );

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Fonts by slug, built once.
	 *
	 * @var array<string, Font_Family>|null
	 */
	private ?array $families = null;

	/**
	 * Dropped child entries and lost slugs, by "<slug>|<key>".
	 *
	 * @var array<string, array{slug: string, key: string, message: string}>
	 */
	private array $findings = array();

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
	 * @param self|null $catalog Instance.
	 * @return void
	 */
	public static function set_instance( ?self $catalog ): void {
		self::$instance = $catalog;
	}

	/**
	 * Returns all fonts.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, Font_Family> Fonts by slug: the built-in ones first, then the child entries in filter order.
	 */
	public function all(): array {
		if ( null === $this->families ) {
			$this->families = $this->build();
		}
		return $this->families;
	}

	/**
	 * Returns the slugs of all fonts.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Slugs.
	 */
	public function slugs(): array {
		return array_keys( $this->all() );
	}

	/**
	 * Returns stack and theme.json font faces of every font, the source of the font presets.
	 *
	 * Theme::boot() hands it to Theme_Json_Tokens::set_fonts(), so the core
	 * prints the font-face rules of the chosen fonts.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array{stack: string, faces: list<array<string, mixed>>}> Stack and font faces by font slug.
	 */
	public static function theme_json_fonts(): array {
		$fonts = array();
		foreach ( self::instance()->all() as $slug => $font ) {
			$fonts[ $slug ] = array(
				'stack' => $font->stack,
				'faces' => $font->theme_json_faces(),
			);
		}
		return $fonts;
	}

	/**
	 * Returns a font by slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Slug.
	 * @return Font_Family|null Font, or null when the catalog does not know it.
	 */
	public function get( string $slug ): ?Font_Family {
		return $this->all()[ $slug ] ?? null;
	}

	/**
	 * Returns the font a font setting selects.
	 *
	 * A slug the catalog does not know (e.g. a child font that was removed after
	 * it was saved) falls back to the default of the setting and is reported.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Setting key of type "font".
	 * @return Font_Family|null Font; null for "inherit" and for a key that is no font setting.
	 */
	public function for_setting( string $key ): ?Font_Family {
		$registry = Registry::instance();
		if ( ! $registry->has( $key ) || 'font' !== $registry->get( $key )->type ) {
			return null;
		}
		$definition = $registry->get( $key );
		$slug       = Settings::instance()->get( $key );
		if ( Sanitizer::INHERIT === $slug ) {
			return null;
		}
		$family = is_string( $slug ) ? $this->get( $slug ) : null;
		if ( null !== $family ) {
			return $family;
		}
		$this->report(
			is_string( $slug ) ? $slug : '',
			$key,
			sprintf( 'The font %1$s of the setting %2$s is not in the font catalog; the default font applies.', is_string( $slug ) ? $slug : '?', $key )
		);
		return is_string( $definition->default ) ? $this->get( $definition->default ) : null;
	}

	/**
	 * Returns the dropped child entries and the lost slugs found so far.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{slug: string, key: string, message: string}> Findings; "key" is empty for a dropped entry.
	 */
	public function findings(): array {
		$this->all();
		return array_values( $this->findings );
	}

	/**
	 * Builds the catalog from the built-in fonts and the filter.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, Font_Family> Fonts by slug.
	 */
	private function build(): array {
		$families = array(
			'system-sans'  => new Font_Family( 'system-sans', static fn(): string => __( 'System font (sans-serif)', 'creationell-wp-theme' ), self::SANS_STACK, self::SCRIPTS ),
			'system-serif' => new Font_Family( 'system-serif', static fn(): string => __( 'System font (serif)', 'creationell-wp-theme' ), self::SERIF_STACK, self::SCRIPTS ),
		);

		/**
		 * Filters the fonts a child theme adds to the font catalog.
		 *
		 * Each entry needs a slug (lower case letters, digits, hyphens) as key, a
		 * name, a CSS font stack and the scripts it covers (latin, latin-ext,
		 * vietnamese, cyrillic, arabic, cjk). Font files are optional and must lie in
		 * assets/fonts/ of the child theme as .woff2; the core prints the font-face
		 * rules for them. Invalid entries are dropped and reported.
		 *
		 * Example:
		 *
		 *     add_filter(
		 *         'creationell_wp_theme_font_catalog',
		 *         static function ( mixed $fonts ): mixed {
		 *             $fonts = is_array( $fonts ) ? $fonts : array();
		 *             $fonts['brand-sans'] = array(
		 *                 'name'    => 'Brand Sans',
		 *                 'stack'   => '"Brand Sans", Arial, sans-serif',
		 *                 'scripts' => array( 'latin', 'latin-ext' ),
		 *                 'faces'   => array(
		 *                     array( 'src' => 'file:./assets/fonts/brand-sans.woff2', 'weight' => '400', 'style' => 'normal' ),
		 *                 ),
		 *             );
		 *             return $fonts;
		 *         }
		 *     );
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, array{name: string, stack: string, scripts: array<int, string>, faces?: array<int, array{src: string, weight: string, style: string, unicode_range?: string}>}> $fonts Child fonts by slug; empty in the parent theme.
		 */
		$entries = apply_filters( 'creationell_wp_theme_font_catalog', array() );
		if ( ! is_array( $entries ) ) {
			$this->report( '', '', sprintf( 'The filter %s must return an array; it is ignored.', self::FILTER ) );
			return $families;
		}
		foreach ( $entries as $slug => $entry ) {
			$slug   = (string) $slug;
			$family = $this->entry( $slug, $entry, $families );
			if ( null !== $family ) {
				$this->check_stack( $family );
				$this->check_ranges( $family );
				$families[ $slug ] = $family;
			}
		}
		return $families;
	}

	/**
	 * Checks one child entry.
	 *
	 * @since 1.0.0
	 *
	 * @param string                     $slug     Slug.
	 * @param mixed                      $entry    Entry.
	 * @param array<string, Font_Family> $families Fonts so far.
	 * @return Font_Family|null Font, or null when the entry is dropped.
	 */
	private function entry( string $slug, mixed $entry, array $families ): ?Font_Family {
		$problem = null;
		if ( 1 !== preg_match( '~^[a-z0-9]+(?:-[a-z0-9]+)*$~D', $slug ) || Sanitizer::INHERIT === $slug ) {
			$problem = 'needs a slug of lower case letters, digits and hyphens';
		} elseif ( isset( $families[ $slug ] ) ) {
			$problem = 'uses the slug of another font';
		} elseif ( ! is_array( $entry ) || ! is_string( $entry['name'] ?? null ) || '' === trim( $entry['name'] ) ) {
			$problem = 'needs a name';
		} elseif ( ! is_string( $entry['stack'] ?? null ) || ! self::valid_stack( $entry['stack'] ) ) {
			$problem = 'needs a CSS font stack of font names and generic families';
		}
		$scripts = is_array( $entry ) ? self::scripts( $entry['scripts'] ?? null ) : null;
		$faces   = is_array( $entry ) ? self::faces( $entry['faces'] ?? array() ) : null;
		if ( null === $problem && null === $scripts ) {
			$problem = 'needs a list of scripts from ' . implode( ', ', self::SCRIPTS );
		} elseif ( null === $problem && null === $faces ) {
			$problem = 'has faces that are no list of .woff2 files in assets/fonts/ with weight and style';
		}
		if ( null !== $problem || ! is_array( $entry ) || null === $scripts || null === $faces ) {
			$this->report( $slug, '', sprintf( 'The font "%1$s" of the filter %2$s %3$s; it is ignored.', $slug, self::FILTER, $problem ?? 'is invalid' ) );
			return null;
		}
		return new Font_Family( $slug, trim( (string) $entry['name'] ), (string) $entry['stack'], $scripts, $faces );
	}

	/**
	 * Tells whether a stack is safe for SCSS.
	 *
	 * @since 1.0.0
	 *
	 * @param string $stack Stack.
	 * @return bool True when Scss_Value::font_stack() accepts it.
	 */
	private static function valid_stack( string $stack ): bool {
		try {
			Scss_Value::font_stack( $stack );
		} catch ( InvalidArgumentException $exception ) {
			unset( $exception );
			return false;
		}
		return true;
	}

	/**
	 * Checks the scripts of an entry.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $scripts Scripts.
	 * @return array<int, string>|null Scripts, or null when empty or unknown.
	 * @phpstan-return list<string>|null
	 */
	private static function scripts( mixed $scripts ): ?array {
		if ( ! is_array( $scripts ) || array() === $scripts || ! array_is_list( $scripts ) ) {
			return null;
		}
		$clean = array();
		foreach ( $scripts as $script ) {
			if ( ! is_string( $script ) || ! in_array( $script, self::SCRIPTS, true ) ) {
				return null;
			}
			$clean[] = $script;
		}
		return array_values( array_unique( $clean ) );
	}

	/**
	 * Checks the faces of an entry.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $faces Faces.
	 * @return array<int, array{src: string, weight: string, style: string, unicode_range?: string}>|null Faces with the weight as string, or null when one is invalid.
	 * @phpstan-return list<array{src: string, weight: string, style: string, unicode_range?: string}>|null
	 */
	private static function faces( mixed $faces ): ?array {
		if ( ! is_array( $faces ) || ! array_is_list( $faces ) ) {
			return null;
		}
		$clean = array();
		foreach ( $faces as $face ) {
			$src    = is_array( $face ) ? ( $face['src'] ?? null ) : null;
			$weight = is_array( $face ) ? ( $face['weight'] ?? null ) : null;
			$weight = is_int( $weight ) ? (string) $weight : $weight;
			$style  = is_array( $face ) ? ( $face['style'] ?? null ) : null;
			$range  = is_array( $face ) ? ( $face['unicode_range'] ?? null ) : null;
			if ( ! is_string( $src ) || 1 !== preg_match( self::SRC_PATTERN, $src )
				|| ! is_string( $weight ) || 1 !== preg_match( '~^[1-9]00(?: [1-9]00)?$~D', $weight )
				|| ! in_array( $style, array( 'normal', 'italic', 'oblique' ), true )
				|| ( null !== $range && ( ! is_string( $range ) || 1 !== preg_match( '~^U\+[0-9A-Fa-f?]{1,6}(?:-[0-9A-Fa-f]{1,6})?(?:, ?U\+[0-9A-Fa-f?]{1,6}(?:-[0-9A-Fa-f]{1,6})?)*$~D', $range ) ) ) ) {
				return null;
			}
			$item = array(
				'src'    => $src,
				'weight' => $weight,
				'style'  => $style,
			);
			if ( is_string( $range ) ) {
				$item['unicode_range'] = $range;
			}
			$clean[] = $item;
		}
		return $clean;
	}

	/**
	 * Reports a stack whose first name breaks the font-face rules WordPress prints; the entry stays.
	 *
	 * WordPress takes the family of every face from the first name of the
	 * stack, removes its quotes and quotes it again only when it holds a space.
	 *
	 * @since 1.0.0
	 *
	 * @param Font_Family $family Font with faces.
	 * @return void
	 */
	private function check_stack( Font_Family $family ): void {
		if ( array() === $family->faces ) {
			return;
		}
		$first  = trim( explode( ',', $family->stack, 2 )[0] );
		$quoted = 1 === preg_match( '~^(["\'])(.*)\1$~sD', $first, $matches );
		$name   = $quoted ? $matches[2] : $first;
		if ( ! $quoted && in_array( strtolower( $name ), self::GENERIC_FAMILIES, true ) ) {
			$this->report( $family->slug, 'stack_generic', sprintf( 'The font "%1$s" of the filter %2$s starts its stack with the generic family %3$s, so its font files are never used; put the name of the font first.', $family->slug, self::FILTER, $name ) );
			return;
		}
		if ( $quoted && ! str_contains( $name, ' ' ) && ( 1 !== preg_match( '~^-?[A-Za-z][A-Za-z0-9_-]*$~D', $name ) || in_array( strtolower( $name ), self::GENERIC_FAMILIES, true ) ) ) {
			$this->report( $family->slug, 'stack_name', sprintf( 'The font "%1$s" of the filter %2$s starts its stack with "%3$s"; WordPress prints that name without quotes, so browsers drop its font-face rules. Use letters, digits, "_" and "-" (no digit first) or a name with a space.', $family->slug, self::FILTER, $name ) );
		}
	}

	/**
	 * Reports face ranges with parts CSS does not allow; the entry stays.
	 *
	 * Browsers drop such a unicode-range as a whole and use the face for every
	 * character, so an extra font would load on every page.
	 *
	 * @since 1.0.0
	 *
	 * @param Font_Family $family Font.
	 * @return void
	 */
	private function check_ranges( Font_Family $family ): void {
		foreach ( $family->faces as $face ) {
			$invalid = isset( $face['unicode_range'] ) ? Unicode_Range::invalid_parts( $face['unicode_range'] ) : array();
			if ( array() !== $invalid ) {
				$this->report( $family->slug, 'unicode_range', sprintf( 'The font "%1$s" of the filter %2$s has a unicode range with parts CSS does not allow (%3$s); browsers then ignore the whole range of %4$s and load it for every page. Fix or remove these parts.', $family->slug, self::FILTER, implode( ', ', $invalid ), $face['src'] ) );
				return;
			}
		}
	}

	/**
	 * Records and reports a finding once.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug    Font slug; empty when unknown.
	 * @param string $key     Setting key; empty for a dropped entry; stack_name, stack_generic or unicode_range for a kept entry with a problem.
	 * @param string $message Message in English; not translated, it may come before init.
	 * @return void
	 */
	private function report( string $slug, string $key, string $message ): void {
		if ( isset( $this->findings[ $slug . '|' . $key ] ) ) {
			return;
		}
		$this->findings[ $slug . '|' . $key ] = array(
			'slug'    => $slug,
			'key'     => $key,
			'message' => $message,
		);
		_doing_it_wrong( __CLASS__ . '::all', esc_html( $message ), '1.0.0' );
	}
}
