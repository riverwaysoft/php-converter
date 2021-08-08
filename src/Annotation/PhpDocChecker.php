<?php

namespace App\Annotation;

use PhpParser\Node;

class PhpDocChecker implements AnnotationCheckerInterface
{
    public function hasDtoAttribute(Node\Stmt\Class_ $node): bool
    {
        return $node->getDocComment() && str_contains($node->getDocComment()->getText(), "@Dto");
    }
}
