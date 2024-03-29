<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Dto;

use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeInterface;
use JsonSerializable;

class DtoClassProperty implements JsonSerializable
{
    public function __construct(
        private PhpTypeInterface $type,
        private string $name,
    ) {
    }

    public function getType(): PhpTypeInterface
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
            'name' => $this->name,
        ];
    }
}
