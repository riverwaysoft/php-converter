<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Language;

use Riverwaysoft\DtoConverter\Dto\SingleType;

class UnsupportedTypeException extends \Exception
{
    public static function forType(SingleType $type, string $class): self
    {
        return new self(sprintf("PHP Type %s is not supported. PHP class: %s", $type->getName(), $class));
    }
}
