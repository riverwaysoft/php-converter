<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Dto;

use Riverwaysoft\DtoConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpListType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnknownType;

class DtoClassProperty implements \JsonSerializable
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
            'name' => $this->name
        ];
    }
}
