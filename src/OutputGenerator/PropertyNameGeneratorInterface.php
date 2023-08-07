<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator;

use Riverwaysoft\PhpConverter\Dto\DtoClassProperty;
use Riverwaysoft\PhpConverter\Dto\DtoType;

interface PropertyNameGeneratorInterface
{
    public function supports(DtoType $dto): bool;

    public function generate(DtoClassProperty $property): string;
}
