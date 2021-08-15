<?php

declare(strict_types=1);

namespace App\Tests;

use Riverwaysoft\DtoConverter\CodeProvider\FileSystemCodeProvider;
use Riverwaysoft\DtoConverter\CodeProvider\InlineCodeProvider;
use Riverwaysoft\DtoConverter\Converter;
use Riverwaysoft\DtoConverter\Language\TypeScript\DateTimeTypeResolver;
use Riverwaysoft\DtoConverter\Language\TypeScript\TypeScriptGenerator;
use Riverwaysoft\DtoConverter\Normalizer;
use Riverwaysoft\DtoConverter\Testing\TypeScriptSnapshotComparator;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ConverterTest extends TestCase
{
    use MatchesSnapshots;

    private $codeWithDateTime = <<<'CODE'
<?php

class UserCreate
{
    public \DateTimeImmutable $createdAt;
    public DateTime $updatedAt;
    public ?DateTimeImmutable $promotedAt;
}

class UserCreateConstructor
{
    public function __construct(
       public DateTimeImmutable $createdAt,
       public \DateTime $updatedAt,
       public ?\DateTimeImmutable $promotedAt,
    )
    {
    
    }
}
CODE;

    public function testNormalization(): void
    {
        $converter = new Converter(Normalizer::factory(), new FileSystemCodeProvider(__DIR__ . '/fixtures'));
        $result = $converter->convert();
        $this->assertMatchesJsonSnapshot($result->getList());
        $this->assertMatchesSnapshot((new TypeScriptGenerator())->generate($result), new TypeScriptSnapshotComparator());
    }

    public function testNormalizationWithCustomTypeResolvers(): void
    {
        $converter = new Converter(Normalizer::factory(), new InlineCodeProvider([$this->codeWithDateTime]));
        $result = $converter->convert();
        $typeScriptGenerator = new TypeScriptGenerator([new DateTimeTypeResolver()]);
        $this->assertMatchesSnapshot(($typeScriptGenerator)->generate($result), new TypeScriptSnapshotComparator());
    }
}
