<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Dto;

use JsonSerializable;

class DtoEnumProperty implements JsonSerializable
{
    public function __construct(
        private string $name,
        private string|int|null $value,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): int|string|null
    {
        return $this->value;
    }

    public function isNumeric(): bool
    {
        return is_numeric($this->value);
    }

    public function isNull(): bool
    {
        return $this->value === null;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' =>$this->name,
            'value'=>$this->value,
        ];
    }
}
