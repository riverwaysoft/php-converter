<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Filter;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;

interface FilterInterface
{
    public function isMatch(ClassMethod|Class_|Enum_ $value): bool;
}
