<?php

declare(strict_types=1);

namespace App\Dto;

class UnionType implements \JsonSerializable
{
    public function __construct(
        /** @var SingleType[] $types */
        private array $types,
    ) {
    }

    public static function nullable(SingleType $singleType): self
    {
        return new self([$singleType, SingleType::null()]);
    }

    public function isNullable(): bool
    {
        foreach ($this->getTypes() as $type) {
            if ($type->isNull()) {
                return true;
            }
        }

        return false;
    }

    /** @return SingleType[] */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function jsonSerialize()
    {
        return [
            'types' => $this->types,
        ];
    }
}
