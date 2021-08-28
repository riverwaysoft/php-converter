<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Language\TypeScript;

use Riverwaysoft\DtoConverter\Dto\DtoList;
use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\Dto\SingleType;
use Riverwaysoft\DtoConverter\Language\UnknownTypeResolverInterface;

class ClassNameTypeResolver implements UnknownTypeResolverInterface
{
    public function supports(SingleType $type, DtoType $dto, DtoList $dtoList): bool
    {
        return $dtoList->hasDtoWithType($type->getName());
    }

    public function resolve(SingleType $type, DtoType $dto, DtoList $dtoList): mixed
    {
        return $type->getName();
    }
}
