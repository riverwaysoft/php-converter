<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\ClassFilter;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use function in_array;

class PhpAttributeFilter implements ClassFilterInterface
{
    public function __construct(
        private string $attribute
    ) {
    }

    public function isMatch(Class_|Enum_ $class): bool
    {
        foreach ($class->attrGroups as $attributeGroup) {
            foreach ($attributeGroup->attrs as $attr) {
                if (in_array(needle: $this->attribute, haystack: $attr->name->getParts())) {
                    return true;
                }
            }
        }
        return false;
    }
}
