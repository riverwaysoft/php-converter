<?php

declare(strict_types=1);

namespace App\Language;

use App\Dto\DtoList;
use App\Dto\DtoProperty;
use App\Dto\DtoType;
use App\Dto\SingleType;
use App\Dto\UnionType;

class TypeScriptGenerator implements LanguageGeneratorInterface
{
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
        return sprintf("export type %s = {%s\n};", $dto->name, $this->convertToTypeScriptProperties($dto->properties, $dtoList));
    }

    /** @param DtoProperty[] $properties */
    private function convertToTypeScriptProperties(array $properties, DtoList $dtoList): string
    {
        $string = '';

        foreach ($properties as $property) {
            $string .= sprintf("\n  %s: %s;", $property->name, $this->getTypeScriptTypeFromPhp($property->type, $dtoList));
        }

        return $string;
    }

    private function getTypeScriptTypeFromPhp(SingleType|UnionType $type, DtoList $dtoList): string
    {
        if ($type instanceof UnionType) {
            $arr = array_map(fn (SingleType $type) => $this->getTypeScriptTypeFromPhp($type, $dtoList), $type->types);
            return implode(separator: ' | ', array: $arr);
        }

        // https://www.php.net/manual/en/language.types.declarations.php
        return match ($type->name) {
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
        if ($dtoList->hasDtoWithType($type->name)) {
            return $type->name;
        }

        throw new \InvalidArgumentException('PHP Type ' . $type->name . ' is not supported');
    }
}
