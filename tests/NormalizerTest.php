<?php

declare(strict_types=1);

namespace App\Tests;

use Riverwaysoft\DtoConverter\Language\Dart\DartGenerator;
use Riverwaysoft\DtoConverter\Language\TypeScript\TypeScriptGenerator;
use Riverwaysoft\DtoConverter\Normalizer;
use Riverwaysoft\DtoConverter\Testing\DartSnapshotComparator;
use PHPUnit\Framework\TestCase;
use Riverwaysoft\DtoConverter\Testing\TypeScriptSnapshotComparator;
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
    public function __construct(public string $id, public string|null $fcmToken)
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

    private $codeDart = <<<'CODE'
<?php

use MyCLabs\Enum\Enum;

final class ColorEnum extends Enum
{
    private const RED = 0;
    private const GREEN = 1;
    private const BLUE = 2;
}

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
    public ColorEnum $themeColor;
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

    public function testDart()
    {
        $normalized = (Normalizer::factory())->normalize($this->codeDart);
        $this->assertMatchesSnapshot((new DartGenerator())->generate($normalized), new DartSnapshotComparator());
    }
}
