<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Language\TypeScript;

use Riverwaysoft\DtoConverter\Dto\DtoEnumProperty;
use Riverwaysoft\DtoConverter\Dto\DtoList;
use Riverwaysoft\DtoConverter\Dto\DtoClassProperty;
use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\Dto\ExpressionType;
use Riverwaysoft\DtoConverter\Dto\SingleType;
use Riverwaysoft\DtoConverter\Dto\UnionType;
use Riverwaysoft\DtoConverter\Language\LanguageGeneratorInterface;
use Riverwaysoft\DtoConverter\Language\UnknownTypeResolverInterface;

class TypeScriptGenerator implements LanguageGeneratorInterface
{
    public function __construct(
        /** @var UnknownTypeResolverInterface[] */
        private array $unknownTypeResolvers = [],
    ) {
    }


    public function generate(DtoList $dtoList): string
    {
        $string = '';

        foreach ($dtoList->getList() as $dto) {
            $string .= $this->convertToTypeScriptType($dto, $dtoList) . "\n\n";
        }

        return $string;
    }

    private function convertToTypeScriptType(DtoType $dto, DtoList $dtoList): string
    {
        if ($dto->getExpressionType()->equals(ExpressionType::class())) {
            return sprintf("export type %s = {%s\n};", $dto->getName(), $this->convertToTypeScriptProperties($dto->getProperties(), $dtoList));
        }
        if ($dto->getExpressionType()->equals(ExpressionType::enum())) {
            return sprintf("export enum %s {%s\n}", $dto->getName(), $this->convertEnumToTypeScriptProperties($dto->getProperties()));
        }
        throw new \Exception('Unknown expression type '.$dto->getExpressionType()->jsonSerialize());
    }

    /** @param DtoClassProperty[] $properties */
    private function convertToTypeScriptProperties(array $properties, DtoList $dtoList): string
    {
        $string = '';

        foreach ($properties as $property) {
            $string .= sprintf("\n  %s: %s;", $property->getName(), $this->getTypeScriptTypeFromPhp($property->getType(), $dtoList));
        }

        return $string;
    }

    /** @param DtoEnumProperty[] $properties */
    private function convertEnumToTypeScriptProperties(array $properties): string
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

    private function getTypeScriptTypeFromPhp(SingleType|UnionType $type, DtoList $dtoList): string
    {
        if ($type instanceof UnionType) {
            $arr = array_map(fn (SingleType $type) => $this->getTypeScriptTypeFromPhp($type, $dtoList), $type->getTypes());
            return implode(separator: ' | ', array: $arr);
        }

        if ($type->isList()) {
            return sprintf('%s[]', $this->getTypeScriptTypeFromPhp(new SingleType($type->getName()), $dtoList));
        }

        // https://www.php.net/manual/en/language.types.declarations.php
        return match ($type->getName()) {
            'int', 'float' => 'number',
            'string' => 'string',
            'bool' => 'boolean',
            'mixed', 'object' => 'any',
            'array' => 'any[]',
            'null' => 'null',
            default => $this->handleUnknownType($type, $dtoList),
        };
    }

    private function handleUnknownType(SingleType $type, DtoList $dtoList): string
    {
        if ($dtoList->hasDtoWithType($type->getName())) {
            return $type->getName();
        }

        foreach ($this->unknownTypeResolvers as $unknownTypeResolver) {
            if ($unknownTypeResolver->supports($type)) {
                return $unknownTypeResolver->resolve($type, $dtoList);
            }
        }

        throw new \InvalidArgumentException(sprintf("PHP Type %s is not supported", $type->getName()));
    }
}
