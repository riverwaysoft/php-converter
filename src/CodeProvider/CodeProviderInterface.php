<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\CodeProvider;

interface CodeProviderInterface
{
    /** @return string[]|iterable */
    public function getListings(): iterable;
}
