<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator\UnknownTypeResolver;

use Riverwaysoft\PhpConverter\Dto\DtoList;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;

class ClassNameTypeResolver implements UnknownTypeResolverInterface
{
    public function supports(PhpUnknownType $type, DtoType|null $dto, DtoList $dtoList): bool
    {
        return $dtoList->hasDtoWithType($type->getName());
    }

    public function resolve(PhpUnknownType $type, DtoType|null $dto, DtoList $dtoList): string|PhpTypeInterface
    {
        return $type->getName();
    }
}
