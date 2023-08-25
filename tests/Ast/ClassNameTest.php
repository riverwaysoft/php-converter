<?php

declare(strict_types=1);

namespace App\Tests\Ast;

use PHPUnit\Framework\TestCase;
use Riverwaysoft\PhpConverter\Ast\ClassName;

class FQNClass
{
}

class ClassNameTest extends TestCase
{
    public function testShortName(): void
    {
        $cn1 = new ClassName('Dto');
        $this->assertEquals('Dto', $cn1->getShortName());
        $this->assertFalse($cn1->isFQCN());

        $cn2 = new ClassName(FQNClass::class);
        $this->assertEquals('FQNClass', $cn2->getShortName());
        $this->assertTrue($cn2->isFQCN());
    }
}
