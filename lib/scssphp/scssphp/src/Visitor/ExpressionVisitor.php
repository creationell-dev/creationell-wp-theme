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

use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Sass\Expression\BinaryOperationExpression;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Sass\Expression\BooleanExpression;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Sass\Expression\ColorExpression;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Sass\Expression\FunctionExpression;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Sass\Expression\IfExpression;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Sass\Expression\InterpolatedFunctionExpression;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Sass\Expression\ListExpression;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Sass\Expression\MapExpression;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Sass\Expression\NullExpression;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Sass\Expression\NumberExpression;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Sass\Expression\ParenthesizedExpression;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Sass\Expression\SelectorExpression;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Sass\Expression\StringExpression;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Sass\Expression\SupportsExpression;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Sass\Expression\UnaryOperationExpression;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Sass\Expression\ValueExpression;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Sass\Expression\VariableExpression;
/**
 * An interface for visitors that traverse SassScript expressions.
 *
 * @internal
 *
 * @template T
 */
interface ExpressionVisitor
{
    /**
     * @return T
     */
    public function visitBinaryOperationExpression(BinaryOperationExpression $node);
    /**
     * @return T
     */
    public function visitBooleanExpression(BooleanExpression $node);
    /**
     * @return T
     */
    public function visitColorExpression(ColorExpression $node);
    /**
     * @return T
     */
    public function visitInterpolatedFunctionExpression(InterpolatedFunctionExpression $node);
    /**
     * @return T
     */
    public function visitFunctionExpression(FunctionExpression $node);
    /**
     * @return T
     */
    public function visitIfExpression(IfExpression $node);
    /**
     * @return T
     */
    public function visitListExpression(ListExpression $node);
    /**
     * @return T
     */
    public function visitMapExpression(MapExpression $node);
    /**
     * @return T
     */
    public function visitNullExpression(NullExpression $node);
    /**
     * @return T
     */
    public function visitNumberExpression(NumberExpression $node);
    /**
     * @return T
     */
    public function visitParenthesizedExpression(ParenthesizedExpression $node);
    /**
     * @return T
     */
    public function visitSelectorExpression(SelectorExpression $node);
    /**
     * @return T
     */
    public function visitStringExpression(StringExpression $node);
    /**
     * @return T
     */
    public function visitSupportsExpression(SupportsExpression $node);
    /**
     * @return T
     */
    public function visitUnaryOperationExpression(UnaryOperationExpression $node);
    /**
     * @return T
     */
    public function visitValueExpression(ValueExpression $node);
    /**
     * @return T
     */
    public function visitVariableExpression(VariableExpression $node);
}
