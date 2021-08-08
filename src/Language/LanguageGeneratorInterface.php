<?php

declare(strict_types=1);

namespace App\Language;

use App\Dto\DtoList;

interface LanguageGeneratorInterface
{
    public function generate(DtoList $dtoList): string;
}
