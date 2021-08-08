<?php

namespace App\Annotation;

use PhpParser\Node;

interface AnnotationCheckerInterface
{
    public function hasDtoAttribute(Node\Stmt\Class_ $node): bool;
}
