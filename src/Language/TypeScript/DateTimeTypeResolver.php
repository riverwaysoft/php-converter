<?php

declare(strict_types=1);

namespace App\Language\TypeScript;

use App\Dto\SingleType;
use App\Language\UnknownTypeResolverInterface;

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
