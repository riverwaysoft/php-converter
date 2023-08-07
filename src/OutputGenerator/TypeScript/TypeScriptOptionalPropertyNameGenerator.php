<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator\TypeScript;

use Riverwaysoft\PhpConverter\Dto\DtoClassProperty;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpOptionalType;
use Riverwaysoft\PhpConverter\OutputGenerator\PropertyNameGeneratorInterface;

class TypeScriptOptionalPropertyNameGenerator implements PropertyNameGeneratorInterface
{
    public function supports(DtoType $dto): bool
    {
        return str_ends_with($dto->getName(), 'Query');
    }

    public function generate(DtoClassProperty $property): string
    {
        return sprintf(
            "%s%s",
            $property->getName(),
            $property->getType() instanceof PhpOptionalType ? '?' : '',
        );
    }
}
