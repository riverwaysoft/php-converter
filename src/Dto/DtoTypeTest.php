<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Dto;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpBaseType;
use Generator;

class DtoTypeTest extends TestCase
{
    #[DataProvider('provideEmptyDto')]
    public function testIsEmpty(DtoType $dto, bool $isEmptyExpected): void
    {
        $this->assertEquals($isEmptyExpected, $dto->isEmpty());
    }

    #[DataProvider('provideStringEnumDto')]
    public function testIsStringEnum(DtoType $dto, bool $isStringExpected): void
    {
        $this->assertEquals($isStringExpected, $dto->isStringEnum());
    }

    /** @return Generator<array{0: DtoType, 1: bool}> */
    public static function provideEmptyDto(): iterable
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
                ),
            ]
        ), false];
    }

    /** @return Generator<array{0: DtoType, 1: bool}> */
    public static function provideStringEnumDto(): iterable
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
