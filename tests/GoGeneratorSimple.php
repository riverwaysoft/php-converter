<?php

declare(strict_types=1);

namespace App\Tests;

use Riverwaysoft\DtoConverter\Dto\DtoClassProperty;
use Riverwaysoft\DtoConverter\Dto\DtoList;
use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\Dto\ExpressionType;
use Riverwaysoft\DtoConverter\Dto\SingleType;
use Riverwaysoft\DtoConverter\Dto\UnionType;
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
        Assert::false($dto->getExpressionType()->equals(ExpressionType::enum()), 'Go language doesn\'t support enums');

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

    private function getGoTypeFromPhp(SingleType|UnionType $type, DtoType $dto, DtoList $dtoList): string
    {
        if ($type instanceof UnionType) {
            Assert::greaterThan($type->getTypes(), 2, "Go does not support union types");
            Assert::true($type->isNullable(), "Go only supports nullable union types");
            $notNullType = $type->getNotNullType();
            return sprintf('*%s', $this->getGoTypeFromPhp($notNullType, $dto, $dtoList));
        }

        if ($type->isList()) {
            return sprintf('[]%s', $this->getGoTypeFromPhp(new SingleType($type->getName()), $dto, $dtoList));
        }

        return match ($type->getName()) {
            'int', 'float' => 'int',
            'string' => 'string',
            'bool' => 'bool',
            'mixed', 'object' => 'interface{}',
            default => $this->handleUnknownType($type, $dto, $dtoList),
        };
    }

    private function handleUnknownType(SingleType $type, DtoType $dto, DtoList $dtoList): string
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
