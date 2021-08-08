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
            $string .= $this->convertToTypeScriptType($dto) . "\n\n";
        }

        return $string;
    }

    private function convertToTypeScriptType(DtoType $dto): string
    {
        return sprintf("export type %s = {%s\n};", $dto->title, $this->convertToTypeScriptProperties($dto->properties));
    }

    /** @param DtoProperty[] $properties */
    private function convertToTypeScriptProperties(array $properties): string
    {
        $string = '';

        foreach ($properties as $property) {
            $string .= sprintf("\n  %s: %s;", $property->name, $this->getTypeScriptTypeFromPhp($property->type));
        }

        return $string;
    }

    private function getTypeScriptTypeFromPhp(SingleType|UnionType $type): string
    {
        if ($type instanceof UnionType) {
            $arr = array_map(fn (SingleType $type) => $this->getTypeScriptTypeFromPhp($type), $type->types);
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
            default => throw new \InvalidArgumentException('PHP Type ' . $type->name . ' is not supported'),
        };
    }
}
