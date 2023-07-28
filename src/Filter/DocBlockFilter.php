<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Filter;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use function str_contains;

class DocBlockFilter implements FilterInterface
{
    public function __construct(
        private string $string,
    ) {
    }

    public function isMatch(ClassMethod|Class_|Enum_ $value): bool
    {
        return $value->getDocComment() && str_contains($value->getDocComment()->getText(), $this->string);
    }
}
