<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\ClassFilter;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PHPUnit\Framework\TestCase;

class AndFilterTest extends TestCase
{
    private function getAlwaysYes(): ClassFilterInterface
    {
        return new class () implements ClassFilterInterface {
            public function isMatch(Enum_|Class_ $class): bool
            {
                return true;
            }
        };
    }

    private function getAlwaysNo(): ClassFilterInterface
    {
        return new class () implements ClassFilterInterface {
            public function isMatch(Enum_|Class_ $class): bool
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
