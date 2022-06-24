<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Dto\PhpType;

class PhpListType implements PhpTypeInterface
{
    public function __construct(
        private PhpTypeInterface $type,
    ) {
    }

    public function getType(): PhpTypeInterface
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
