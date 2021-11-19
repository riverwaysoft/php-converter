<?php

namespace Riverwaysoft\DtoConverter\CodeProvider;

interface CodeProviderInterface
{
    /** @return string[] */
    public function getListings(string $directory): iterable;
}