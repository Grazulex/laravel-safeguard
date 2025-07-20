# Laravel Safeguard Examples

This directory contains practical examples and code samples for using Laravel Safeguard in various scenarios.

## 📁 Directory Structure

```
examples/
├── README.md                          # This file
├── comprehensive-demo.php             # Complete feature demonstration
├── basic-usage/                       # Basic usage examples
│   ├── simple-check.php
│   ├── detailed-output.php
│   ├── environment-specific.php
│   ├── environment-specific-advanced.php
│   └── json-output.php
├── custom-rules/                      # Custom security rule examples
│   ├── make-rule-example.php
│   ├── DatabaseSecurityRule.php
│   └── AdvancedDatabaseSecurityRule.php
├── configuration/                     # Configuration examples
│   ├── production-config.php
│   └── development-config.php
├── ci-cd/                            # CI/CD integration examples
│   ├── github-actions/
│   │   ├── security.yml
│   │   └── detailed-security.yml
│   └── gitlab-ci/
│       └── .gitlab-ci.yml
└── scripts/                          # Utility scripts
    ├── pre-deploy-check.sh
    └── security-report-generator.sh
```

## 🚀 Quick Start Examples

### Complete Feature Demo
```bash
php comprehensive-demo.php
```

### Basic Security Check
```bash
cd examples/basic-usage/
php simple-check.php
```

### Custom Rule Implementation
```bash
cd examples/custom-rules/
php make-rule-example.php
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
- **Detailed Output**: Using --details and --show-all options for comprehensive information
- **Environment-Specific**: Running checks for different environments
- **Environment-Specific Advanced**: Advanced environment-specific rules with --env-rules option
- **JSON Output**: Programmatic usage with JSON output

### 2. Custom Rules
Real-world examples of custom security rules for specific use cases.

- **Make Rule Example**: Complete guide to creating custom rules with different severity levels
- **Database Security**: Validate database configuration and credentials
- **Advanced Database Security**: Extended database security validation with comprehensive checks

### 3. Configuration
Various configuration setups for different scenarios.

- **Production Config**: Strict security rules for production
- **Development Config**: Developer-friendly configuration

### 4. CI/CD Integration
Ready-to-use CI/CD pipeline configurations.

- **GitHub Actions**: Complete workflow files (basic and detailed)
- **GitLab CI**: Pipeline configurations

### 5. Scripts
Utility scripts for automation and reporting.

- **Pre-Deploy Check**: Pre-deployment security validation
- **Report Generator**: Generate comprehensive security reports

## 🧪 Testing Examples

All examples include documentation and can be explored:

```bash
# View a specific example
cd examples/basic-usage/
php simple-check.php

# View custom rule example
cd examples/custom-rules/
php make-rule-example.php

# View script examples
cd examples/scripts/
cat pre-deploy-check.sh
```

**Note**: Examples are primarily demonstration scripts that show concepts and expected output. To actually run Laravel Safeguard commands, you need a Laravel application environment.

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