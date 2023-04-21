<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\OutputWriter\EntityPerClassOutputWriter;

use PHPUnit\Framework\TestCase;
use Riverwaysoft\DtoConverter\Dto\DtoClassProperty;
use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\Dto\ExpressionType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnknownType;

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
