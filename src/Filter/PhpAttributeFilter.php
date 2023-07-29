<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Filter;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use function in_array;

class PhpAttributeFilter implements FilterInterface
{
    public function __construct(
        private string $attribute,
    ) {
    }

    public function isMatch(ClassMethod|Class_|Enum_ $value): bool
    {
        foreach ($value->attrGroups as $attributeGroup) {
            foreach ($attributeGroup->attrs as $attr) {
                if (in_array(needle: $this->attribute, haystack: $attr->name->getParts())) {
                    return true;
                }
            }
        }
        return false;
    }
}
