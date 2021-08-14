### DTO converter

Work-in-progress PHP DTO to TypeScript & Dart converter. See unit tests for details.

### Features
- Converting PHP DTO to TypeScript 
- Union types / Nullable types
- Array types with PHP DocBlock support e.g. `User[]`
- Enums (myclabs/enum)
- Nested DTO
- Recursive DTO
- Custom type converting (like `DateTimeImmutable`)

### Scripts
- `composer test` - Run tests
- `composer php-parser-dump` - Obtain a node dump for a DTO fixture class
- `composer cs` - Fix code style
