## Troubleshooting
Here is a list of errors `php-converter` might throw, along with descriptions of what to do if you encounter them:

### 1. Property A#b has no type. Please add PHP type
This error means that you've forgotten to add a type for property `z` in class `X`. Here's an example:

```php
#[Dto]
class X {
  public $z;
} 
```

Currently, there is no option to switch between strict or loose modes in `php-converter` - it is always in strict mode. If you're unsure about the PHP type, you can use the [mixed](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.mixed) type to explicitly convert it to `any` (for TypeScript) or `Object` (for Dart). Although it's possible for the tool to silently convert such types to TypeScript's any or Dart's Object, we prefer an explicit approach. If you think having a loose mode would be beneficial, feel free to raise an issue.


### 2. PHP Type X is not supported
This error implies that `php-converter` doesn't know how to convert the type 'X' into TypeScript or Dart. If you're using the `#[Dto]` attribute, you may have forgotten to add it to class `X`. Here's an example:

```php
#[Dto]
class A {
  public X $x;
}

class X {
  public int $foo;
}
```