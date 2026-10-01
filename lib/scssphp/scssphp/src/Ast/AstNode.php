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
namespace Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast;

use Creationell\WpTheme\Vendor\SourceSpan\FileSpan;
/**
 * A node in an abstract syntax tree.
 *
 * @internal
 */
interface AstNode extends \Stringable
{
    public function getSpan(): FileSpan;
}
