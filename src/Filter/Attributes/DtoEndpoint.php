<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Filter\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class DtoEndpoint
{
    public function __construct()
    {
    }
}
