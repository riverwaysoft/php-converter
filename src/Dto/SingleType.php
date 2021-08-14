<?php

declare(strict_types=1);

namespace App\Dto;

class SingleType
{
    public function __construct(public string $name, public bool $isArray = false)
    {
    }

    public static function array(string $name)
    {
        return new self(name: $name, isArray: true);
    }
}
