<?php

declare(strict_types=1);

namespace App\CodeProvider;

use Webmozart\Assert\Assert;

class FileSystemCodeProvider implements CodeProviderInterface
{
    public function __construct(private string $directory)
    {
        Assert::directory($directory);
    }

    /** @return string[]|iterable */
    public function getListings(): iterable
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->directory));
        $files = new \RegexIterator($files, '/\.php$/');

        foreach ($files as $file) {
            yield file_get_contents($file->getPathName());
        }
    }
}
