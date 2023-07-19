<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Config;

use Riverwaysoft\PhpConverter\Ast\ConverterVisitor;
use Riverwaysoft\PhpConverter\CodeProvider\FileSystemCodeProvider;
use Riverwaysoft\PhpConverter\OutputGenerator\OutputGeneratorInterface;

class PhpConverterConfig
{
    /** @var ConverterVisitor[] */
    private array $visitors = [];
    private OutputGeneratorInterface|null $outputGenerator = null;
    private FileSystemCodeProvider|null $codeProvider = null;


    public function addVisitor(ConverterVisitor $visitor): void
    {
        $this->visitors[] = $visitor;
    }

    /** @return ConverterVisitor[] */
    public function getVisitors(): array
    {
        return $this->visitors;
    }

    public function setOutputGenerator(OutputGeneratorInterface $generator): void
    {
        $this->outputGenerator = $generator;
    }

    public function getOutputGenerator(): OutputGeneratorInterface
    {
        return $this->outputGenerator;
    }

    public function getCodeProvider(): FileSystemCodeProvider
    {
        return $this->codeProvider;
    }

    public function setCodeProvider(FileSystemCodeProvider $codeProvider): void
    {
        $this->codeProvider = $codeProvider;
    }

}
