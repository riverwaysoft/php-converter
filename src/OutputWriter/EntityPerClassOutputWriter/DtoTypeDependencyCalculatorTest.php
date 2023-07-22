<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputWriter\EntityPerClassOutputWriter;

use PHPUnit\Framework\TestCase;
use Riverwaysoft\PhpConverter\Dto\DtoClassProperty;
use Riverwaysoft\PhpConverter\Dto\DtoEnumProperty;
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

        $dtoObject = new DtoType(
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
                new DtoClassProperty(type: new PhpUnknownType('Unknown'), name: 'unknownField'),
            ]
        );

        $dependencies = $dependencyCalculator->getDependencies($dtoObject);
        $this->assertCount(2, $dependencies);
        $this->assertEquals(new PhpUnknownType('FullName'), $dependencies[0]);
        $this->assertEquals(new PhpUnknownType('Unknown'), $dependencies[1]);

        $dtoEnum = new DtoType(
            name: 'UserRole',
            expressionType: ExpressionType::enum(),
            properties: [
                new DtoEnumProperty(
                    name: 'Admin',
                    value: 'Admin',
                ),
                new DtoEnumProperty(
                    name: 'User',
                    value: 'User',
                ),
            ]
        );

        $dependencies = $dependencyCalculator->getDependencies($dtoEnum);
        $this->assertEmpty($dependencies);
    }
}
