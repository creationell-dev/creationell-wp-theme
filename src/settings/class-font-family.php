<?php
/**
 * One font of the font catalog.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings;

use Closure;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Describes a font of the catalog: slug, name, CSS font stack, covered scripts and font files.
 *
 * The stack goes into SCSS only through Scss_Value::font_stack(); the faces
 * become fontFace presets of theme.json, so the core prints their @font-face rules.
 *
 * @since 1.0.0
 */
final class Font_Family {

	/**
	 * Name, or a closure that returns it (translated names are built on demand).
	 *
	 * @var Closure|string
	 * @phpstan-var (Closure(): string)|string
	 */
	private Closure|string $name;

	/**
	 * Sets the values; Font_Catalog checks them before.
	 *
	 * @since 1.0.0
	 *
	 * @param string                                                                                $slug    Slug: lower case letters, digits and hyphens.
	 * @param Closure|string                                                                        $name    Name, or a closure returning it.
	 * @param string                                                                                $stack   CSS font stack.
	 * @param array<int, string>                                                                    $scripts Covered scripts, see Font_Catalog::SCRIPTS.
	 * @param array<int, array{src: string, weight: string, style: string, unicode_range?: string}> $faces   Font files, "file:./assets/fonts/<name>.woff2" relative to the child theme.
	 * @phpstan-param (Closure(): string)|string $name
	 * @phpstan-param list<string> $scripts
	 * @phpstan-param list<array{src: string, weight: string, style: string, unicode_range?: string}> $faces
	 */
	public function __construct(
		public readonly string $slug,
		Closure|string $name,
		public readonly string $stack,
		public readonly array $scripts,
		public readonly array $faces = array()
	) {
		$this->name = $name;
	}

	/**
	 * Returns the name for lists and messages.
	 *
	 * @since 1.0.0
	 *
	 * @return string Name.
	 */
	public function name(): string {
		return $this->name instanceof Closure ? ( $this->name )() : $this->name;
	}

	/**
	 * Returns the font faces as fontFace entries of theme.json.
	 *
	 * The family name is the first name of the stack, so the font-face rules
	 * serve the name the stylesheet asks for; the faces show with font-display
	 * swap. A unicode range goes in only where the face has one.
	 *
	 * @since 1.0.0
	 *
	 * @return list<array<string, mixed>> Font faces of theme.json.
	 */
	public function theme_json_faces(): array {
		$family = trim( explode( ',', $this->stack, 2 )[0], " \t\"'" );
		$faces  = array();
		foreach ( $this->faces as $face ) {
			$entry = array(
				'fontFamily'  => $family,
				'fontWeight'  => $face['weight'],
				'fontStyle'   => $face['style'],
				'fontDisplay' => 'swap',
				'src'         => array( $face['src'] ),
			);
			if ( isset( $face['unicode_range'] ) ) {
				$entry['unicodeRange'] = $face['unicode_range'];
			}
			$faces[] = $entry;
		}
		return $faces;
	}
}
