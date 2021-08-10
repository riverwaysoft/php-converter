<?php

declare(strict_types=1);

namespace App\Dto;

use JetBrains\PhpStorm\Immutable;

#[Immutable]
class SingleType
{
    public function __construct(public string $name)
    {
    }
}
