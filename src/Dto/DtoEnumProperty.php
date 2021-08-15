<?php

declare(strict_types=1);

namespace App\Dto;

class DtoEnumProperty implements \JsonSerializable
{
    public function __construct(
        private string $name,
        private string|int $value,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): int|string
    {
        return $this->value;
    }

    public function isNumeric(): bool
    {
        return is_numeric($this->value);
    }

    public function jsonSerialize()
    {
        return [
            'name' =>$this->name,
            'value'=>$this->value,
        ];
    }
}
