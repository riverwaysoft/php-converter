<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Ast;

use PhpParser\NodeVisitorAbstract;

abstract class ConverterVisitor extends NodeVisitorAbstract
{
    abstract public function popResult(): ConverterResult;
}
