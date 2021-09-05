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

## Customize
If you'd like to customize dto-convert you need to copy the generator script to your project folder:

```
cp vendor/bin/dto-converter bin/dto-convert
``` 

Now you can start customizing the dto-converter by editing the executable file.

### How to customize generated output?
By default `dto-converter` writes all the types into one file. You can configure it to put each type / class in a separate file with all the required imports. Here is an example how to achieve it:

```diff
+ $fileNameGenerator = new KebabCaseFileNameGenerator('.ts');

$application->add(
    new ConvertCommand(
        new Converter(Normalizer::factory(
            new PhpAttributeFilter('Dto'),
        )),
        new TypeScriptGenerator(
-            new SingleFileOutputWriter('generated.ts'),
+            new EntityPerClassOutputWriter(
+                $fileNameGenerator,
+                new TypeScriptImportGenerator(
+                    $fileNameGenerator,
+                    new DtoTypeDependencyCalculator()
+                )
+            ),
            [
                new DateTimeTypeResolver(),
                new ClassNameTypeResolver(),
            ],
        ),
        new Filesystem(),
        new FileSystemCodeProvider('/\.php$/'),
        new OutputDiffCalculator(),
    )
);
```

Feel free to create your own OutputWriter.

### How to customize class filtering?
Suppose you don't want to mark each DTO individually with `#[Dto]` but want to convert all the files ending with "Dto" automatically:

```diff
$application->add(
    new ConvertCommand(
-       new Converter(Normalizer::factory(
-           new PhpAttributeFilter('Dto'),
-       )),
+       new Converter(Normalizer::factory()),
        new TypeScriptGenerator(
            new SingleFileOutputWriter('generated.ts'),
            [
                new DateTimeTypeResolver(),
                new ClassNameTypeResolver(),
            ],
        ),
        new Filesystem(),
-       new FileSystemCodeProvider('/\.php$/'),
+       new FileSystemCodeProvider('/Dto\.php$/'),
        new OutputDiffCalculator(),
    )
);
```

You can even go further and use `NegationFilter` to exclude specific files as shown in [unit tests](https://github.com/riverwaysoft/dto-converter/blob/a8d5df2c03303c02bc9148bd1d7822d7fe48c5d8/tests/EndToEndTest.php#L297).


## Error list
Here is a list of errors `dto-converter` can throw and description what to do if you encounter these errors:

### 1. Property z of class X has no type. Please add PHP type
It means that you've forgotten to add type for property `a` of class Y. Example:

```php
#[Dto]
class X {
  public $z;
} 
```

At the moment there is no strict / loose mode in `dto-converter`. It could silently convert such types to TypeScript `any` or Dart `Object` but we don't need that feature. Feel free to raise an issue if having loose mode makes sense for you. If you don't know the PHP type just use [mixed](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.mixed) type to explicitly convert it to `any`/`Object`.


### 2. PHP Type X is not supported
It means `dto-converter` doesn't understand how to convert the type X into TypeScript or Dart. If you are using `#[Dto]` attribute you probably forgot to add it to class `X`. Example:

```php
#[Dto]
class A {
  public X $x;
}

class X {
  public int $foo;
}
```

## Testing

``` bash
composer test
```

## How it is different from alternatives?
- Unlike [spatie/typescript-transformer](https://github.com/spatie/typescript-transformer) `dto-converter` supports not only TypeScript but also Dart. Support for other languages can be easily added by implementing LanguageInterface. `dto-converter` can also output generated types / classes into different files.

## Contributing

Please see [CONTRIBUTING](./CONTRIBUTING.md) for details.

