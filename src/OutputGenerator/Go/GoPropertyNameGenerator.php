<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator\Go;

use Riverwaysoft\PhpConverter\Dto\DtoClassProperty;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\OutputGenerator\PropertyNameGeneratorInterface;

class GoPropertyNameGenerator implements PropertyNameGeneratorInterface
{
    public function supports(DtoType $dto): bool
    {
        return true;
    }

    public function generate(DtoClassProperty $property): string
    {
        return ucfirst($property->getName());
    }
}
