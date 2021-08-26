<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\OutputWriter\EntityPerClassOutputWriter;

use Riverwaysoft\DtoConverter\Dto\DtoType;

class DartImportGenerator implements ImportGeneratorInterface
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
                "import './%s';\n",
                $this->fileNameGenerator->generateFileNameWithExtension($dependency->getName())
            ) . $content;
        }

        return $content;
    }
}
