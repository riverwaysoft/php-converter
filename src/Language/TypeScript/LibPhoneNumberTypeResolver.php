<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Language\TypeScript;

use Riverwaysoft\DtoConverter\Dto\DtoList;
use Riverwaysoft\DtoConverter\Dto\SingleType;
use Riverwaysoft\DtoConverter\Language\UnknownTypeResolverInterface;

class LibPhoneNumberTypeResolver implements UnknownTypeResolverInterface
{
    public function supports(SingleType $type): bool
    {
        return $type->getName() === 'PhoneNumber';
    }

    public function resolve(SingleType $type, DtoList $dtoList): string
    {
        return 'string';
    }
}
