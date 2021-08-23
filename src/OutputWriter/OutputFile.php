<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\OutputWriter;

class OutputFile
{
    public function __construct(private string $relativeName, private string $content)
    {
    }

    public function getRelativeName(): string
    {
        return $this->relativeName;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function appendContent(string $content): void
    {
        $this->content .= $content;
    }

    public function isEmpty(): bool
    {
        return empty($this->content);
    }
}
