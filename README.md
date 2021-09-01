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

2) Mark a few classes with `#[Dto]` annotation to convert them into TypeScript or Dart
```php

use Riverwaysoft\DtoConverter\ClassFilter\Dto;

#[Dto]
class User
{
    public string $id;
    public int $age;
    public ?User $bestFriend;
    /** @var User[] */
    public array $friends;
}

```

4) Run CLI command to generate TypeScript or Dart
```bash
vendor/bin/dto-converter generate --from=/path/to/project/src --to=.
```

You'll get file `generated.ts` with the following contents:

```typescript

type User = {
  id: string;
  age: number;
  bestFriend: User | null;
  friends: User[];
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

## How it is different from alternatives?
- Unlike [spatie/typescript-transformer](https://github.com/spatie/typescript-transformer) dto-converter supports not only TypeScript but also Dart. Support for other languages can be easily added by implementing LanguageInterface. dto-converter can also output generated types / classes into different files.

## Contributing

Please see [CONTRIBUTING](./CONTRIBUTING.md) for details.

