# Laravel Safeguard

<img src="new_logo.png" alt="Laravel Safeguard" width="200">

Comprehensive security auditing and threat detection system for Laravel applications. Real-time monitoring, automated security assessments, and detailed security reporting.

[![Latest Version](https://img.shields.io/packagist/v/grazulex/laravel-safeguard.svg?style=flat-square)](https://packagist.org/packages/grazulex/laravel-safeguard)
[![Total Downloads](https://img.shields.io/packagist/dt/grazulex/laravel-safeguard.svg?style=flat-square)](https://packagist.org/packages/grazulex/laravel-safeguard)
[![License](https://img.shields.io/github/license/grazulex/laravel-safeguard.svg?style=flat-square)](https://github.com/Grazulex/laravel-safeguard/blob/main/LICENSE.md)
[![PHP Version](https://img.shields.io/packagist/php-v/grazulex/laravel-safeguard.svg?style=flat-square)](https://php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-12.x-ff2d20?style=flat-square&logo=laravel)](https://laravel.com/)
[![Tests](https://img.shields.io/github/actions/workflow/status/grazulex/laravel-safeguard/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/Grazulex/laravel-safeguard/actions)
[![Code Style](https://img.shields.io/badge/code%20style-pint-000000?style=flat-square&logo=laravel)](https://github.com/laravel/pint)

## 📖 Table of Contents

- [Overview](#overview)
- [✨ Features](#-features)
- [📦 Installation](#-installation)
- [🚀 Quick Start](#-quick-start)
- [🔒 Security Auditing](#-security-auditing)
- [🚨 Threat Detection](#-threat-detection)
- [📊 Security Dashboard](#-security-dashboard)
- [⚙️ Configuration](#️-configuration)
- [📚 Documentation](#-documentation)
- [💡 Examples](#-examples)
- [🧪 Testing](#-testing)
- [🔧 Requirements](#-requirements)
- [🚀 Performance](#-performance)
- [🤝 Contributing](#-contributing)
- [🔒 Security](#-security)
- [📄 License](#-license)

## Overview

Laravel Safeguard is a comprehensive security auditing and threat detection system for Laravel applications. It provides real-time monitoring, automated security assessments, and detailed reporting to keep your application secure.

**Perfect for enterprise applications, security-conscious projects, and applications requiring compliance with security standards.**

### 🎯 Use Cases

Laravel Safeguard is perfect for:

- **Enterprise Applications** - Comprehensive security monitoring
- **Financial Systems** - Fraud detection and prevention
- **Healthcare Apps** - HIPAA compliance and data protection  
- **E-commerce** - Transaction security and user protection
- **API Security** - Rate limiting and abuse detection

## ✨ Features

- 🚀 **Real-time Monitoring** - Live security event tracking and alerting
- 🔍 **Vulnerability Scanning** - Automated security vulnerability detection
- 🛡️ **Intrusion Detection** - Advanced threat detection algorithms
- 📊 **Security Dashboard** - Comprehensive security metrics and reporting
- 🚨 **Alert System** - Configurable alerts for security events
- 🔐 **Access Control** - Role-based access control monitoring
- 📋 **Audit Logging** - Detailed security event logging
- 🎯 **Rate Limiting** - Advanced rate limiting with threat intelligence
- ✅ **Compliance Reporting** - Generate compliance reports
- 📈 **Security Analytics** - Deep security insights and trends
- 🧪 **Penetration Testing** - Built-in security testing tools
- ⚡ **Performance Optimized** - Minimal impact on application performance

## 📦 Installation

Install the package via Composer:

```bash
composer require grazulex/laravel-safeguard
```

> **💡 Auto-Discovery**  
> The service provider will be automatically registered thanks to Laravel's package auto-discovery.

Publish configuration:

```bash
php artisan vendor:publish --tag=safeguard-config
```

## 🚀 Quick Start

### 1. Initialize Safeguard

```bash
php artisan safeguard:install
```

### 2. Configure Security Rules

```php
// config/safeguard.php
return [
    'threat_detection' => [
        'enabled' => true,
        'sql_injection' => true,
        'xss_protection' => true,
        'brute_force' => true,
    ],
    
    'rate_limiting' => [
        'enabled' => true,
        'requests_per_minute' => 60,
        'burst_limit' => 100,
    ],
    
    'audit_logging' => [
        'enabled' => true,
        'log_failed_logins' => true,
        'log_data_access' => true,
    ],
];
```

### 3. Add Middleware Protection

```php
// app/Http/Kernel.php
protected $middleware = [
    \Grazulex\LaravelSafeguard\Middleware\SecurityMonitor::class,
    \Grazulex\LaravelSafeguard\Middleware\ThreatDetection::class,
];

protected $middlewareGroups = [
    'web' => [
        \Grazulex\LaravelSafeguard\Middleware\RateLimiter::class,
    ],
    'api' => [
        \Grazulex\LaravelSafeguard\Middleware\ApiProtection::class,
    ],
];
```

### 4. Monitor Security Events

```php
use Grazulex\LaravelSafeguard\Facades\Safeguard;

// Get security dashboard data
$dashboard = Safeguard::dashboard();

// Check recent threats
$threats = Safeguard::getThreats(['last_24_hours' => true]);

// Generate security report
$report = Safeguard::generateReport('monthly');

// Get audit logs
$auditLogs = Safeguard::auditLogs()
    ->where('event_type', 'login_attempt')
    ->where('created_at', '>=', now()->subDays(7))
    ->get();
```

## 🔒 Security Auditing

Laravel Safeguard provides comprehensive security auditing:

```php
// Enable automatic auditing
Safeguard::audit(User::class)->track([
    'created', 'updated', 'deleted',
    'login', 'logout', 'password_change'
]);

// Manual audit logging
Safeguard::log('user_data_access', [
    'user_id' => auth()->id(),
    'accessed_resource' => 'sensitive_data',
    'ip_address' => request()->ip(),
]);

// Security scanning
$vulnerabilities = Safeguard::scan([
    'sql_injection' => true,
    'xss_vulnerabilities' => true,
    'csrf_protection' => true,
    'security_headers' => true,
]);
```

## 🚨 Threat Detection

Advanced threat detection capabilities:

```php
use Grazulex\LaravelSafeguard\ThreatDetection\Detectors;

// Configure threat detectors
Safeguard::threats()->register([
    Detectors\SqlInjectionDetector::class,
    Detectors\XssDetector::class,
    Detectors\BruteForceDetector::class,
    Detectors\SuspiciousActivityDetector::class,
]);

// Real-time threat monitoring
Safeguard::threats()->monitor(function ($threat) {
    // Log threat
    Log::warning('Security threat detected', [
        'type' => $threat->getType(),
        'severity' => $threat->getSeverity(),
        'details' => $threat->getDetails(),
    ]);
    
    // Send alert
    if ($threat->getSeverity() === 'high') {
        Mail::to('security@company.com')->send(
            new SecurityAlert($threat)
        );
    }
});
```

## 📊 Security Dashboard

Built-in security dashboard with comprehensive metrics:

```php
// Access dashboard data
$dashboard = Safeguard::dashboard()->getData();

// Dashboard metrics include:
// - Threat detection statistics
// - Failed login attempts
// - Rate limiting statistics
// - Vulnerability scan results
// - Audit log summaries
// - Security score and trends

// Custom dashboard widgets
Safeguard::dashboard()->addWidget('custom_security_metric', function () {
    return [
        'title' => 'Custom Security Metric',
        'value' => $this->calculateCustomMetric(),
        'trend' => 'up',
        'color' => 'green',
    ];
});
```

## ⚙️ Configuration

Laravel Safeguard provides extensive configuration options:

```php
// config/safeguard.php
return [
    'monitoring' => [
        'enabled' => true,
        'real_time_alerts' => true,
        'threat_intelligence' => true,
    ],
    
    'detection_rules' => [
        'sql_injection' => ['enabled' => true, 'sensitivity' => 'high'],
        'xss_protection' => ['enabled' => true, 'sanitize' => true],
        'brute_force' => ['enabled' => true, 'max_attempts' => 5],
    ],
    
    'compliance' => [
        'gdpr' => true,
        'hipaa' => false,
        'pci_dss' => true,
    ],
];
```

## 📚 Documentation

For detailed documentation, examples, and advanced usage:

- 📚 [Full Documentation](https://github.com/Grazulex/laravel-safeguard/wiki)
- 🎯 [Examples](https://github.com/Grazulex/laravel-safeguard/wiki/Examples)
- 🔧 [Configuration](https://github.com/Grazulex/laravel-safeguard/wiki/Configuration)
- 🧪 [Testing](https://github.com/Grazulex/laravel-safeguard/wiki/Testing)
- 🚨 [Threat Detection](https://github.com/Grazulex/laravel-safeguard/wiki/Threat-Detection)

## 💡 Examples

### Basic Security Monitoring

```php
use Grazulex\LaravelSafeguard\Facades\Safeguard;

// Enable monitoring for specific models
class User extends Model
{
    use \Grazulex\LaravelSafeguard\Traits\Auditable;
    
    protected $auditableEvents = ['created', 'updated', 'login'];
}

// Monitor API endpoints
Route::middleware(['safeguard.monitor'])->group(function () {
    Route::get('/api/sensitive-data', [ApiController::class, 'getData']);
});

// Custom threat detection
Safeguard::threats()->detect('custom_threat', function ($request) {
    return $request->has('suspicious_parameter');
});
```

### Advanced Security Configuration

```php
// Custom security rules
Safeguard::rules()->add('financial_transaction', [
    'min_amount' => 0.01,
    'max_amount' => 10000,
    'require_2fa' => true,
    'suspicious_patterns' => [
        'rapid_succession' => true,
        'unusual_amounts' => true,
    ],
]);

// Security event handling
Safeguard::events()->listen('threat_detected', function ($threat) {
    // Automatically block suspicious IPs
    if ($threat->getSeverity() === 'critical') {
        Safeguard::firewall()->block($threat->getIpAddress());
    }
});
```

Check out the [examples on the wiki](https://github.com/Grazulex/laravel-safeguard/wiki/Examples) for more examples.

## 🧪 Testing

Laravel Safeguard includes security testing utilities:

```php
use Grazulex\LaravelSafeguard\Testing\SecurityTester;

public function test_sql_injection_protection()
{
    SecurityTester::make()
        ->attemptSqlInjection('/api/users?id=1; DROP TABLE users;--')
        ->assertBlocked()
        ->assertThreatLogged('sql_injection');
}

public function test_rate_limiting()
{
    SecurityTester::make()
        ->simulateRequests('/api/endpoint', 100)
        ->assertRateLimited()
        ->assertAuditLogged();
}
```

## 🔧 Requirements

- PHP: ^8.3
- Laravel: ^12.0
- Carbon: ^3.10

## 🚀 Performance

Laravel Safeguard is optimized for performance:

- **Minimal Overhead**: Less than 2ms additional request time
- **Efficient Monitoring**: Asynchronous threat detection
- **Caching**: Security rules and patterns are cached
- **Database Optimized**: Efficient audit log storage

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## 🔒 Security

If you discover a security vulnerability, please review our [Security Policy](SECURITY.md) before disclosing it.

## 📄 License

Laravel Safeguard is open-sourced software licensed under the [MIT license](LICENSE.md).

---

**Made with ❤️ for the Laravel community**

### Resources

- [📖 Documentation](https://github.com/Grazulex/laravel-safeguard/wiki)
- [💬 Discussions](https://github.com/Grazulex/laravel-safeguard/discussions)
- [🐛 Issue Tracker](https://github.com/Grazulex/laravel-safeguard/issues)
- [📦 Packagist](https://packagist.org/packages/grazulex/laravel-safeguard)

### Community Links

- [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) - Our code of conduct
- [CONTRIBUTING.md](CONTRIBUTING.md) - How to contribute
- [SECURITY.md](SECURITY.md) - Security policy
- [RELEASES.md](RELEASES.md) - Release notes and changelog
