<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Dto;

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

    public function jsonSerialize(): mixed
    {
        return [
            'type' => $this->type,
            'name' => $this->name
        ];
    }
}
