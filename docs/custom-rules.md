# Custom Rules Guide

Laravel Safeguard allows you to create custom security rules tailored to your application's specific requirements.

## Creating a Custom Rule

### Using the Artisan Command

Generate a new custom rule:

```bash
php artisan safeguard:make-rule CustomSecurityRule
```

This creates `app/SafeguardRules/CustomSecurityRule.php`:

```php
<?php

namespace App\SafeguardRules;

use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;

class CustomSecurityRule implements SafeguardRule
{
    public function id(): string
    {
        return 'custom_security_rule';
    }

    public function description(): string
    {
        return 'Custom security validation';
    }

    public function check(): SafeguardResult
    {
        // Your custom logic here
        return SafeguardResult::pass('Custom check passed');
    }

    public function appliesToEnvironment(string $environment): bool
    {
        return true; // Run in all environments
    }

    public function severity(): string
    {
        return 'error';
    }
}
```

### Manual Creation

You can also create rules manually by implementing the `SafeguardRule` contract:

```php
<?php

namespace App\SafeguardRules;

use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;

class DatabaseSecurityRule implements SafeguardRule
{
    public function id(): string
    {
        return 'database_security_check';
    }

    public function description(): string
    {
        return 'Validates database security configuration';
    }

    public function check(): SafeguardResult
    {
        $dbConfig = config('database.connections.mysql');
        
        // Check for default passwords
        if ($dbConfig['password'] === 'password' || $dbConfig['password'] === 'root') {
            return SafeguardResult::critical(
                'Database is using a default password',
                [
                    'connection' => 'mysql',
                    'recommendation' => 'Use a strong, unique password for your database'
                ]
            );
        }

        // Check for SSL in production
        if (app()->environment('production') && !($dbConfig['options'][PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] ?? false)) {
            return SafeguardResult::warning(
                'SSL not configured for database connection in production',
                [
                    'connection' => 'mysql',
                    'recommendation' => 'Enable SSL for secure database connections'
                ]
            );
        }

        return SafeguardResult::pass(
            'Database security configuration is acceptable',
            ['connection' => 'mysql']
        );
    }

    public function appliesToEnvironment(string $environment): bool
    {
        return in_array($environment, ['production', 'staging']);
    }

    public function severity(): string
    {
        return 'critical';
    }
}
```

## SafeguardResult Methods

The `SafeguardResult` class provides methods for different result types:

### Success Results
```php
// Simple pass
SafeguardResult::pass('Check passed');

// Pass with details
SafeguardResult::pass('Configuration is secure', [
    'setting' => 'value',
    'recommendation' => 'Keep this configuration'
]);
```

### Failure Results
```php
// Critical failure
SafeguardResult::critical('Critical security issue', [
    'issue' => 'Details about the problem',
    'recommendation' => 'How to fix it'
]);

// Error
SafeguardResult::error('Security error found', $details);

// Warning
SafeguardResult::warning('Security concern detected', $details);

// Info
SafeguardResult::info('Security information', $details);
```

## Real-World Examples

### API Security Rule

```php
<?php

namespace App\SafeguardRules;

use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;
use Illuminate\Support\Facades\Route;

class ApiSecurityRule implements SafeguardRule
{
    public function id(): string
    {
        return 'api_security_check';
    }

    public function description(): string
    {
        return 'Validates API security configuration';
    }

    public function check(): SafeguardResult
    {
        $apiRoutes = collect(Route::getRoutes())
            ->filter(fn($route) => str_starts_with($route->uri(), 'api/'));

        $unprotectedRoutes = $apiRoutes->filter(function ($route) {
            $middleware = $route->middleware();
            return !in_array('auth:sanctum', $middleware) && 
                   !in_array('auth:api', $middleware);
        });

        if ($unprotectedRoutes->isNotEmpty()) {
            return SafeguardResult::warning(
                'Unprotected API routes found',
                [
                    'unprotected_routes' => $unprotectedRoutes->pluck('uri')->toArray(),
                    'recommendation' => 'Add authentication middleware to API routes'
                ]
            );
        }

        return SafeguardResult::pass(
            'All API routes are properly protected',
            ['total_api_routes' => $apiRoutes->count()]
        );
    }

    public function appliesToEnvironment(string $environment): bool
    {
        return $environment !== 'testing';
    }

    public function severity(): string
    {
        return 'warning';
    }
}
```

### File Upload Security Rule

```php
<?php

namespace App\SafeguardRules;

use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;

class FileUploadSecurityRule implements SafeguardRule
{
    public function id(): string
    {
        return 'file_upload_security';
    }

    public function description(): string
    {
        return 'Validates file upload security configuration';
    }

    public function check(): SafeguardResult
    {
        $maxFileSize = ini_get('upload_max_filesize');
        $maxPostSize = ini_get('post_max_size');
        
        // Check for reasonable file size limits
        if ($this->parseSize($maxFileSize) > 50 * 1024 * 1024) { // 50MB
            return SafeguardResult::warning(
                'File upload size limit is very high',
                [
                    'current_limit' => $maxFileSize,
                    'recommendation' => 'Consider limiting file uploads to reduce security risks'
                ]
            );
        }

        // Check file extensions configuration
        $allowedExtensions = config('filesystems.allowed_extensions', []);
        $dangerousExtensions = ['php', 'exe', 'bat', 'sh', 'cmd'];
        
        $dangerousAllowed = array_intersect($allowedExtensions, $dangerousExtensions);
        
        if (!empty($dangerousAllowed)) {
            return SafeguardResult::critical(
                'Dangerous file extensions are allowed for upload',
                [
                    'dangerous_extensions' => $dangerousAllowed,
                    'recommendation' => 'Remove executable file extensions from allowed uploads'
                ]
            );
        }

        return SafeguardResult::pass('File upload security configuration is acceptable');
    }

    private function parseSize(string $size): int
    {
        $unit = strtolower(substr($size, -1));
        $value = (int) substr($size, 0, -1);
        
        return match($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value
        };
    }

    public function appliesToEnvironment(string $environment): bool
    {
        return true;
    }

    public function severity(): string
    {
        return 'error';
    }
}
```

### Third-Party Service Security Rule

```php
<?php

namespace App\SafeguardRules;

use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;

class ThirdPartyServiceSecurityRule implements SafeguardRule
{
    public function id(): string
    {
        return 'third_party_service_security';
    }

    public function description(): string
    {
        return 'Validates third-party service security configuration';
    }

    public function check(): SafeguardResult
    {
        $issues = [];
        
        // Check Stripe configuration
        if (config('services.stripe.secret')) {
            $stripeSecret = config('services.stripe.secret');
            if (str_starts_with($stripeSecret, 'sk_test_') && app()->environment('production')) {
                $issues[] = 'Stripe test keys are being used in production';
            }
        }

        // Check Mail configuration
        if (config('mail.mailers.smtp.password') === 'password') {
            $issues[] = 'Default SMTP password detected';
        }

        // Check AWS configuration
        if (config('services.aws.key') === 'your-aws-key') {
            $issues[] = 'Default AWS credentials detected';
        }

        if (!empty($issues)) {
            return SafeguardResult::critical(
                'Third-party service security issues found',
                [
                    'issues' => $issues,
                    'recommendation' => 'Update service configurations with proper credentials'
                ]
            );
        }

        return SafeguardResult::pass('Third-party service configurations are secure');
    }

    public function appliesToEnvironment(string $environment): bool
    {
        return in_array($environment, ['production', 'staging']);
    }

    public function severity(): string
    {
        return 'critical';
    }
}
```

## Configuration

### Enabling Custom Rules

Add your custom rule to the configuration:

```php
// config/safeguard.php
'rules' => [
    // Built-in rules
    'app-key-is-set' => true,
    'csrf-enabled' => true,
    
    // Your custom rules
    'database_security_check' => true,
    'api_security_check' => true,
    'file_upload_security' => true,
    'third_party_service_security' => true,
],
```

### Custom Rules Path

Configure where Laravel Safeguard looks for custom rules:

```php
// config/safeguard.php
'custom_rules_path' => app_path('SafeguardRules'),
'custom_rules_namespace' => 'App\\SafeguardRules',
```

## Testing Custom Rules

### Unit Testing

Create tests for your custom rules:

```php
<?php

namespace Tests\Unit\SafeguardRules;

use App\SafeguardRules\DatabaseSecurityRule;
use Tests\TestCase;

class DatabaseSecurityRuleTest extends TestCase
{
    public function test_it_detects_default_passwords()
    {
        config(['database.connections.mysql.password' => 'password']);
        
        $rule = new DatabaseSecurityRule();
        $result = $rule->check();
        
        $this->assertFalse($result->passed());
        $this->assertStringContains('default password', $result->message());
    }

    public function test_it_passes_with_strong_password()
    {
        config(['database.connections.mysql.password' => 'strong-unique-password-123']);
        
        $rule = new DatabaseSecurityRule();
        $result = $rule->check();
        
        $this->assertTrue($result->passed());
    }
}
```

### Manual Testing

Test your custom rules individually:

```bash
php artisan safeguard:test-rule database_security_check
```

## Best Practices

1. **Specific IDs**: Use descriptive, unique rule IDs
2. **Clear Messages**: Provide actionable error messages and recommendations
3. **Environment Awareness**: Consider which environments each rule should run in
4. **Performance**: Keep rule logic efficient for large codebases
5. **Documentation**: Document custom rules for your team
6. **Testing**: Always test custom rules thoroughly
7. **Gradual Rollout**: Start with warning severity, then increase to error/critical

## Advanced Patterns

### Conditional Rules

```php
public function check(): SafeguardResult
{
    if (!$this->shouldRun()) {
        return SafeguardResult::info('Rule skipped due to configuration');
    }
    
    // ... rule logic
}

private function shouldRun(): bool
{
    return config('app.custom_security_enabled', false);
}
```

### Dynamic Configuration

```php
public function check(): SafeguardResult
{
    $configKey = "security.rules.{$this->id()}";
    $ruleConfig = config($configKey, []);
    
    // Use configuration to customize rule behavior
    $threshold = $ruleConfig['threshold'] ?? 10;
    
    // ... rule logic using $threshold
}
```

### Composite Rules

```php
public function check(): SafeguardResult
{
    $results = [];
    
    $results[] = $this->checkDatabaseSecurity();
    $results[] = $this->checkFilePermissions();
    $results[] = $this->checkNetworkConfiguration();
    
    // Combine results and return overall status
    return $this->combineResults($results);
}
```

## Related Documentation

- [Rules Reference](rules-reference.md) - Learn about built-in rules
- [Configuration Guide](configuration.md) - Configure rule behavior
- [API Reference](api-reference.md) - Programmatic usage