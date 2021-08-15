<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Language;

use Riverwaysoft\DtoConverter\Dto\DtoList;
use Riverwaysoft\DtoConverter\Dto\SingleType;

interface UnknownTypeResolverInterface
{
    public function supports(SingleType $type): bool;
    public function resolve(SingleType $type, DtoList $dtoList): mixed;
}
