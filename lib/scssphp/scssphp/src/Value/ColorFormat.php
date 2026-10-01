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

use Creationell\WpTheme\Vendor\JiriPudil\SealedClasses\Sealed;
/**
 * @internal
 */
#[Sealed(permits: [ColorFormatEnum::class, SpanColorFormat::class])]
interface ColorFormat
{
}
