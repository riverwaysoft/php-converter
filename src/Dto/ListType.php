<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Dto;

class ListType implements \JsonSerializable
{
    public function __construct(
        private SingleType $type,
    )
    {
    }

    public function getType(): SingleType
    {
        return $this->type;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'array' => $this->type
        ];
    }
}