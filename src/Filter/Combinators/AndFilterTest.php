<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Filter\Combinators;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PHPUnit\Framework\TestCase;
use Riverwaysoft\PhpConverter\Filter\FilterInterface;

class AndFilterTest extends TestCase
{
    private function getAlwaysYes(): FilterInterface
    {
        return new class() implements FilterInterface {
            public function isMatch(ClassMethod|Class_|Enum_ $value): bool
            {
                return true;
            }
        };
    }

    private function getAlwaysNo(): FilterInterface
    {
        return new class() implements FilterInterface {
            public function isMatch(ClassMethod|Class_|Enum_ $value): bool
            {
                return false;
            }
        };
    }

    public function testFilter(): void
    {
        $node = new Class_('test');

        $yesAndNoFilter = new AndFilter([$this->getAlwaysYes(), $this->getAlwaysNo()]);
        $this->assertFalse($yesAndNoFilter->isMatch($node));

        $yesAndYesFilter = new AndFilter([$this->getAlwaysYes(), $this->getAlwaysYes()]);
        $this->assertTrue($yesAndYesFilter->isMatch($node));
    }
}
