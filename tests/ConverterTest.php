<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use Riverwaysoft\DtoConverter\Ast\Converter;
use Riverwaysoft\DtoConverter\ClassFilter\DocBlockCommentFilter;
use Riverwaysoft\DtoConverter\ClassFilter\NegationFilter;
use Riverwaysoft\DtoConverter\ClassFilter\PhpAttributeFilter;
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

        $normalized = (new Converter())->convert([$codeNestedDto]);
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

        $converter = new Converter(new DocBlockCommentFilter('@DTO'));
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
        $converter = new Converter($classesWithoutIgnoreFilter);
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

use \Riverwaysoft\DtoConverter\ClassFilter\DtoEndpoint;

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
  #[DtoEndpoint(returnOne: User::class)]
  #[Route('/api/users', methods: ['GET'])]
  public function getUserMethodsArray() {
  
  }
  
  #[DtoEndpoint(returnOne: User::class)]
  #[Route('/api/users', methods: ['POST'])]
  public function createUser(
    #[Input] CreateUserInput $input,
  ) {
  
  }
  
//  #[DtoEndpoint(returnOne: User::class)]
//  #[Route('/api/users_create')]
//  public function createUserShouldThrow(
//    #[Input] CreateUserInput $input,
//  ) {
//  
//  }

  
  // same url and same method should throw
//  #[DtoEndpoint(returnOne: User::class)]
//  #[Route(name: '/api/users', methods: ['GET'])]
//  public function getUserWithNamedKey() {
//  
//  }
  
//  #[Route('/api/users', methods: ['GET'])]
//  #[DtoEndpoint(returnOne: User::class)]
//  public function getUserMethodsArrayReversedOrder() {
//  
//  }

//  #[DtoEndpoint()]
//  public function getUsersWithoutOutputTypeShouldThrow() {
//  
//  }
  
//    #[DtoEndpoint(returnOne: User::class)]
//  #[Route('/api/users', methods: 'GET')]
//  public function getUserMethodsString() {
//  
//  }
}

CODE;

        $converter = new Converter(new PhpAttributeFilter('Dto'));
        $result = $converter->convert([$codeWithDateTime]);

        $this->assertTrue($result->dtoList->hasDtoWithType('User'));
        $this->assertTrue($result->dtoList->hasDtoWithType('Recipe'));
        $this->assertTrue($result->dtoList->hasDtoWithType('Category'));
        $this->assertTrue($result->dtoList->hasDtoWithType('ColorEnum'));
        $this->assertFalse($result->dtoList->hasDtoWithType('IgnoreMe'));

        $this->assertMatchesSnapshot($result->apiEndpointList);
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

        $converter = new Converter(new PhpAttributeFilter('Dto'));
        $this->expectExceptionMessageMatches('/^Non-backed enums are not supported because they are not serializable. Please use backed enums/');
        $converter->convert([$codeWithDateTime]);
    }
}
