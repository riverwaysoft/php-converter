<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\CodeProvider;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class RemoteRepositoryCodeProvider implements CodeProviderInterface
{
    private Filesystem $filesystem;

    public function __construct(
        private string $repositoryUrl,
        private string $branch,
    ) {
        $this->filesystem = new Filesystem();
    }

    public function getListings(): iterable
    {
        $repoDownloadFolder = $this->generateRepoDownloadFolder($this->repositoryUrl);
        if ($this->filesystem->exists($repoDownloadFolder)) {
            $this->filesystem->remove($repoDownloadFolder);
        }
        $process = new Process(["git", "clone", "--branch", $this->branch, $this->repositoryUrl, "--depth", "1", $repoDownloadFolder]);
        $process->mustRun();
        $fileSystemCodeProvider = FileSystemCodeProvider::phpFiles($repoDownloadFolder);

        return $fileSystemCodeProvider->getListings();
    }

    private function generateRepoDownloadFolder(string $repositoryUrl): string
    {
        return sprintf("%s/%s", sys_get_temp_dir(), basename($repositoryUrl));
    }
}
