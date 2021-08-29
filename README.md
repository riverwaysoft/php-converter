## DTO converter

DTO converter generates TypeScript & Dart out of your PHP DTO classes.

## Why?
Statically typed languages like TypeScript or Dart are great because they allow catching bugs without even running your code. But unless you have well-defined contracts between API and consumer apps, you have to always fix outdated typings when the API changes.
This library generates types for you so you can move faster and encounter fewer bugs.

## Quick start

1) Installation
```bash
composer require riverwaysoft/php-converter --dev
```

2) Create config file
```
cp vendor/riverwaysoft/php-converter/bin/php-generator bin/php-generator
```

3) Mark a few classes with `#[Dto]` annotation to convert them into TypeScript or Dart
```php

use Riverwaysoft\DtoConverter\ClassFilter\Dto;

#[Dto]
class UserDto
{
    public string $id;
    public string $name;
    public int $age;
}

#[Dto]
class UserWithFriendsDto
{
    public User $user;
    public ?User $bestFriend;
    /** @var User[] */
    public array $friends;
}

```

4) Run CLI command to generate TypeScript or Dart
```bash
bin/php-generator generate --from=/path/to/project/src --to=.
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

