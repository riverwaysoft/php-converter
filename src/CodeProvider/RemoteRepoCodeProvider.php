<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\CodeProvider;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class RemoteRepoCodeProvider implements CodeProviderInterface
{
    public function __construct(
        private FileSystemCodeProvider $fileSystemCodeProvider,
        private Filesystem             $filesystem,
        private string                 $repositoryUrl,
        private string                 $branchName,
        private string                 $repoDownloadFolder,
        private string                 $appendToPath = ''
    )
    {

    }

    public function getListings(string $directory): iterable
    {
        if ($this->filesystem->exists($this->repoDownloadFolder)) {
            $this->filesystem->remove($this->repoDownloadFolder);
        }
        $process = new Process(["git", "clone", "--branch", $this->branchName, $this->repositoryUrl, "--depth", "1", $this->repoDownloadFolder]);
        $process->mustRun();

        return $this->fileSystemCodeProvider->getListings(rtrim($this->repoDownloadFolder, '/') . '/' . ltrim($this->appendToPath, '/'));
    }
}