<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Filter\Combinators;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use Riverwaysoft\PhpConverter\Filter\FilterInterface;

class AndFilter implements FilterInterface
{
    /** @param FilterInterface[] $filters */
    public function __construct(
        public array $filters,
    ) {
    }

    public function isMatch(ClassMethod|Class_|Enum_ $value): bool
    {
        foreach ($this->filters as $filter) {
            if (!$filter->isMatch($value)) {
                return false;
            }
        }
        return true;
    }
}
