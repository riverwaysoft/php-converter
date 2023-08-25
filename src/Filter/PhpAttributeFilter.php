<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Filter;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use Riverwaysoft\PhpConverter\Ast\ClassName;

class PhpAttributeFilter implements FilterInterface
{
    private ClassName $attribute;

    public function __construct(
        string $attribute,
    ) {
        $this->attribute = new ClassName($attribute);
    }

    public function isMatch(ClassMethod|Class_|Enum_ $value): bool
    {
        foreach ($value->attrGroups as $attributeGroup) {
            foreach ($attributeGroup->attrs as $attr) {
                if ($this->attribute->getShortName() === $attr->name->getLast()) {
                    return true;
                }
            }
        }
        return false;
    }
}
