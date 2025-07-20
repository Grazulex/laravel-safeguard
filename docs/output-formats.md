# Output Formats Guide

Laravel Safeguard supports multiple output formats for different use cases.

## CLI Format (Default)

The default human-readable format with colors and icons.

### Usage
```bash
php artisan safeguard:check
# or explicitly
php artisan safeguard:check --format=cli
```

### Basic Output Example
```
🔐 Laravel Safeguard Security Check
═══════════════════════════════════════

Environment: production

✅ APP_KEY is set
❌ APP_DEBUG is true in production
✅ CSRF protection enabled
⚠️  HTTPS not enforced (rule disabled)
✅ Storage directories are writable

═══════════════════════════════════════
🎯 1 issue found, 4 checks passed
```

### Detailed Output Options

#### Show Details for Failed Checks Only
```bash
php artisan safeguard:check --details
```

**Example Output:**
```
🔐 Laravel Safeguard Security Check
═══════════════════════════════════════

Environment: production

✅ APP_KEY is set
❌ APP_DEBUG is true in production
   ⚙️ Current Setting: true
   💡 Recommendation: Set APP_DEBUG=false in production environment
   ⚠️ Security Impact: Debug mode exposes sensitive application information

✅ CSRF protection enabled
⚠️  HTTPS not enforced (rule disabled)
✅ Storage directories are writable

═══════════════════════════════════════
🎯 1 issue found, 4 checks passed
```

#### Show Details for All Checks
```bash
php artisan safeguard:check --show-all
```

**Example Output:**
```
🔐 Laravel Safeguard Security Check
═══════════════════════════════════════

Environment: production

✅ APP_KEY is set
   📌 Status: Application key is properly configured
   💡 Recommendation: Rotate key periodically for enhanced security

❌ APP_DEBUG is true in production
   ⚙️ Current Setting: true
   💡 Recommendation: Set APP_DEBUG=false in production environment
   ⚠️ Security Impact: Debug mode exposes sensitive application information

✅ CSRF protection enabled
   📌 Status: CSRF middleware is active and properly configured
   💡 Recommendation: Ensure all forms include CSRF tokens

⚠️  HTTPS not enforced (rule disabled)
   📌 Status: Rule is disabled in configuration
   💡 Recommendation: Enable HTTPS enforcement for production

✅ Storage directories are writable
   📌 Status: All required directories have proper permissions
   💡 Recommendation: Monitor disk space regularly

═══════════════════════════════════════
🎯 1 issue found, 4 checks passed
```

### Features
- **Colors**: Green for success, red for failures, yellow for warnings
- **Icons**: Visual indicators for different result types
- **Summary**: Total counts at the bottom
- **Human-readable**: Designed for developers and manual review
- **Detailed information**: Use `--details` or `--show-all` for additional context
- **Actionable recommendations**: Clear guidance on how to fix issues

## JSON Format

Machine-readable format for programmatic usage and CI/CD integration.

### Usage
```bash
php artisan safeguard:check --format=json
```

### Basic JSON Output
```json
{
  "status": "failed",
  "environment": "production",
  "summary": {
    "total": 5,
    "passed": 4,
    "failed": 1
  },
  "results": [
    {
      "rule": "app-key-is-set",
      "description": "Verifies that Laravel application key is generated",
      "status": "passed",
      "message": "APP_KEY is properly configured",
      "severity": "critical",
      "details": {
        "key_length": 32,
        "key_format": "base64"
      }
    },
    {
      "rule": "app-debug-false-in-production",
      "description": "Ensures APP_DEBUG is false in production environment",
      "status": "failed",
      "message": "APP_DEBUG is enabled in production environment",
      "severity": "critical",
      "details": {
        "current_setting": "true",
        "recommendation": "Set APP_DEBUG=false in your .env file for production",
        "security_impact": "Debug mode exposes sensitive application information"
      }
    }
  ]
}
```

### Enhanced JSON Output with All Details
```bash
php artisan safeguard:check --format=json --show-all
```

**Example with comprehensive details:**
```json
{
  "status": "failed",
  "environment": "production", 
  "summary": {
    "total": 5,
    "passed": 4,
    "failed": 1
  },
  "results": [
    {
      "rule": "app-key-is-set",
      "description": "Verifies that Laravel application key is generated",
      "status": "passed",
      "message": "APP_KEY is properly configured",
      "severity": "critical",
      "details": {
        "current_setting": "Generated",
        "key_length": 32,
        "key_format": "base64",
        "recommendation": "Rotate key periodically for enhanced security",
        "security_impact": "Strong application key provides encryption security"
      }
    },
    {
      "rule": "app-debug-false-in-production", 
      "description": "Ensures APP_DEBUG is false in production environment",
      "status": "failed",
      "message": "APP_DEBUG is enabled in production environment",
      "severity": "critical",
      "details": {
        "current_setting": "true",
        "recommendation": "Set APP_DEBUG=false in your .env file for production",
        "security_impact": "Debug mode exposes sensitive application information",
        "file_path": ".env",
        "expected_value": "false"
      }
    },
    {
      "rule": "csrf-enabled",
      "description": "Ensures CSRF protection is properly configured",
      "status": "passed", 
      "message": "CSRF protection is active",
      "severity": "critical",
      "details": {
        "current_setting": "enabled",
        "middleware_active": true,
        "recommendation": "Ensure all forms include CSRF tokens",
        "security_impact": "Prevents cross-site request forgery attacks"
      }
    }
  ]
}
```

### JSON Schema
```json
{
  "type": "object",
  "properties": {
    "status": {
      "type": "string",
      "enum": ["passed", "failed"]
    },
    "environment": {
      "type": "string",
      "description": "Environment where checks were run"
    },
    "summary": {
      "type": "object",
      "properties": {
        "total": {"type": "integer"},
        "passed": {"type": "integer"},
        "failed": {"type": "integer"}
      }
    },
    "results": {
      "type": "array",
      "items": {
        "type": "object",
        "properties": {
          "rule": {"type": "string"},
          "description": {"type": "string"},
          "status": {"type": "string", "enum": ["passed", "failed"]},
          "message": {"type": "string"},
          "severity": {"type": "string", "enum": ["critical", "error", "warning", "info"]},
          "details": {"type": "object"}
        }
      }
    }
  }
}
```

## CI Format

Optimized format for continuous integration environments.

### Usage
```bash
php artisan safeguard:check --ci
```

### Features
- **No colors**: Plain text for better CI/CD compatibility
- **Compact output**: Reduced visual noise
- **Machine-friendly**: Easy to parse in scripts
- **Exit codes**: Proper exit codes for pipeline control

### Example Output
```
[PASS] app-key-is-set: APP_KEY is properly configured
[FAIL] app-debug-false-in-production: APP_DEBUG is enabled in production environment
[PASS] csrf-enabled: CSRF protection is properly configured
[WARN] https_enforced_in_production: HTTPS enforcement is disabled
[PASS] storage_writable: All storage directories are writable

Summary: 1 failed, 4 passed, 5 total
```

## Combining Options

### CI with JSON Output
```bash
php artisan safeguard:check --ci --format=json
```

This provides JSON output without colors or decorative elements.

### Detailed Information Options

#### Failed Checks Only with Details
```bash
php artisan safeguard:check --details
```

Shows detailed information only for checks that fail, keeping the output focused.

#### Comprehensive Analysis
```bash
php artisan safeguard:check --show-all
```

Shows detailed information for all checks (passed and failed), providing a complete audit trail.

#### Detailed JSON for Automation
```bash
php artisan safeguard:check --format=json --show-all
```

Combines machine-readable JSON format with comprehensive details for automated processing and reporting.

#### CI Mode with Details
```bash
php artisan safeguard:check --ci --details
```

Provides detailed failure information in CI-friendly format (no colors, compact).

### Fail on Error
```bash
php artisan safeguard:check --fail-on-error
```

Exits with code 1 if any rules fail, useful for blocking deployments.

### Environment-Specific with Details
```bash
php artisan safeguard:check --env=production --details --fail-on-error
```

Runs production checks with detailed output and exits on failure.

### Complete Production Audit
```bash
php artisan safeguard:check --env=production --show-all --format=json --fail-on-error
```

Comprehensive production security audit with full details in JSON format.

## Processing Output

### Bash Examples

#### Parse JSON with jq (Enhanced with Details)
```bash
#!/bin/bash

# Run security check with full details and capture JSON
RESULT=$(php artisan safeguard:check --format=json --show-all)

# Extract status and summary
STATUS=$(echo "$RESULT" | jq -r '.status')
FAILED_COUNT=$(echo "$RESULT" | jq -r '.summary.failed')
TOTAL_COUNT=$(echo "$RESULT" | jq -r '.summary.total')

echo "🔐 Security Audit Results"
echo "========================="
echo "Status: $STATUS"
echo "Total Checks: $TOTAL_COUNT"
echo "Failed: $FAILED_COUNT"
echo ""

if [ "$STATUS" = "failed" ]; then
    echo "❌ Security issues found:"
    echo ""
    
    # Extract failed rules with detailed information
    echo "$RESULT" | jq -r '.results[] | select(.status == "failed") | 
        "🚨 " + .rule + "\n" +
        "   Message: " + .message + "\n" +
        "   Severity: " + .severity + "\n" +
        (if .details.current_setting then "   Current: " + .details.current_setting + "\n" else "" end) +
        (if .details.recommendation then "   💡 Fix: " + .details.recommendation + "\n" else "" end) +
        (if .details.security_impact then "   ⚠️  Impact: " + .details.security_impact + "\n" else "" end) +
        ""'
    
    exit 1
else
    echo "✅ All security checks passed!"
    
    # Show summary of passed checks
    echo ""
    echo "📋 Passed Checks:"
    echo "$RESULT" | jq -r '.results[] | select(.status == "passed") | "  ✓ " + .rule + ": " + .message'
fi
```

#### Parse CI format
```bash
#!/bin/bash

# Run security check in CI format
OUTPUT=$(php artisan safeguard:check --ci)

# Count failures
FAILED=$(echo "$OUTPUT" | grep -c "\[FAIL\]" || echo "0")

if [ "$FAILED" -gt 0 ]; then
    echo "Security issues found: $FAILED"
    echo "$OUTPUT" | grep "\[FAIL\]"
    exit 1
fi
```

### PHP Examples

#### Process JSON output
```php
<?php

// Run security check and get JSON output
$output = shell_exec('php artisan safeguard:check --format=json');
$result = json_decode($output, true);

// Process results
if ($result['status'] === 'failed') {
    echo "❌ Security check failed: {$result['summary']['failed']} issues found\n";
    
    // Get failed rules
    $failedRules = array_filter($result['results'], fn($r) => $r['status'] === 'failed');
    
    foreach ($failedRules as $rule) {
        echo "- {$rule['rule']}: {$rule['message']}\n";
        
        // Show recommendation if available
        if (isset($rule['details']['recommendation'])) {
            echo "  💡 {$rule['details']['recommendation']}\n";
        }
    }
    
    exit(1);
} else {
    echo "✅ All security checks passed\n";
}
```

### Python Examples

```python
import json
import subprocess
import sys

# Run security check
result = subprocess.run(
    ['php', 'artisan', 'safeguard:check', '--format=json'],
    capture_output=True,
    text=True
)

# Parse JSON output
data = json.loads(result.stdout)

# Process results
if data['status'] == 'failed':
    print(f"❌ Security check failed: {data['summary']['failed']} issues found")
    
    # Show failed rules
    for rule in data['results']:
        if rule['status'] == 'failed':
            print(f"- {rule['rule']}: {rule['message']}")
            
            # Show recommendation if available
            if 'recommendation' in rule.get('details', {}):
                print(f"  💡 {rule['details']['recommendation']}")
    
    sys.exit(1)
else:
    print("✅ All security checks passed")
```

## Integration Examples

### GitHub Actions

```yaml
- name: Run security checks
  id: security
  run: |
    # Run check and save output
    php artisan safeguard:check --format=json > security-report.json
    
    # Parse results
    STATUS=$(jq -r '.status' security-report.json)
    echo "status=$STATUS" >> $GITHUB_OUTPUT
    
    if [ "$STATUS" = "failed" ]; then
      FAILED=$(jq -r '.summary.failed' security-report.json)
      echo "failed_count=$FAILED" >> $GITHUB_OUTPUT
      exit 1
    fi

- name: Upload security report
  uses: actions/upload-artifact@v3
  if: always()
  with:
    name: security-report
    path: security-report.json

- name: Comment on PR
  if: github.event_name == 'pull_request' && always()
  uses: actions/github-script@v6
  with:
    script: |
      const fs = require('fs');
      const report = JSON.parse(fs.readFileSync('security-report.json', 'utf8'));
      
      let comment = '## 🔐 Security Audit Results\n\n';
      comment += `**Status:** ${report.status === 'passed' ? '✅ Passed' : '❌ Failed'}\n`;
      comment += `**Environment:** ${report.environment}\n`;
      comment += `**Total:** ${report.summary.total} | **Passed:** ${report.summary.passed} | **Failed:** ${report.summary.failed}\n\n`;
      
      if (report.status === 'failed') {
        comment += '### Issues Found\n\n';
        for (const result of report.results) {
          if (result.status === 'failed') {
            comment += `- ❌ **${result.rule}**: ${result.message}\n`;
          }
        }
      }
      
      github.rest.issues.createComment({
        issue_number: context.issue.number,
        owner: context.repo.owner,
        repo: context.repo.repo,
        body: comment
      });
```

### GitLab CI

```yaml
security_audit:
  script:
    - php artisan safeguard:check --format=json > security-report.json
    - |
      if [ "$(jq -r '.status' security-report.json)" = "failed" ]; then
        echo "Security issues found:"
        jq -r '.results[] | select(.status == "failed") | "- " + .rule + ": " + .message' security-report.json
        exit 1
      fi
  artifacts:
    reports:
      # GitLab can parse JSON reports
      junit: security-report.json
    paths:
      - security-report.json
    when: always
```

### Jenkins

```groovy
pipeline {
    agent any
    
    stages {
        stage('Security Audit') {
            steps {
                sh '''
                    php artisan safeguard:check --format=json > security-report.json
                    
                    STATUS=$(jq -r '.status' security-report.json)
                    if [ "$STATUS" = "failed" ]; then
                        echo "Security check failed"
                        jq -r '.results[] | select(.status == "failed") | .rule + ": " + .message' security-report.json
                        exit 1
                    fi
                '''
            }
            post {
                always {
                    archiveArtifacts artifacts: 'security-report.json'
                    publishHTML([
                        allowMissing: false,
                        alwaysLinkToLastBuild: true,
                        keepAll: true,
                        reportDir: '.',
                        reportFiles: 'security-report.json',
                        reportName: 'Security Report'
                    ])
                }
            }
        }
    }
}
```

## Custom Output Processing

### Creating Reports

```php
<?php

// Generate HTML report from JSON
function generateHtmlReport(array $data): string
{
    $html = "<h1>Security Audit Report</h1>";
    $html .= "<p><strong>Environment:</strong> {$data['environment']}</p>";
    $html .= "<p><strong>Status:</strong> " . ($data['status'] === 'passed' ? '✅ Passed' : '❌ Failed') . "</p>";
    
    $html .= "<h2>Summary</h2>";
    $html .= "<ul>";
    $html .= "<li>Total: {$data['summary']['total']}</li>";
    $html .= "<li>Passed: {$data['summary']['passed']}</li>";
    $html .= "<li>Failed: {$data['summary']['failed']}</li>";
    $html .= "</ul>";
    
    $html .= "<h2>Results</h2>";
    $html .= "<table border='1'>";
    $html .= "<tr><th>Rule</th><th>Status</th><th>Message</th><th>Severity</th></tr>";
    
    foreach ($data['results'] as $result) {
        $statusIcon = $result['status'] === 'passed' ? '✅' : '❌';
        $html .= "<tr>";
        $html .= "<td>{$result['rule']}</td>";
        $html .= "<td>{$statusIcon} {$result['status']}</td>";
        $html .= "<td>{$result['message']}</td>";
        $html .= "<td>{$result['severity']}</td>";
        $html .= "</tr>";
    }
    
    $html .= "</table>";
    
    return $html;
}

// Usage
$output = shell_exec('php artisan safeguard:check --format=json');
$data = json_decode($output, true);
$html = generateHtmlReport($data);
file_put_contents('security-report.html', $html);
```

### Slack Notifications

```bash
#!/bin/bash

# Run security check
RESULT=$(php artisan safeguard:check --format=json)
STATUS=$(echo "$RESULT" | jq -r '.status')

if [ "$STATUS" = "failed" ]; then
    FAILED_COUNT=$(echo "$RESULT" | jq -r '.summary.failed')
    
    # Create Slack message
    MESSAGE="🚨 Security audit failed: $FAILED_COUNT issues found in $(echo "$RESULT" | jq -r '.environment') environment"
    
    # Send to Slack webhook
    curl -X POST -H 'Content-type: application/json' \
        --data "{\"text\":\"$MESSAGE\"}" \
        "$SLACK_WEBHOOK_URL"
fi
```

## Best Practices

### Output Format Selection

- **Development**: Use CLI format for human readability
- **CI/CD**: Use JSON format for programmatic processing
- **Monitoring**: Use JSON format for integration with monitoring tools
- **Reports**: Use JSON format and convert to desired report format

### Error Handling

Always handle cases where security checks might fail to run:

```bash
# Bash example with error handling
if ! OUTPUT=$(php artisan safeguard:check --format=json 2>&1); then
    echo "Failed to run security check: $OUTPUT"
    exit 1
fi

if ! echo "$OUTPUT" | jq . >/dev/null 2>&1; then
    echo "Invalid JSON output: $OUTPUT"
    exit 1
fi

# Process valid JSON...
```

### Performance Considerations

- Use `--ci` flag in CI/CD to reduce output overhead
- Cache JSON reports between pipeline stages
- Process large result sets efficiently
- Consider using `--fail-on-error` to exit early on critical issues

## Related Documentation

- [Commands Reference](commands.md) - Available command options
- [CI/CD Integration](ci-cd-integration.md) - Pipeline integration examples
- [Configuration Guide](configuration.md) - Rule configuration options