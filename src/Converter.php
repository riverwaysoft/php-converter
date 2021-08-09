<?php

declare(strict_types=1);

namespace App;

use App\CodeProvider\CodeProviderInterface;
use App\Dto\DtoList;

class Converter
{
    public function __construct(
        private Normalizer $normalizer,
        private CodeProviderInterface $codeProvider,
    ) {
    }

    public function convert(): DtoList
    {
        $dtoList = new DtoList();
        foreach ($this->codeProvider->getListings() as $listing) {
            $dtoList->merge($this->normalizer->normalize($listing));
        }

        return $dtoList;
    }
}
