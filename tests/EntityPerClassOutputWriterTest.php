<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use Riverwaysoft\DtoConverter\Dto\DtoClassProperty;
use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\Dto\ExpressionType;
use Riverwaysoft\DtoConverter\Dto\SingleType;
use Riverwaysoft\DtoConverter\Dto\UnionType;
use Riverwaysoft\DtoConverter\OutputWriter\EntityPerClassOutputWriter\DtoTypeDependencyCalculator;
use Riverwaysoft\DtoConverter\OutputWriter\EntityPerClassOutputWriter\EntityPerClassOutputWriter;
use Riverwaysoft\DtoConverter\OutputWriter\EntityPerClassOutputWriter\KebabCaseFileNameGenerator;
use Riverwaysoft\DtoConverter\OutputWriter\EntityPerClassOutputWriter\TypeScriptImportGenerator;
use Spatie\Snapshots\MatchesSnapshots;

class EntityPerClassOutputWriterTest extends TestCase
{
    use MatchesSnapshots;

    public function testItIsEmptyByDefault()
    {
        $fileNameGenerator = new KebabCaseFileNameGenerator('.ts');
        $outputWriter = new EntityPerClassOutputWriter($fileNameGenerator, new TypeScriptImportGenerator($fileNameGenerator, new DtoTypeDependencyCalculator()));
        $this->assertEmpty($outputWriter->getTypes());
    }

    public function testGeneratingMultipleFiles()
    {
        $fileNameGenerator = new KebabCaseFileNameGenerator('.ts');
        $outputWriter = new EntityPerClassOutputWriter($fileNameGenerator, new TypeScriptImportGenerator($fileNameGenerator, new DtoTypeDependencyCalculator()));

        $outputWriter->writeType(
            'export type FullName = { /** skip for testing */ };',
            new DtoType(
                'FullName',
                ExpressionType::class(),
                properties: [
                    new DtoClassProperty(type: new SingleType('string'), name: 'firstName'),
                    new DtoClassProperty(type: new SingleType('string'), name: 'lastName'),
                ]
            )
        );

        $outputWriter->writeType(
            'export type Profile = { /** skip for testing */ };',
            new DtoType(
                'Profile',
                ExpressionType::class(),
                properties: [
                    new DtoClassProperty(
                        type: new UnionType(
                            types: [
                                new SingleType(name: 'null'),
                                new SingleType('string'),
                                new SingleType('FullName'),
                            ]
                        ),
                        name: 'name',
                    ),
                    new DtoClassProperty(type: new SingleType('int'), name: 'age'),
                ]
            )
        );

        $this->assertMatchesJsonSnapshot($outputWriter->getTypes());
    }
}
