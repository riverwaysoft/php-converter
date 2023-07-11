<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Bridge\ApiPlatform;

use Riverwaysoft\PhpConverter\OutputWriter\OutputFile;
use Riverwaysoft\PhpConverter\OutputWriter\OutputProcessor\SingleOutputFileProcessorInterface;

class AppendCollectionResponseFileProcessor implements SingleOutputFileProcessorInterface
{
    private string $text = "\nexport type CollectionResponse<Resource extends {id: string}> = {
  'hydra:member': Resource[];
  'hydra:totalItems': number;
  'hydra:view': { '@id': string; 'hydra:last': string };
  'hydra:search': { 'hydra:mapping': any[] };
  'hydra:last': string;
};";

    public function process(OutputFile $outputFile): OutputFile
    {
        return new OutputFile(
            relativeName: $outputFile->getRelativeName(),
            content: $outputFile->getContent() . $this->text,
        );
    }
}
