# Troubleshooting Guide

This guide helps you resolve common issues when using Laravel Safeguard.

## Installation Issues

### Package Not Found

**Problem**: `composer require` fails with "Package not found"

**Solution**:
```bash
# Update Composer
composer self-update

# Clear Composer cache
composer clear-cache

# Try again
composer require --dev grazulex/laravel-safeguard
```

### Dependency Conflicts

**Problem**: Composer reports dependency conflicts

**Solution**:
```bash
# Check conflicts
composer why-not grazulex/laravel-safeguard

# Update dependencies
composer update --with-dependencies

# If conflicts persist, check requirements:
# - PHP 8.3+
# - Laravel 12.19+
```

### Service Provider Not Registered

**Problem**: Commands not available after installation

**Solution**:
```bash
# Clear caches
php artisan cache:clear
php artisan config:clear

# Re-discover packages
php artisan package:discover

# Check if provider is registered
php artisan list | grep safeguard
```

**Manual Registration** (if auto-discovery fails):
```php
// config/app.php
'providers' => [
    // ...
    Grazulex\LaravelSafeguard\LaravelSafeguardServiceProvider::class,
],
```

## Configuration Issues

### Config File Not Found

**Problem**: "Configuration file not found" error

**Solution**:
```bash
# Publish configuration
php artisan vendor:publish --tag=safeguard-config

# Check if file exists
ls -la config/safeguard.php

# Force republish if needed
php artisan vendor:publish --tag=safeguard-config --force
```

### Rules Not Loading

**Problem**: Custom rules not being loaded

**Solution**:
1. **Check file location**:
   ```bash
   ls -la app/SafeguardRules/
   ```

2. **Verify namespace**:
   ```php
   // In your custom rule file
   namespace App\SafeguardRules;
   ```

3. **Check configuration**:
   ```php
   // config/safeguard.php
   'custom_rules_path' => app_path('SafeguardRules'),
   'custom_rules_namespace' => 'App\\SafeguardRules',
   ```

4. **Clear cache**:
   ```bash
   php artisan cache:clear
   composer dump-autoload
   ```

### Invalid Configuration

**Problem**: "Invalid configuration" errors

**Solution**:
```bash
# Validate configuration syntax
php artisan config:show safeguard

# Check for PHP syntax errors
php -l config/safeguard.php

# Reset to default if needed
php artisan vendor:publish --tag=safeguard-config --force
```

## Command Issues

### Command Not Found

**Problem**: `php artisan safeguard:check` not found

**Solution**:
```bash
# Check if package is installed
composer show grazulex/laravel-safeguard

# Check if commands are registered
php artisan list | grep safeguard

# Clear cache and re-discover
php artisan cache:clear
php artisan package:discover

# Check Laravel version compatibility
php artisan --version
```

### Permission Denied

**Problem**: "Permission denied" when running commands

**Solution**:
```bash
# Check storage permissions
ls -la storage/
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/

# Check file ownership
sudo chown -R www-data:www-data storage/
sudo chown -R www-data:www-data bootstrap/cache/

# For development environments
chmod -R 777 storage/
chmod -R 777 bootstrap/cache/
```

### Memory Limit Exceeded

**Problem**: "Fatal error: Allowed memory size exhausted"

**Solution**:
```bash
# Increase memory limit temporarily
php -d memory_limit=512M artisan safeguard:check

# Or increase in php.ini
memory_limit = 512M

# For large projects, limit scan paths
# Edit config/safeguard.php:
'scan_paths' => [
    'app/',
    'config/',
    // Remove large directories
],
```

## Rule Execution Issues

### Rules Not Running

**Problem**: Rules appear enabled but don't run

**Solution**:
1. **Check rule configuration**:
   ```bash
   php artisan safeguard:list
   ```

2. **Test individual rules**:
   ```bash
   php artisan safeguard:test-rule app_key_is_set
   ```

3. **Check environment-specific settings**:
   ```php
   // config/safeguard.php
   'environments' => [
       'production' => [
           'rule_name', // Must be listed here
       ],
   ],
   ```

4. **Verify rule implementation**:
   ```php
   public function appliesToEnvironment(string $environment): bool
   {
       return true; // Should return true for current environment
   }
   ```

### False Positives

**Problem**: Rules fail incorrectly

**Common Issues and Solutions**:

#### 1. APP_DEBUG false positive in development
```php
// In your custom rule or configuration
if (app()->environment('local', 'development')) {
    return SafeguardResult::pass('APP_DEBUG allowed in development');
}
```

#### 2. Secret detection false positives
```php
// Adjust secret patterns to be more specific
'secret_patterns' => [
    '*_KEY',
    '*_SECRET',
    // Remove overly broad patterns
],
```

#### 3. File permission issues
```bash
# Check actual file permissions
ls -la .env
# Should be 600 or 644, not 666 or 777

# Fix permissions
chmod 600 .env
```

### Custom Rule Errors

**Problem**: Custom rules throwing exceptions

**Common Issues**:

#### 1. Missing dependencies
```php
// In custom rule
try {
    $result = DB::connection()->getPdo();
} catch (Exception $e) {
    return SafeguardResult::warning(
        'Database connection not available for security check',
        ['error' => $e->getMessage()]
    );
}
```

#### 2. Invalid return types
```php
// Must return SafeguardResult
public function check(): SafeguardResult
{
    // ❌ Wrong
    return true;
    
    // ✅ Correct
    return SafeguardResult::pass('Check passed');
}
```

#### 3. Namespace issues
```php
// Ensure proper namespace
namespace App\SafeguardRules;

use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;
```

## Performance Issues

### Slow Execution

**Problem**: Security checks take too long

**Solutions**:

#### 1. Limit scan paths
```php
// config/safeguard.php
'scan_paths' => [
    'app/',
    'config/',
    'routes/',
    // Remove large directories like:
    // 'vendor/',
    // 'node_modules/',
    // 'public/storage/',
],
```

#### 2. Optimize file scanning
```bash
# Check directory sizes
du -sh app/ config/ routes/ vendor/

# Consider excluding large files
# Add to .gitignore patterns in scan logic
```

#### 3. Use caching in CI/CD
```yaml
# GitHub Actions example
- name: Cache security scan
  uses: actions/cache@v3
  with:
    path: .safeguard-cache
    key: ${{ runner.os }}-safeguard-${{ hashFiles('**/*.php') }}
```

### High Memory Usage

**Problem**: Security checks consume too much memory

**Solutions**:

#### 1. Increase PHP memory limit
```ini
; php.ini
memory_limit = 512M
```

#### 2. Process files in batches
```php
// In custom rules, process large datasets in chunks
$files = collect($allFiles)->chunk(100);
foreach ($files as $batch) {
    // Process batch
}
```

#### 3. Use generators for large datasets
```php
// Instead of loading all files at once
function scanFiles(): Generator
{
    foreach ($files as $file) {
        yield $file;
    }
}
```

## Output Issues

### No Output

**Problem**: Commands run but show no output

**Solution**:
```bash
# Check if rules are enabled
php artisan safeguard:list

# Run with verbose output
php artisan safeguard:check -v

# Check for suppressed output
php artisan safeguard:check --no-interaction
```

### Malformed JSON Output

**Problem**: Invalid JSON when using `--format=json`

**Solution**:
```bash
# Check for PHP errors/warnings
php artisan safeguard:check --format=json 2>&1

# Suppress warnings if needed
php artisan safeguard:check --format=json 2>/dev/null

# Validate JSON output
php artisan safeguard:check --format=json | jq .
```

### Missing Colors/Formatting

**Problem**: Output lacks colors or formatting

**Solution**:
```bash
# Force colors (if terminal supports it)
php artisan safeguard:check --ansi

# Disable colors for CI/CD
php artisan safeguard:check --no-ansi

# Use CI-friendly format
php artisan safeguard:check --ci
```

## Environment-Specific Issues

### Production Environment

**Problem**: Rules behave differently in production

**Common Issues**:

#### 1. Environment detection
```php
// Check current environment
echo app()->environment(); // Should return 'production'

// In .env file
APP_ENV=production
```

#### 2. Cache issues
```bash
# Clear all caches in production
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### 3. File permissions
```bash
# Production file permissions
chmod 644 .env                    # Read-only for group/others
chmod -R 755 storage/             # Writable for application
chmod -R 755 bootstrap/cache/     # Writable for application
```

### Development Environment

**Problem**: Overly strict rules in development

**Solution**:
```php
// config/safeguard.php
'environments' => [
    'local' => [
        // Only essential rules for development
        'app_key_is_set',
        'storage_writable',
        // Don't include production-specific rules
    ],
],
```

## CI/CD Issues

### Failed Pipelines

**Problem**: CI/CD fails due to security checks

**Common Solutions**:

#### 1. Environment setup
```yaml
# GitHub Actions
- name: Setup environment
  run: |
    cp .env.example .env
    php artisan key:generate
    # Set proper values for CI
```

#### 2. Missing dependencies
```yaml
# Ensure all dependencies are installed
- name: Install dependencies
  run: composer install --no-interaction --prefer-dist
```

#### 3. File permissions
```yaml
# Fix permissions in CI
- name: Fix permissions
  run: |
    chmod -R 755 storage/
    chmod -R 755 bootstrap/cache/
```

### Exit Code Issues

**Problem**: Wrong exit codes from security checks

**Solution**:
```bash
# Check exit code behavior
php artisan safeguard:check; echo "Exit code: $?"

# Use --fail-on-error for CI/CD
php artisan safeguard:check --fail-on-error

# Handle exit codes in scripts
if ! php artisan safeguard:check --fail-on-error; then
    echo "Security check failed"
    exit 1
fi
```

## Debugging

### Enable Debug Mode

```bash
# Run with maximum verbosity
php artisan safeguard:check -vvv

# Check Laravel logs
tail -f storage/logs/laravel.log

# Enable query logging (if rules use database)
DB::enableQueryLog();
// Run checks
dd(DB::getQueryLog());
```

### Manual Testing

```php
// Create a test script
<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Test specific functionality
$manager = app(Grazulex\LaravelSafeguard\Services\SafeguardManager::class);
$rules = $manager->getRules();
dd($rules);
```

### Common Debug Information

When reporting issues, include:

```bash
# System information
php --version
composer --version

# Laravel information
php artisan --version
php artisan env

# Package information
composer show grazulex/laravel-safeguard

# Configuration
php artisan config:show safeguard

# Rule status
php artisan safeguard:list
```

## Detailed Output Issues

### No Detailed Information Shown

**Problem**: Using `--details` or `--show-all` but not seeing additional information

**Possible Causes**:
1. Rules don't provide detailed information
2. Output is being truncated
3. Using incompatible options

**Solutions**:
```bash
# Verify the options are working
php artisan safeguard:check --show-all | head -50

# Check specific rule details
php artisan safeguard:check --details --format=json | jq '.results[0].details'

# Ensure you're not using --ci which may suppress details
php artisan safeguard:check --details --format=cli
```

### Detailed Output Too Verbose

**Problem**: `--show-all` produces too much output

**Solutions**:
```bash
# Use --details to show only failed check details
php artisan safeguard:check --details

# Use JSON format for programmatic processing
php artisan safeguard:check --show-all --format=json

# Pipe to less for pagination
php artisan safeguard:check --show-all | less

# Save to file for review
php artisan safeguard:check --show-all > security-audit.txt
```

### Details Not Helpful

**Problem**: Detailed information doesn't provide actionable guidance

**Solution**: 
The quality of details depends on rule implementation. Core rules provide:
- Current settings
- Recommendations  
- Security impact
- File paths where relevant

```bash
# Check which rules provide the most helpful details
php artisan safeguard:check --show-all --format=json | jq '.results[] | select(.details.recommendation != null) | .rule'
```

### Performance Issues with Detailed Output

**Problem**: Commands with `--show-all` run slowly

**Solutions**:
```bash
# Use --details instead (only failed checks)
php artisan safeguard:check --details

# Run without details for quick checks
php artisan safeguard:check

# Use specific environment rules only
php artisan safeguard:check --env=production --env-rules --details
```

## Getting Help

If you can't resolve an issue:

1. **Check documentation**: Review relevant docs sections
2. **Search issues**: Look for similar issues on GitHub
3. **Enable detailed output**: Use `--details` or `--show-all` for more context
4. **Create minimal reproduction**: Isolate the problem
5. **Report bug**: Include debug information above

### Creating a Minimal Reproduction

```bash
# Create fresh Laravel installation
composer create-project laravel/laravel safeguard-debug
cd safeguard-debug

# Install Safeguard
composer require --dev grazulex/laravel-safeguard

# Reproduce the issue with details
php artisan safeguard:check --details

# If the issue involves specific rules, show rule status
php artisan safeguard:list

# Share the exact steps and output
```