<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Dto\PhpType;

use Webmozart\Assert\Assert;

class PhpUnionType implements PhpTypeInterface
{
    /** @var PhpTypeInterface[] $types */
    private array $types;

    /** @param PhpTypeInterface[] $types */
    public function __construct(
        array $types,
    ) {
        // Exclude duplicates in a type, e.g ?string|null -> string|null
        $this->types = array_values(array_unique($types, SORT_REGULAR));
    }

    public static function nullable(PhpTypeInterface $singleType): self
    {
        return new self([$singleType, PhpBaseType::null()]);
    }

    public function isNullable(): bool
    {
        foreach ($this->getTypes() as $type) {
            if ($type instanceof PhpBaseType && $type->equalsTo(PhpBaseType::null())) {
                return true;
            }
        }

        return false;
    }

    public function getFirstNotNullType(): PhpTypeInterface
    {
        Assert::true($this->isNullable());

        /** @var PhpTypeInterface|null $notNullType */
        $notNullType = null;
        foreach ($this->getTypes() as $type) {
            $isUnknown = $type instanceof PhpUnknownType;
            $isBaseNotNull = $type instanceof PhpBaseType && !$type->equalsTo(PhpBaseType::null());
            $isBaseOrArray = $isBaseNotNull || $type instanceof PhpListType;
            if ($isUnknown || $isBaseOrArray) {
                $notNullType = $type;
                break;
            }
        }

        Assert::notNull($notNullType);

        return $notNullType;
    }

    /** @return PhpTypeInterface[] */
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
