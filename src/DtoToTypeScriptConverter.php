<?php

declare(strict_types=1);

namespace App;

class DtoToTypeScriptConverter
{
    public function convert(DtoList $dtoList): string
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

    private function getTypeScriptTypeFromPhp(string $type): string
    {
        // https://www.php.net/manual/en/language.types.declarations.php
        return match ($type) {
            'int', 'float' => 'number',
            'string' => 'string',
            'bool' => 'boolean',
            'mixed', 'object' => 'any',
            'array' => 'any[]',
            default => throw new \InvalidArgumentException('PHP Type ' . $type . ' is not supported'),
        };
    }
}