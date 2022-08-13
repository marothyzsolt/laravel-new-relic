# Laravel New Relic Logger

[![Latest Version on Packagist](https://img.shields.io/packagist/v/marothyzsolt/laravel-new-relic.svg?style=flat-square)](https://packagist.org/packages/marothyzsolt/laravel-new-relic)
[![Total Downloads](https://img.shields.io/packagist/dt/marothyzsolt/laravel-new-relic.svg?style=flat-square)](https://packagist.org/packages/marothyzsolt/laravel-new-relic)

## Installation

You can install the package via composer:

```bash
composer require marothyzsolt/laravel-new-relic
```

## Usage

Change logging.php file with the following code:
```php
'single' => [
    'driver' => env('APP_ENV') === 'local' ? 'single' : 'custom',
    'via' => NewRelicLogger::class,
    'path' => storage_path('logs/laravel.log'),
    'level' => env('LOG_LEVEL', 'debug'),
],
```

You have to specify these the environment variables:

```dotenv
NEW_RELIC_APP_NAME="app-name"
NEW_RELIC_LICENSE_KEY="newrelic-license-key-here"
```

### Custom Extra Data

You can add more data to send to New Relic. Firstly publish the config file.

```php
php artisan vendor:publish --provider="MarothyZsolt\LaravelNewRelic\LaravelNewRelicServiceProvider"
```

Now you see the config file structure, and you can extend the Closure.

```php
'extra_data' => function (?Request $request, ?Throwable $throwable): array {
    return [
        'fingerprint' => $request->fingerprint(),
    ];
}
```

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email zsolt@marothy.me instead of using the issue tracker.

## Credits

-   [Zsolt Marothy](https://github.com/marothyzsolt)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.