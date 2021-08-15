<?php

declare(strict_types=1);

namespace App\Dto;

class SingleType implements \JsonSerializable
{
    public function __construct(
        private string $name,
        private bool $isList = false,
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

    public function getName(): string
    {
        return $this->name;
    }

    public function isList(): bool
    {
        return $this->isList;
    }

    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'isList' => $this->isList,
        ];
    }
}
