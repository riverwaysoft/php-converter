<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Language\UnknownTypeResolver;

use Riverwaysoft\DtoConverter\Dto\DtoList;
use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnknownType;

interface UnknownTypeResolverInterface
{
    public function supports(PhpUnknownType $type, DtoType $dto, DtoList $dtoList): bool;
    public function resolve(PhpUnknownType $type, DtoType $dto, DtoList $dtoList): string|PhpTypeInterface;
}
