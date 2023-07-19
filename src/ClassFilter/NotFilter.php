<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\ClassFilter;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;

class NotFilter implements ClassFilterInterface
{
    public function __construct(private ClassFilterInterface $filter)
    {
    }

    public function isMatch(Class_|Enum_ $class): bool
    {
        return !$this->filter->isMatch($class);
    }
}
