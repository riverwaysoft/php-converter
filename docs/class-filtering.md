## Customize class filtering
Suppose you don't want to individually mark each DTO with the #[Dto] annotation, but instead, you want to automatically convert all files that end with "Dto":

```php
return static function (PhpConverterConfig $config) {
    $config->setCodeProvider(new FileSystemCodeProvider('/Dto\.php$/'));
    $config->addVisitor(new DtoVisitor());

    $config->setOutputGenerator(new TypeScriptGenerator(
        new SingleFileOutputWriter('generated.ts'),
        [
            new DateTimeTypeResolver(),
            new ClassNameTypeResolver(),
        ],
    ));
};
```

You can even go a step further by using the `NotFilter` to exclude specific files, as demonstrated in [unit tests](https://github.com/riverwaysoft/php-converter/blob/a8d5df2c03303c02bc9148bd1d7822d7fe48c5d8/tests/EndToEndTest.php#L297):
