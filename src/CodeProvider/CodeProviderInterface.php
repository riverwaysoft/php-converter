<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\CodeProvider;

interface CodeProviderInterface
{
    /** @return string[] */
    public function getListings(): iterable;
}
