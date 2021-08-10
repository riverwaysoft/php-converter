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


    private $codeRecursiveDto = <<<'CODE'
<?php

#[\Attribute]
class Dto {}


#[Dto]
class UserCreate {
    public string $id;
    public ?Profile $profile;
}

#[Dto]
class FullName {
    public string $firstName;
    public string $lastName;
}

#[Dto]
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
        $this->assertMatchesTextSnapshot((new TypeScriptGenerator())->generate($normalized));
    }

    public function testConvertPhpDoc(): void
    {
        $normalized = (Normalizer::factory())->normalize($this->codePhpDoc);
        $this->assertMatchesTextSnapshot((new TypeScriptGenerator())->generate($normalized));
    }

    public function testNestedDtoNormalize(): void
    {
        $normalized = (Normalizer::factory())->normalize($this->codeRecursiveDto);
        $this->assertMatchesJsonSnapshot($normalized->getList());
    }

    public function testNestedDtoConvert(): void
    {
        $normalized = (Normalizer::factory())->normalize($this->codeRecursiveDto);
        $this->assertMatchesTextSnapshot((new TypeScriptGenerator())->generate($normalized));
    }
}
