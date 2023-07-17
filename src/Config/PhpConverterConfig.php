<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Config;

use Riverwaysoft\PhpConverter\Ast\ConverterVisitor;
use Riverwaysoft\PhpConverter\CodeProvider\FileSystemCodeProvider;
use Riverwaysoft\PhpConverter\Language\LanguageGeneratorInterface;

class PhpConverterConfig
{
    /** @var ConverterVisitor[] */
    private array $visitors = [];
    private LanguageGeneratorInterface|null $languageGenerator = null;
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

    public function setLanguageGenerator(LanguageGeneratorInterface $languageGenerator): void
    {
        $this->languageGenerator = $languageGenerator;
    }

    public function getLanguageGenerator(): LanguageGeneratorInterface
    {
        return $this->languageGenerator;
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
