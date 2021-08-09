<?php

declare(strict_types=1);

namespace App\AnnotationChecker;

use PhpParser\Node;

class PhpAttributesChecker implements AnnotationCheckerInterface
{
    public function hasDtoAttribute(Node\Stmt\Class_ $node): bool
    {
        foreach ($node->attrGroups as $attributeGroup) {
            foreach ($attributeGroup->attrs as $attr) {
                if (in_array(needle: 'Dto', haystack: $attr->name->parts)) {
                    return true;
                }
            }
        }
        return false;
    }
}
