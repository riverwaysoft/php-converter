<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator\Go;

use Exception;
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

class GoTypeResolver
{
    /** @param UnknownTypeResolverInterface[] $unknownTypeResolvers */
    public function __construct(
        private array $unknownTypeResolvers = []
    ) {
    }

    public function getTypeFromPhp(
        PhpTypeInterface $type,
        ?DtoType $dto,
        DtoList $dtoList
    ): string {
        if ($type instanceof PhpUnionType) {
            $fn = fn (PhpTypeInterface $type) => $this->getTypeFromPhp(
                $type,
                $dto,
                $dtoList
            );
            $types = array_map($fn, $type->getTypes());

            // Two args, one of them is null
            if (count($types) === 2 && in_array('null', $types)) {
                $types = array_diff($types, ['null']);

                return "*$types[0]";
            }

            return 'any';
        }

        if ($type instanceof PhpListType) {
            $listType = $this->getTypeFromPhp($type->getType(), $dto, $dtoList);

            if ($type->getType() instanceof PhpUnionType) {
                return sprintf('[](%s)', $listType);
            }

            return sprintf('[]%s', $listType);
        }

        if ($type instanceof PhpOptionalType) {
            return $this->getTypeFromPhp($type->getType(), $dto, $dtoList);
        }

        if ($type instanceof PhpBaseType) {
            return match (true) {
                $type->equalsTo(PhpBaseType::int()) => 'int',
                $type->equalsTo(PhpBaseType::float()) => 'float64',
                $type->equalsTo(PhpBaseType::string()) => 'string',
                $type->equalsTo(PhpBaseType::bool()) => 'bool',
                $type->equalsTo(PhpBaseType::mixed()),
                $type->equalsTo(PhpBaseType::object()) => 'any',
                $type->equalsTo(PhpBaseType::array()),
                $type->equalsTo(PhpBaseType::iterable()) => '[]any',
                $type->equalsTo(PhpBaseType::null()) => 'null',
                $type->equalsTo(PhpBaseType::self()) => $dto->getName(),
                default => throw new Exception(
                    sprintf("Unknown base PHP type: %s", $type->jsonSerialize())
                )
            };
        }

        if ($type instanceof PhpUnknownType && $dto?->hasGeneric($type)) {
            return 'any';
        }

        if (
            $type instanceof PhpUnknownType &&
            $type->hasGenerics() && (
                $dtoList->hasDtoWithType($type->getName()) ||
                !empty(
                    $type->getContext()[PhpUnknownType::GENERIC_IGNORE_NO_RESOLVER]
                )
            )
        ) {
            $result = $type->getName();

            $generics = array_map(
                fn (PhpTypeInterface $innerGeneric) => $this->getTypeFromPhp(
                    $innerGeneric,
                    $dto,
                    $dtoList,
                ),
                $type->getGenerics()
            );

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

    private function handleUnknownType(
        PhpUnknownType $type,
        ?DtoType $dto,
        DtoList $dtoList
    ): string|PhpTypeInterface {
        foreach ($this->unknownTypeResolvers as $unknownTypeResolver) {
            if ($unknownTypeResolver->supports($type, $dto, $dtoList)) {
                return $unknownTypeResolver->resolve($type, $dto, $dtoList);
            }
        }

        throw UnsupportedTypeException::forType($type, $dto?->getName() ?? '');
    }
}
