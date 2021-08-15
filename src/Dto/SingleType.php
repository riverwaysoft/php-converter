<?php

declare(strict_types=1);

namespace App\Dto;

class SingleType
{
    public function __construct(
        public string $name,
        public bool $isList = false,
    ) {
    }

    public static function list(string $name): self
    {
        return new self(name: $name, isList: true);
    }

    public static function null(): self
    {
        return new self(name: 'null');
    }

    public function isNull(): bool
    {
        return $this->name === 'null';
    }
}
