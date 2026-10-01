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
namespace Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Css;

use Creationell\WpTheme\Vendor\JiriPudil\SealedClasses\Sealed;
/**
 * @internal
 */
#[Sealed(permits: [CssMediaQuery::class, MediaQuerySingletonMergeResult::class])]
interface MediaQueryMergeResult
{
}
