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

class DtoTypeDependencyCalculatorTest extends TestCase
{
    public function testBasicStructure()
    {
        $dependencyCalculator = new DtoTypeDependencyCalculator();

        $dto = new DtoType(
            'Profile',
            ExpressionType::class(),
            properties: [
                new DtoClassProperty(
                    type: new UnionType(
                        types: [
                            new SingleType(name: 'null'),
                            new SingleType('string'),
                            new SingleType('FullName'),
                            new SingleType('Profile'), // Self reference
                        ]
                    ),
                    name: 'name',
                ),
                new DtoClassProperty(type: new SingleType('int'), name: 'age'),
            ]
        );

        $dependencies = $dependencyCalculator->getDependencies($dto);
        $this->assertCount(1, $dependencies);
    }
}
