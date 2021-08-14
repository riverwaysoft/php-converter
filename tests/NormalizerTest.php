<?php

declare(strict_types=1);

use App\Normalizer;
use App\Language\TypeScriptGenerator;
use PHPUnit\Framework\TestCase;
use App\Testing\TypeScriptSnapshotComparator;
use Spatie\Snapshots\MatchesSnapshots;

class NormalizerTest extends TestCase
{
    use MatchesSnapshots;

    private $codeAttribute = <<<'CODE'
<?php

class UserCreate {
    /** @var string[] */
    public array $achievements;
    public ?string $name;
    public int|string|float $age;
    public bool|null $isApproved;
    public float $latitude;
    public float $longitude;
    public mixed $mixed;
}

class CloudNotify {
    public function __construct(public string $id, public string $fcmToken)
    {
    }
}
CODE;

    private $codeRecursiveDto = <<<'CODE'
<?php

class UserCreate {
    public string $id;
    public ?Profile $profile;
}

class FullName {
    public string $firstName;
    public string $lastName;
}

class Profile {
    public FullName|null|string $name;
    public int $age;
}
CODE;


    public function testNormalization(): void
    {
        $normalized = (Normalizer::factory())->normalize($this->codeAttribute);
        $this->assertMatchesJsonSnapshot($normalized->getList());
    }

    public function testConvertAnnotations()
    {
        $normalized = (Normalizer::factory())->normalize($this->codeAttribute);
        $this->assertMatchesSnapshot((new TypeScriptGenerator())->generate($normalized), new TypeScriptSnapshotComparator());
    }

    public function testNestedDtoNormalize(): void
    {
        $normalized = (Normalizer::factory())->normalize($this->codeRecursiveDto);
        $this->assertMatchesJsonSnapshot($normalized->getList());
    }

    public function testNestedDtoConvert(): void
    {
        $normalized = (Normalizer::factory())->normalize($this->codeRecursiveDto);
        $this->assertMatchesSnapshot((new TypeScriptGenerator())->generate($normalized), new TypeScriptSnapshotComparator());
    }
}
