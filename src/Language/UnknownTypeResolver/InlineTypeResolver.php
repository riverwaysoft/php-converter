<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Language\UnknownTypeResolver;

use Riverwaysoft\PhpConverter\Dto\DtoList;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;

class InlineTypeResolver implements UnknownTypeResolverInterface
{
    public function __construct(
        /** @var array<string, string> */
        private array $map,
    ) {
    }

    public function supports(PhpUnknownType $type, DtoType|null $dto, DtoList $dtoList): bool
    {
        return !empty($this->map[$type->getName()]);
    }

    public function resolve(PhpUnknownType $type, DtoType|null $dto, DtoList $dtoList): string|PhpTypeInterface
    {
        $result = $this->map[$type->getName()];
        if (!$result) {
            throw new \Exception(sprintf('Unsupported type %s', $type->getName()));
        }
        return $result;
    }
}
