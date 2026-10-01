<?php
/**
 * RTL subclass of line 5 on top of the adapted RTL port.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Compiler\Line5;

use Creationell\WpTheme\Vendor\MoodleHQ\RTLCSS\RTLCSS;
use Creationell\WpTheme\Vendor\Sabberworm\CSS\CSSList\CSSBlockList;
use Creationell\WpTheme\Vendor\Sabberworm\CSS\OutputFormat;
use Creationell\WpTheme\Vendor\Sabberworm\CSS\Parser;
use Creationell\WpTheme\Vendor\Sabberworm\CSS\Property\Declaration;
use Creationell\WpTheme\Vendor\Sabberworm\CSS\RuleSet\DeclarationList;
use Creationell\WpTheme\Vendor\Sabberworm\CSS\Value\CSSFunction;
use Creationell\WpTheme\Vendor\Sabberworm\CSS\Value\Color;
use Creationell\WpTheme\Vendor\Sabberworm\CSS\Value\RuleValueList;
use Creationell\WpTheme\Vendor\Sabberworm\CSS\Value\Size;
use Creationell\WpTheme\Vendor\Sabberworm\CSS\Value\ValueList;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Flips what moodlehq/rtlcss-php leaves as it is, the way Node RTLCSS 4.3.0 does.
 *
 * - Custom properties ("--*") keep name and value (only safe-area insets swap).
 * - transform: translate, translateX, translate3d, rotate, rotateZ and rotateY
 *   negate their first argument, skew, skewX and skewY all arguments, matrix,
 *   matrix3d and rotate3d the arguments of the horizontal axis; also inside a
 *   var() fallback. A length, number or angle changes its sign; calc(), min(),
 *   max() and clamp() are wrapped in calc(-1*...). Node keeps min(), max() and
 *   clamp(); the result keeps the type of the arguments, so it stays valid.
 *   var(), env() and other functions without a type stay as they are, as in
 *   Node RTLCSS: wrapping them would turn an empty or unitless 0 into an
 *   invalid transform. A right-to-left offset from a variable needs its own
 *   value under [dir=rtl] or a value directive.
 * - env(safe-area-inset-left) and env(safe-area-inset-right) swap where Node
 *   RTLCSS swaps them (SAFE_AREA_PROPERTIES), custom properties included.
 * - Gradients in background and background-image: "left" and "right" swap,
 *   a first angle is negated.
 * - Shadows: the first length of each shadow is negated.
 * - justify-content, justify-items, justify-self: "left" and "right" swap.
 * - Declarations of rtl:raw keep the place of their comment, also at the end
 *   of a block, where Rtl_Preprocessor puts an anchor declaration that flip()
 *   removes (the parser attaches a comment to the next declaration only).
 *   A raw directive after the last rule of an at-rule or of the file has no
 *   rule to attach to and is lost. Parser 9 renders
 *   the declarations of a block in the order of their line and column, and a
 *   parsed raw declaration has line 1, so the subclass numbers the declarations
 *   of each block in the order of the port.
 *
 * Parser 9 also keeps the comments in front of every declaration, while 8.7.0
 * kept only those in front of the first one; rtl:ignore, rtl:remove and
 * rtl:raw therefore work before any declaration, as in Node RTLCSS.
 *
 * Example:
 *
 *     ( new Rtl_Port( $document ) )->flip();
 *
 * @since 1.0.0
 */
final class Rtl_Port extends RTLCSS {

	/**
	 * Functions of transform whose first argument is negated.
	 *
	 * @since 1.0.0
	 */
	private const NEGATE_FIRST = array( 'translate', 'translatex', 'translate3d', 'rotate', 'rotatez', 'rotatey' );

	/**
	 * Functions of transform with the positions (from 0) of the arguments to negate; null for all.
	 *
	 * @since 1.0.0
	 */
	private const NEGATE_AT = array(
		'skew'     => null,
		'skewx'    => null,
		'skewy'    => null,
		'matrix'   => array( 1, 2, 4 ),
		'matrix3d' => array( 1, 3, 4, 12 ),
		'rotate3d' => array( 0, 3 ),
	);

	/**
	 * Units of an angle.
	 *
	 * @since 1.0.0
	 */
	private const ANGLE_UNITS = array( 'deg', 'grad', 'rad', 'turn' );

	/**
	 * Math functions whose result has the type of their arguments; negate() wraps them in calc().
	 *
	 * @since 1.0.0
	 */
	private const MATH_FUNCTIONS = array( 'calc', 'min', 'max', 'clamp' );

	/**
	 * Properties whose env(safe-area-inset-left/right) swap: those a processor of Node RTLCSS 4.3.0 handles.
	 *
	 * Node swaps the insets only there (lib/rtlcss.js, processEnv); inset,
	 * scroll-padding, scroll-margin, width or logical properties keep them,
	 * which suits values that the chain does not mirror either.
	 *
	 * @since 1.0.0
	 */
	private const SAFE_AREA_PROPERTIES = '~^--|direction|left|right|^(?:margin|padding|border-(?:color|style|width))$|border-radius|shadow|(?:transform|perspective)-origin|^(?!text-).*transform$|transition(?:-property)?$|(?:background|object)(?:-position(?:-x)?|-image)?$|float|clear|text-align|justify-(?:content|items|self)|cursor~i';

	/**
	 * Flips the document and removes the anchor declarations of Rtl_Preprocessor.
	 *
	 * The anchors go everywhere, also in rules that rtl:ignore skipped; a
	 * declaration of the same name with another value stays.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed The flipped document.
	 */
	public function flip() {
		$tree = parent::flip();
		if ( $tree instanceof CSSBlockList ) {
			foreach ( $tree->getAllRuleSets() as $set ) {
				foreach ( $set->getDeclarations( Rtl_Preprocessor::ANCHOR ) as $declaration ) {
					$value = $declaration->getValue();
					if ( Rtl_Preprocessor::ANCHOR_VALUE === ( is_string( $value ) ? $value : '' ) ) {
						$set->removeDeclaration( $declaration );
					}
				}
			}
		}
		return $tree;
	}

	/**
	 * Flips the declarations of a block and keeps the order of rtl:raw declarations.
	 *
	 * The same steps as the port: directives, raw declarations, remove, ignore,
	 * then processRule(); afterwards each declaration gets its place in the list
	 * as its line.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $node Block with declarations.
	 * @return void
	 */
	protected function processDeclaration( $node ) {
		if ( ! $node instanceof DeclarationList ) {
			return;
		}
		$declarations = array();
		foreach ( $node->getDeclarations() as $declaration ) {
			$this->parseComments( $declaration->getComments() );
			foreach ( (array) $this->shouldAddCss() as $raw ) {
				$tree = ( new Parser( '.wrapper{' . $raw . '}' ) )->parse();
				foreach ( $tree->getAllDeclarationBlocks() as $block ) {
					array_push( $declarations, ...$block->getDeclarations() );
				}
			}
			if ( $this->shouldRemoveNext() ) {
				continue;
			}
			if ( ! $this->shouldIgnoreNext() ) {
				$this->processRule( $declaration );
			}
			$declarations[] = $declaration;
		}
		foreach ( $declarations as $index => $declaration ) {
			$declaration->setPosition( $index + 1, 0 );
		}
		$node->setDeclarations( $declarations );
	}

	/**
	 * Flips one declaration: custom properties stay, transform, shadows, justify-* and gradients here, the rest in the port.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $rule Declaration.
	 * @return void
	 */
	protected function processRule( $rule ) {
		if ( ! $rule instanceof Declaration ) {
			return;
		}
		$property = strtolower( $rule->getPropertyName() );
		$value    = $rule->getValue();
		if ( 1 === preg_match( self::SAFE_AREA_PROPERTIES, $property ) ) {
			$this->swap_safe_area( $value );
		}
		if ( str_starts_with( $property, '--' ) ) {
			return;
		}
		if ( 1 === preg_match( '~^(?!text-).*transform$~', $property ) ) {
			$this->flip_transform( $value );
			return;
		}
		if ( str_contains( $property, 'shadow' ) ) {
			$this->flip_shadows( $value );
			return;
		}
		if ( 1 === preg_match( '~^justify-(?:content|items|self)$~', $property ) ) {
			$rule->setValue( $this->swapLeftRight( $value ) );
			return;
		}
		if ( 1 === preg_match( '~background(?:-image)?$~', $property ) ) {
			$this->flip_gradients( $value );
		}
		parent::processRule( $rule );
	}

	/**
	 * Negates the horizontal arguments of the transform functions in a value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value of a transform declaration.
	 * @return void
	 */
	private function flip_transform( mixed $value ): void {
		if ( ! $value instanceof ValueList || $value instanceof Color ) {
			return;
		}
		$name = $value instanceof CSSFunction ? strtolower( $value->getName() ) : '';
		if ( in_array( $name, self::NEGATE_FIRST, true ) || array_key_exists( $name, self::NEGATE_AT ) ) {
			$positions  = in_array( $name, self::NEGATE_FIRST, true ) ? array( 0 ) : self::NEGATE_AT[ $name ];
			$components = $value->getListComponents();
			foreach ( $components as $index => $component ) {
				if ( null === $positions || in_array( $index, $positions, true ) ) {
					$components[ $index ] = $this->negated( $component );
				}
			}
			$value->setListComponents( $components );
			return;
		}
		foreach ( $value->getListComponents() as $component ) {
			$this->flip_transform( $component );
		}
	}

	/**
	 * Returns a negated value: a size changes its sign, calc(), min(), max() and clamp() are wrapped in calc(-1*...).
	 *
	 * Other functions (var(), env(), attr()) have no type of their own; calc(-1*var(--x))
	 * with --x: 0 would be a number, not a length, and void the whole transform.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Argument of a transform function.
	 * @return mixed The same size with the other sign, a string with calc(), or the value as it was.
	 */
	private function negated( mixed $value ): mixed {
		if ( $value instanceof Size ) {
			if ( 0.0 !== (float) $value->getSize() ) {
				$value->setSize( -$value->getSize() );
			}
			return $value;
		}
		if ( $value instanceof CSSFunction && ! $value instanceof Color && in_array( strtolower( $value->getName() ), self::MATH_FUNCTIONS, true ) ) {
			$css = $value->render( OutputFormat::createCompact() );
			if ( 'calc' === strtolower( $value->getName() ) ) {
				return 'calc(-1*(' . substr( $css, strlen( 'calc(' ), -1 ) . '))';
			}
			return 'calc(-1*' . $css . ')';
		}
		return $value;
	}

	/**
	 * Swaps safe-area-inset-left and safe-area-inset-right in a value, as Node RTLCSS does for env().
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value or part of it.
	 * @return void
	 */
	private function swap_safe_area( mixed $value ): void {
		if ( ! $value instanceof ValueList || $value instanceof Color ) {
			return;
		}
		$components = $value->getListComponents();
		$changed    = false;
		foreach ( $components as $index => $component ) {
			if ( is_string( $component ) && 1 === preg_match( '~^safe-area-inset-(left|right)$~i', $component, $side ) ) {
				$components[ $index ] = 'safe-area-inset-' . ( 'left' === strtolower( $side[1] ) ? 'right' : 'left' );
				$changed              = true;
			}
			$this->swap_safe_area( $component );
		}
		if ( $changed ) {
			$value->setListComponents( $components );
		}
	}

	/**
	 * Negates the first length of every shadow in a value.
	 *
	 * The parser binds a comma tighter than a space: "1px 2px red,3px 0 #fff"
	 * becomes a space list with the comma list "red,3px" in it. The value is
	 * therefore flattened into its tokens, with null where a comma separates
	 * two shadows.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value of a shadow declaration.
	 * @return void
	 */
	private function flip_shadows( mixed $value ): void {
		$done = false;
		foreach ( self::shadow_tokens( $value ) as $token ) {
			if ( null === $token ) {
				$done = false;
			} elseif ( ! $done && $token instanceof Size ) {
				$this->negated( $token );
				$done = true;
			}
		}
	}

	/**
	 * Flattens a shadow value into its tokens; null marks a comma between two shadows.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value or part of it.
	 * @return list<mixed> Tokens.
	 */
	private static function shadow_tokens( mixed $value ): array {
		if ( ! $value instanceof RuleValueList ) {
			return array( $value );
		}
		$tokens = array();
		foreach ( $value->getListComponents() as $index => $component ) {
			if ( ',' === $value->getListSeparator() && $index > 0 ) {
				$tokens[] = null;
			}
			array_push( $tokens, ...self::shadow_tokens( $component ) );
		}
		return $tokens;
	}

	/**
	 * Flips the gradients in a background value: "left" and "right" swap, a first angle is negated.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value of a background declaration.
	 * @return void
	 */
	private function flip_gradients( mixed $value ): void {
		if ( ! $value instanceof ValueList || $value instanceof Color ) {
			return;
		}
		if ( $value instanceof CSSFunction && str_ends_with( strtolower( $value->getName() ), 'gradient' ) ) {
			$components = $value->getListComponents();
			$first      = $components[0] ?? null;
			if ( $first instanceof Size && in_array( strtolower( (string) $first->getUnit() ), self::ANGLE_UNITS, true ) ) {
				$this->negated( $first );
			}
			$value->setListComponents( array_map( array( $this, 'swap_keywords' ), $components ) );
			return;
		}
		foreach ( $value->getListComponents() as $component ) {
			$this->flip_gradients( $component );
		}
	}

	/**
	 * Swaps "left" and "right" in a keyword or in the keywords of a list.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Argument of a gradient.
	 * @return mixed The keyword with the other side, the list with swapped keywords, or the value.
	 */
	private function swap_keywords( mixed $value ): mixed {
		if ( is_string( $value ) ) {
			return $this->swapLeftRight( $value );
		}
		if ( $value instanceof RuleValueList ) {
			$value->setListComponents( array_map( array( $this, 'swap_keywords' ), $value->getListComponents() ) );
		}
		return $value;
	}
}
