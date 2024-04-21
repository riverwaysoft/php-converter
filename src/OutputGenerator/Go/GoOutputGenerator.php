<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator\Go;

use Exception;
use Riverwaysoft\PhpConverter\Ast\ConverterResult;
use Riverwaysoft\PhpConverter\Dto\DtoList;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\ExpressionType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpOptionalType;
use Riverwaysoft\PhpConverter\OutputGenerator\OutputGeneratorInterface;
use Riverwaysoft\PhpConverter\OutputWriter\OutputFile;
use Riverwaysoft\PhpConverter\OutputWriter\OutputProcessor\OutputFilesProcessor;
use Riverwaysoft\PhpConverter\OutputWriter\OutputWriterInterface;

class GoOutputGenerator
    implements OutputGeneratorInterface
{
    private OutputFilesProcessor $outputFilesProcessor;

    private GoEnumResolver $enumResolver;

    public function __construct(
        private OutputWriterInterface $outputWriter,
        private GoTypeResolver $resolver,
        ?OutputFilesProcessor $outputFilesProcessor = null,
    ) {
        $this->outputFilesProcessor = OutputFilesProcessorProvider::provide($outputFilesProcessor);
        $this->enumResolver = new GoEnumResolver();
    }

    /**
     * @return OutputFile[]
     * @throws Exception
     */
    public function generate(ConverterResult $result): array
    {
        $this->outputWriter->reset();
        foreach ($result->dtoList->getList() as $dto) {
            $this->outputWriter->writeType($this->convert($dto, $result->dtoList), $dto);
        }

        return $this->outputFilesProcessor->process($this->outputWriter->getTypes());
    }

    /** @throws Exception */
    private function convert(DtoType $dto, DtoList $dtoList): string
    {
        if ($dto->getExpressionType()->equals(ExpressionType::class())) {
            $structProps = '';

            $maxPropLen = 0;
            $maxPropRowLen = 0;
            foreach ($dto->getProperties() as $prop) {
                $maxPropLen = max($maxPropLen, strlen($prop->getName()));
            }

            $normProps = [];
            foreach ($dto->getProperties() as $prop) {
                $spaces = str_repeat(' ', $maxPropLen - strlen($prop->getName()) + 1);

                $tagsTemplate = '`json:"%s"`';
                if ($prop->getType() instanceof PhpOptionalType) {
                    $tagsTemplate = '`json:"%s,omitempty"`';
                }
                $structPropsRow = sprintf(
                    "%s$spaces%s",
                    ucfirst($prop->getName()),
                    $this->resolver->resolve($prop->getType(), $dto, $dtoList)
                );
                $maxPropRowLen = max($maxPropRowLen, strlen($structPropsRow));
                $normProps[] = [
                    'structPropsRow' => $structPropsRow,
                    'tagsRow' => sprintf($tagsTemplate, $prop->getName()),
                ];
            }

            foreach ($normProps as $prop) {
                $tagsSpaces = str_repeat(' ', $maxPropRowLen - strlen($prop['structPropsRow']) + 1);
                $structProps .= sprintf("\n\t%s$tagsSpaces%s", $prop['structPropsRow'], $prop['tagsRow']);
            }

            return sprintf("type %s struct {%s\n}", $dto->getName(), $structProps);
        }
        if ($dto->getExpressionType()->isAnyEnum()) {
            return $this->enumResolver->resolve($dto);
        }
        throw new Exception('Unknown expression type '.$dto->getExpressionType()->jsonSerialize());
    }
}
