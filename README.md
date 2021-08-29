# dto-converter [![Latest Version on Packagist](https://img.shields.io/packagist/v/riverwaysoft/php-converter.svg?style=flat-square)](https://packagist.org/packages/riverwaysoft/php-converter) [![Tests](https://github.com/riverwaysoft/dto-converter/actions/workflows/php.yml/badge.svg?branch=master)](https://github.com/riverwaysoft/dto-converter/actions/workflows/php.yml) [![PHPStan](https://github.com/riverwaysoft/dto-converter/actions/workflows/static_analysis.yml/badge.svg?branch=master)](https://github.com/riverwaysoft/dto-converter/actions/workflows/static_analysis.yml) [![Total Downloads](https://img.shields.io/packagist/dt/riverwaysoft/php-converter.svg?style=flat-square)](https://packagist.org/packages/riverwaysoft/php-converter)


Generates TypeScript & Dart out of your PHP DTO classes.

## Why?
Statically typed languages like TypeScript or Dart are great because they allow catching bugs without even running your code. But unless you have well-defined contracts between API and consumer apps, you have to always fix outdated typings when the API changes.
This library generates types for you so you can move faster and encounter fewer bugs.

## Quick start

1) Installation
```bash
composer require riverwaysoft/php-converter --dev
```

2) Copy executable file
```
cp vendor/riverwaysoft/php-converter/bin/dto-converter bin/dto-converter
```

3) Mark a few classes with `#[Dto]` annotation to convert them into TypeScript or Dart
```php

use Riverwaysoft\DtoConverter\ClassFilter\Dto;

#[Dto]
class UserWithFriendsDto
{
    public UserDto $user;
    public ?UserDto $bestFriend;
    /** @var UserDto[] */
    public array $friends;
}

#[Dto]
class UserDto
{
    public string $id;
    public string $name;
    public int $age;
}

```

4) Run CLI command to generate TypeScript or Dart
```bash
bin/dto-converter generate --from=/path/to/project/src --to=.
```

You'll get file `generated.ts` with the following contents:

```typescript
type UserDto = { 
  id: string; 
  name: string; 
  age: number;
}

type UserWithFriendsDto = {
  user: UserDto;
  bestFriend: UserDto | null;
  friends: UserDto[];
}
```

## Features
- Union types / Nullable types / Enums / Array types with PHP DocBlock support e.g. `User[]`
- Nested DTO / Recursive DTO
- Custom type resolvers (e.g. `DateTimeImmutable`)
- Generate a single output file or multiple files (entity per class)
- Custom class filters

## Testing

``` bash
composer test
```

## Contributing

Please see [CONTRIBUTING](./CONTRIBUTING.md) for details.

