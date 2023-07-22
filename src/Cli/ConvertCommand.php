<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Cli;

use Riverwaysoft\PhpConverter\Ast\UsageCollector;
use Riverwaysoft\PhpConverter\Ast\Converter;
use Riverwaysoft\PhpConverter\Config\PhpConverterConfig;
use Riverwaysoft\PhpConverter\OutputDiffCalculator\OutputDiffCalculator;
use Composer\XdebugHandler\XdebugHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Assert\Assert;
use function sprintf;
use function rtrim;
use function file_get_contents;
use function json_encode;

class ConvertCommand extends Command
{
    protected static $defaultName = 'generate';

    private UsageCollector $usageCollector;

    private OutputDiffCalculator $diffWriter;

    private Filesystem $fileSystem;

    public function __construct()
    {
        parent::__construct();
        $this->usageCollector = new UsageCollector();
        $this->diffWriter = new OutputDiffCalculator();
        $this->fileSystem = new Filesystem();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate TypeScript / Dart from PHP sources')
            ->addOption('from', 'f', InputOption::VALUE_REQUIRED)
            ->addOption('to', 't', InputOption::VALUE_REQUIRED)
            ->addOption(
                name: 'config',
                shortcut: 'c',
                mode: InputOption::VALUE_REQUIRED,
                description: 'A path to php-converter config',
                default: './bin/default-config.php',
            )
            ->addOption('xdebug', 'x', InputArgument::OPTIONAL, 'Do not turn off Xdebug')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->getOption('xdebug')) {
            $this->turnOffXdebug();
        }

        $from = $input->getOption('from');
        $to = $input->getOption('to');
        Assert::directory($to);

        $configFile = $input->getOption('config');
        Assert::file($configFile);
        Assert::readable($configFile);

        $config = new PhpConverterConfig();
        (require_once $configFile)($config);

        $files = $config->getCodeProvider()->getListings($from);
        if (empty($files)) {
            $output->writeln('No files to convert');
            return Command::SUCCESS;
        }

        if ($output->isVerbose()) {
            $this->usageCollector->startMeasuring();
        }

        $converter = new Converter($config->getVisitors());
        $converterResult = $converter->convert($files);
        $outputFiles = $config->getOutputGenerator()->generate($converterResult);

        foreach ($outputFiles as $outputFile) {
            $outputAbsolutePath = sprintf("%s/%s", rtrim($to, '/'), $outputFile->getRelativeName());
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

        if ($output->isVerbose()) {
            $this->usageCollector->endMeasuring();
            $output->writeln("\n\nScript usage: " . json_encode($this->usageCollector->report()));
        }

        return Command::SUCCESS;
    }

    private function turnOffXdebug(): void
    {
        $xdebug = new XdebugHandler('phpConverter');
        $xdebug->setPersistent();
        $xdebug->check();
        unset($xdebug);
    }
}
