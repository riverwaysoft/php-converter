<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Ast;

use PhpParser\NodeVisitorAbstract;

abstract class ConverterVisitor extends NodeVisitorAbstract
{
    /** @phpstan-impure */
    abstract public function popResult(): ConverterResult;
}
