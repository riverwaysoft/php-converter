<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Filter\Combinators;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PHPUnit\Framework\TestCase;
use Riverwaysoft\PhpConverter\Filter\FilterInterface;

class NotFilterTest extends TestCase
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

    public function testFilter(): void
    {
        $node = new Class_('test');

        $notFilter = new NotFilter($this->getAlwaysYes());
        $this->assertFalse($notFilter->isMatch($node));
    }
}
