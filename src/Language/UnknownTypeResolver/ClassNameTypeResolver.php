<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Language\UnknownTypeResolver;

use Riverwaysoft\DtoConverter\Dto\DtoList;
use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnknownType;

class ClassNameTypeResolver implements UnknownTypeResolverInterface
{
    public function supports(PhpUnknownType $type, DtoType $dto, DtoList $dtoList): bool
    {
        return $dtoList->hasDtoWithType($type->getName());
    }

    public function resolve(PhpUnknownType $type, DtoType $dto, DtoList $dtoList): string|PhpTypeInterface
    {
        return $type->getName();
    }
}
