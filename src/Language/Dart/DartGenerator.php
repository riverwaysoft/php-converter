<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Language\Dart;

use Riverwaysoft\DtoConverter\Dto\DtoClassProperty;
use Riverwaysoft\DtoConverter\Dto\DtoEnumProperty;
use Riverwaysoft\DtoConverter\Dto\DtoList;
use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\Dto\ExpressionType;
use Riverwaysoft\DtoConverter\Dto\SingleType;
use Riverwaysoft\DtoConverter\Dto\UnionType;
use Riverwaysoft\DtoConverter\Language\LanguageGeneratorInterface;
use Riverwaysoft\DtoConverter\Language\UnknownTypeResolverInterface;
use Riverwaysoft\DtoConverter\Language\UnsupportedTypeException;
use Riverwaysoft\DtoConverter\OutputWriter\OutputWriterInterface;
use Webmozart\Assert\Assert;

class DartGenerator implements LanguageGeneratorInterface
{
    public function __construct(
        private OutputWriterInterface $outputWriter,
        /** @var UnknownTypeResolverInterface[] */
        private array $unknownTypeResolvers = [],
    ) {
    }

    /** @inheritDoc */
    public function generate(DtoList $dtoList): array
    {
        $this->outputWriter->reset();

        foreach ($dtoList->getList() as $dto) {
            // Dart only supports numeric enum
            if ($dto->getExpressionType()->equals(ExpressionType::enum()) && !$dto->isNumericEnum()) {
                continue;
            }

            $this->outputWriter->writeType($this->convertToDartType($dto, $dtoList), $dto);
        }

        return $this->outputWriter->getTypes();
    }

    private function convertToDartType(DtoType $dto, DtoList $dtoList): string
    {
        if ($dto->getExpressionType()->equals(ExpressionType::class())) {
            return sprintf(
                "class %s {%s\n\n  %s({%s\n  })\n}",
                $dto->getName(),
                $this->convertToDartProperties($dto, $dtoList),
                $dto->getName(),
                $this->generateConstructor($dto->getProperties()),
            );
        }

        if ($dto->getExpressionType()->equals(ExpressionType::enum())) {
            return sprintf("enum %s {%s\n}", $dto->getName(), $this->convertEnumToTypeScriptProperties($dto->getProperties()));
        }

        throw new \Exception('Unknown expression type '.$dto->getExpressionType()->jsonSerialize());
    }

    private function convertToDartProperties(DtoType $dto, DtoList $dtoList)
    {
        $string = '';

        $properties = $dto->getProperties();

        foreach ($properties as $property) {
            $string .= sprintf("\n  final %s %s;", $this->getDartTypeFromPhp($property->getType(), $dto, $dtoList), $property->getName());
        }

        return $string;
    }

    /** @param DtoClassProperty[] $properties */
    private function generateConstructor(array $properties): string
    {
        $string = '';

        foreach ($properties as $property) {
            $string .= sprintf(
                "\n    %sthis.%s,",
                $property->getType() instanceof UnionType && $property->getType()->isNullable() ? '' : 'required ',
                $property->getName()
            );
        }

        return $string;
    }

    private function getDartTypeFromPhp(UnionType|SingleType $type, DtoType $dto, DtoList $dtoList)
    {
        if ($type instanceof UnionType) {
            Assert::greaterThan($type->getTypes(), 2, "Dart does not support union types");
            Assert::true($type->isNullable());
            /** @var SingleType|null $notNullType */
            $notNullType = null;
            foreach ($type->getTypes() as $type) {
                if (!$type->isNull()) {
                    $notNullType = $type;
                }
            }
            Assert::notNull($notNullType);
            return sprintf('%s?', $this->getDartTypeFromPhp($notNullType, $dto, $dtoList));
        }

        if ($type->isList()) {
            return sprintf('List<%s>', $this->getDartTypeFromPhp(new SingleType($type->getName()), $dto, $dtoList));
        }

        return match ($type->getName()) {
            'int' => 'int',
            'float' => 'double',
            'string' => 'String',
            'bool' => 'bool',
            'mixed', 'object', 'array' => 'Object',
            'null' => 'null',
            'self' => $dto->getName(),
            default => $this->handleUnknownType($type, $dto, $dtoList),
        };
    }

    private function handleUnknownType(SingleType $type, DtoType $dto, DtoList $dtoList): string
    {
        /** @var UnknownTypeResolverInterface $unknownTypeResolver */
        foreach ($this->unknownTypeResolvers as $unknownTypeResolver) {
            if ($unknownTypeResolver->supports($type, $dto, $dtoList)) {
                return $unknownTypeResolver->resolve($type, $dto, $dtoList);
            }
        }

        throw UnsupportedTypeException::forType($type, $dto->getName());
    }

    /** @param DtoEnumProperty[] $properties */
    private function convertEnumToTypeScriptProperties(array $properties)
    {
        $string = '';

        foreach ($properties as $property) {
            $string .= sprintf("\n  %s,", $property->getName());
        }

        return $string;
    }
}
