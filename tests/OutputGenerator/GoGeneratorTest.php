<?php

declare(strict_types=1);

namespace App\Tests\OutputGenerator;

use App\Tests\SnapshotComparator\GoSnapshotComparator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Riverwaysoft\PhpConverter\Ast\Converter;
use Riverwaysoft\PhpConverter\Ast\DtoVisitor;
use Riverwaysoft\PhpConverter\CodeProvider\FileSystemCodeProvider;
use Riverwaysoft\PhpConverter\Filter\PhpAttributeFilter;
use Riverwaysoft\PhpConverter\OutputGenerator\Go\GoOutputGenerator;
use Riverwaysoft\PhpConverter\OutputGenerator\Go\GoTypeResolver;
use Riverwaysoft\PhpConverter\OutputGenerator\UnknownTypeResolver\ClassNameTypeResolver;
use Riverwaysoft\PhpConverter\OutputGenerator\UnknownTypeResolver\DateTimeTypeResolver;
use Riverwaysoft\PhpConverter\OutputWriter\SingleFileOutputWriter\SingleFileOutputWriter;
use Spatie\Snapshots\MatchesSnapshots;

class GoGeneratorTest extends TestCase
{
    use MatchesSnapshots;

    private ?string $snapshotSubDirectory = null;

    private const TEMPLATES = [
        'testNormalization' => <<<'CODE'
<?php

class UserCreate {
    /** @var string[] */
    public array $achievements;
    /** @var int[][] */
    public array $matrix;
    public ?string $name;
    public string|string|null|null $duplicatesInType;
    public int $age;
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

/**
* @template T
 */
class Response {
    public function __construct(public mixed $data) {}
}
CODE,
        'nestedDtoConvert' => <<<'CODE'
<?php

class UserCreate {
    public string $id;
    public ?Profile $profile;
    public Me $me;
}

class Me {
    public UserCreate $request;
}

class FullName {
    public string $firstName;
    public string $lastName;
}

class Profile {
    public FullName|null $name;
    public int $age;
}
CODE,
        'useTypeEnum' => <<<'CODE'
<?php

use MyCLabs\Enum\Enum;

final class ColorEnum extends Enum
{
    private const RED = 0;
    private const GREEN = 1;
    private const BLUE = 2;
}

final class RoleEnum extends Enum
{
    private const ADMIN = 'admin';
    private const READER = 'reader';
    private const EDITOR = 'editor';
}

class User
{
    public string $id;
    public ColorEnum $themeColor;
    public RoleEnum $role;
}
CODE,
        'useDateTime' => <<<'CODE'
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
CODE,
        'unknownType' => <<<'CODE'
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
CODE,
        'dtoConstant' => <<<'CODE'
<?php

#[Dto]
class A
{
    public const SOME_CONSTANT = 1;
    public \DateTimeImmutable $createdAt;
}

#[Dto]
final class GenderEnum extends Enum
{
    public const UNKNOWN = 2;
    private const MAN = 0;
    private const WOMAN = 1;
}

CODE,
        'backed' => <<<'CODE'
<?php

#[\Attribute(\Attribute::TARGET_CLASS)]
class Dto
{

}

#[Dto]
enum Color: int
{
    case RED = 0;
    case BLUE = 1;
    case WHITE = 2;
}

#[Dto]
enum Role: string
{
    case ADMIN = 'admin';
    case EDITOR = 'editor';
    case READER = 'reader';
}

#[Dto]
class User {
    public function __construct(public Color $color, public readonly int $user, public Role $role)
    {

    }

    public function getColor(): Color
    {
        return $this->color;
    }

    public function getUser(): int
    {
        return $this->user;
    }
}
CODE,
    ];

    private GoOutputGenerator $gen1; // Only with ClassNameTypeResolver

    private GoOutputGenerator $gen2; // Without ClassNameTypeResolver

    private GoOutputGenerator $gen3; // With DateTimeTypeResolver and ClassNameTypeResolver

    protected function getSnapshotDirectory(): string
    {
        if ($this->snapshotSubDirectory === null) {
            return (
                dirname((new ReflectionClass($this))->getFileName()) .
                DIRECTORY_SEPARATOR . '__snapshots__'
            );
        }

        return (
            dirname((new ReflectionClass($this))->getFileName()) .
            DIRECTORY_SEPARATOR . '__snapshots__' . DIRECTORY_SEPARATOR . $this->snapshotSubDirectory
        );
    }

    protected function setUp(): void
    {
        $this->snapshotSubDirectory = 'GoGeneratorTest/' . $this->name();

        $this->gen1 = new GoOutputGenerator(
            outputWriter: new SingleFileOutputWriter('generated.go'),
            resolver: new GoTypeResolver([new ClassNameTypeResolver()]),
        );

        $this->gen2 = new GoOutputGenerator(
            outputWriter: new SingleFileOutputWriter('generated.go'),
            resolver: new GoTypeResolver([]),
        );

        $this->gen3 = new GoOutputGenerator(
            outputWriter: new SingleFileOutputWriter('generated.go'),
            resolver: new GoTypeResolver([new ClassNameTypeResolver(), new DateTimeTypeResolver()])
        );
    }

    public function testNormalization(): void
    {
        $converted = (new Converter([new DtoVisitor()]))->convert([self::TEMPLATES['testNormalization']]);
        $this->assertMatchesJsonSnapshot($converted->dtoList->getList());
        $results = $this->gen2->generate($converted);
        $this->assertCount(1, $results);
        $this->assertMatchesSnapshot($results[0]->getContent(), new GoSnapshotComparator());
    }

    public function testNestedDtoConvert(): void
    {
        $converted = (new Converter([new DtoVisitor()]))->convert([self::TEMPLATES['nestedDtoConvert']]);
        $results = $this->gen1->generate($converted);
        $this->assertCount(1, $results);
        $this->assertMatchesSnapshot($results[0]->getContent(), new GoSnapshotComparator());
    }

    public function testUseTypeEnum(): void
    {
        $normalized = (new Converter([new DtoVisitor()]))->convert([self::TEMPLATES['useTypeEnum']]);
        $results = $this->gen1->generate($normalized);
        $this->assertCount(1, $results);
        $this->assertMatchesSnapshot($results[0]->getContent(), new GoSnapshotComparator());
    }

    public function testNormalizationDirectory(): void
    {
        $fileProvider = FileSystemCodeProvider::phpFiles(__DIR__ . '/Fixtures');
        $result = (new Converter([new DtoVisitor()]))->convert($fileProvider->getListings());
        $this->assertMatchesJsonSnapshot($result->dtoList->getList());
        $results = $this->gen1->generate($result);
        $this->assertCount(1, $results);
        $this->assertMatchesSnapshot($results[0]->getContent(), new GoSnapshotComparator());
    }

    public function testNormalizationWithCustomTypeResolvers(): void
    {
        $converted = (new Converter([new DtoVisitor()]))->convert([self::TEMPLATES['useDateTime']]);
        $results = $this->gen3->generate($converted);
        $this->assertCount(1, $results);
        $this->assertMatchesSnapshot($results[0]->getContent(), new GoSnapshotComparator());
    }

    public function testUnknownTypeThrows(): void
    {
        $visitors = [new DtoVisitor(new PhpAttributeFilter('Dto'))];
        $result = (new Converter($visitors))->convert([self::TEMPLATES['unknownType']]);
        $this->expectExceptionMessage('PHP Type B is not supported. PHP class: A');
        $this->gen3->generate($result);
    }

    public function testDtoConstantDoesntThrow(): void
    {
        $visitors = [new DtoVisitor(new PhpAttributeFilter('Dto'))];
        $converted = (new Converter($visitors))->convert([self::TEMPLATES['dtoConstant']]);
        $results = $this->gen3->generate($converted);
        $this->assertMatchesSnapshot($results[0]->getContent(), new GoSnapshotComparator());
    }

    public function testPhp81SuccessWhenBacked(): void
    {
        $visitors = [new DtoVisitor(new PhpAttributeFilter('Dto'))];
        $result = (new Converter($visitors))->convert([self::TEMPLATES['backed']]);
        $results = $this->gen1->generate($result);
        $this->assertCount(1, $results);
        $this->assertMatchesSnapshot($results[0]->getContent(), new GoSnapshotComparator());
    }
}
