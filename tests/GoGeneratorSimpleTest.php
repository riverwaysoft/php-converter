<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use Riverwaysoft\DtoConverter\Ast\Converter;
use Riverwaysoft\DtoConverter\Language\TypeScript\ClassNameTypeResolver;
use Riverwaysoft\DtoConverter\OutputWriter\SingleFileOutputWriter\SingleFileOutputWriter;
use Spatie\Snapshots\Drivers\TextDriver;
use Spatie\Snapshots\MatchesSnapshots;

class GoGeneratorSimpleTest extends TestCase
{
    use MatchesSnapshots;

    private string $codePhp = <<<'CODE'
<?php

class Category
{
    public string $id;
    public string $title;
    public int $rating;
    /** @var Recipe[] */
    public array $recipes;
}

class Recipe
{
    public string $id;
    public ?string $imageUrl;
    public string|null $url;
    public bool $isCooked;
    public float $weight;
}

class User
{
    public string $id;
    public ?User $bestFriend;
    /** @var User[] */
    public array $friends;
}
CODE;

    public function testDart(): void
    {
        $normalized = (new Converter())->convert([$this->codePhp]);
        $results = (new GoGeneratorSimple(new SingleFileOutputWriter('generated.go'), [new ClassNameTypeResolver()]))->generate($normalized);

        $this->assertCount(1, $results);

        $this->assertMatchesSnapshot($results[0]->getContent(), new class () extends TextDriver {
            public function extension(): string
            {
                return 'go';
            }
        });
    }
}
