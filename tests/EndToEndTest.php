<?php

declare(strict_types=1);

namespace App\Tests;

use PhpParser\Node\Stmt\Class_;
use Riverwaysoft\DtoConverter\ClassFilter\ClassFilterInterface;
use Riverwaysoft\DtoConverter\ClassFilter\DocBlockCommentFilter;
use Riverwaysoft\DtoConverter\ClassFilter\NegationFilter;
use Riverwaysoft\DtoConverter\ClassFilter\PhpAttributeFilter;
use Riverwaysoft\DtoConverter\CodeProvider\FileSystemCodeProvider;
use Riverwaysoft\DtoConverter\Converter;
use Riverwaysoft\DtoConverter\Language\Dart\DartGenerator;
use Riverwaysoft\DtoConverter\Language\TypeScript\DateTimeTypeResolver;
use Riverwaysoft\DtoConverter\Language\TypeScript\TypeScriptGenerator;
use Riverwaysoft\DtoConverter\Normalizer;
use Riverwaysoft\DtoConverter\Testing\DartSnapshotComparator;
use PHPUnit\Framework\TestCase;
use Riverwaysoft\DtoConverter\Testing\TypeScriptSnapshotComparator;
use Spatie\Snapshots\MatchesSnapshots;

class EndToEndTest extends TestCase
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
    public function __construct(public string $id, public string|null $fcmToken, string $notPublicIgnoreMe)
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


    public function testNormalizationDirectory(): void
    {
        $converter = new Converter(Normalizer::factory());
        $fileProvider = new FileSystemCodeProvider('/\.php$/');
        $result = $converter->convert($fileProvider->getListings(__DIR__ . '/fixtures'));
        $this->assertMatchesJsonSnapshot($result->getList());
        $this->assertMatchesSnapshot((new TypeScriptGenerator())->generate($result), new TypeScriptSnapshotComparator());
    }

    public function testNormalizationWithCustomTypeResolvers(): void
    {
        $codeWithDateTime = <<<'CODE'
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

        $converter = new Converter(Normalizer::factory());
        $result = $converter->convert([$codeWithDateTime]);
        $typeScriptGenerator = new TypeScriptGenerator([new DateTimeTypeResolver()]);
        $this->assertMatchesSnapshot(($typeScriptGenerator)->generate($result), new TypeScriptSnapshotComparator());
    }

    public function testFilterClassesByDocBlock(): void
    {
        $codeWithDateTime = <<<'CODE'
<?php

/** @DTO */
final class ColorEnum extends Enum
{
    private const RED = 0;
    private const GREEN = 1;
    private const BLUE = 2;
}

/** @DTO */
class Category
{
    public string $id;
    public string $title;
    public int $rating;
    /** @var Recipe[] */
    public array $recipes;
}

/** @DTO */
class Recipe
{
    public string $id;
    public ?string $imageUrl;
    public string|null $url;
    public bool $isCooked;
    public float $weight;
}

class IgnoreMe {

}

/** @DTO */
class User
{
    public string $id;
    public ?User $bestFriend;
    /** @var User[] */
    public array $friends;
    public ColorEnum $themeColor;
}


CODE;

        $converter = new Converter(Normalizer::factory(new DocBlockCommentFilter('@DTO')));
        $result = $converter->convert([$codeWithDateTime]);

        $this->assertTrue($result->hasDtoWithType('User'));
        $this->assertTrue($result->hasDtoWithType('Recipe'));
        $this->assertTrue($result->hasDtoWithType('Category'));
        $this->assertTrue($result->hasDtoWithType('ColorEnum'));
        $this->assertFalse($result->hasDtoWithType('IgnoreMe'));
    }

    public function testExcludeFilterClassesByDocBlock(): void
    {
        $codeWithDateTime = <<<'CODE'
<?php

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

/** @ignore */
class IgnoreMe {

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

        $classesWithoutIgnoreFilter = new NegationFilter(new DocBlockCommentFilter('@ignore'));
        $converter = new Converter(Normalizer::factory($classesWithoutIgnoreFilter));
        $result = $converter->convert([$codeWithDateTime]);

        $this->assertTrue($result->hasDtoWithType('User'));
        $this->assertTrue($result->hasDtoWithType('Recipe'));
        $this->assertTrue($result->hasDtoWithType('Category'));
        $this->assertTrue($result->hasDtoWithType('ColorEnum'));
        $this->assertFalse($result->hasDtoWithType('IgnoreMe'));
    }


    public function testFilterClassesByPhpAttribute(): void
    {
        $codeWithDateTime = <<<'CODE'
<?php

#[\Attribute(\Attribute::TARGET_CLASS)]
class Dto
{

}

#[Dto]
final class ColorEnum extends Enum
{
    private const RED = 0;
    private const GREEN = 1;
    private const BLUE = 2;
}

#[Dto]
class Category
{
    public string $id;
    public string $title;
    public int $rating;
    /** @var Recipe[] */
    public array $recipes;
}

#[Dto]
class Recipe
{
    public string $id;
    public ?string $imageUrl;
    public string|null $url;
    public bool $isCooked;
    public float $weight;
}

class IgnoreMe {

}

#[Dto]
class User
{
    public string $id;
    public ?User $bestFriend;
    /** @var User[] */
    public array $friends;
    public ColorEnum $themeColor;
}


CODE;

        $converter = new Converter(Normalizer::factory(new PhpAttributeFilter('Dto')));
        $result = $converter->convert([$codeWithDateTime]);

        $this->assertTrue($result->hasDtoWithType('User'));
        $this->assertTrue($result->hasDtoWithType('Recipe'));
        $this->assertTrue($result->hasDtoWithType('Category'));
        $this->assertTrue($result->hasDtoWithType('ColorEnum'));
        $this->assertFalse($result->hasDtoWithType('IgnoreMe'));
    }
}
