<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Ast;

use PhpParser\NodeVisitorAbstract;

abstract class ConverterVisitor extends NodeVisitorAbstract
{
    abstract public function getResult(): ConverterResult;
}
