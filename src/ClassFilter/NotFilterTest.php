<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\ClassFilter;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PHPUnit\Framework\TestCase;

class NotFilterTest extends TestCase
{
    private function getAlwaysYes(): ClassFilterInterface
    {
        return new class() implements ClassFilterInterface {
            public function isMatch(Enum_|Class_ $class): bool
            {
                return true;
            }
        };
    }

    public function testFilter(): void
    {
        $node = new Class_('test');

        $notFilter = new NotFilter($this->getAlwaysYes());
        $this->assertFalse($notFilter->isMatch($node));
    }
}
