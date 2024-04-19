<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator\Go;

use Exception;
use Riverwaysoft\PhpConverter\Dto\DtoEnumProperty;
use Riverwaysoft\PhpConverter\Dto\DtoType;

class GoEnumResolver
{
    /** @var string[] */
    private array $usedConstantsStore = [];

    /** @throws Exception */
    public function resolve(DtoType $dto): string
    {
        $firstEnumProperty = $dto->getProperties()[0] ?? null;
        if ($firstEnumProperty === null) {
            throw new Exception('Enum must have at least one property');
        }

        $firstEnumPropValue = $firstEnumProperty->getValue();
        if (is_int($firstEnumPropValue)) {
            $type = 'int';
        } elseif (is_string($firstEnumPropValue)) {
            $type = 'string';
        } else {
            throw new Exception('Enum property must be int or string, got ' . gettype($firstEnumPropValue));
        }
        $props = self::convertEnumToGoEnumProperties($dto->getProperties(), $dto->getName());

        return sprintf("type %s %s\n\nconst (%s\n)", $dto->getName(), $type, $props);
    }

    /**
     * @param DtoEnumProperty[] $properties
     * @throws Exception
     */
    private function convertEnumToGoEnumProperties(array $properties, string $enum): string
    {
        $string = '';

        $maxEnumPropNameLength = 0;
        foreach ($properties as $prop) {
            $maxEnumPropNameLength = max($maxEnumPropNameLength, strlen($prop->getName()));
        }

        foreach ($properties as $prop) {
            $const = $prop->getName();
            if (in_array($const, $this->usedConstantsStore)) {
                $const .= $enum;
            }
            if (array_key_exists($const, $this->usedConstantsStore)) {
                throw new Exception('Please rename constant ' . $const);
            }
            $this->usedConstantsStore[] = $const;

            $spaces = str_repeat(' ', $maxEnumPropNameLength - strlen($prop->getName()) + 1);

            $value = $prop->isNumeric() ? $prop->getValue() : sprintf('"%s"', $prop->getValue());
            $string .= sprintf("\n\t%s$spaces%s = %s", $const, $enum, $value);
        }

        return $string;
    }
}
