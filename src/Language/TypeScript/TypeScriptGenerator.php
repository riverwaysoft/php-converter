<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Language\TypeScript;

use Riverwaysoft\DtoConverter\Dto\DtoEnumProperty;
use Riverwaysoft\DtoConverter\Dto\DtoList;
use Riverwaysoft\DtoConverter\Dto\DtoClassProperty;
use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\Dto\ExpressionType;
use Riverwaysoft\DtoConverter\Dto\ListType;
use Riverwaysoft\DtoConverter\Dto\SingleType;
use Riverwaysoft\DtoConverter\Dto\UnionType;
use Riverwaysoft\DtoConverter\Language\LanguageGeneratorInterface;
use Riverwaysoft\DtoConverter\Language\UnknownTypeResolverInterface;
use Riverwaysoft\DtoConverter\Language\UnsupportedTypeException;
use Riverwaysoft\DtoConverter\OutputWriter\OutputFile;
use Riverwaysoft\DtoConverter\OutputWriter\OutputWriterInterface;
use Webmozart\Assert\Assert;

class TypeScriptGenerator implements LanguageGeneratorInterface
{
    public function __construct(
        private OutputWriterInterface $outputWriter,
        /** @var UnknownTypeResolverInterface[] $unknownTypeResolvers */
        private array $unknownTypeResolvers = [],
    ) {
    }

    /** @return OutputFile[] */
    public function generate(DtoList $dtoList): array
    {
        $this->outputWriter->reset();

        foreach ($dtoList->getList() as $dto) {
            $this->outputWriter->writeType($this->convertToTypeScriptType($dto, $dtoList), $dto);
        }

        return $this->outputWriter->getTypes();
    }

    private function convertToTypeScriptType(DtoType $dto, DtoList $dtoList): string
    {
        if ($dto->getExpressionType()->equals(ExpressionType::class())) {
            return sprintf("export type %s = {%s\n};", $dto->getName(), $this->convertToTypeScriptProperties($dto, $dtoList));
        }
        if ($dto->getExpressionType()->equals(ExpressionType::enum())) {
            if ($this->shouldEnumBeConverterToUnion($dto)) {
                return sprintf("export type %s = %s;", $dto->getName(), $this->convertEnumToTypeScriptUnionProperties($dto->getProperties()));
            }
            return sprintf("export enum %s {%s\n}", $dto->getName(), $this->convertEnumToTypeScriptEnumProperties($dto->getProperties()));
        }
        throw new \Exception('Unknown expression type ' . $dto->getExpressionType()->jsonSerialize());
    }

    // TS only supports string or number backed enums. If one of the enum values is null TS gives compilation error
    // In this case it's better to convert enum to union type
    private function shouldEnumBeConverterToUnion(DtoType $dto): bool
    {
        Assert::true($dto->getExpressionType()->equals(ExpressionType::enum()));

        foreach ($dto->getProperties() as $property) {
            if ($property->isNull()) {
                return true;
            }
        }

        return false;
    }

    private function convertToTypeScriptProperties(DtoType $dto, DtoList $dtoList): string
    {
        $string = '';

        /** @param DtoClassProperty[] $properties */
        $properties = $dto->getProperties();
        foreach ($properties as $property) {
            $string .= sprintf("\n  %s: %s;", $property->getName(), $this->getTypeScriptTypeFromPhp($property->getType(), $dto, $dtoList));
        }

        return $string;
    }

    /** @param DtoEnumProperty[] $properties */
    private function convertEnumToTypeScriptEnumProperties(array $properties): string
    {
        $string = '';

        foreach ($properties as $property) {
            $propertyValue = $property->isNumeric()
                ? $property->getValue()
                : sprintf("'%s'", $property->getValue());

            $string .= sprintf("\n  %s = %s,", $property->getName(), $propertyValue);
        }

        return $string;
    }

    /** @param DtoEnumProperty[] $properties */
    private function convertEnumToTypeScriptUnionProperties(array $properties): string
    {
        $propertyValues = array_map(function (DtoEnumProperty $property) {
            if ($property->isNumeric()) {
                return $property->getValue();
            }
            if ($property->isNull()) {
                return 'null';
            }
            return sprintf("'%s'", $property->getValue());
        }, $properties);

        return implode(' | ', $propertyValues);
    }

    private function getTypeScriptTypeFromPhp(SingleType|UnionType|ListType $type, DtoType $dto, DtoList $dtoList): string
    {
        if ($type instanceof UnionType) {
            $arr = array_map(fn (SingleType|ListType|UnionType $type) => $this->getTypeScriptTypeFromPhp($type, $dto, $dtoList), $type->getTypes());
            return implode(separator: ' | ', array: $arr);
        }

        if ($type instanceof ListType) {
            return sprintf('%s[]', $this->getTypeScriptTypeFromPhp($type->getType(), $dto, $dtoList));
        }

        // https://www.php.net/manual/en/language.types.declarations.php
        return match ($type->getName()) {
            'int', 'float' => 'number',
            'string' => 'string',
            'bool' => 'boolean',
            'mixed', 'object' => 'any',
            'array', 'iterable', => 'any[]',
            'null' => 'null',
            'self' => $dto->getName(),
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
