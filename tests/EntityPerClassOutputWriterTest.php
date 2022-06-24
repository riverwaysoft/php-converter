<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use Riverwaysoft\DtoConverter\Dto\DtoClassProperty;
use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\Dto\ExpressionType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnknownType;
use Riverwaysoft\DtoConverter\Language\TypeScript\TypeScriptImportGenerator;
use Riverwaysoft\DtoConverter\OutputWriter\EntityPerClassOutputWriter\DtoTypeDependencyCalculator;
use Riverwaysoft\DtoConverter\OutputWriter\EntityPerClassOutputWriter\EntityPerClassOutputWriter;
use Riverwaysoft\DtoConverter\OutputWriter\EntityPerClassOutputWriter\KebabCaseFileNameGenerator;
use Spatie\Snapshots\MatchesSnapshots;

class EntityPerClassOutputWriterTest extends TestCase
{
    use MatchesSnapshots;

    public function testItIsEmptyByDefault(): void
    {
        $fileNameGenerator = new KebabCaseFileNameGenerator('.ts');
        $outputWriter = new EntityPerClassOutputWriter($fileNameGenerator, new TypeScriptImportGenerator($fileNameGenerator, new DtoTypeDependencyCalculator()));
        $this->assertEmpty($outputWriter->getTypes());
    }

    public function testGeneratingMultipleFiles(): void
    {
        $fileNameGenerator = new KebabCaseFileNameGenerator('.ts');
        $outputWriter = new EntityPerClassOutputWriter($fileNameGenerator, new TypeScriptImportGenerator($fileNameGenerator, new DtoTypeDependencyCalculator()));

        $outputWriter->writeType(
            'export type FullName = { /** skip for testing */ };',
            new DtoType(
                'FullName',
                ExpressionType::class(),
                properties: [
                    new DtoClassProperty(type: PhpBaseType::string(), name: 'firstName'),
                    new DtoClassProperty(type: PhpBaseType::string(), name: 'lastName'),
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
                        type: new PhpUnionType(
                            types: [
                                PhpBaseType::null(),
                                PhpBaseType::string(),
                                new PhpUnknownType('FullName'),
                            ]
                        ),
                        name: 'name',
                    ),
                    new DtoClassProperty(type: PhpBaseType::int(), name: 'age'),
                ]
            )
        );

        $this->assertMatchesJsonSnapshot($outputWriter->getTypes());
    }
}
