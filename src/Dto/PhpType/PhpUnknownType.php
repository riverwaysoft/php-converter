<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Dto\PhpType;

class PhpUnknownType implements PhpTypeInterface
{
    public function __construct(private string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name
        ];
    }
}
