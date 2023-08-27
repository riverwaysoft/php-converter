<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\CodeProvider;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use RegexIterator;
use function file_get_contents;

class FileSystemCodeProvider implements CodeProviderInterface
{
    public function __construct(
        private string $pattern,
        private string $directory,
    ) {
    }

    public static function phpFiles(string $directory): self
    {
        return new self('/\.php$/', $directory);
    }

    /** @return string[] */
    public function getListings(): iterable
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->directory));
        $files = new RegexIterator($files, $this->pattern);

        foreach ($files as $file) {
            yield file_get_contents($file->getPathName());
        }
    }
}
