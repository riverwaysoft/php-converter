<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Cli;

use Riverwaysoft\DtoConverter\CodeProvider\FileSystemCodeProvider;
use Riverwaysoft\DtoConverter\Converter;
use Riverwaysoft\DtoConverter\Language\TypeScript\TypeScriptGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class ConvertToTypeScriptCommand extends Command
{
    protected static $defaultName = 'dto-generator:typescript';

    public function __construct(
        private Converter $converter,
        private TypeScriptGenerator $typeScriptGenerator,
        private Filesystem $fileSystem,
        private FileSystemCodeProvider $codeProvider,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate TypeScript DTO from PHP sources')
            ->addOption('from', 'f', InputOption::VALUE_REQUIRED)
            ->addOption('to', 't', InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $from = $input->getOption('from');
        $to = $input->getOption('to');

        $files = $this->codeProvider->getListings($from);
        $normalized = $this->converter->convert($files);
        $result = $this->typeScriptGenerator->generate($normalized);

        if ($this->fileSystem->exists($to)) {
            $this->fileSystem->remove($to);
        }
        $this->fileSystem->touch($to);
        $this->fileSystem->appendToFile($to, $result);

        return Command::SUCCESS;
    }
}
