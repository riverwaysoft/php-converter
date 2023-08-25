<?php

declare(strict_types=1);

namespace App\Tests\Bridge\ApiPlatform;

use PHPUnit\Framework\TestCase;
use Riverwaysoft\PhpConverter\Bridge\ApiPlatform\ApiPlatformIriGenerator;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;

class ApiPlatformIriGeneratorTest extends TestCase
{
    public function testGeneration(): void
    {
        $generator = new ApiPlatformIriGenerator();

        $this->assertEquals('food_categories', $generator->generate((new PhpUnknownType('FoodCategory'))->getName()));
        $this->assertEquals('users', $generator->generate((new PhpUnknownType('User'))->getName()));
        $this->assertEquals('food_analyze_rule_categories', $generator->generate((new PhpUnknownType('FoodAnalyzeRuleCategory'))->getName()));
    }
}
