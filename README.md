# php-converter [![Latest Version on Packagist](https://img.shields.io/packagist/v/riverwaysoft/php-converter.svg)](https://packagist.org/packages/riverwaysoft/php-converter) [![Tests](https://github.com/riverwaysoft/php-converter/actions/workflows/php.yml/badge.svg?branch=master)](https://github.com/riverwaysoft/php-converter/actions/workflows/php.yml) [![PHPStan](https://github.com/riverwaysoft/php-converter/actions/workflows/static_analysis.yml/badge.svg?branch=master)](https://github.com/riverwaysoft/php-converter/actions/workflows/static_analysis.yml) [![Total Downloads](https://img.shields.io/packagist/dt/riverwaysoft/php-converter.svg)](https://packagist.org/packages/riverwaysoft/php-converter)

<img width="818" alt="Screen Shot 2022-10-07 at 09 04 35" src="https://user-images.githubusercontent.com/22447849/194478818-7276da5c-bf5e-4ad2-8efd-6463c53d01d3.png">

Generates TypeScript & Dart out of your PHP DTO classes.

## Why?
Statically typed languages like TypeScript or Dart are great because they allow catching bugs without even running your code. But unless there are well-defined contracts between the API and consumer apps, you may find yourself frequently adjusting outdated typings whenever the API  changes. This library generates types for you, enabling you to move faster and encounter fewer bugs.

## Requirements

PHP 8.0 or above

## Quick start

1) Installation
```bash
composer require riverwaysoft/php-converter --dev
```

If the installation leads to dependency conflicts, consider using the [standalone Phar version](docs/standalone-installation.md) of the package.

2) Mark a few classes with the #[Dto] annotation to convert them into TypeScript or Dart.

```php
use Riverwaysoft\PhpConverter\Filter\Attributes\Dto;

#[Dto]
class UserOutput
{
    public string $id;
    public int $age;
    public ?UserOutput $bestFriend;
    /** @var UserOutput[] */
    public array $friends;
}
```

4) Run the CLI command to generate TypeScript
```bash
vendor/bin/php-converter --from=/path/to/project/src --to=.
```

This will generate a file `generated.ts` with the following content:

```typescript
type UserOutput = {
  id: string;
  age: number;
  bestFriend: UserOutput | null;
  friends: UserOutput[];
}
```

## Features
- Supports all PHP data types including union types, nullable types, and enums.
- Supports PHP DocBlock types, e.g., `User[]`, `int[][]|null`, and generics thanks to [phpstan/phpdoc-parser](https://github.com/phpstan/phpdoc-parser)
- Custom type resolvers (for instance, for `DateTimeImmutable`).
- Generate a single output file or multiple files (1 type per file).
- Option to override the generation logic.
- Flexible class filters with the option to use your own filters.
- Generate API client from Symfony or API Platform code.

## Customization
If you'd like to customize the conversion process, you need to copy the config script to your project folder:

```
cp vendor/riverwaysoft/php-converter/bin/default-config.php config/ts-config.php
``` 

Now you can customize this config and run the php-converter using the following script:
```bash
vendor/bin/php-converter --from=/path/to/project/src --to=. --config=config/ts-config.php
```

## Documentation
- [Class filtering](docs/class-filtering.md)
- [Customize output](docs/customize-output-generation.md)
- [Unknown type resolvers](docs/unknown-type-resolvers.md)
- [Other languages support](docs/other-language-support.md)
- [Troubleshooting](docs/troubleshooting.md)
- [Development](docs/development.md)
- [Standalone installation](docs/standalone-installation.md)
