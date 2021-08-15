<?php

declare(strict_types=1);

namespace App\Dto;

class DtoClassProperty implements \JsonSerializable
{
    public function __construct(
        private SingleType|UnionType $type,
        private string $name,
    ) {
    }

    public function getType(): UnionType|SingleType
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function jsonSerialize()
    {
        return [
            'type' =>$this->type,
            'name' => $this->name
        ];
    }
}
