<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator\UnknownTypeResolver;

use Riverwaysoft\PhpConverter\Dto\DtoList;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;

interface UnknownTypeResolverInterface
{
    public function supports(PhpUnknownType $type, DtoType|null $dto, DtoList $dtoList): bool;
    public function resolve(PhpUnknownType $type, DtoType|null $dto, DtoList $dtoList): string|PhpTypeInterface;
}
