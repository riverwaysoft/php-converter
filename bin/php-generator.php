<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Riverwaysoft\DtoConverter\Cli\ConvertToTypeScriptCommand;
use Riverwaysoft\DtoConverter\CodeProvider\FileSystemCodeProvider;
use Riverwaysoft\DtoConverter\Converter;
use Riverwaysoft\DtoConverter\Normalizer;
use Riverwaysoft\DtoConverter\Language\TypeScript\DateTimeTypeResolver;
use Riverwaysoft\DtoConverter\Language\TypeScript\LibPhoneNumberTypeResolver;
use Riverwaysoft\DtoConverter\Language\TypeScript\TypeScriptGenerator;
use Riverwaysoft\DtoConverter\OutputDiffCalculator\OutputDiffCalculator;
use Riverwaysoft\DtoConverter\OutputWriter\SingleFileOutputWriter;
use Symfony\Component\Console\Application;
use Symfony\Component\Filesystem\Filesystem;

$application = new Application();

$application->add(
    new ConvertToTypeScriptCommand(
        new Converter(Normalizer::factory()),
        new TypeScriptGenerator(
            new SingleFileOutputWriter('generated.ts'),
            [
                new DateTimeTypeResolver(),
                new LibPhoneNumberTypeResolver(),
            ],
        ),
        new Filesystem(),
        new FileSystemCodeProvider('/(Output|Enum)\.php$/'),
        new OutputDiffCalculator(),
    )
);

$application->run();
