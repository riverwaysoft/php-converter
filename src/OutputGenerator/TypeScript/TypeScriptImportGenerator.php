<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator\TypeScript;

use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\OutputGenerator\ImportGeneratorInterface;
use Riverwaysoft\PhpConverter\OutputWriter\EntityPerClassOutputWriter\DtoTypeDependencyCalculator;
use Riverwaysoft\PhpConverter\OutputWriter\EntityPerClassOutputWriter\FileNameGeneratorInterface;
use function count;
use function sprintf;

class TypeScriptImportGenerator implements ImportGeneratorInterface
{
    public function __construct(
        private FileNameGeneratorInterface $fileNameGenerator,
        private DtoTypeDependencyCalculator $dependencyCalculator,
    ) {
    }

    public function generateFileContent(string $languageType, DtoType $dtoType): string
    {
        $dependencies = $this->dependencyCalculator->getDependencies($dtoType);
        if (!count($dependencies)) {
            return $languageType;
        }

        $content = "\n" . $languageType;

        foreach ($dependencies as $dependency) {
            $content = sprintf(
                "import { %s } from './%s';\n",
                $dependency->getName(),
                $this->fileNameGenerator->generateFileName($dependency->getName())
            ) . $content;
        }

        return $content;
    }
}
