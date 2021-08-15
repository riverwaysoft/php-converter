<?php

use App\CodeProvider\FileSystemCodeProvider;
use App\CodeProvider\InlineCodeProvider;
use App\Converter;
use App\Language\TypeScript\DateTimeTypeResolver;
use App\Language\TypeScript\TypeScriptGenerator;
use App\Normalizer;
use App\Testing\TypeScriptSnapshotComparator;
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
