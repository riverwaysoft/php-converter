<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\ClassFilter;

use PhpParser\Node\Stmt\Class_;

class PhpAttributeFilter implements ClassFilterInterface
{
    public function __construct(private string $attribute)
    {
    }

    public function isMatch(Class_ $class): bool
    {
        foreach ($class->attrGroups as $attributeGroup) {
            foreach ($attributeGroup->attrs as $attr) {
                if (in_array(needle: $this->attribute, haystack: $attr->name->parts)) {
                    return true;
                }
            }
        }
        return false;
    }
}
