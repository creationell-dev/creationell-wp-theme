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
namespace Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Visitor;

use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Css\CssAtRule;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Css\CssComment;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Css\CssDeclaration;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Css\CssImport;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Css\CssKeyframeBlock;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Css\CssMediaRule;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Css\CssStyleRule;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Css\CssStylesheet;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Css\CssSupportsRule;
/**
 * An interface for visitors that traverse CSS statements.
 *
 * @internal
 *
 * @template T
 * @template-extends ModifiableCssVisitor<T>
 */
interface CssVisitor extends ModifiableCssVisitor
{
    /**
     * @return T
     */
    public function visitCssAtRule(CssAtRule $node);
    /**
     * @return T
     */
    public function visitCssComment(CssComment $node);
    /**
     * @return T
     */
    public function visitCssDeclaration(CssDeclaration $node);
    /**
     * @return T
     */
    public function visitCssImport(CssImport $node);
    /**
     * @return T
     */
    public function visitCssKeyframeBlock(CssKeyframeBlock $node);
    /**
     * @return T
     */
    public function visitCssMediaRule(CssMediaRule $node);
    /**
     * @return T
     */
    public function visitCssStyleRule(CssStyleRule $node);
    /**
     * @return T
     */
    public function visitCssStylesheet(CssStylesheet $node);
    /**
     * @return T
     */
    public function visitCssSupportsRule(CssSupportsRule $node);
}
