<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\CodeProvider;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class RemoteRepoCodeProvider
{
    public function __construct(
        private FileSystemCodeProvider $fileSystemCodeProvider,
        private Filesystem $filesystem,
        private ?string $repoDownloadFolder = null,
    ) {
    }

    public function getListings(string $repositoryUrl, string $branch): iterable
    {
        $repoDownloadFolder = $this->repoDownloadFolder ?? $this->generateRepoDownloadFolder($repositoryUrl);
        if ($this->filesystem->exists($repoDownloadFolder)) {
            $this->filesystem->remove($repoDownloadFolder);
        }
        $process = new Process(["git", "clone", "--branch", $branch, $repositoryUrl, "--depth", "1", $repoDownloadFolder]);
        $process->mustRun();

        return $this->fileSystemCodeProvider->getListings($repoDownloadFolder);
    }

    private function generateRepoDownloadFolder(string $repositoryUrl): string
    {
        return sys_get_temp_dir() . "/" . basename($repositoryUrl);
    }
}
