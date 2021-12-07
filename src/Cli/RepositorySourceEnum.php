<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Cli;

class RepositorySourceEnum
{
    private function __construct(private string $type)
    {
    }

    public static function directory(): self
    {
        return new self('directory');
    }

    public static function remote(): self
    {
        return new self('remote');
    }

    public function equals(self $other): bool
    {
        return $this->type === $other->type;
    }
}
