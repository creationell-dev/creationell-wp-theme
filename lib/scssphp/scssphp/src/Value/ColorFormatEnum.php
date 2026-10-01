<?php

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */
namespace Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Value;

/**
 * @internal
 */
enum ColorFormatEnum implements \Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Value\ColorFormat
{
    /**
     * A color defined using the `rgb()` or `rgba()` functions.
     */
    case rgbFunction;
    /**
     * A color defined using the `hsl()` or `hsla()` functions.
     */
    case hslFunction;
}
