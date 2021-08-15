<?php

declare(strict_types=1);

namespace App\Language;

use App\Dto\SingleType;

interface UnknownTypeResolverInterface
{
    public function supports(SingleType $type): bool;
    public function resolve(SingleType $type): mixed;
}
