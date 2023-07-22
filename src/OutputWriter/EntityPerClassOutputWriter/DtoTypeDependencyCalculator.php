<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputWriter\EntityPerClassOutputWriter;

use Riverwaysoft\PhpConverter\Dto\DtoClassProperty;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;

class DtoTypeDependencyCalculator
{
    /** @return PhpUnknownType[] */
    public function getDependencies(DtoType $dtoType): array
    {
        // Enums can't have other types as values
        if ($dtoType->getExpressionType()->isAnyEnum()) {
            return [];
        }

        /** @var PhpUnknownType[] $dependencies */
        $dependencies = [];
        foreach ($dtoType->getProperties() as $property) {
            /** @var DtoClassProperty $property */
            $type = $property->getType();
            if ($type instanceof PhpUnknownType && $type->getName() !== $dtoType->getName()) {
                $dependencies[] = $type;
            }
            if ($type instanceof PhpUnionType) {
                foreach ($type->getTypes() as $innerType) {
                    if ($innerType instanceof PhpUnknownType && $dtoType->getName() !== $innerType->getName()) {
                        $dependencies[] = $innerType;
                    }
                }
            }
        }

        return $dependencies;
    }
}
