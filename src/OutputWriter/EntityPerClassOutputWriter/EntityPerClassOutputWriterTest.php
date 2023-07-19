<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputWriter\EntityPerClassOutputWriter;

use PHPUnit\Framework\TestCase;
use Riverwaysoft\PhpConverter\Dto\DtoClassProperty;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\ExpressionType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;
use Riverwaysoft\PhpConverter\OutputGenerator\TypeScript\TypeScriptImportGenerator;
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
