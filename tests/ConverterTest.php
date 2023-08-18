<?php

declare(strict_types=1);

namespace App\Tests;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Riverwaysoft\PhpConverter\Ast\Converter;
use Riverwaysoft\PhpConverter\Ast\DtoVisitor;
use Riverwaysoft\PhpConverter\Bridge\Symfony\SymfonyControllerVisitor;
use Riverwaysoft\PhpConverter\Dto\DtoClassProperty;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\ExpressionType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpOptionalType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\PhpConverter\Filter\Combinators\NotFilter;
use Riverwaysoft\PhpConverter\Filter\DocBlockFilter;
use Riverwaysoft\PhpConverter\Filter\PhpAttributeFilter;
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
    public readonly int $age;
}
CODE;

        $normalized = (new Converter([new DtoVisitor()]))->convert([$codeNestedDto]);
        $this->assertMatchesJsonSnapshot($normalized->dtoList->getList());
    }

    public function testNestedDtoWithOptionalProperties(): void
    {
        $codeNestedDto = <<<'CODE'
<?php

class UserCreate {
    public string $a;
    public null|string $b = null;
    public string|int $c = '';
    public ?int $d = null;
    
    public function __construct(
        public ?string $e = null,
    ) {}
}
CODE;

        $normalized = (new Converter([new DtoVisitor()]))->convert([$codeNestedDto]);

        $result = $normalized->dtoList->getList()[0];

        $this->assertEquals(
            $result,
            new DtoType(
                name: 'UserCreate',
                expressionType: ExpressionType::class(),
                properties: [
                    new DtoClassProperty(type: PhpBaseType::string(), name: 'a'),
                    new DtoClassProperty(
                        type: new PhpOptionalType(new PhpUnionType(
                            types: [
                                PhpBaseType::null(),
                                PhpBaseType::string(),
                            ]
                        )),
                        name: 'b',
                    ),
                    new DtoClassProperty(
                        type: new PhpOptionalType(new PhpUnionType(
                            types: [
                                PhpBaseType::string(),
                                PhpBaseType::int(),
                            ]
                        )),
                        name: 'c',
                    ),
                    new DtoClassProperty(
                        type: new PhpOptionalType(new PhpUnionType(
                            types: [
                                PhpBaseType::int(),
                                PhpBaseType::null(),
                            ]
                        )),
                        name: 'd',
                    ),
                    new DtoClassProperty(
                        type: new PhpOptionalType(new PhpUnionType(
                            types: [
                                PhpBaseType::string(),
                                PhpBaseType::null(),
                            ]
                        )),
                        name: 'e',
                    ),
                ]
            )
        );
    }

    public function testConstructorParamsParse(): void
    {
        $codeNestedDto = <<<'CODE'
<?php

class UserCreate {
    /** 
    * @param FullName|null $fullName, 
    * @param string $id, 
    */
    public function __construct(
        public $id,
        public readonly $fullName,
    ) {
    
    }
}

class FullName {
    public string $firstName;
    /** @var string $lastName */
    public $lastName;
}
CODE;

        $normalized = (new Converter([new DtoVisitor()]))->convert([$codeNestedDto]);
        $this->assertMatchesJsonSnapshot($normalized->dtoList->getList());
    }

    public function testGenerics(): void
    {
        $codeNestedDto = <<<'CODE'
<?php

/** 
 * @template T 
 */
class PaginatedResponse1 {
    /**
    * @param T[] $array 
    * @param T $one 
    */
    public function __construct(
       /** @var string $array */
        public $array,
       /** @var string $one */
        public $one,
    ) {
    }
}

/** 
 * @template T 
 */
class PaginatedResponse2 {
    /** @var T $data */
    public $data;

    /**
    * @param T $data
    */
    public function __construct($data) {
        $this->data = $data;
    }
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

        $converter = new Converter([new DtoVisitor(new DocBlockFilter('@DTO'))]);
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

        $classesWithoutIgnoreFilter = new NotFilter(new DocBlockFilter('@ignore'));
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

use Riverwaysoft\PhpConverter\Filter\Attributes\DtoEndpoint;

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
  /** @return User[] */
  #[DtoEndpoint()]
  #[Route('/api/users', methods: ['GET'])]
  public function getUserMethodsArray() {}
  
  /** @return User */
  #[DtoEndpoint()]
  #[Route('/api/users', methods: ['POST'])]
  public function createUser(
    #[Input] CreateUserInput $input,
  ) {}
  
  /** @return User */
  #[DtoEndpoint()]
  #[Route('/api/users/{id}', methods: ['GET'])]
  public function getUser(User $id) {}
  
  /** @return User[] */
  #[Route('/api/users_reversed_order', methods: ['GET'])]
  #[DtoEndpoint()]
  public function getUserMethodsArrayReversedOrder() {}

  /** @return User */
  #[DtoEndpoint()]
  #[Route('/api/users_methods_string', methods: 'GET')]
  public function getUserMethodsString() {}
}
CODE;

        $converter = new Converter([
            new DtoVisitor(new PhpAttributeFilter('Dto')),
            new SymfonyControllerVisitor(new PhpAttributeFilter('DtoEndpoint')),
        ]);
        $result = $converter->convert([$codeWithDateTime]);

        $this->assertTrue($result->dtoList->hasDtoWithType('User'));
        $this->assertTrue($result->dtoList->hasDtoWithType('Recipe'));
        $this->assertTrue($result->dtoList->hasDtoWithType('Category'));
        $this->assertTrue($result->dtoList->hasDtoWithType('ColorEnum'));
        $this->assertFalse($result->dtoList->hasDtoWithType('IgnoreMe'));

        $this->assertMatchesJsonSnapshot(json_encode($result->apiEndpointList));
    }

    public function testFilterClassesByPhpAnnotation(): void
    {
        $codeWithDateTime = <<<'CODE'
<?php

#[\Attribute(\Attribute::TARGET_METHOD)]
class Route {
  public function __construct(
     public string|array $path = null,
     public array|string $methods = [],
  ) {}
}

/**
 * @Dto
 */
class User
{
    public string $id;
}

class SomeController {
  /**
   * @return User[] 
   * @DtoEndpoint
   */
  #[Route('/api/users', methods: ['GET'])]
  public function getUserMethodsArray() {}
}
CODE;

        $converter = new Converter([
            new DtoVisitor(new DocBlockFilter('Dto')),
            new SymfonyControllerVisitor(new DocBlockFilter('DtoEndpoint')),
        ]);
        $result = $converter->convert([$codeWithDateTime]);

        $this->assertTrue($result->dtoList->hasDtoWithType('User'));

        $this->assertMatchesJsonSnapshot(json_encode($result->apiEndpointList));
    }

    #[DataProvider('provideInvalidControllers')]
    public function testTheFollowingCodeShouldThrow(string $invalidControllerActionCode, string $expectedError): void
    {
        $codeWithDateTime = <<<'CODE'
<?php

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
            new SymfonyControllerVisitor(new PhpAttributeFilter('DtoEndpoint')),
        ]);
        $this->expectExceptionMessage($expectedError);
        $converter->convert([$codeWithDateTime]);
    }

    public static function provideInvalidControllers(): Generator
    {
        yield [
            <<<'CODE'
#[DtoEndpoint()]
#[Route('/api/users/{id}', methods: ['GET'])]
public function getUserRouteAndMethodParamDontMatch(User $user) {
}
CODE,
            'Route /api/users/{id} has parameter id, but there are no method params with this name. Available parameters: user',
        ];

        yield [
            <<<'CODE'
  #[DtoEndpoint()]
  #[Route('/api/users_create')]
  public function createUserShouldThrow(
    #[Input] CreateUserInput $input,
  ) {
  }
CODE,
            '#[Route()] argument "methods" is required',
        ];

        yield [
            <<<'CODE'
  #[DtoEndpoint()]
  #[Route(name: '/api/users', methods: ['GET'])]
  public function getUserWithNamedKey() {
  
  }
  
  #[DtoEndpoint()]
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
            'The method was marked as generated but it does not have #[Route] attribute',
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
