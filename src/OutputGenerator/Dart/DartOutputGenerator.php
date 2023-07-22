<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator\Dart;

use Riverwaysoft\PhpConverter\Ast\ConverterResult;
use Riverwaysoft\PhpConverter\Dto\DtoEnumProperty;
use Riverwaysoft\PhpConverter\Dto\DtoList;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\ExpressionType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpListType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;
use Riverwaysoft\PhpConverter\OutputGenerator\OutputGeneratorInterface;
use Riverwaysoft\PhpConverter\OutputGenerator\UnknownTypeResolver\UnknownTypeResolverInterface;
use Riverwaysoft\PhpConverter\OutputGenerator\UnsupportedTypeException;
use Riverwaysoft\PhpConverter\OutputWriter\OutputProcessor\OutputFilesProcessor;
use Riverwaysoft\PhpConverter\OutputWriter\OutputWriterInterface;
use Webmozart\Assert\Assert;
use Exception;
use function sprintf;

class DartOutputGenerator implements OutputGeneratorInterface
{
    private DartEnumValidator $dartEnumValidator;

    public function __construct(
        private OutputWriterInterface $outputWriter,
        /** @var UnknownTypeResolverInterface[] */
        private array $unknownTypeResolvers = [],
        private ?OutputFilesProcessor $outputFilesProcessor = null,
        private ?DartClassFactoryGenerator $classFactoryGenerator = null,
        private ?DartEquitableGenerator $equitableGenerator = null,
    ) {
        $this->outputFilesProcessor = $this->outputFilesProcessor ?? new OutputFilesProcessor();
        $this->dartEnumValidator = new DartEnumValidator();
    }

    public function generate(ConverterResult $converterResult): array
    {
        $this->outputWriter->reset();

        $dtoList = $converterResult->dtoList;
        foreach ($dtoList->getList() as $dto) {
            $this->outputWriter->writeType($this->convertToDartType($dto, $dtoList), $dto);
        }

        return $this->outputFilesProcessor->process($this->outputWriter->getTypes());
    }

    private function convertToDartType(DtoType $dto, DtoList $dtoList): string
    {
        if ($dto->getExpressionType()->equals(ExpressionType::class())) {
            // https://dart-lang.github.io/linter/lints/empty_constructor_bodies.html
            $isEmpty = $dto->isEmpty();
            return sprintf(
                "class %s%s {%s\n\n  %s\n%s%s}",
                $dto->getName(),
                $this->equitableGenerator && !$isEmpty ? $this->equitableGenerator->generateEquitableHeader($dto) : '',
                $this->convertToDartProperties($dto, $dtoList),
                !$isEmpty ? $this->generateConstructor($dto) : '',
                $this->classFactoryGenerator && !$isEmpty ? $this->classFactoryGenerator->generateClassFactory($dto, $dtoList) : '',
                $this->equitableGenerator && !$isEmpty ? $this->equitableGenerator->generateEquitableId($dto) : '',
            );
        }

        if ($dto->getExpressionType()->isAnyEnum()) {
            $this->dartEnumValidator->assertIsValidEnumForDart($dto);
            return sprintf("enum %s {%s\n}", $dto->getName(), $this->convertEnumToTypeScriptProperties($dto->getProperties()));
        }

        throw new Exception('Unknown expression type ' . $dto->getExpressionType()->jsonSerialize());
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

    private function generateConstructor(DtoType $dtoType): string
    {
        $string = '';

        foreach ($dtoType->getProperties() as $property) {
            $string .= sprintf(
                "\n    %sthis.%s,",
                $property->getType() instanceof PhpUnionType && $property->getType()->isNullable() ? '' : 'required ',
                $property->getName()
            );
        }

        return sprintf("%s({%s\n  });", $dtoType->getName(), $string);
    }

    private function getDartTypeFromPhp(PhpTypeInterface $type, DtoType $dto, DtoList $dtoList): string
    {
        if ($type instanceof PhpUnionType) {
            Assert::greaterThan($type->getTypes(), 2, "Dart does not support union types");
            if (!$type->isNullable()) {
                return $this->getDartTypeFromPhp(PhpBaseType::mixed(), $dto, $dtoList);
            }
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
                $type->equalsTo(PhpBaseType::float()) => 'num',
                $type->equalsTo(PhpBaseType::string()) => 'String',
                $type->equalsTo(PhpBaseType::bool()) => 'bool',
                $type->equalsTo(PhpBaseType::mixed()), $type->equalsTo(PhpBaseType::iterable()), $type->equalsTo(PhpBaseType::array()) => 'dynamic',
                $type->equalsTo(PhpBaseType::null()) => 'null',
                $type->equalsTo(PhpBaseType::self()) => $dto->getName(),
                default => throw new Exception(sprintf("Unknown base PHP type: %s", $type->jsonSerialize()))
            };
        }

        /** @var PhpUnknownType $type */
        $result = $this->handleUnknownType($type, $dto, $dtoList);

        if ($result instanceof PhpTypeInterface) {
            return $this->getDartTypeFromPhp($result, $dto, $dtoList);
        }

        return $result;
    }

    private function handleUnknownType(PhpUnknownType $type, DtoType|null $dto, DtoList $dtoList): string|PhpTypeInterface
    {
        foreach ($this->unknownTypeResolvers as $unknownTypeResolver) {
            if ($unknownTypeResolver->supports($type, $dto, $dtoList)) {
                return $unknownTypeResolver->resolve($type, $dto, $dtoList);
            }
        }

        throw UnsupportedTypeException::forType($type, $dto?->getName() ?? '');
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
