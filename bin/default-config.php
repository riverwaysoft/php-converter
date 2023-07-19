<?php

declare(strict_types=1);

use Riverwaysoft\PhpConverter\Ast\DtoVisitor;
use Riverwaysoft\PhpConverter\ClassFilter\PhpAttributeFilter;
use Riverwaysoft\PhpConverter\CodeProvider\FileSystemCodeProvider;
use Riverwaysoft\PhpConverter\Config\PhpConverterConfig;
use Riverwaysoft\PhpConverter\OutputGenerator\TypeScript\TypeScriptOutputGenerator;
use Riverwaysoft\PhpConverter\OutputGenerator\UnknownTypeResolver\ClassNameTypeResolver;
use Riverwaysoft\PhpConverter\OutputGenerator\UnknownTypeResolver\DateTimeTypeResolver;
use Riverwaysoft\PhpConverter\OutputWriter\SingleFileOutputWriter\SingleFileOutputWriter;

return static function (PhpConverterConfig $config) {
    $config->setCodeProvider(new FileSystemCodeProvider('/\.php$/'));

    $config->addVisitor(new DtoVisitor(new PhpAttributeFilter('Dto')));

    $config->setOutputGenerator(new TypeScriptOutputGenerator(
        new SingleFileOutputWriter('generated.ts'),
        [
            new DateTimeTypeResolver(),
            new ClassNameTypeResolver(),
        ],
    ));
};