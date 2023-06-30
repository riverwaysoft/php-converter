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

class DtoTypeDependencyCalculatorTest extends TestCase
{
    public function testBasicStructure(): void
    {
        $dependencyCalculator = new DtoTypeDependencyCalculator();

        $dto = new DtoType(
            name: 'Profile',
            expressionType: ExpressionType::class(),
            properties: [
                new DtoClassProperty(
                    type: new PhpUnionType(
                        types: [
                            PhpBaseType::null(),
                            PhpBaseType::string(),
                            new PhpUnknownType('FullName'),
                            new PhpUnknownType('Profile'), // Self reference
                        ]
                    ),
                    name: 'name',
                ),
                new DtoClassProperty(type: PhpBaseType::int(), name: 'age'),
            ]
        );

        $dependencies = $dependencyCalculator->getDependencies($dto);
        $this->assertCount(1, $dependencies);
        $this->assertEquals(new PhpUnknownType('FullName'), $dependencies[0]);
    }
}
