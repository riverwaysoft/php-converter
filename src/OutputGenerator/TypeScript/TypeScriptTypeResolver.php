<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator\TypeScript;

use Riverwaysoft\PhpConverter\Dto\DtoList;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpListType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpOptionalType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;
use Riverwaysoft\PhpConverter\OutputGenerator\UnknownTypeResolver\UnknownTypeResolverInterface;
use Riverwaysoft\PhpConverter\OutputGenerator\UnsupportedTypeException;
use Exception;

class TypeScriptTypeResolver
{
    /** @param UnknownTypeResolverInterface[] $unknownTypeResolvers */
    public function __construct(
        private array $unknownTypeResolvers = [],
    ) {
    }

    public function getTypeFromPhp(PhpTypeInterface $type, DtoType|null $dto, DtoList $dtoList): string
    {
        if ($type instanceof PhpUnionType) {
            $types = array_map(fn (PhpTypeInterface $type) => $this->getTypeFromPhp($type, $dto, $dtoList), $type->getTypes());
            return implode(separator: ' | ', array: $types);
        }

        if ($type instanceof PhpListType) {
            $listType = $this->getTypeFromPhp($type->getType(), $dto, $dtoList);
            return $type->getType() instanceof PhpUnionType
                ? sprintf('(%s)[]', $listType)
                : sprintf('%s[]', $listType);
        }

        if ($type instanceof PhpOptionalType) {
            return sprintf('%s | null = null', $this->getTypeFromPhp($type->getType(), $dto, $dtoList));
        }

        if ($type instanceof PhpBaseType) {
            /** @var PhpBaseType $type */
            return match (true) {
                $type->equalsTo(PhpBaseType::int()), $type->equalsTo(PhpBaseType::float()) => 'number',
                $type->equalsTo(PhpBaseType::string()) => 'string',
                $type->equalsTo(PhpBaseType::bool()) => 'boolean',
                $type->equalsTo(PhpBaseType::mixed()), $type->equalsTo(PhpBaseType::object()) => 'any',
                $type->equalsTo(PhpBaseType::array()), $type->equalsTo(PhpBaseType::iterable()) => 'any[]',
                $type->equalsTo(PhpBaseType::null()) => 'null',
                $type->equalsTo(PhpBaseType::self()) => $dto->getName(),
                default => throw new Exception(sprintf("Unknown base PHP type: %s", $type->jsonSerialize()))
            };
        }

        if ($type instanceof PhpUnknownType && $dto?->isGeneric() && $dto->hasGeneric($type)) {
            return $type->getName();
        }

        if (
            $type instanceof PhpUnknownType
            && $type->hasGenerics()
            && ($dtoList->hasDtoWithType($type->getName()) || !empty($type->getContext()[PhpUnknownType::GENERIC_IGNORE_NO_RESOLVER]))
        ) {
            $result = $type->getName();

            $generics = array_map(fn (PhpTypeInterface $innerGeneric) => $this->getTypeFromPhp(
                $innerGeneric,
                $dto,
                $dtoList,
            ), $type->getGenerics());

            $result .= sprintf("<%s>", join(', ', $generics));
            return $result;
        }

        /** @var PhpUnknownType $type */
        $result = $this->handleUnknownType($type, $dto, $dtoList);
        if ($result instanceof PhpTypeInterface) {
            return $this->getTypeFromPhp($result, $dto, $dtoList);
        }

        return $result;
    }

    private function handleUnknownType(PhpUnknownType $type, DtoType|null $dto, DtoList $dtoList): string|PhpTypeInterface
    {
        foreach ($this->unknownTypeResolvers as $unknownTypeResolver) {
            if ($unknownTypeResolver->supports($type, $dto, $dtoList)) {
                return $unknownTypeResolver->resolve($type, $dto, $dtoList);
            }
        }

        throw UnsupportedTypeException::forType($type, $dto?->getName() ?? '');
    }
}
