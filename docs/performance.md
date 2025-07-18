# Performance Optimization

Laravel Safeguard is designed to be fast and efficient, but when dealing with large codebases or extensive rule sets, you may want to optimize performance. This guide covers strategies to keep security checks running smoothly.

## Understanding Performance Impact

### Rule Execution Time

Different types of rules have varying performance characteristics:

- **Configuration Rules**: Very fast (microseconds)
- **File System Rules**: Fast to moderate (milliseconds)
- **Database Rules**: Moderate (milliseconds to seconds)
- **Code Scanning Rules**: Slower (seconds for large codebases)
- **Network Rules**: Variable (depends on external services)

### Typical Performance Metrics

For a medium Laravel application:
- **Total Rules**: 15-20 rules
- **Execution Time**: 1-5 seconds
- **Memory Usage**: 10-50 MB

## Optimization Strategies

### 1. Rule Selection

Only enable rules you actually need:

```php
// config/safeguard.php
'rules' => [
    // ✅ Enable only necessary rules
    'app-key-is-set' => true,
    'app-debug-false-in-production' => true,
    
    // ❌ Disable unused rules
    'two-factor-auth-enabled' => false, // If not using 2FA
],
```

### 2. Environment-Specific Rules

Use different rule sets for different environments:

```php
'environments' => [
    'local' => [
        // Minimal rules for development
        'app-key-is-set',
    ],
    'production' => [
        // Complete rule set for production
        'app-key-is-set',
        'app-debug-false-in-production',
        'database-connection-encrypted',
        // ... more rules
    ],
],
```

### 3. Optimize Scan Paths

Limit scanning to relevant directories:

```php
'scan_paths' => [
    'app/',           // ✅ Always include
    'config/',        // ✅ Always include
    // 'vendor/',     // ❌ Skip vendor directory
    // 'node_modules/', // ❌ Skip if exists
],
```

### 4. Code Scanning Optimization

#### Exclude Large Files

```php
'scan_exclusions' => [
    '*.min.js',
    '*.min.css',
    'storage/logs/*',
    'bootstrap/cache/*',
],
```

#### Limit File Size

```php
'max_file_size' => 1024 * 1024, // 1MB limit
```

### 5. Parallel Execution

For CI/CD environments, run checks in parallel:

```bash
# Run different environments in parallel
php artisan safeguard:check --env=staging &
php artisan safeguard:check --env=production &
wait
```

## Performance Monitoring

### Timing Individual Rules

Create a custom command to time rule execution:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Grazulex\LaravelSafeguard\Services\SafeguardManager;

class SafeguardProfileCommand extends Command
{
    protected $signature = 'safeguard:profile';
    protected $description = 'Profile individual rule performance';

    public function handle(SafeguardManager $manager)
    {
        $rules = $manager->getEnabledRules();
        
        $this->table(['Rule', 'Time (ms)', 'Memory (MB)'], 
            $rules->map(function ($rule) {
                $start = microtime(true);
                $startMemory = memory_get_usage(true);
                
                $result = $rule->check();
                
                $time = round((microtime(true) - $start) * 1000, 2);
                $memory = round((memory_get_usage(true) - $startMemory) / 1024 / 1024, 2);
                
                return [
                    $rule->id(),
                    $time,
                    $memory
                ];
            })->toArray()
        );
    }
}
```

### Memory Usage Monitoring

Monitor memory usage during execution:

```php
// Log memory usage in custom rules
public function check(): SafeguardResult
{
    $memoryBefore = memory_get_usage(true);
    
    // Your rule logic here
    $result = $this->performCheck();
    
    $memoryAfter = memory_get_usage(true);
    $memoryUsed = $memoryAfter - $memoryBefore;
    
    if ($memoryUsed > 10 * 1024 * 1024) { // 10MB threshold
        \Log::warning("Rule {$this->id()} used {$memoryUsed} bytes of memory");
    }
    
    return $result;
}
```

## Caching Strategies

### 1. Configuration Caching

Cache expensive configuration lookups:

```php
public function check(): SafeguardResult
{
    // ❌ Slow: Re-read config every time
    $config = config('database.connections');
    
    // ✅ Fast: Cache the config
    static $cachedConfig = null;
    if ($cachedConfig === null) {
        $cachedConfig = config('database.connections');
    }
    
    // Use $cachedConfig
}
```

### 2. File System Caching

Cache file existence checks:

```php
private static $fileCache = [];

public function fileExists(string $path): bool
{
    if (!isset(self::$fileCache[$path])) {
        self::$fileCache[$path] = file_exists($path);
    }
    
    return self::$fileCache[$path];
}
```

### 3. Database Query Optimization

Optimize database-related rules:

```php
public function check(): SafeguardResult
{
    // ❌ Multiple queries
    $users = User::all();
    foreach ($users as $user) {
        $user->profile; // N+1 query
    }
    
    // ✅ Single optimized query
    $users = User::with('profile')->get();
}
```

## CI/CD Optimization

### 1. Conditional Execution

Only run security checks when relevant files change:

```yaml
# GitHub Actions
- name: Check if security check needed
  id: check-changes
  run: |
    if git diff --name-only HEAD~1 | grep -E "(\.php$|\.env|composer\.)" ; then
      echo "run-security=true" >> $GITHUB_OUTPUT
    else
      echo "run-security=false" >> $GITHUB_OUTPUT
    fi

- name: Run security checks
  if: steps.check-changes.outputs.run-security == 'true'
  run: php artisan safeguard:check
```

### 2. Result Caching

Cache results between builds:

```yaml
- name: Cache security results
  uses: actions/cache@v3
  with:
    path: .safeguard-cache
    key: security-${{ hashFiles('app/**/*.php', 'config/**/*.php') }}
    
- name: Run security checks
  run: |
    if [ -f .safeguard-cache/results.json ]; then
      echo "Using cached results"
      cat .safeguard-cache/results.json
    else
      php artisan safeguard:check --format=json | tee .safeguard-cache/results.json
    fi
```

### 3. Parallel Jobs

Split rules across multiple jobs:

```yaml
strategy:
  matrix:
    rule-group: [config, security, database, authentication]

steps:
- name: Run rule group
  run: |
    case "${{ matrix.rule-group }}" in
      "config")
        php artisan safeguard:check --rules="app-key-is-set,app-debug-false-in-production"
        ;;
      "security")
        php artisan safeguard:check --rules="csrf-enabled,no-secrets-in-code"
        ;;
      # ... other groups
    esac
```

## Memory Optimization

### 1. Limit Memory Usage

Set memory limits for security checks:

```bash
# Set memory limit
php -d memory_limit=256M artisan safeguard:check

# Or in CI
export MEMORY_LIMIT=256M
php artisan safeguard:check
```

### 2. Stream Processing

For large files, use streaming:

```php
public function scanLargeFile(string $path): array
{
    $handle = fopen($path, 'r');
    $issues = [];
    
    while (($line = fgets($handle)) !== false) {
        if ($this->containsSecret($line)) {
            $issues[] = $line;
        }
    }
    
    fclose($handle);
    return $issues;
}
```

### 3. Garbage Collection

Force garbage collection in memory-intensive rules:

```php
public function check(): SafeguardResult
{
    $result = $this->performExpensiveCheck();
    
    // Force garbage collection
    gc_collect_cycles();
    
    return $result;
}
```

## Large Codebase Strategies

### 1. Incremental Scanning

Only scan changed files:

```php
public function scanChangedFiles(): array
{
    // Get list of changed files from git
    $changedFiles = shell_exec('git diff --name-only HEAD~1');
    $files = array_filter(explode("\n", $changedFiles));
    
    $issues = [];
    foreach ($files as $file) {
        if (str_ends_with($file, '.php')) {
            $issues = array_merge($issues, $this->scanFile($file));
        }
    }
    
    return $issues;
}
```

### 2. Background Processing

For very large projects, consider background processing:

```php
// Queue security checks
dispatch(new SecurityCheckJob($environment));

// Check results later
php artisan queue:work
```

### 3. Selective Rule Execution

Create custom rule sets for different scenarios:

```php
// Quick check for development
'rule_sets' => [
    'quick' => [
        'app-key-is-set',
        'app-debug-false-in-production',
    ],
    'comprehensive' => [
        // All rules
    ],
],
```

## Benchmarking

### Performance Testing

Create automated performance tests:

```php
<?php

namespace Tests\Performance;

use Tests\TestCase;
use Grazulex\LaravelSafeguard\Services\SafeguardManager;

class SafeguardPerformanceTest extends TestCase
{
    public function testPerformance()
    {
        $manager = app(SafeguardManager::class);
        
        $start = microtime(true);
        $results = $manager->runChecks('production');
        $duration = microtime(true) - $start;
        
        // Assert performance is acceptable
        $this->assertLessThan(10.0, $duration, 'Security checks took too long');
        $this->assertLessThan(100 * 1024 * 1024, memory_get_peak_usage(true), 'Too much memory used');
    }
}
```

### Continuous Monitoring

Track performance over time:

```bash
# Add to CI pipeline
echo "$(date),$(php artisan safeguard:profile --format=csv)" >> performance-log.csv
```

## Troubleshooting Performance Issues

### 1. Identify Slow Rules

Use profiling to find bottlenecks:

```bash
# Enable profiling
XDEBUG_MODE=profile php artisan safeguard:check

# Or use custom timing
php artisan safeguard:profile
```

### 2. Resource Monitoring

Monitor system resources during execution:

```bash
# Monitor in real-time
top -p $(pgrep php)

# Memory usage
/usr/bin/time -v php artisan safeguard:check
```

### 3. Common Issues

**File System Scanning Too Slow**
- Reduce scan paths
- Add exclusion patterns
- Use `.gitignore` patterns

**Database Checks Slow**
- Optimize database queries
- Cache connection checks
- Limit query complexity

**Memory Usage Too High**
- Enable garbage collection
- Stream large files
- Reduce cached data

## Best Practices

1. **Profile Regularly**: Monitor performance as your codebase grows
2. **Environment-Specific**: Use appropriate rule sets for each environment
3. **Incremental Checks**: Only check what's changed in development
4. **Cache Wisely**: Cache expensive operations but not rule results
5. **Monitor Resources**: Keep an eye on memory and CPU usage
6. **Optimize Scanning**: Be selective about what directories to scan

## Related Documentation

- [Configuration Guide](configuration.md) - Configure rules efficiently
- [CI/CD Integration](ci-cd-integration.md) - Optimize for continuous integration
- [Custom Rules](custom-rules.md) - Write performance-conscious rules
- [Troubleshooting](troubleshooting.md) - Solve performance problems