<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator\Go;

use Exception;
use Riverwaysoft\PhpConverter\Dto\DtoClassProperty;
use Riverwaysoft\PhpConverter\Dto\DtoList;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;
use Riverwaysoft\PhpConverter\OutputGenerator\UnknownTypeResolver\ClassNameTypeResolver;

class GoRecursionValidator
{
    /**
     * @param string[] $visited
     * @throws Exception
     */
    public static function isRecursionFound(DtoType $dto, DtoList $dtoList, array $visited = []): bool
    {
        $classNameResolver = new ClassNameTypeResolver();

        foreach ($dto->getProperties() as $value) {
            if (!$value instanceof DtoClassProperty) {
                continue;
            }
            $type = $value->getType();
            if (!$type instanceof PhpUnknownType) {
                continue;
            }
            if (!$classNameResolver->supports($type, $dto, $dtoList)) {
                continue;
            }

            $dtoName = $dto->getName();
            if (in_array($dtoName, $visited)) {
                return true;
            }

            $visited[] = $dtoName;

            /** @var string $resolvedType */
            $resolvedType = $classNameResolver->resolve($type, $dto, $dtoList);

            $resolvedDto = $dtoList->getDtoByType($resolvedType);
            if (!$resolvedDto) {
                throw new Exception(sprintf('Unknown type %s', $resolvedType));
            }

            return self::isRecursionFound($resolvedDto, $dtoList, $visited);
        }

        return false;
    }
}
