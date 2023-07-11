<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\ClassFilter;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;

class AndFilter implements ClassFilterInterface
{
    public function __construct(
        /** @var ClassFilterInterface[] $filters */
        public array $filters,
    ) {
    }

    public function isMatch(Class_|Enum_ $class): bool
    {
        foreach ($this->filters as $filter) {
            if (!$filter->isMatch($class)) {
                return false;
            }
        }
        return true;
    }
}
