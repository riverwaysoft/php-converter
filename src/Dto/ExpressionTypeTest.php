<?php

namespace Riverwaysoft\PhpConverter\Dto;

use PHPUnit\Framework\TestCase;

class ExpressionTypeTest extends TestCase
{
    public function testEquality(): void
    {
        $this->assertTrue(ExpressionType::class()->equals(ExpressionType::class()));
        $this->assertTrue(ExpressionType::enum()->equals(ExpressionType::enum()));
        $this->assertFalse(ExpressionType::enum()->equals(ExpressionType::class()));
    }

    public function testIsAnyEnum(): void
    {
        $this->assertTrue(ExpressionType::enum()->isAnyEnum());
        $this->assertTrue(ExpressionType::enumNonStandard()->isAnyEnum());
        $this->assertFalse(ExpressionType::class()->isAnyEnum());
    }
}
