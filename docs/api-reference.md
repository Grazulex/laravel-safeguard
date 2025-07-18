# API Reference

Laravel Safeguard provides a programmatic API for integrating security checks into your Laravel applications. This reference covers all available classes, methods, and interfaces.

## Core Classes

### SafeguardManager

The main service class for managing and executing security rules.

```php
use Grazulex\LaravelSafeguard\Services\SafeguardManager;

$manager = app(SafeguardManager::class);
```

#### Methods

##### `registerRule(SafeguardRule $rule): self`

Register a new security rule.

```php
$manager->registerRule(new CustomSecurityRule());
```

**Parameters:**
- `$rule` - Instance of a class implementing `SafeguardRule`

**Returns:** Self for method chaining

##### `loadCustomRules(): self`

Load custom rules from the configured path.

```php
$manager->loadCustomRules();
```

**Returns:** Self for method chaining

##### `getRules(): Collection`

Get all registered rules.

```php
$allRules = $manager->getRules();

foreach ($allRules as $rule) {
    echo $rule->id() . "\n";
}
```

**Returns:** `Illuminate\Support\Collection` of `SafeguardRule` instances

##### `getRule(string $id): ?SafeguardRule`

Get a specific rule by ID.

```php
$rule = $manager->getRule('app-key-is-set');

if ($rule) {
    $result = $rule->check();
}
```

**Parameters:**
- `$id` - Rule identifier

**Returns:** `SafeguardRule` instance or `null`

##### `getEnabledRules(): Collection`

Get rules that are enabled in configuration.

```php
$enabledRules = $manager->getEnabledRules();
echo "Enabled rules: " . $enabledRules->count();
```

**Returns:** `Collection` of enabled `SafeguardRule` instances

##### `getRulesForEnvironment(string $environment): Collection`

Get rules configured for a specific environment.

```php
$productionRules = $manager->getRulesForEnvironment('production');
$stagingRules = $manager->getRulesForEnvironment('staging');
```

**Parameters:**
- `$environment` - Environment name

**Returns:** `Collection` of applicable `SafeguardRule` instances

##### `runChecks(?string $environment = null): Collection`

Execute security checks and return results.

```php
// Run all enabled rules
$results = $manager->runChecks();

// Run rules for specific environment
$results = $manager->runChecks('production');

foreach ($results as $result) {
    echo "Rule: " . $result['rule'] . "\n";
    echo "Status: " . ($result['result']->passed() ? 'PASS' : 'FAIL') . "\n";
}
```

**Parameters:**
- `$environment` - Optional environment name

**Returns:** `Collection` of result arrays with keys:
- `rule` - Rule ID
- `description` - Rule description
- `severity` - Rule severity
- `result` - `SafeguardResult` instance

### SafeguardResult

Represents the result of a security rule check.

```php
use Grazulex\LaravelSafeguard\SafeguardResult;
```

#### Static Factory Methods

##### `pass(string $message, array $details = []): SafeguardResult`

Create a passing result.

```php
return SafeguardResult::pass(
    'APP_KEY is properly configured',
    ['key_length' => 32, 'format' => 'base64']
);
```

##### `fail(string $message, string $severity = 'error', array $details = []): SafeguardResult`

Create a failing result.

```php
return SafeguardResult::fail(
    'Database credentials are weak',
    'critical',
    ['recommendation' => 'Use strong passwords']
);
```

##### `warning(string $message, array $details = []): SafeguardResult`

Create a warning result.

```php
return SafeguardResult::warning(
    'Two-factor authentication not configured',
    ['recommendation' => 'Enable 2FA for admin users']
);
```

##### `critical(string $message, array $details = []): SafeguardResult`

Create a critical failure result.

```php
return SafeguardResult::critical(
    'APP_DEBUG enabled in production',
    ['current_value' => true, 'expected' => false]
);
```

#### Instance Methods

##### `passed(): bool`

Check if the rule passed.

```php
if ($result->passed()) {
    echo "Security check passed!";
}
```

##### `message(): string`

Get the result message.

```php
echo $result->message();
// Output: "APP_KEY is properly configured"
```

##### `severity(): string`

Get the severity level.

```php
$severity = $result->severity();
// Values: 'info', 'warning', 'error', 'critical'
```

##### `details(): array`

Get additional details about the result.

```php
$details = $result->details();
foreach ($details as $key => $value) {
    echo "$key: $value\n";
}
```

## Contracts/Interfaces

### SafeguardRule

Interface that all security rules must implement.

```php
use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;

class CustomRule implements SafeguardRule
{
    // Implementation required
}
```

#### Required Methods

##### `id(): string`

Return unique identifier for the rule.

```php
public function id(): string
{
    return 'custom-security-check';
}
```

##### `description(): string`

Return human-readable description.

```php
public function description(): string
{
    return 'Validates custom security configuration';
}
```

##### `check(): SafeguardResult`

Perform the security check.

```php
public function check(): SafeguardResult
{
    if ($this->isSecure()) {
        return SafeguardResult::pass('Security check passed');
    }
    
    return SafeguardResult::fail('Security issue found', 'warning');
}
```

##### `appliesToEnvironment(string $environment): bool`

Check if rule applies to given environment.

```php
public function appliesToEnvironment(string $environment): bool
{
    return in_array($environment, ['production', 'staging']);
}
```

##### `severity(): string`

Return the default severity level.

```php
public function severity(): string
{
    return 'critical'; // or 'error', 'warning', 'info'
}
```

## Service Provider Integration

### Registering Custom Rules

Register rules in your service provider:

```php
use Grazulex\LaravelSafeguard\Services\SafeguardManager;

public function boot()
{
    $manager = $this->app->make(SafeguardManager::class);
    
    // Register individual rules
    $manager->registerRule(new CustomSecurityRule());
    $manager->registerRule(new ApiSecurityRule());
    
    // Load rules from directory
    $manager->loadCustomRules();
}
```

### Dependency Injection

Use dependency injection in controllers and services:

```php
use Grazulex\LaravelSafeguard\Services\SafeguardManager;

class SecurityController extends Controller
{
    public function __construct(
        private SafeguardManager $safeguard
    ) {}
    
    public function runChecks(Request $request)
    {
        $environment = $request->get('environment', 'production');
        $results = $this->safeguard->runChecks($environment);
        
        return response()->json([
            'status' => $results->contains(fn($r) => !$r['result']->passed()) ? 'failed' : 'passed',
            'results' => $results->toArray(),
        ]);
    }
}
```

## Programmatic Usage Examples

### Basic Security Check

```php
use Grazulex\LaravelSafeguard\Services\SafeguardManager;

// Get the manager
$manager = app(SafeguardManager::class);

// Run all enabled rules
$results = $manager->runChecks();

// Process results
$failed = $results->filter(fn($r) => !$r['result']->passed());

if ($failed->isNotEmpty()) {
    foreach ($failed as $failure) {
        Log::warning("Security check failed: {$failure['rule']}", [
            'message' => $failure['result']->message(),
            'severity' => $failure['result']->severity(),
            'details' => $failure['result']->details(),
        ]);
    }
}
```

### Environment-Specific Checks

```php
$environments = ['staging', 'production'];

foreach ($environments as $env) {
    $results = $manager->runChecks($env);
    
    $summary = [
        'environment' => $env,
        'total' => $results->count(),
        'passed' => $results->filter(fn($r) => $r['result']->passed())->count(),
        'failed' => $results->filter(fn($r) => !$r['result']->passed())->count(),
    ];
    
    echo "Environment: {$env}\n";
    echo "Total: {$summary['total']}, Passed: {$summary['passed']}, Failed: {$summary['failed']}\n";
}
```

### Custom Rule Registration

```php
use Grazulex\LaravelSafeguard\Contracts\SafeguardRule;
use Grazulex\LaravelSafeguard\SafeguardResult;

class DatabaseSecurityRule implements SafeguardRule
{
    public function id(): string
    {
        return 'database-security-custom';
    }
    
    public function description(): string
    {
        return 'Custom database security validation';
    }
    
    public function check(): SafeguardResult
    {
        $connections = config('database.connections');
        
        foreach ($connections as $name => $config) {
            if (empty($config['password'])) {
                return SafeguardResult::critical(
                    "Database connection '{$name}' has no password",
                    ['connection' => $name, 'recommendation' => 'Set a strong password']
                );
            }
        }
        
        return SafeguardResult::pass('All database connections have passwords');
    }
    
    public function appliesToEnvironment(string $environment): bool
    {
        return true; // Apply to all environments
    }
    
    public function severity(): string
    {
        return 'critical';
    }
}

// Register the rule
$manager = app(SafeguardManager::class);
$manager->registerRule(new DatabaseSecurityRule());
```

### Conditional Rule Execution

```php
// Get specific rules
$criticalRules = $manager->getRules()->filter(
    fn($rule) => $rule->severity() === 'critical'
);

// Execute only critical rules
foreach ($criticalRules as $rule) {
    if ($rule->appliesToEnvironment('production')) {
        $result = $rule->check();
        
        if (!$result->passed()) {
            // Handle critical failure
            Log::critical("Critical security issue: {$rule->id()}", [
                'message' => $result->message(),
                'details' => $result->details(),
            ]);
        }
    }
}
```

### Result Processing

```php
$results = $manager->runChecks('production');

// Group by severity
$groupedResults = $results->groupBy(fn($result) => $result['result']->severity());

foreach ($groupedResults as $severity => $severityResults) {
    echo "Severity: {$severity}\n";
    
    foreach ($severityResults as $result) {
        $status = $result['result']->passed() ? 'PASS' : 'FAIL';
        echo "  [{$status}] {$result['rule']}: {$result['result']->message()}\n";
    }
}
```

### Integration with Events

```php
use Grazulex\LaravelSafeguard\Services\SafeguardManager;

Event::listen('safeguard.check.completed', function ($results) {
    $failed = collect($results)->filter(fn($r) => !$r['result']->passed());
    
    if ($failed->isNotEmpty()) {
        // Send notification
        Notification::route('slack', config('slack.webhook'))
            ->notify(new SecurityCheckFailedNotification($failed));
    }
});

// In your service provider or controller
$results = $manager->runChecks();
Event::dispatch('safeguard.check.completed', [$results]);
```

### Middleware Integration

```php
use Grazulex\LaravelSafeguard\Services\SafeguardManager;

class SecurityCheckMiddleware
{
    public function __construct(
        private SafeguardManager $safeguard
    ) {}
    
    public function handle(Request $request, Closure $next)
    {
        // Run security checks for production-critical routes
        if (app()->environment('production') && $this->isCriticalRoute($request)) {
            $results = $this->safeguard->runChecks('production');
            
            $criticalFailures = collect($results)->filter(function ($result) {
                return !$result['result']->passed() && 
                       $result['result']->severity() === 'critical';
            });
            
            if ($criticalFailures->isNotEmpty()) {
                abort(503, 'Service temporarily unavailable due to security issues');
            }
        }
        
        return $next($request);
    }
    
    private function isCriticalRoute(Request $request): bool
    {
        return $request->is(['admin/*', 'api/sensitive/*']);
    }
}
```

## Error Handling

### Exception Types

Laravel Safeguard may throw these exceptions:

- `InvalidConfigurationException` - Configuration errors
- `RuleNotFoundException` - Rule not found
- `SecurityCheckException` - General security check errors

```php
use Grazulex\LaravelSafeguard\Exceptions\SecurityCheckException;

try {
    $results = $manager->runChecks();
} catch (SecurityCheckException $e) {
    Log::error('Security check failed: ' . $e->getMessage());
    
    // Handle gracefully
    return response()->json(['error' => 'Security check unavailable'], 503);
}
```

### Safe Execution

Wrap security checks in try-catch for production safety:

```php
function runSafeSecurityCheck(string $environment = null): array
{
    try {
        $manager = app(SafeguardManager::class);
        $results = $manager->runChecks($environment);
        
        return [
            'status' => 'success',
            'results' => $results->toArray(),
        ];
    } catch (\Exception $e) {
        Log::error('Security check exception: ' . $e->getMessage());
        
        return [
            'status' => 'error', 
            'message' => 'Security check failed to execute',
            'error' => $e->getMessage(),
        ];
    }
}
```

## Performance Considerations

### Lazy Loading

Rules are loaded lazily to improve performance:

```php
// Rules are not executed until needed
$manager = app(SafeguardManager::class);

// Execution happens here
$results = $manager->runChecks();
```

### Caching Results

Cache results for repeated checks:

```php
use Illuminate\Support\Facades\Cache;

function getCachedSecurityResults(string $environment): Collection
{
    $cacheKey = "security_results_{$environment}";
    
    return Cache::remember($cacheKey, 300, function () use ($environment) {
        $manager = app(SafeguardManager::class);
        return $manager->runChecks($environment);
    });
}
```

### Selective Execution

Execute only specific rules for performance:

```php
$specificRules = ['app-key-is-set', 'csrf-enabled'];

$results = collect();
foreach ($specificRules as $ruleId) {
    $rule = $manager->getRule($ruleId);
    if ($rule && $rule->appliesToEnvironment('production')) {
        $result = $rule->check();
        $results->push([
            'rule' => $ruleId,
            'description' => $rule->description(),
            'severity' => $rule->severity(),
            'result' => $result,
        ]);
    }
}
```

## Related Documentation

- [Custom Rules Guide](custom-rules.md) - Create custom security rules
- [Configuration Reference](configuration-reference.md) - Configure the API
- [Commands Reference](commands.md) - CLI commands that use the API
- [Performance Guide](performance.md) - Optimize API usage