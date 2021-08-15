<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Language\TypeScript;

use Riverwaysoft\DtoConverter\Dto\SingleType;
use Riverwaysoft\DtoConverter\Language\UnknownTypeResolverInterface;

class DateTimeTypeResolver implements UnknownTypeResolverInterface
{
    public function supports(SingleType $type): bool
    {
        return $type->getName() === 'DateTime' || $type->getName() === 'DateTimeImmutable';
    }

    public function resolve(SingleType $type): string
    {
        return 'string';
    }
}
