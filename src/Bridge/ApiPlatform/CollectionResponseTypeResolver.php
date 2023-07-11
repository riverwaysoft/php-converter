<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Bridge\ApiPlatform;

use Riverwaysoft\PhpConverter\Dto\DtoList;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;
use Riverwaysoft\PhpConverter\Language\UnknownTypeResolver\ClassNameTypeResolver;

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
