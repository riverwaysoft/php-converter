<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\OutputWriter\OutputProcessor;

use Riverwaysoft\DtoConverter\OutputWriter\OutputFile;

class OutputFilesProcessor
{
    public function __construct(
        /** @var SingleOutputFileProcessorInterface[] $outputProcessors */
        private array $outputProcessors = [],
    ) {
    }

    /**
     * @param OutputFile[] $outputFiles
     * @return OutputFile[]
     */
    public function process(array $outputFiles): array
    {
        /** @var OutputFile[] $newOutputFiles */
        $newOutputFiles = [];

        foreach ($outputFiles as $outputFile) {
            $newOutputFile = new OutputFile(
                relativeName: $outputFile->getRelativeName(),
                content: $outputFile->getContent(),
            );

            foreach ($this->outputProcessors as $outputProcessor) {
                $newOutputFile = $outputProcessor->process($newOutputFile);
            }

            $newOutputFiles[] = $newOutputFile;
        }

        return $newOutputFiles;
    }
}
