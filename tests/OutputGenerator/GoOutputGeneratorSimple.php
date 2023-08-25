<?php

declare(strict_types=1);

namespace App\Tests\OutputGenerator;

use Exception;
use Riverwaysoft\PhpConverter\Ast\ConverterResult;
use Riverwaysoft\PhpConverter\Dto\DtoClassProperty;
use Riverwaysoft\PhpConverter\Dto\DtoList;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpListType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;
use Riverwaysoft\PhpConverter\OutputGenerator\OutputGeneratorInterface;
use Riverwaysoft\PhpConverter\OutputGenerator\UnknownTypeResolver\UnknownTypeResolverInterface;
use Riverwaysoft\PhpConverter\OutputGenerator\UnsupportedTypeException;
use Riverwaysoft\PhpConverter\OutputWriter\OutputWriterInterface;
use Webmozart\Assert\Assert;
use function sprintf;

class GoOutputGeneratorSimple implements OutputGeneratorInterface
{
    public function __construct(
        private OutputWriterInterface $outputWriter,
        /** @var UnknownTypeResolverInterface[] $unknownTypeResolvers */
        private array $unknownTypeResolvers = [],
    ) {
    }

    public function generate(ConverterResult $converterResult): array
    {
        $this->outputWriter->reset();

        $dtoList = $converterResult->dtoList;
        foreach ($dtoList->getList() as $dto) {
            $this->outputWriter->writeType($this->convertToGoType($dto, $dtoList), $dto);
        }

        return $this->outputWriter->getTypes();
    }

    private function convertToGoType(DtoType $dto, DtoList $dtoList): string
    {
        Assert::false($dto->getExpressionType()->isAnyEnum(), 'Go language doesn\'t support enums');

        return sprintf("type %s struct {%s\n};", $dto->getName(), $this->convertToGoProperties($dto, $dtoList));
    }

    private function convertToGoProperties(DtoType $dto, DtoList $dtoList): string
    {
        $string = '';

        /** @param DtoClassProperty[] $properties */
        $properties = $dto->getProperties();
        foreach ($properties as $property) {
            $string .= sprintf("\n  %s %s", $property->getName(), $this->getGoTypeFromPhp($property->getType(), $dto, $dtoList));
        }

        return $string;
    }

    private function getGoTypeFromPhp(PhpTypeInterface $type, DtoType $dto, DtoList $dtoList): string
    {
        if ($type instanceof PhpUnionType) {
            Assert::greaterThan($type->getTypes(), 2, "Go does not support union types");
            Assert::true($type->isNullable(), "Go only supports nullable union types");
            $notNullType = $type->getFirstNotNullType();
            return sprintf('*%s', $this->getGoTypeFromPhp($notNullType, $dto, $dtoList));
        }

        if ($type instanceof PhpListType) {
            return sprintf('[]%s', $this->getGoTypeFromPhp($type->getType(), $dto, $dtoList));
        }

        if ($type instanceof PhpBaseType) {
            /** @var PhpBaseType $type */
            return match (true) {
                $type->equalsTo(PhpBaseType::int()), $type->equalsTo(PhpBaseType::float()) => 'int',
                $type->equalsTo(PhpBaseType::string()) => 'string',
                $type->equalsTo(PhpBaseType::bool()) => 'bool',
                $type->equalsTo(PhpBaseType::mixed()), $type->equalsTo(PhpBaseType::object()) => 'any',
                default => throw new Exception(sprintf("Unknown base PHP type: %s", $type->jsonSerialize()))
            };
        }

        /** @var PhpUnknownType $type */
        return $this->handleUnknownType($type, $dto, $dtoList);
    }

    private function handleUnknownType(PhpUnknownType $type, DtoType $dto, DtoList $dtoList): string
    {
        foreach ($this->unknownTypeResolvers as $unknownTypeResolver) {
            if ($unknownTypeResolver->supports($type, $dto, $dtoList)) {
                return $unknownTypeResolver->resolve($type, $dto, $dtoList);
            }
        }

        throw UnsupportedTypeException::forType($type, $dto->getName());
    }
}
