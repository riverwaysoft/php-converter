<?php

declare(strict_types=1);

namespace App;

class DtoProperty
{
    public function __construct(
        public string $type,
        public string $name,
    )
    {

    }
}