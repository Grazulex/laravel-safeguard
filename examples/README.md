# Laravel Safeguard Examples

This directory contains practical examples and code samples for using Laravel Safeguard in various scenarios.

## 📁 Directory Structure

```
examples/
├── README.md                          # This file
├── basic-usage/                       # Basic usage examples
│   ├── simple-check.php
│   ├── environment-specific.php
│   └── json-output.php
├── custom-rules/                      # Custom security rule examples
│   ├── DatabaseSecurityRule.php
│   ├── ApiSecurityRule.php
│   ├── FileUploadSecurityRule.php
│   └── ThirdPartyServiceSecurityRule.php
├── configuration/                     # Configuration examples
│   ├── production-config.php
│   ├── development-config.php
│   ├── ci-config.php
│   └── multi-environment-config.php
├── ci-cd/                            # CI/CD integration examples
│   ├── github-actions/
│   ├── gitlab-ci/
│   ├── jenkins/
│   ├── azure-pipelines/
│   └── docker/
├── scripts/                          # Utility scripts
│   ├── pre-deploy-check.sh
│   ├── security-report-generator.php
│   └── batch-environment-check.php
└── integration/                      # Framework integration examples
    ├── artisan-commands/
    ├── middleware/
    └── event-listeners/
```

## 🚀 Quick Start Examples

### Basic Security Check
```bash
cd examples/basic-usage/
php simple-check.php
```

### Custom Rule Implementation
```bash
cd examples/custom-rules/
php -f DatabaseSecurityRule.php
```

### CI/CD Setup
```bash
cd examples/ci-cd/github-actions/
cp security.yml ../../.github/workflows/
```

## 📚 Example Categories

### 1. Basic Usage
Learn the fundamentals of Laravel Safeguard with simple, practical examples.

- **Simple Check**: Basic security audit
- **Environment-Specific**: Running checks for different environments
- **JSON Output**: Programmatic usage with JSON output

### 2. Custom Rules
Real-world examples of custom security rules for specific use cases.

- **Database Security**: Validate database configuration and credentials
- **API Security**: Check API routes and authentication
- **File Upload Security**: Validate file upload configurations
- **Third-Party Services**: Audit external service integrations

### 3. Configuration
Various configuration setups for different scenarios.

- **Production Config**: Strict security rules for production
- **Development Config**: Developer-friendly configuration
- **CI Config**: Optimized for continuous integration
- **Multi-Environment**: Complex multi-environment setup

### 4. CI/CD Integration
Ready-to-use CI/CD pipeline configurations.

- **GitHub Actions**: Complete workflow files
- **GitLab CI**: Pipeline configurations
- **Jenkins**: Declarative and scripted pipelines
- **Azure Pipelines**: YAML configurations
- **Docker**: Containerized security checks

### 5. Scripts
Utility scripts for automation and reporting.

- **Pre-Deploy Check**: Pre-deployment security validation
- **Report Generator**: Generate comprehensive security reports
- **Batch Checker**: Check multiple environments at once

### 6. Integration
Advanced integration examples with Laravel features.

- **Artisan Commands**: Custom commands that use Safeguard
- **Middleware**: HTTP middleware for runtime security checks
- **Event Listeners**: React to security events

## 🧪 Testing Examples

All examples include tests and can be executed independently:

```bash
# Test a specific example
cd examples/custom-rules/
php DatabaseSecurityRule.php

# Test all examples
./test-all-examples.sh
```

## 🔧 Requirements

- PHP 8.3 or higher
- Laravel 12.19 or higher (for Laravel-specific examples)
- Composer (for dependency management)

## 📝 Usage Instructions

1. **Copy Examples**: Copy relevant examples to your project
2. **Customize**: Modify examples to fit your specific needs
3. **Test**: Always test examples in your environment
4. **Integrate**: Integrate into your development workflow

## 🛠️ Example Modifications

### Adapting Configuration Examples

```php
// Copy configuration example
cp examples/configuration/production-config.php config/safeguard.php

// Customize for your needs
// Edit config/safeguard.php with your specific requirements
```

### Using Custom Rules

```php
// Copy custom rule
cp examples/custom-rules/DatabaseSecurityRule.php app/SafeguardRules/

// Register in configuration
// Add rule to config/safeguard.php
```

### Implementing CI/CD

```yaml
# Copy GitHub Actions workflow
cp examples/ci-cd/github-actions/security.yml .github/workflows/

# Customize for your project
# Edit .github/workflows/security.yml
```

## 📊 Example Output

Most examples include sample output to help you understand what to expect:

```bash
🔐 Laravel Safeguard Security Check
═══════════════════════════════════════

Environment: production

✅ APP_KEY is set
❌ APP_DEBUG is true in production
✅ CSRF protection enabled
⚠️  HTTPS not enforced (rule disabled)

═══════════════════════════════════════
🎯 1 issue found, 2 checks passed
```

## 🤝 Contributing Examples

Have a useful example? We'd love to include it! Please:

1. Follow the existing structure and naming conventions
2. Include clear documentation and comments
3. Add sample output where applicable
4. Test your example thoroughly
5. Submit a pull request

## 📖 Related Documentation

- [Installation Guide](../docs/installation.md) - Get started with Laravel Safeguard
- [Configuration Guide](../docs/configuration.md) - Learn about configuration options
- [Custom Rules Guide](../docs/custom-rules.md) - Create your own security rules
- [CI/CD Integration Guide](../docs/ci-cd-integration.md) - Automate security checks

## 🆘 Getting Help

- **Documentation**: Check the [docs](../docs/) directory
- **Issues**: Report problems on [GitHub Issues](https://github.com/Grazulex/laravel-safeguard/issues)
- **Discussions**: Ask questions in [GitHub Discussions](https://github.com/Grazulex/laravel-safeguard/discussions)