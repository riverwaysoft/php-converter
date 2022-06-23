<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Dto;

use Webmozart\Assert\Assert;

class UnionType implements \JsonSerializable
{
    /** @var SingleType[] $types */
    private array $types;

    public function __construct(
        /** @var SingleType[] $types */
        array $types,
    )
    {
        // Exclude duplicates in a type, e.g ?string|null -> string|null
        $this->types = array_values(array_unique($types, SORT_REGULAR));
    }

    public static function nullable(SingleType|ListType|UnionType $singleType): self
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

    public function getNotNullType(): SingleType|ListType|UnionType
    {
        Assert::true($this->isNullable());

        /** @var SingleType|ListType|UnionType|null $notNullType */
        $notNullType = null;
        foreach ($this->getTypes() as $type) {
            if (!$type->isNull()) {
                $notNullType = $type;
            }
        }

        Assert::notNull($notNullType);

        return $notNullType;
    }

    /** @return SingleType[] */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'types' => $this->types,
        ];
    }
}
