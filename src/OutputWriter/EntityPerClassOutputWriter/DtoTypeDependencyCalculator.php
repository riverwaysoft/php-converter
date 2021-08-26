<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\OutputWriter\EntityPerClassOutputWriter;

use Riverwaysoft\DtoConverter\Dto\DtoClassProperty;
use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\Dto\ExpressionType;
use Riverwaysoft\DtoConverter\Dto\SingleType;
use Riverwaysoft\DtoConverter\Dto\UnionType;

class DtoTypeDependencyCalculator
{
    private array $phpBuiltInTypes = [
        'int',
        'float',
        'string',
        'bool',
        'mixed',
        'array',
        'null',
        'self',
        'static',
    ];

    /** @return SingleType[] */
    public function getDependencies(DtoType $dtoType): array
    {
        // Enums can't have other types as values
        if ($dtoType->getExpressionType()->equals(ExpressionType::enum())) {
            return [];
        }

        $dependencies = [];
        foreach ($dtoType->getProperties() as $property) {
            /** @var DtoClassProperty $property */
            if ($property->getType() instanceof SingleType) {
                if ($this->isDependency($property->getType()) && $property->getType()->getName() !== $dtoType->getName()) {
                    $dependencies[] = $property->getType();
                }
            }
            if ($property->getType() instanceof UnionType) {
                foreach ($property->getType()->getTypes() as $type) {
                    if ($this->isDependency($type) && $dtoType->getName() !== $type->getName()) {
                        $dependencies[] = $type;
                    }
                }
            }
        }

        return $dependencies;
    }

    private function isDependency(SingleType $singleType): bool
    {
        if (in_array(needle: $singleType->getName(), haystack: $this->phpBuiltInTypes)) {
            return false;
        }

        return true;
    }
}
