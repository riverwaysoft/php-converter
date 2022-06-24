<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\OutputWriter\EntityPerClassOutputWriter;

use Riverwaysoft\DtoConverter\Dto\DtoClassProperty;
use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\Dto\ExpressionType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnknownType;

class DtoTypeDependencyCalculator
{
    /** @return PhpUnknownType[] */
    public function getDependencies(DtoType $dtoType): array
    {
        // Enums can't have other types as values
        if ($dtoType->getExpressionType()->equals(ExpressionType::enum())) {
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
