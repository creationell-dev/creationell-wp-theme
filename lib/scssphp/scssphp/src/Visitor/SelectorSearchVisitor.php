<?php

namespace Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Visitor;

use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Selector\AttributeSelector;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Selector\ClassSelector;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Selector\ComplexSelector;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Selector\ComplexSelectorComponent;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Selector\CompoundSelector;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Selector\IDSelector;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Selector\ParentSelector;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Selector\PlaceholderSelector;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Selector\PseudoSelector;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Selector\SelectorList;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Selector\SimpleSelector;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Selector\TypeSelector;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Ast\Selector\UniversalSelector;
use Creationell\WpTheme\Vendor\ScssPhp\ScssPhp\Util\IterableUtil;
/**
 * A {@see SelectorVisitor} whose `visit*` methods default to returning `null`, but
 * which returns the first non-`null` value returned by any method.
 *
 * This can be extended to find the first instance of particular nodes in the
 * AST.
 *
 * @template T
 * @template-implements SelectorVisitor<T|null>
 *
 * @internal
 */
abstract class SelectorSearchVisitor implements SelectorVisitor
{
    public function visitAttributeSelector(AttributeSelector $attribute)
    {
        return null;
    }
    public function visitClassSelector(ClassSelector $klass)
    {
        return null;
    }
    public function visitIDSelector(IDSelector $id)
    {
        return null;
    }
    public function visitParentSelector(ParentSelector $parent)
    {
        return null;
    }
    public function visitPlaceholderSelector(PlaceholderSelector $placeholder)
    {
        return null;
    }
    public function visitTypeSelector(TypeSelector $type)
    {
        return null;
    }
    public function visitUniversalSelector(UniversalSelector $universal)
    {
        return null;
    }
    public function visitComplexSelector(ComplexSelector $complex)
    {
        return IterableUtil::search($complex->getComponents(), fn(ComplexSelectorComponent $component) => $this->visitCompoundSelector($component->getSelector()));
    }
    public function visitCompoundSelector(CompoundSelector $compound)
    {
        return IterableUtil::search($compound->getComponents(), fn(SimpleSelector $simple) => $simple->accept($this));
    }
    public function visitPseudoSelector(PseudoSelector $pseudo)
    {
        if ($pseudo->getSelector() !== null) {
            return $this->visitSelectorList($pseudo->getSelector());
        }
        return null;
    }
    public function visitSelectorList(SelectorList $list)
    {
        return IterableUtil::search($list->getComponents(), $this->visitComplexSelector(...));
    }
}
