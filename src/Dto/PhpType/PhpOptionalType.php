<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Dto\PhpType;

class PhpOptionalType implements PhpTypeInterface
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
            'type' => $this->type,
            'isOptional' => true,
        ];
    }
}
