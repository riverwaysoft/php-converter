<?php

declare(strict_types=1);

namespace App\Tests;

use Riverwaysoft\DtoConverter\Dto\DtoClassProperty;
use Riverwaysoft\DtoConverter\Dto\DtoList;
use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\Dto\ExpressionType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpListType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnknownType;
use Riverwaysoft\DtoConverter\Language\LanguageGeneratorInterface;
use Riverwaysoft\DtoConverter\Language\UnknownTypeResolverInterface;
use Riverwaysoft\DtoConverter\Language\UnsupportedTypeException;
use Riverwaysoft\DtoConverter\OutputWriter\OutputWriterInterface;
use Webmozart\Assert\Assert;

class GoGeneratorSimple implements LanguageGeneratorInterface
{
    public function __construct(
        private OutputWriterInterface $outputWriter,
        /** @var UnknownTypeResolverInterface[] $unknownTypeResolvers */
        private array $unknownTypeResolvers = [],
    ) {
    }

    public function generate(DtoList $dtoList): array
    {
        $this->outputWriter->reset();

        foreach ($dtoList->getList() as $dto) {
            $this->outputWriter->writeType($this->convertToGoType($dto, $dtoList), $dto);
        }

        return $this->outputWriter->getTypes();
    }

    private function convertToGoType(DtoType $dto, DtoList $dtoList): string
    {
        Assert::false($dto->getExpressionType()->isEnum(), 'Go language doesn\'t support enums');

        return sprintf("type %s struct {%s\n};", $dto->getName(), $this->convertToGoProperties($dto, $dtoList));
    }

    private function convertToGoProperties(DtoType $dto, DtoList $dtoList): string
    {
        $string = '';

        /** @param DtoClassProperty[] $properties */
        $properties = $dto->getProperties();
        foreach ($properties as $property) {
            $string .= sprintf("\n  %s %s", $property->getName(), $this->getGoTypeFromPhp($property->getType(), $dto, $dtoList));
        }

        return $string;
    }

    private function getGoTypeFromPhp(PhpTypeInterface $type, DtoType $dto, DtoList $dtoList): string
    {
        if ($type instanceof PhpUnionType) {
            Assert::greaterThan($type->getTypes(), 2, "Go does not support union types");
            Assert::true($type->isNullable(), "Go only supports nullable union types");
            $notNullType = $type->getFirstNotNullType();
            return sprintf('*%s', $this->getGoTypeFromPhp($notNullType, $dto, $dtoList));
        }

        if ($type instanceof PhpListType) {
            return sprintf('[]%s', $this->getGoTypeFromPhp($type->getType(), $dto, $dtoList));
        }

        if ($type instanceof PhpBaseType) {
            /** @var PhpBaseType $type */
            return match (true) {
                $type->equalsTo(PhpBaseType::int()), $type->equalsTo(PhpBaseType::float()) => 'int',
                $type->equalsTo(PhpBaseType::string()) => 'string',
                $type->equalsTo(PhpBaseType::bool()) => 'bool',
                $type->equalsTo(PhpBaseType::mixed()), $type->equalsTo(PhpBaseType::object()) => 'any',
                default => throw new \Exception(sprintf("Unknown base PHP type: %s", $type->jsonSerialize()))
            };
        }


        /** @var PhpUnknownType $type */
        return $this->handleUnknownType($type, $dto, $dtoList);
    }

    private function handleUnknownType(PhpUnknownType $type, DtoType $dto, DtoList $dtoList): string
    {
        /** @var UnknownTypeResolverInterface $unknownTypeResolver */
        foreach ($this->unknownTypeResolvers as $unknownTypeResolver) {
            if ($unknownTypeResolver->supports($type, $dto, $dtoList)) {
                return $unknownTypeResolver->resolve($type, $dto, $dtoList);
            }
        }

        throw UnsupportedTypeException::forType($type, $dto->getName());
    }
}
