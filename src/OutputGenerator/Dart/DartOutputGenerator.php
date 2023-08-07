<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator\Dart;

use Riverwaysoft\PhpConverter\Ast\ConverterResult;
use Riverwaysoft\PhpConverter\Dto\DtoEnumProperty;
use Riverwaysoft\PhpConverter\Dto\DtoList;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\ExpressionType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;
use Riverwaysoft\PhpConverter\OutputGenerator\OutputGeneratorInterface;
use Riverwaysoft\PhpConverter\OutputWriter\OutputFile;
use Riverwaysoft\PhpConverter\OutputWriter\OutputProcessor\OutputFilesProcessor;
use Riverwaysoft\PhpConverter\OutputWriter\OutputWriterInterface;
use Exception;
use function sprintf;

class DartOutputGenerator implements OutputGeneratorInterface
{
    private DartEnumValidator $dartEnumValidator;

    public function __construct(
        private OutputWriterInterface $outputWriter,
        private DartTypeResolver $typeResolver,
        private ?OutputFilesProcessor $outputFilesProcessor = null,
        private ?DartClassFactoryGenerator $classFactoryGenerator = null,
        private ?DartEquitableGenerator $equitableGenerator = null,
    ) {
        $this->outputFilesProcessor = $this->outputFilesProcessor ?? new OutputFilesProcessor();
        $this->dartEnumValidator = new DartEnumValidator();
    }

    /** @return OutputFile[] */
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
            $typeName = $dto->getName();
            if ($dto->isGeneric()) {
                $generics = array_map(fn (PhpUnknownType $generic) => $generic->getName(), $dto->getGenerics());
                $typeName .= sprintf("<%s>", join(', ', $generics));
            }

            return sprintf(
                "class %s%s {%s\n\n  %s\n%s%s}",
                $typeName,
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
            $string .= sprintf("\n  final %s %s;", $this->typeResolver->getDartTypeFromPhp($property->getType(), $dto, $dtoList), $property->getName());
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
