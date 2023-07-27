<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Bridge\Symfony;

use Riverwaysoft\PhpConverter\Dto\DtoList;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\PhpConverter\OutputGenerator\UnknownTypeResolver\UnknownTypeResolverInterface;

class JsonResponseTypeResolver implements UnknownTypeResolverInterface
{
    public function supports(PhpUnknownType $type, DtoType|null $dto, DtoList $dtoList): bool
    {
        return $type->getName() === 'JsonResponse';
    }

    public function resolve(PhpUnknownType $type, DtoType|null $dto, DtoList $dtoList): string|PhpTypeInterface
    {
        if (!$type->hasGenerics()) {
            throw new \Exception(sprintf('Should not be reached. Type %s is expected to be generic', $type->getName()));
        }

        return $type->getGenerics()[0];
    }
}