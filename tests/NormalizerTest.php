<?php

declare(strict_types=1);

use App\Normalizer;
use App\Language\TypeScriptGenerator;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class NormalizerTest extends TestCase
{
    use MatchesSnapshots;

    private $codeAttribute = <<<'CODE'
<?php

#[\Attribute]
class Dto {}

#[Dto]
class UserCreate {
  public ?string $name;
  public int|string|float $age;
  public bool|null $isApproved;
  public float $latitude;
  public float $longitude;
  public array $achievements;
  public mixed $mixed;
}

class NoAttr {}

#[Dto]
class CloudNotify {
    public function __construct(public string $id, public string $fcmToken)
    {
    }
}
CODE;


    private $codePhpDoc = <<<'CODE'
<?php

/**
 * @Dto
 */
class UserCreate {
  public ?string $name;
  public int|string|float $age;
  public bool|null $isApproved;
  public float $latitude;
  public float $longitude;
  public array $achievements;
  public mixed $mixed;
}

class Test {}
CODE;


    public function testNormalization(): void
    {
        $normalized = (Normalizer::factory())->normalize($this->codeAttribute);
        $this->assertMatchesJsonSnapshot($normalized->getList());
    }

    public function testConvertingToTypeScript()
    {
        $normalized = (Normalizer::factory())->normalize($this->codeAttribute);
        $this->assertMatchesTextSnapshot((new TypeScriptGenerator())->generate($normalized));
    }

    public function testConvertintToTypeScript(): void
    {
        $normalized = (Normalizer::factory())->normalize($this->codePhpDoc);
        $this->assertMatchesTextSnapshot((new TypeScriptGenerator())->generate($normalized));
    }
}


