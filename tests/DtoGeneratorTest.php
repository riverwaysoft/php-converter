<?php

declare(strict_types=1);

use App\DtoGenerator;
use App\DtoToTypeScriptConverter;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class DtoGeneratorTest extends TestCase
{
    use MatchesSnapshots;

    private $code = <<<'CODE'
<?php

#[\Attribute]
class Dto {}

#[Dto]
class UserCreate {
  public string $name;
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

    public function testNormalization(): void
    {
        $normalized = (new DtoGenerator())->generate($this->code);
        $this->assertMatchesJsonSnapshot($normalized->getList());
    }

    public function testConvertingToTypeScript()
    {
        $normalized = (new DtoGenerator())->generate($this->code);
        $this->assertMatchesTextSnapshot((new DtoToTypeScriptConverter())->convert($normalized));
    }
}


