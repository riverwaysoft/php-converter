<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator\UnknownTypeResolver;

use Riverwaysoft\PhpConverter\Dto\DtoList;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;

class DateTimeTypeResolver implements UnknownTypeResolverInterface
{
    public function supports(PhpUnknownType $type, DtoType|null $dto, DtoList $dtoList): bool
    {
        return $type->getName() === 'DateTime' || $type->getName() === 'DateTimeImmutable';
    }

    public function resolve(PhpUnknownType $type, DtoType|null $dto, DtoList $dtoList): string|PhpTypeInterface
    {
        return PhpBaseType::string();
    }
}
