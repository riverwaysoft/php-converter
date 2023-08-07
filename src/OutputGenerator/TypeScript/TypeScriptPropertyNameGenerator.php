<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator\TypeScript;

use Riverwaysoft\PhpConverter\Dto\DtoClassProperty;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\OutputGenerator\PropertyNameGeneratorInterface;

class TypeScriptPropertyNameGenerator implements PropertyNameGeneratorInterface
{
    public function supports(DtoType $dto): bool
    {
        return true;
    }

    public function generate(DtoClassProperty $property): string
    {
        return $property->getName();
    }
}