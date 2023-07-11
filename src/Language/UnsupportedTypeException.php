<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Language;

use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;

class UnsupportedTypeException extends \Exception
{
    public static function forType(PhpUnknownType $type, string $class): self
    {
        return new self(sprintf("PHP Type %s is not supported. PHP class: %s", $type->getName(), $class));
    }
}
