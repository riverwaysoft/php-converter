<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Language;

use Riverwaysoft\DtoConverter\Dto\DtoList;

interface LanguageGeneratorInterface
{
    public function generate(DtoList $dtoList): string;
}
