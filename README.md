# DTO converter

Work-in-progress PHP DTO to TypeScript & Dart converter. See unit tests for details.

## Features
- Converting PHP DTO to TypeScript & Dart
- Union types / Nullable types
- Array types with PHP DocBlock support e.g. `User[]`
- Enums (myclabs/enum)
- Nested DTO
- Recursive DTO
- Custom type converting (like `DateTimeImmutable`)
  
## Usage
```bash
php bin/php-generator.php dto-generator:generate --from=/path/to/project --to=/path/to
```

## Testing

``` bash
composer test
```

## Contributing

Please see [CONTRIBUTING](./CONTRIBUTING.md) for details.

