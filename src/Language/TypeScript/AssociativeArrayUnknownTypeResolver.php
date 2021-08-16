<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Language\TypeScript;

use Riverwaysoft\DtoConverter\Dto\DtoList;
use Riverwaysoft\DtoConverter\Dto\SingleType;
use Riverwaysoft\DtoConverter\Language\UnknownTypeResolverInterface;

class AssociativeArrayUnknownTypeResolver implements UnknownTypeResolverInterface
{
    public function __construct(private array $map)
    {

    }

    public function supports(SingleType $type): bool
    {
        return !empty($this->map[$type->getName()]);
    }

    public function resolve(SingleType $type, DtoList $dtoList): string
    {
        $result = $this->map[$type->getName()];
        if (!$result) {
            throw new \Exception(sprintf('Unsupported type %s', $type->getName()));
        }
        return $result;
    }
}
