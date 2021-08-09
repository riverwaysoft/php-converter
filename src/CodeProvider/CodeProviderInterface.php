<?php

declare(strict_types=1);

namespace App\CodeProvider;

interface CodeProviderInterface
{
    /** @return string[]|iterable */
    public function getListings(): iterable;
}
