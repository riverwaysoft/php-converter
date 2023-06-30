<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\CodeProvider;

class FileSystemCodeProvider
{
    public function __construct(
        private string $pattern,
    ) {
    }

    /** @return string[] */
    public function getListings(string $directory): iterable
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        $files = new \RegexIterator($files, $this->pattern);

        foreach ($files as $file) {
            yield file_get_contents($file->getPathName());
        }
    }
}
