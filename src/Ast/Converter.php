<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Ast;

use Riverwaysoft\DtoConverter\Dto\DtoList;

class Converter
{
    public function __construct(
        private Normalizer $normalizer,
    ) {
    }

    /** @param string[]|iterable $listings */
    public function convert(iterable $listings): DtoList
    {
        $dtoList = new DtoList();

        foreach ($listings as $listing) {
            $dtoList->merge($this->normalizer->normalize($listing));
        }

        return $dtoList;
    }
}
