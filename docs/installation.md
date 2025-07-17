# Installation Guide

## Requirements

- PHP 8.3 or higher
- Laravel 12.19 or higher

## Installation via Composer

Install Laravel Safeguard using Composer:

```bash
composer require --dev grazulex/laravel-safeguard
```

> **Note**: We recommend installing this as a development dependency since it's primarily used for auditing and CI/CD processes.

## Publishing Configuration

Publish the configuration file to customize the security rules:

```bash
php artisan vendor:publish --tag=safeguard-config
```

This will create a `config/safeguard.php` file in your Laravel application.

## Verification

Verify the installation by running:

```bash
php artisan safeguard:list
```

This should display all available security rules.

## Basic Usage

Run your first security check:

```bash
php artisan safeguard:check
```

## Next Steps

- [Configure your rules](configuration.md)
- [Learn about available security rules](rules-reference.md)
- [Set up CI/CD integration](ci-cd-integration.md)

## Installation in Existing Projects

### Laravel 12.x
```bash
composer require --dev grazulex/laravel-safeguard
php artisan vendor:publish --tag=safeguard-config
```

### Framework-Agnostic Usage
While primarily designed for Laravel, you can use the core security rules in any PHP project:

```bash
composer require grazulex/laravel-safeguard
```

Then manually configure the rules and services as needed.

## Troubleshooting Installation

### Composer Issues
If you encounter Composer issues:

```bash
# Clear Composer cache
composer clear-cache

# Update Composer
composer self-update

# Install with verbose output
composer require --dev grazulex/laravel-safeguard -vvv
```

### Laravel Auto-Discovery
Laravel Safeguard uses package auto-discovery. If you need to manually register the service provider:

```php
// config/app.php
'providers' => [
    // ...
    Grazulex\LaravelSafeguard\LaravelSafeguardServiceProvider::class,
],
```

### Permission Issues
Ensure your storage directories are writable:

```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```