## Standalone installation

If you're unable to install php-converter due to composer dependency conflicts, consider using the standalone version of the package. PHAR files, similar to JAR files in Java, bundle a PHP application and its dependencies into a single file. This provides the isolation of dependencies. Each PHAR can include its specific version of dependencies, avoiding conflicts with other packages on the same project. To download `phar` version of this package go to releases and download the `.phar` file from there. Note that static analyzers like PHPStan may raise issues if you use classes from the PHAR in your code, so you'll need to instruct the static analyzer where to locate these classes. Here's an example for PHPStan:

```
// phpstan.neon

parameters:
    bootstrapFiles:
        - phar://path/to/php-converter.phar/vendor/autoload.php
```