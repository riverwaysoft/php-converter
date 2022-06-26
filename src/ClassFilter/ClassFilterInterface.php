<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\ClassFilter;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;

interface ClassFilterInterface
{
    public function isMatch(Class_|Enum_ $class): bool;
}
