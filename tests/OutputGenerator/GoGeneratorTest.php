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
use Riverwaysoft\PhpConverter\OutputGenerator\Go\GoGeneratorOptions;
use Riverwaysoft\PhpConverter\OutputGenerator\Go\GoOutputGenerator;
use Riverwaysoft\PhpConverter\OutputGenerator\Go\GoTypeResolver;
use Riverwaysoft\PhpConverter\OutputGenerator\UnknownTypeResolver\ClassNameTypeResolver;
use Riverwaysoft\PhpConverter\OutputGenerator\UnknownTypeResolver\DateTimeTypeResolver;
use Riverwaysoft\PhpConverter\OutputWriter\SingleFileOutputWriter\SingleFileOutputWriter;
use Spatie\Snapshots\MatchesSnapshots;

class GoGeneratorTest
    extends TestCase
{
    use MatchesSnapshots;

    private ?string $snapshotSubDirectory = null;

    protected function getSnapshotDirectory(): string
    {
        if ($this->snapshotSubDirectory === null) {
            return (
                dirname(
                    (new ReflectionClass($this))->getFileName()
                ).
                DIRECTORY_SEPARATOR.
                '__snapshots__'
            );
        }

        return (
            dirname(
                (new ReflectionClass($this))->getFileName()
            ).
            DIRECTORY_SEPARATOR.
            '__snapshots__'.
            DIRECTORY_SEPARATOR.
            $this->snapshotSubDirectory
        );
    }

    public function testNormalizationTsDefault(): void
    {
        $this->snapshotSubDirectory = 'GoGeneratorTest_testNormalizationTsDefault';
        $codeAttribute = <<<'CODE'
<?php

class UserCreate {
    /** @var string[] */
    public array $achievements;
    /** @var int[][] */
    public array $matrix;
    public ?string $name;
    public string|int|string|null|null $duplicatesInType;
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

/**
* @template T
 */
class Response {
    /**
    * @param T $data
    */
    public function __construct(
        public $data,
    ) {}
}
CODE;

        $normalized = (new Converter([new DtoVisitor()]))->convert(
            [$codeAttribute]
        );
        $this->assertMatchesJsonSnapshot($normalized->dtoList->getList());
        $results = (new GoOutputGenerator(
            outputWriter: new SingleFileOutputWriter('generated.go'),
            typeResolver: new GoTypeResolver([]),
        ))->generate($normalized);
        $this->assertCount(1, $results);
        $this->assertMatchesSnapshot(
            $results[0]->getContent(),
            new GoSnapshotComparator()
        );
    }

    public function testNestedDtoConvert(): void
    {
        $this->snapshotSubDirectory = 'GoGeneratorTest_testNestedDtoConvert';
        $codeNestedDto = <<<'CODE'
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

        $normalized = (new Converter([new DtoVisitor()]))->convert(
            [$codeNestedDto]
        );
        $results = (new GoOutputGenerator(
            outputWriter: new SingleFileOutputWriter('generated.go'),
            typeResolver: new GoTypeResolver(
                [new ClassNameTypeResolver()]
            ),
        ))->generate($normalized);
        $this->assertCount(1, $results);
        $this->assertMatchesSnapshot(
            $results[0]->getContent(),
            new GoSnapshotComparator()
        );
    }

    public function testUseTypeOverEnumTs(): void
    {
        $this->snapshotSubDirectory = 'GoGeneratorTest_testUseTypeOverEnumTs';
        $code = <<<'CODE'
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
CODE;

        $normalized = (new Converter([new DtoVisitor()]))->convert([$code]);
        $typeScriptGenerator = new GoOutputGenerator(
            outputWriter: new SingleFileOutputWriter('generated.go'),
            typeResolver: new GoTypeResolver([new ClassNameTypeResolver()]),
            propertyNameGenerators: [],
        );
        $results = $typeScriptGenerator->generate($normalized);
        $this->assertCount(1, $results);
        $this->assertMatchesSnapshot(
            $results[0]->getContent(),
            new GoSnapshotComparator()
        );
    }

    public function testNormalizationDirectory(): void
    {
        $this->snapshotSubDirectory = 'GoGeneratorTest_testNormalizationDirectory';
        $converter = new Converter([new DtoVisitor()]);
        $fileProvider = FileSystemCodeProvider::phpFiles(__DIR__.'/Fixtures');
        $result = $converter->convert($fileProvider->getListings());
        $this->assertMatchesJsonSnapshot($result->dtoList->getList());
        $results = (new GoOutputGenerator(
            outputWriter: new SingleFileOutputWriter('generated.go'),
            typeResolver: new GoTypeResolver([new ClassNameTypeResolver()]),
        )
        )->generate($result);
        $this->assertCount(1, $results);
        $this->assertMatchesSnapshot(
            $results[0]->getContent(),
            new GoSnapshotComparator()
        );
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

        $converter = new Converter([new DtoVisitor()]);
        $result = $converter->convert([$codeWithDateTime]);
        $typeScriptGenerator = new GoOutputGenerator(
            outputWriter: new SingleFileOutputWriter('generated.go'),
            typeResolver: new GoTypeResolver(
                [new ClassNameTypeResolver(), new DateTimeTypeResolver()]
            )
        );
        $results = ($typeScriptGenerator)->generate($result);
        $this->assertCount(1, $results);
        $this->assertMatchesSnapshot(
            $results[0]->getContent(),
            new GoSnapshotComparator()
        );
    }

    public function testUnknownTypeThrows(): void
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

        $converter = new Converter(
            [new DtoVisitor(new PhpAttributeFilter('Dto'))]
        );
        $result = $converter->convert([$codeWithDateTime]);
        $typeScriptGenerator = new GoOutputGenerator(
            new SingleFileOutputWriter('generated.go'),
            new GoTypeResolver(
                [new ClassNameTypeResolver(), new DateTimeTypeResolver()]
            )
        );

        $this->expectExceptionMessage(
            'PHP Type B is not supported. PHP class: A'
        );
        $typeScriptGenerator->generate($result);
    }

    public function testDtoConstantDoesntThrow(): void
    {
        $codeWithDateTime = <<<'CODE'
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
    public const UNKNOWN = null;
    private const MAN = 0;
    private const WOMAN = 1;
}

CODE;

        $converter = new Converter(
            [new DtoVisitor(new PhpAttributeFilter('Dto'))]
        );
        $result = $converter->convert([$codeWithDateTime]);
        $typeScriptGenerator = new GoOutputGenerator(
            new SingleFileOutputWriter('generated.go'),
            new GoTypeResolver(
                [new ClassNameTypeResolver(), new DateTimeTypeResolver()]
            )
        );

        $results = $typeScriptGenerator->generate($result);

        $this->assertMatchesSnapshot(
            $results[0]->getContent(),
            new GoSnapshotComparator()
        );
    }

    public function testPhp81SuccessWhenBacked(): void
    {
        $this->snapshotSubDirectory = 'GoGeneratorTest_testPhp81SuccessWhenBacked';
        $codeWithDateTime = <<<'CODE'
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
CODE;

        $converter = new Converter(
            [new DtoVisitor(new PhpAttributeFilter('Dto'))]
        );
        $result = $converter->convert([$codeWithDateTime]);

        $typeScriptGenerator = new GoOutputGenerator(
            outputWriter: new SingleFileOutputWriter('generated.go'),
            typeResolver: new GoTypeResolver([
                new ClassNameTypeResolver(),
            ]),
        );
        $results = ($typeScriptGenerator)->generate($result);
        $this->assertCount(1, $results);
        $this->assertMatchesSnapshot(
            $results[0]->getContent(),
            new GoSnapshotComparator()
        );
    }
}
