<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Ast;

// version 0.8.2.40
class ClassName
{
    private string|null $short = null;

    public function __construct(
        private string $className,
    ) {
        if (str_contains($this->className, '\\')) {
            $this->short = substr($this->className, strrpos($this->className, '\\') + 1);
        }
    }

    public function isFQCN(): bool
    {
        return !!$this->short;
    }

    public function getShortName(): string
    {
        if ($this->isFQCN()) {
            return $this->short;
        }

        return $this->className;
    }
}
