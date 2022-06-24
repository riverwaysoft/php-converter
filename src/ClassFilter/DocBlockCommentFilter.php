<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\ClassFilter;

use PhpParser\Node\Stmt\Class_;

class DocBlockCommentFilter implements ClassFilterInterface
{
    public function __construct(private string $string)
    {
    }

    public function isMatch(Class_ $class): bool
    {
        return $class->getDocComment() && str_contains($class->getDocComment()->getText(), $this->string);
    }
}
