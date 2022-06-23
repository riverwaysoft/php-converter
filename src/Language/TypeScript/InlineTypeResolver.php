<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Language\TypeScript;

use Riverwaysoft\DtoConverter\Dto\DtoList;
use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\Dto\SingleType;
use Riverwaysoft\DtoConverter\Language\UnknownTypeResolverInterface;

class InlineTypeResolver implements UnknownTypeResolverInterface
{
    public function __construct(
        /** @var array<string, string> */
        private array $map,
    )
    {
    }

    public function supports(SingleType $type, DtoType $dto, DtoList $dtoList): bool
    {
        return !empty($this->map[$type->getName()]);
    }

    public function resolve(SingleType $type, DtoType $dto, DtoList $dtoList): string
    {
        $result = $this->map[$type->getName()];
        if (!$result) {
            throw new \Exception(sprintf('Unsupported type %s', $type->getName()));
        }
        return $result;
    }
}
