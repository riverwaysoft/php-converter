## Development

The information is for package contributors and those who are willing to contribute.

### Testing

``` bash
composer test
```

### Work with a local copy of `php-converter` inside your project

1. Go to `your-project` with `php-converter` already installed. Add the following code to your `composer.json`:

```
    "repositories": [
        {
            "type": "path",
            "url": "/Path/to/local/php-converter"
        }
    ],
```

To find out the absolute path of your local `php-converter` repository run `realpath .` in the package directory

2. Create a symbolic link by running `composer require "riverwaysoft/php-converter @dev" --dev`

### Profile

Show memory & time usage:

`bin/php-converter --from=./ --to=./assets/ -v`

Generate Xdebug profiler output:

`php -d xdebug.mode=profile -d xdebug.output_dir=. bin/php-converter generate --from=./ --to=./assets/ -v -xdebug`

Then open the result `.cachegrind` file in PHPStorm -> Tools -> Analyze XDebug Profiler Snapshot

### Code coverage

1) Run tests with code coverage: `composer run test:with-coverage`
2) Check coverage level: `composer run test:coverage-level`
3) Browser generated HTML report: `npm run coverage-server`
