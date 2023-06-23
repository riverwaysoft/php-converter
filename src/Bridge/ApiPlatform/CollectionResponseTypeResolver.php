<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Bridge\ApiPlatform;

use Riverwaysoft\DtoConverter\Dto\DtoList;
use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnknownType;
use Riverwaysoft\DtoConverter\Language\UnknownTypeResolver\ClassNameTypeResolver;

class CollectionResponseTypeResolver extends ClassNameTypeResolver
{
    public function supports(PhpUnknownType $type, DtoType|null $dto, DtoList $dtoList): bool
    {
        return parent::supports($type, $dto, $dtoList) && !empty($type->getContext()[ApiPlatformDtoResourceVisitor::COLLECTION_RESPONSE_CONTEXT_KEY]);
    }

    public function resolve(PhpUnknownType $type, DtoType|null $dto, DtoList $dtoList): string|PhpTypeInterface
    {
        return sprintf('CollectionResponse<%s>', $type->getName());
    }
}
