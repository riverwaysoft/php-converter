<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Bridge\ApiPlatform;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Jawira\CaseConverter\Convert;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnknownType;

class ApiPlatformIriGenerator
{
    private Inflector $inflector;

    public function __construct()
    {
        $this->inflector = InflectorFactory::create()->build();
    }

    public function generate(string $typeName): string
    {
        $pluralized = $this->inflector->pluralize($typeName);
        return (new Convert($pluralized))->toSnake();
    }
}
