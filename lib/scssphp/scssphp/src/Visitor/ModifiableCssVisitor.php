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

use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Css\ModifiableCssAtRule;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Css\ModifiableCssComment;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Css\ModifiableCssDeclaration;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Css\ModifiableCssImport;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Css\ModifiableCssKeyframeBlock;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Css\ModifiableCssMediaRule;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Css\ModifiableCssStyleRule;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Css\ModifiableCssStylesheet;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Css\ModifiableCssSupportsRule;
/**
 * An interface for visitors that traverse CSS statements.
 *
 * @internal
 *
 * @template T
 */
interface ModifiableCssVisitor
{
    /**
     * @return T
     */
    public function visitCssAtRule(ModifiableCssAtRule $node);
    /**
     * @return T
     */
    public function visitCssComment(ModifiableCssComment $node);
    /**
     * @return T
     */
    public function visitCssDeclaration(ModifiableCssDeclaration $node);
    /**
     * @return T
     */
    public function visitCssImport(ModifiableCssImport $node);
    /**
     * @return T
     */
    public function visitCssKeyframeBlock(ModifiableCssKeyframeBlock $node);
    /**
     * @return T
     */
    public function visitCssMediaRule(ModifiableCssMediaRule $node);
    /**
     * @return T
     */
    public function visitCssStyleRule(ModifiableCssStyleRule $node);
    /**
     * @return T
     */
    public function visitCssStylesheet(ModifiableCssStylesheet $node);
    /**
     * @return T
     */
    public function visitCssSupportsRule(ModifiableCssSupportsRule $node);
}
