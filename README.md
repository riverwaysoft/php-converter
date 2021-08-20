# DTO converter

Work-in-progress PHP DTO to TypeScript & Dart converter. See unit tests for details.

## Features
- Converting PHP DTO to TypeScript & Dart
- TypeScript
  - Union types / Nullable types
  - Array types with PHP DocBlock support e.g. `User[]`
  - Enums (myclabs/enum)
  - Nested DTO
  - Recursive DTO
  - Custom type converting (like `DateTimeImmutable`)
- Dart
  - Nullable types
  - Dart List types
  - Enums (myclabs/enum)
  - Nested DTO
  - Recursive DTO
  
## Usage
```bash
php bin/php-generator.php dto-generator:typescript --from=/path/to/project --to=/path/to/out.ts
```

## Testing

``` bash
composer test
```

## Contributing

Please see [CONTRIBUTING](./CONTRIBUTING.md) for details.

