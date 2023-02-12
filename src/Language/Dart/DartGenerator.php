<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Language\Dart;

use Riverwaysoft\DtoConverter\Dto\DtoClassProperty;
use Riverwaysoft\DtoConverter\Dto\DtoEnumProperty;
use Riverwaysoft\DtoConverter\Dto\DtoList;
use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\Dto\ExpressionType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpListType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnknownType;
use Riverwaysoft\DtoConverter\Language\LanguageGeneratorInterface;
use Riverwaysoft\DtoConverter\Language\UnknownTypeResolver\UnknownTypeResolverInterface;
use Riverwaysoft\DtoConverter\Language\UnsupportedTypeException;
use Riverwaysoft\DtoConverter\OutputWriter\OutputProcessor\OutputFilesProcessor;
use Riverwaysoft\DtoConverter\OutputWriter\OutputWriterInterface;
use Webmozart\Assert\Assert;

class DartGenerator implements LanguageGeneratorInterface
{
    public function __construct(
        private OutputWriterInterface $outputWriter,
        /** @var UnknownTypeResolverInterface[] */
        private array $unknownTypeResolvers = [],
        private ?OutputFilesProcessor $outputFilesProcessor = null
    ) {
        $this->outputFilesProcessor = $this->outputFilesProcessor ?? new OutputFilesProcessor();
    }

    /** @inheritDoc */
    public function generate(DtoList $dtoList): array
    {
        $this->outputWriter->reset();

        foreach ($dtoList->getList() as $dto) {
            $this->outputWriter->writeType($this->convertToDartType($dto, $dtoList), $dto);
        }

        return $this->outputFilesProcessor->process($this->outputWriter->getTypes());
    }

    private function convertToDartType(DtoType $dto, DtoList $dtoList): string
    {
        if ($dto->getExpressionType()->equals(ExpressionType::class())) {
            return sprintf(
                "class %s {%s\n\n  %s({%s\n  }) {}\n}",
                $dto->getName(),
                $this->convertToDartProperties($dto, $dtoList),
                $dto->getName(),
                $this->generateConstructor($dto->getProperties()),
            );
        }

        if ($dto->getExpressionType()->isAnyEnum()) {
            return sprintf("enum %s {%s\n}", $dto->getName(), $this->convertEnumToTypeScriptProperties($dto->getProperties()));
        }

        throw new \Exception('Unknown expression type '.$dto->getExpressionType()->jsonSerialize());
    }

    private function convertToDartProperties(DtoType $dto, DtoList $dtoList): string
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
                $property->getType() instanceof PhpUnionType && $property->getType()->isNullable() ? '' : 'required ',
                $property->getName()
            );
        }

        return $string;
    }

    private function getDartTypeFromPhp(PhpTypeInterface $type, DtoType $dto, DtoList $dtoList): string
    {
        if ($type instanceof PhpUnionType) {
            Assert::greaterThan($type->getTypes(), 2, "Dart does not support union types");
            Assert::true($type->isNullable(), "Dart only support nullable union types");
            $notNullType = $type->getFirstNotNullType();
            return sprintf('%s?', $this->getDartTypeFromPhp($notNullType, $dto, $dtoList));
        }

        if ($type instanceof PhpListType) {
            return sprintf('List<%s>', $this->getDartTypeFromPhp($type->getType(), $dto, $dtoList));
        }

        if ($type instanceof PhpBaseType) {
            /** @var PhpBaseType $type */
            return match (true) {
                $type->equalsTo(PhpBaseType::int()) => 'int',
                $type->equalsTo(PhpBaseType::float()) => 'double',
                $type->equalsTo(PhpBaseType::string()) => 'String',
                $type->equalsTo(PhpBaseType::bool()) => 'bool',
                $type->equalsTo(PhpBaseType::mixed()), $type->equalsTo(PhpBaseType::iterable()), $type->equalsTo(PhpBaseType::array()) => 'Object',
                $type->equalsTo(PhpBaseType::null()) => 'null',
                $type->equalsTo(PhpBaseType::self()) => $dto->getName(),
                default => throw new \Exception(sprintf("Unknown base PHP type: %s", $type->jsonSerialize()))
            };
        }

        /** @var PhpUnknownType $type */
        $result = $this->handleUnknownType($type, $dto, $dtoList);

        if ($result instanceof PhpTypeInterface) {
            return $this->getDartTypeFromPhp($result, $dto, $dtoList);
        }

        return $result;
    }

    private function handleUnknownType(PhpUnknownType $type, DtoType $dto, DtoList $dtoList): string|PhpTypeInterface
    {
        foreach ($this->unknownTypeResolvers as $unknownTypeResolver) {
            if ($unknownTypeResolver->supports($type, $dto, $dtoList)) {
                return $unknownTypeResolver->resolve($type, $dto, $dtoList);
            }
        }

        throw UnsupportedTypeException::forType($type, $dto->getName());
    }

    /** @param DtoEnumProperty[] $properties */
    private function convertEnumToTypeScriptProperties(array $properties): string
    {
        $string = '';

        foreach ($properties as $property) {
            $string .= sprintf("\n  %s,", $property->getName());
        }

        return $string;
    }
}
