<?php

namespace Riverwaysoft\PhpConverter\Dto;

use PHPUnit\Framework\TestCase;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpBaseType;

class DtoTypeTest extends TestCase
{
    /** @dataProvider provideEmptyDto */
    public function testIsEmpty(DtoType $dto, bool $isEmptyExpected): void
    {
        $this->assertEquals($isEmptyExpected, $dto->isEmpty());
    }

    /** @dataProvider provideStringEnumDto */
    public function testIsStringEnum(DtoType $dto, bool $isStringExpected): void
    {
        $this->assertEquals($isStringExpected, $dto->isStringEnum());
    }

    /** @return \Generator<array{0: DtoType, 1: bool}> */
    public function provideEmptyDto(): iterable
    {
        yield [new DtoType(
            name: 'User',
            expressionType: ExpressionType::class(),
            properties: []
        ), true];

        yield [new DtoType(
            name: 'RoleEnum',
            expressionType: ExpressionType::enum(),
            properties: []
        ), true];

        yield [new DtoType(
            name: 'RoleEnum',
            expressionType: ExpressionType::enumNonStandard(),
            properties: []
        ), true];

        yield [new DtoType(
            name: 'User',
            expressionType: ExpressionType::class(),
            properties: [
                new DtoClassProperty(
                    type: PhpBaseType::string(),
                    name: 'name',
                )
            ]
        ), false];
    }

    /** @return \Generator<array{0: DtoType, 1: bool}> */
    public function provideStringEnumDto(): iterable
    {
        yield [new DtoType(
            name: 'RoleEnum',
            expressionType: ExpressionType::enum(),
            properties: [
                new DtoEnumProperty(name: 'content', value: 'content'),
                new DtoEnumProperty(name: 'admin', value: 'admin'),
            ]
        ), true];

        yield [new DtoType(
            name: 'RoleEnum',
            expressionType: ExpressionType::enumNonStandard(),
            properties: [
                new DtoEnumProperty(name: 'content', value: 'content'),
                new DtoEnumProperty(name: 'admin', value: 'admin'),
            ]
        ), true];

        yield [new DtoType(
            name: 'RoleEnum',
            expressionType: ExpressionType::enumNonStandard(),
            properties: [
                new DtoEnumProperty(name: 'content', value: 0),
                new DtoEnumProperty(name: 'admin', value: 1),
            ]
        ), false];
    }
}
