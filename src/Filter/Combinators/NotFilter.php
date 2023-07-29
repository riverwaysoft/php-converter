<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Filter\Combinators;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use Riverwaysoft\PhpConverter\Filter\FilterInterface;

class NotFilter implements FilterInterface
{
    public function __construct(
        private FilterInterface $filter
    ) {
    }

    public function isMatch(ClassMethod|Class_|Enum_ $value): bool
    {
        return !$this->filter->isMatch($value);
    }
}
