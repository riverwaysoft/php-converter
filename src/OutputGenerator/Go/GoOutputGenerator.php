<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator\Go;

use Exception;
use Riverwaysoft\PhpConverter\Ast\ConverterResult;
use Riverwaysoft\PhpConverter\Dto\DtoList;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\ExpressionType;
use Riverwaysoft\PhpConverter\OutputGenerator\OutputGeneratorInterface;
use Riverwaysoft\PhpConverter\OutputWriter\OutputFile;
use Riverwaysoft\PhpConverter\OutputWriter\OutputProcessor\OutputFilesProcessor;
use Riverwaysoft\PhpConverter\OutputWriter\OutputWriterInterface;

class GoOutputGenerator implements OutputGeneratorInterface
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
            foreach ($dto->getProperties() as $prop) {
                $structProps .= sprintf(
                    "\n  %s %s",
                    ucfirst($prop->getName()),
                    $this->resolver->resolve($prop->getType(), $dto, $dtoList)
                );
            }

            return sprintf("type %s struct {%s\n}", $dto->getName(), $structProps);
        }
        if ($dto->getExpressionType()->isAnyEnum()) {
            return $this->enumResolver->resolve($dto);
        }
        throw new Exception('Unknown expression type ' . $dto->getExpressionType()->jsonSerialize());
    }
}
