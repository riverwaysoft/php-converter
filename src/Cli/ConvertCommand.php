<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Cli;

use Riverwaysoft\PhpConverter\CodeProvider\FileSystemCodeProvider;
use Riverwaysoft\PhpConverter\Ast\Converter;
use Riverwaysoft\PhpConverter\Language\LanguageGeneratorInterface;
use Riverwaysoft\PhpConverter\OutputDiffCalculator\OutputDiffCalculator;
use Composer\XdebugHandler\XdebugHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Assert\Assert;

class ConvertCommand extends Command
{
    protected static $defaultName = 'generate';

    public function __construct(
        private Converter $converter,
        private LanguageGeneratorInterface $languageGenerator,
        private Filesystem $fileSystem,
        private OutputDiffCalculator $diffWriter,
        private FileSystemCodeProvider $fsCodeProvider,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate TypeScript / Dart from PHP sources')
            ->addOption('from', 'f', InputOption::VALUE_REQUIRED)
            ->addOption('to', 't', InputOption::VALUE_REQUIRED)
            ->addOption('xdebug', null, InputOption::VALUE_OPTIONAL)
            ->addOption('branch', 'b', InputOption::VALUE_OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->getOption('xdebug')) {
            $this->turnOffXdebug();
        }

        $from = $input->getOption('from');
        $to = $input->getOption('to');
        Assert::directory($to);

        $files = $this->fsCodeProvider->getListings($from);
        if (empty($files)) {
            $output->writeln('No files to convert');
            return Command::SUCCESS;
        }

        $converterResult = $this->converter->convert($files);
        $outputFiles = $this->languageGenerator->generate($converterResult);

        foreach ($outputFiles as $outputFile) {
            $outputAbsolutePath = rtrim($to, '/') . '/' . $outputFile->getRelativeName();
            $newFileContent = $outputFile->getContent();
            if ($this->fileSystem->exists($outputAbsolutePath)) {
                $diff = $this->diffWriter->calculate(file_get_contents($outputAbsolutePath), $newFileContent);
                if (empty($diff)) {
                    $output->writeln(sprintf("\nNo difference between the old generated file and the new one: %s", $outputFile->getRelativeName()));
                } else {
                    $output->writeln(sprintf("\nSuccessfully written file: %s", $outputFile->getRelativeName()));
                    $output->write($diff);
                }

                $this->fileSystem->remove($outputAbsolutePath);
            } else {
                $output->writeln(sprintf("\nSuccessfully created file %s", $outputFile->getRelativeName()));
            }
            $this->fileSystem->touch($outputAbsolutePath);
            $this->fileSystem->appendToFile($outputAbsolutePath, $newFileContent);
        }

        return Command::SUCCESS;
    }

    private function turnOffXdebug(): void
    {
        $xdebug = new XdebugHandler('dtoConverter');
        $xdebug->setPersistent();
        $xdebug->check();
        unset($xdebug);
    }
}
