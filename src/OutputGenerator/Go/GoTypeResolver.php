<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator\Go;

use Exception;
use Riverwaysoft\PhpConverter\Dto\DtoList;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpListType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpOptionalType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;
use Riverwaysoft\PhpConverter\OutputGenerator\UnknownTypeResolver\ClassNameTypeResolver;
use Riverwaysoft\PhpConverter\OutputGenerator\UnknownTypeResolver\UnknownTypeResolverInterface;
use Riverwaysoft\PhpConverter\OutputGenerator\UnsupportedTypeException;

class GoTypeResolver
{
    /** @param UnknownTypeResolverInterface[] $unknownTypeResolvers */
    public function __construct(
        private array $unknownTypeResolvers = []
    ) {
    }

    /** @throws Exception */
    private function resolveUnion(PhpUnionType $type, ?DtoType $dto, DtoList $dtoList): string
    {
        $fn = fn (PhpTypeInterface $type) => $this->resolve($type, $dto, $dtoList);
        $types = array_map($fn, $type->getTypes());

        // Two args, one of them is null
        if (count($types) === 2 && in_array('null', $types)) {
            $types = array_diff($types, ['null']);

            if (str_starts_with($types[0], '*')) {
                return $types[0]; // If it was resolveUnknown recursion found(dirty fix)
            }

            return "*$types[0]";
        }
        throw new Exception('Unsupported union type: ' . json_encode($type));
    }

    /** @throws Exception */
    private function resolveUnknown(PhpUnknownType $type, ?DtoType $dto, DtoList $dtoList): string
    {
        $result = null;
        foreach ($this->unknownTypeResolvers as $resolver) {
            if ($resolver->supports($type, $dto, $dtoList)) {
                $result = $resolver->resolve($type, $dto, $dtoList);
                if ($result instanceof PhpTypeInterface) {
                    return $this->resolve($result, $dto, $dtoList);
                }

                // If it was ClassNameTypeResolver
                if (
                    is_string($result) &&
                    $resolver instanceof ClassNameTypeResolver &&
                    GoRecursionValidator::isRecursionFound($dto, $dtoList)
                ) {
                    return "*$result";
                }
            }
        }
        if ($result === null) {
            throw UnsupportedTypeException::forType($type, $dto?->getName() ?? '');
        }

        return $result;
    }

    /** @throws Exception */
    private function resolveBase(PhpBaseType $type, ?DtoType $dto): string
    {
        return match (true) {
            $type->equalsTo(PhpBaseType::int()) => 'int',
            $type->equalsTo(PhpBaseType::float()) => 'float64',
            $type->equalsTo(PhpBaseType::string()) => 'string',
            $type->equalsTo(PhpBaseType::bool()) => 'bool',
            $type->equalsTo(PhpBaseType::mixed()),
            $type->equalsTo(PhpBaseType::object()),
            $type->equalsTo(PhpBaseType::array()),
            $type->equalsTo(PhpBaseType::iterable()) => 'interface{}',
            $type->equalsTo(PhpBaseType::null()) => 'null',
            $type->equalsTo(PhpBaseType::self()) => "*{$dto->getName()}", // * for prevent recursive definition
            default => throw new Exception('Unknown base PHP type: %s' . json_encode($type))
        };
    }

    /** @throws Exception */
    public function resolve(PhpTypeInterface $type, ?DtoType $d, DtoList $dl): string
    {
        return match ($type::class) {
            PhpBaseType::class => $this->resolveBase($type, $d),
            PhpListType::class => sprintf('[]%s', $this->resolve($type->getType(), $d, $dl)),
            PhpUnionType::class => $this->resolveUnion($type, $d, $dl),
            PhpUnknownType::class => $this->resolveUnknown($type, $d, $dl),
            PhpOptionalType::class => $this->resolve($type->getType(), $d, $dl),
            default => throw new Exception('Type not implemented: ' . get_class($type))
        };
    }
}
