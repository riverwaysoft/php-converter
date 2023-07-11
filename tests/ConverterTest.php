<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Riverwaysoft\PhpConverter\Ast\Converter;
use Riverwaysoft\PhpConverter\Ast\DtoVisitor;
use Riverwaysoft\PhpConverter\Bridge\Symfony\SymfonyControllerVisitor;
use Riverwaysoft\PhpConverter\ClassFilter\DocBlockCommentFilter;
use Riverwaysoft\PhpConverter\ClassFilter\NegationFilter;
use Riverwaysoft\PhpConverter\ClassFilter\PhpAttributeFilter;
use Spatie\Snapshots\MatchesSnapshots;

class ConverterTest extends TestCase
{
    use MatchesSnapshots;

    public function testNestedDtoNormalize(): void
    {
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

        $normalized = (new Converter([new DtoVisitor()]))->convert([$codeNestedDto]);
        $this->assertMatchesJsonSnapshot($normalized->dtoList->getList());
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

        $converter = new Converter([new DtoVisitor(new DocBlockCommentFilter('@DTO'))]);
        $result = $converter->convert([$codeWithDateTime]);

        $this->assertTrue($result->dtoList->hasDtoWithType('User'));
        $this->assertTrue($result->dtoList->hasDtoWithType('Recipe'));
        $this->assertTrue($result->dtoList->hasDtoWithType('Category'));
        $this->assertTrue($result->dtoList->hasDtoWithType('ColorEnum'));
        $this->assertFalse($result->dtoList->hasDtoWithType('IgnoreMe'));
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
        $converter = new Converter([new DtoVisitor($classesWithoutIgnoreFilter)]);
        $result = $converter->convert([$codeWithDateTime]);

        $this->assertTrue($result->dtoList->hasDtoWithType('User'));
        $this->assertTrue($result->dtoList->hasDtoWithType('Recipe'));
        $this->assertTrue($result->dtoList->hasDtoWithType('Category'));
        $this->assertTrue($result->dtoList->hasDtoWithType('ColorEnum'));
        $this->assertFalse($result->dtoList->hasDtoWithType('IgnoreMe'));
    }


    public function testFilterClassesByPhpAttribute(): void
    {
        $codeWithDateTime = <<<'CODE'
<?php

use \Riverwaysoft\PhpConverter\ClassFilter\DtoEndpoint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Dto
{

}

#[\Attribute(\Attribute::TARGET_METHOD)]
class Route {
  public function __construct(
     public string|array $path = null,
     public array|string $methods = [],
  ) {}
}

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class Input {
  
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

#[Dto]
class CreateUserInput {
  public string $id;
}

class SomeController {
  #[DtoEndpoint(returnMany: User::class)]
  #[Route('/api/users', methods: ['GET'])]
  public function getUserMethodsArray() {}
  
  #[DtoEndpoint(returnOne: User::class)]
  #[Route('/api/users', methods: ['POST'])]
  public function createUser(
    #[Input] CreateUserInput $input,
  ) {}
  
  #[DtoEndpoint(returnOne: User::class)]
  #[Route('/api/users/{id}', methods: ['GET'])]
  public function getUser(User $id) {}
  
  #[Route('/api/users_reversed_order', methods: ['GET'])]
  #[DtoEndpoint(returnMany: User::class)]
  public function getUserMethodsArrayReversedOrder() {}

  #[DtoEndpoint(returnOne: User::class)]
  #[Route('/api/users_methods_string', methods: 'GET')]
  public function getUserMethodsString() {}
}
CODE;

        $converter = new Converter([
            new DtoVisitor(new PhpAttributeFilter('Dto')),
            new SymfonyControllerVisitor('DtoEndpoint'),
        ]);
        $result = $converter->convert([$codeWithDateTime]);

        $this->assertTrue($result->dtoList->hasDtoWithType('User'));
        $this->assertTrue($result->dtoList->hasDtoWithType('Recipe'));
        $this->assertTrue($result->dtoList->hasDtoWithType('Category'));
        $this->assertTrue($result->dtoList->hasDtoWithType('ColorEnum'));
        $this->assertFalse($result->dtoList->hasDtoWithType('IgnoreMe'));

        $this->assertMatchesJsonSnapshot(json_encode($result->apiEndpointList));
    }

    #[DataProvider('provideInvalidControllers')]
    public function testTheFollowingCodeShouldThrow(string $invalidControllerActionCode, string $expectedError): void
    {
        $codeWithDateTime = <<<'CODE'
<?php

use \Riverwaysoft\PhpConverter\ClassFilter\DtoEndpoint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Dto
{

}

#[\Attribute(\Attribute::TARGET_METHOD)]
class Route {
  public function __construct(
     public string|array $path = null,
     public array|string $methods = [],
  ) {}
}

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class Input {
  
}

#[Dto]
class User
{
    public string $id;
    public ?User $bestFriend;
}

#[Dto]
class CreateUserInput {
  public string $id;
}

class SomeController {
CODE;

        $codeWithDateTime .= "\n" . $invalidControllerActionCode . '}';

        $converter = new Converter([
            new DtoVisitor(new PhpAttributeFilter('Dto')),
            new SymfonyControllerVisitor('DtoEndpoint'),
        ]);
        $this->expectExceptionMessage($expectedError);
        $converter->convert([$codeWithDateTime]);
    }

    public static function provideInvalidControllers(): \Generator
    {
        yield [
            <<<'CODE'
#[DtoEndpoint(returnOne: User::class)]
#[Route('/api/users/{id}', methods: ['GET'])]
public function getUserRouteAndMethodParamDontMatch(User $user) {
}
CODE,
            'Route /api/users/{id} has parameter id, but there are no method params with this name. Available parameters: user',
        ];

        yield [
            <<<'CODE'
  #[DtoEndpoint(returnOne: User::class)]
  #[Route('/api/users_create')]
  public function createUserShouldThrow(
    #[Input] CreateUserInput $input,
  ) {
  }
CODE,
            '#[Route()] argument "methods" is required'
        ];

        yield [
            <<<'CODE'
  #[DtoEndpoint(returnOne: User::class)]
  #[Route(name: '/api/users', methods: ['GET'])]
  public function getUserWithNamedKey() {
  
  }
  
  #[DtoEndpoint(returnOne: User::class)]
  #[Route(name: '/api/users', methods: ['GET'])]
  public function anotherMethod() {
  
  }
CODE,
            'Non-unique api endpoint with route /api/users and method get',
        ];

        yield [
            <<<'CODE'
  #[DtoEndpoint()]
  public function shouldThrow() {}
CODE,
            '#[DtoEndpoint] is used on a method, that does not have #[Route] attribute',
        ];
    }


    public function testPhp81EnumsFailedWhenNonBacked(): void
    {
        $codeWithDateTime = <<<'CODE'
<?php
#[\Attribute(\Attribute::TARGET_CLASS)]
class Dto
{

}

#[Dto]
enum Color
{
    case RED;
    case BLUE;
    case WHITE;
}
CODE;

        $converter = new Converter([new DtoVisitor(new PhpAttributeFilter('Dto'))]);
        $this->expectExceptionMessageMatches('/^Non-backed enums are not supported because they are not serializable. Please use backed enums/');
        $converter->convert([$codeWithDateTime]);
    }
}
