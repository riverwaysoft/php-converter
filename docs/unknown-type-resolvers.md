## Unknown type resolvers

`php-converter` takes care of converting basic PHP types like number, string and so on. But what if you have a type that isn't a DTO? For example `\DateTimeImmutable`. You can write a class that implements [UnknownTypeResolverInterface](https://github.com/riverwaysoft/php-converter/blob/2d434562c1bc73bcb6819257b31dd75c818f4ab1/src/Language/UnknownTypeResolverInterface.php). 

Here is an example of built in `DateTimeTypeResolver`:

```php
class DateTimeTypeResolver implements UnknownTypeResolverInterface
{
    public function supports(PhpUnknownType $type, DtoType|null $dto, DtoList $dtoList): bool
    {
        return $type->getName() === 'DateTime' || $type->getName() === 'DateTimeImmutable';
    }

    public function resolve(PhpUnknownType $type, DtoType|null $dto, DtoList $dtoList): string|PhpTypeInterface
    {
        return PhpBaseType::string();
    }
}
```

There is also a shortcut to achieve it - use [InlineTypeResolver](https://github.com/riverwaysoft/php-converter/blob/2d434562c1bc73bcb6819257b31dd75c818f4ab1/src/Language/TypeScript/InlineTypeResolver.php):

```php
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpBaseType;

return static function (PhpConverterConfig $config) {
    $config->setCodeProvider(new FileSystemCodeProvider('/\.php$/'));

    $config->addVisitor(new DtoVisitor(new PhpAttributeFilter(Dto::class)));

    $config->setOutputGenerator(new TypeScriptGenerator(
        new SingleFileOutputWriter('generated.ts'),
        [
            new DateTimeTypeResolver(),
            new ClassNameTypeResolver(),
            new InlineTypeResolver([
                // Convert libphonenumber object to a string
                // PhpBaseType is used to support both Dart/TypeScript
               'PhoneNumber' => PhpBaseType::string(), 
                // Convert PHP Money object to a custom TypeScript type
                // It's TS-only syntax, to support Dart and the rest of the languages you'd have to create a separate PHP class like MoneyOutput
                'Money' => '{ amount: number; currency: string }',
                // Convert Doctrine Embeddable to an existing Dto marked as #[Dto]
                'SomeDoctrineEmbeddable' => 'SomeDoctrineEmbeddableDto',
            ])
        ],
    ));
};
```