<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator\Dart;

use Riverwaysoft\PhpConverter\Dto\DtoList;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpListType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;
use Riverwaysoft\PhpConverter\OutputGenerator\UnknownTypeResolver\UnknownTypeResolverInterface;
use Riverwaysoft\PhpConverter\OutputGenerator\UnsupportedTypeException;
use Webmozart\Assert\Assert;
use Exception;
use function sprintf;

class DartTypeResolver
{
    /** @param UnknownTypeResolverInterface[] $unknownTypeResolvers */
    public function __construct(
        private array $unknownTypeResolvers = [],
    ) {
    }

    public function getDartTypeFromPhp(PhpTypeInterface $type, DtoType $dto, DtoList $dtoList): string
    {
        if ($type instanceof PhpUnionType) {
            Assert::greaterThan($type->getTypes(), 2, "Dart does not support union types");
            if (!$type->isNullable()) {
                return $this->getDartTypeFromPhp(PhpBaseType::mixed(), $dto, $dtoList);
            }
            $notNullType = $type->getFirstNotNullType();
            return sprintf('%s?', $this->getDartTypeFromPhp($notNullType, $dto, $dtoList));
        }

        if ($type instanceof PhpListType) {
            return sprintf('List<%s>', $this->getDartTypeFromPhp($type->getType(), $dto, $dtoList));
        }

        if ($type instanceof PhpBaseType) {
            /** @var PhpBaseType $type */
            return match (true) {
                $type->equalsTo(PhpBaseType::int()) => 'int',
                $type->equalsTo(PhpBaseType::float()) => 'num',
                $type->equalsTo(PhpBaseType::string()) => 'String',
                $type->equalsTo(PhpBaseType::bool()) => 'bool',
                $type->equalsTo(PhpBaseType::mixed()), $type->equalsTo(PhpBaseType::iterable()), $type->equalsTo(PhpBaseType::array()) => 'dynamic',
                $type->equalsTo(PhpBaseType::null()) => 'null',
                $type->equalsTo(PhpBaseType::self()) => $dto->getName(),
                default => throw new Exception(sprintf("Unknown base PHP type: %s", $type->jsonSerialize()))
            };
        }

        /** @var PhpUnknownType $type */
        $result = $this->handleUnknownType($type, $dto, $dtoList);

        if ($result instanceof PhpTypeInterface) {
            return $this->getDartTypeFromPhp($result, $dto, $dtoList);
        }

        return $result;
    }

    private function handleUnknownType(PhpUnknownType $type, DtoType|null $dto, DtoList $dtoList): string|PhpTypeInterface
    {
        if ($type instanceof PhpUnknownType && $dto?->isGeneric() && $dto->hasGeneric($type)) {
            return $type->getName();
        }

        if ($type instanceof PhpUnknownType && $type->hasGenerics() && $dtoList->hasDtoWithType($type->getName())) {
            $result = $type->getName();

            $generics = array_map(fn (PhpTypeInterface $innerGeneric) => $this->getDartTypeFromPhp(
                $innerGeneric,
                $dto,
                $dtoList,
            ), $type->getGenerics());

            $result .= sprintf("<%s>", join(', ', $generics));
            return $result;
        }

        foreach ($this->unknownTypeResolvers as $unknownTypeResolver) {
            if ($unknownTypeResolver->supports($type, $dto, $dtoList)) {
                return $unknownTypeResolver->resolve($type, $dto, $dtoList);
            }
        }

        throw UnsupportedTypeException::forType($type, $dto?->getName() ?? '');
    }
}
