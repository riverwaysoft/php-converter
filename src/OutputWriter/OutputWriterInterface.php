<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\OutputWriter;

interface OutputWriterInterface
{
    public function writeType(string $languageType): void;
    /** @return OutputFile[] */
    public function getTypes(): array;
    public function reset(): void;
}
