<?php

declare(strict_types=1);

namespace App\Tests;

use Riverwaysoft\DtoConverter\Bridge\ApiPlatform\ApiPlatformInputTypeResolver;
use Riverwaysoft\DtoConverter\ClassFilter\DocBlockCommentFilter;
use Riverwaysoft\DtoConverter\ClassFilter\NegationFilter;
use Riverwaysoft\DtoConverter\ClassFilter\PhpAttributeFilter;
use Riverwaysoft\DtoConverter\CodeProvider\FileSystemCodeProvider;
use Riverwaysoft\DtoConverter\Converter;
use Riverwaysoft\DtoConverter\Language\Dart\DartGenerator;
use Riverwaysoft\DtoConverter\Language\Dart\DartImportGenerator;
use Riverwaysoft\DtoConverter\Language\TypeScript\ClassNameTypeResolver;
use Riverwaysoft\DtoConverter\Language\TypeScript\DateTimeTypeResolver;
use Riverwaysoft\DtoConverter\Language\TypeScript\TypeScriptGenerator;
use Riverwaysoft\DtoConverter\Language\TypeScript\TypeScriptImportGenerator;
use Riverwaysoft\DtoConverter\Normalizer;
use Riverwaysoft\DtoConverter\OutputWriter\EntityPerClassOutputWriter\DtoTypeDependencyCalculator;
use Riverwaysoft\DtoConverter\OutputWriter\EntityPerClassOutputWriter\EntityPerClassOutputWriter;
use Riverwaysoft\DtoConverter\OutputWriter\EntityPerClassOutputWriter\KebabCaseFileNameGenerator;
use Riverwaysoft\DtoConverter\OutputWriter\EntityPerClassOutputWriter\SnakeCaseFileNameGenerator;
use Riverwaysoft\DtoConverter\OutputWriter\SingleFileOutputWriter\SingleFileOutputWriter;
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

    private $codeNestedDto = <<<'CODE'
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
        $results = (new TypeScriptGenerator(new SingleFileOutputWriter('generated.ts')))->generate($normalized);
        $this->assertCount(1, $results);
        $this->assertMatchesSnapshot($results[0]->getContent(), new TypeScriptSnapshotComparator());
    }

    public function testNestedDtoNormalize(): void
    {
        $normalized = (Normalizer::factory())->normalize($this->codeNestedDto);
        $this->assertMatchesJsonSnapshot($normalized->getList());
    }

    public function testNestedDtoConvert(): void
    {
        $normalized = (Normalizer::factory())->normalize($this->codeNestedDto);
        $results = (new TypeScriptGenerator(new SingleFileOutputWriter('generated.ts'), [new ClassNameTypeResolver()]))->generate($normalized);
        $this->assertCount(1, $results);
        $this->assertMatchesSnapshot($results[0]->getContent(), new TypeScriptSnapshotComparator());
    }

    public function testDart()
    {
        $normalized = (Normalizer::factory())->normalize($this->codeDart);
        $results = (new DartGenerator(new SingleFileOutputWriter('generated.dart'), [new ClassNameTypeResolver()]))->generate($normalized);
        $this->assertCount(1, $results);
        $this->assertMatchesSnapshot($results[0]->getContent(), new DartSnapshotComparator());
    }


    public function testNormalizationDirectory(): void
    {
        $converter = new Converter(Normalizer::factory());
        $fileProvider = new FileSystemCodeProvider('/\.php$/');
        $result = $converter->convert($fileProvider->getListings(__DIR__ . '/Fixtures'));
        $this->assertMatchesJsonSnapshot($result->getList());
        $results = (new TypeScriptGenerator(new SingleFileOutputWriter('generated.ts'), [new ClassNameTypeResolver()]))->generate($result);
        $this->assertCount(1, $results);
        $this->assertMatchesSnapshot($results[0]->getContent(), new TypeScriptSnapshotComparator());
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
        $typeScriptGenerator = new TypeScriptGenerator(new SingleFileOutputWriter('generated.ts'), [new ClassNameTypeResolver(), new DateTimeTypeResolver()]);
        $results = ($typeScriptGenerator)->generate($result);
        $this->assertCount(1, $results);
        $this->assertMatchesSnapshot($results[0]->getContent(), new TypeScriptSnapshotComparator());
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

    public function testEntityPerClassOutputWriterTypeScript()
    {
        $normalized = (Normalizer::factory())->normalize($this->codeNestedDto);

        $fileNameGenerator = new KebabCaseFileNameGenerator('.ts');
        $typeScriptGenerator = new TypeScriptGenerator(
            new EntityPerClassOutputWriter(
                $fileNameGenerator,
                new TypeScriptImportGenerator(
                    $fileNameGenerator,
                    new DtoTypeDependencyCalculator()
                )
            ),
            [
                new ClassNameTypeResolver(),
            ]
        );
        $results = $typeScriptGenerator->generate($normalized);

        $this->assertCount(3, $results);

        $this->assertEquals("export type FullName = {
  firstName: string;
  lastName: string;
};", $results[0]->getContent());
        $this->assertEquals('full-name.ts', $results[0]->getRelativeName());

        $this->assertEquals("import { FullName } from './full-name';

export type Profile = {
  name: FullName | null | string;
  age: number;
};", $results[1]->getContent());
        $this->assertEquals('profile.ts', $results[1]->getRelativeName());

        $this->assertEquals("import { Profile } from './profile';

export type UserCreate = {
  id: string;
  profile: Profile | null;
};", $results[2]->getContent());
        $this->assertEquals('user-create.ts', $results[2]->getRelativeName());
    }

    public function testEntityPerClassOutputWriterDart()
    {
        $normalized = (Normalizer::factory())->normalize($this->codeNestedDto);

        $fileNameGenerator = new SnakeCaseFileNameGenerator('.dart');
        $typeScriptGenerator = new DartGenerator(
            new EntityPerClassOutputWriter(
                $fileNameGenerator,
                new DartImportGenerator(
                    $fileNameGenerator,
                    new DtoTypeDependencyCalculator()
                )
            ),
            [
                new ClassNameTypeResolver(),
            ]
        );
        $results = $typeScriptGenerator->generate($normalized);

        $this->assertCount(3, $results);

        $this->assertEquals("class FullName {
  final String firstName;
  final String lastName;

  FullName({
    required this.firstName,
    required this.lastName,
  })
}", $results[0]->getContent());
        $this->assertEquals('full_name.dart', $results[0]->getRelativeName());

        $this->assertEquals("import './full_name.dart';

class Profile {
  final String? name;
  final int age;

  Profile({
    this.name,
    required this.age,
  })
}", $results[1]->getContent());
        $this->assertEquals('profile.dart', $results[1]->getRelativeName());

        $this->assertEquals("import './profile.dart';

class UserCreate {
  final String id;
  final Profile? profile;

  UserCreate({
    required this.id,
    this.profile,
  })
}", $results[2]->getContent());
        $this->assertEquals('user_create.dart', $results[2]->getRelativeName());
    }
    public function testApiPlatformInput(): void
    {
        $codeWithDateTime = <<<'CODE'
<?php

use MyCLabs\Enum\Enum;

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

class Profile
{
    public string $firstName;
    public string $lastName;
}

#[Dto]
class ProfileOutput
{
    public string $firstName;
    public string $lastName;
}

class LocationEmbeddable {
  public function __construct(
    private float $lat,
    private $lan,
  ) {}
}

class Money {

}

#[Dto]
class UserCreateInput
{
    public Profile $profile;
    public ?DateTimeImmutable $promotedAt;
    public ColorEnum $userTheme;
    public Money $money;
    public LocationEmbeddable $location;
}

CODE;

        $converter = new Converter(Normalizer::factory(new PhpAttributeFilter('Dto')));
        $result = $converter->convert([$codeWithDateTime]);
        $typeScriptGenerator = new TypeScriptGenerator(
            new SingleFileOutputWriter('generated.ts'),
            [
                new DateTimeTypeResolver(),
                new ApiPlatformInputTypeResolver([
                    'LocationEmbeddable' => '{ lat: string; lan: string }',
                    'Money' => '{ currency: string; amount: number }',
                ]),
                new ClassNameTypeResolver(),
            ]
        );
        $results = ($typeScriptGenerator)->generate($result);
        $this->assertCount(1, $results);
        $this->assertMatchesSnapshot($results[0]->getContent(), new TypeScriptSnapshotComparator());
    }

    public function testUnkownTypeThrows(): void
    {
        $codeWithDateTime = <<<'CODE'
<?php

#[\Attribute(\Attribute::TARGET_CLASS)]
class Dto
{

}

#[Dto]
class A
{
    public \DateTimeImmutable $createdAt;
    public B $b;
}

class B {}
CODE;

        $converter = new Converter(Normalizer::factory(new PhpAttributeFilter('Dto')));
        $result = $converter->convert([$codeWithDateTime]);
        $typeScriptGenerator = new TypeScriptGenerator(new SingleFileOutputWriter('generated.ts'), [new ClassNameTypeResolver(), new DateTimeTypeResolver()]);

        $this->expectExceptionMessage('PHP Type B is not supported. PHP class: A');
        $results = ($typeScriptGenerator)->generate($result);
    }
}
