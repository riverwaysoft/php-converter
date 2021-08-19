<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\ClassFilter;

use PhpParser\Node\Stmt\Class_;

interface ClassFilterInterface
{
    public function isMatch(Class_ $class): bool;
}
