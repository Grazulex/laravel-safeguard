<?php

/**
 * JSON Output Example
 * 
 * This example demonstrates how to get JSON output from Laravel Safeguard
 * for programmatic usage, CI/CD integration, and reporting.
 */

echo "🔐 Laravel Safeguard - JSON Output Example\n";
echo "==========================================\n\n";

echo "JSON output is useful for:\n";
echo "- CI/CD pipeline integration\n";
echo "- Automated reporting\n";
echo "- Custom dashboards\n";
echo "- Programmatic analysis\n\n";

echo "## Command\n";
echo "php artisan safeguard:check --format=json\n\n";

echo "## Example JSON Output\n\n";

// Example JSON output structure
$exampleOutput = [
    'status' => 'failed',
    'environment' => 'production',
    'summary' => [
        'total' => 6,
        'passed' => 4,
        'failed' => 2,
    ],
    'results' => [
        [
            'rule' => 'app_key_is_set',
            'description' => 'Verifies that Laravel application key is generated',
            'status' => 'passed',
            'message' => 'APP_KEY is properly configured',
            'severity' => 'critical',
            'details' => [
                'key_length' => 32,
                'key_format' => 'base64'
            ]
        ],
        [
            'rule' => 'env_debug_false_in_production',
            'description' => 'Ensures APP_DEBUG is false in production environment',
            'status' => 'failed',
            'message' => 'APP_DEBUG is enabled in production environment',
            'severity' => 'critical',
            'details' => [
                'current_env' => 'production',
                'debug_value' => true,
                'recommendation' => 'Set APP_DEBUG=false in your .env file for production'
            ]
        ],
        [
            'rule' => 'csrf_enabled',
            'description' => 'Ensures CSRF protection is enabled',
            'status' => 'passed',
            'message' => 'CSRF protection is properly configured',
            'severity' => 'critical',
            'details' => [
                'middleware_active' => true
            ]
        ],
        [
            'rule' => 'no_secrets_in_code',
            'description' => 'Detects hardcoded secrets in your codebase',
            'status' => 'failed',
            'message' => 'Hardcoded secrets detected in codebase',
            'severity' => 'critical',
            'details' => [
                'secrets_found' => [
                    'file' => 'config/services.php',
                    'line' => 15,
                    'pattern' => 'STRIPE_SECRET',
                    'value' => 'sk_test_***'
                ],
                'recommendation' => 'Move secrets to environment variables'
            ]
        ],
        [
            'rule' => 'secure_cookies_in_production',
            'description' => 'Validates secure cookie settings for production',
            'status' => 'passed',
            'message' => 'Cookie security is properly configured',
            'severity' => 'error',
            'details' => [
                'secure' => true,
                'http_only' => true,
                'same_site' => 'strict'
            ]
        ],
        [
            'rule' => 'storage_writable',
            'description' => 'Checks if storage directories are writable',
            'status' => 'passed',
            'message' => 'All storage directories are writable',
            'severity' => 'error',
            'details' => [
                'directories_checked' => [
                    'storage/app',
                    'storage/logs',
                    'storage/framework',
                    'bootstrap/cache'
                ]
            ]
        ]
    ]
];

echo "```json\n";
echo json_encode($exampleOutput, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo "\n```\n\n";

echo "## Using JSON Output in Scripts\n\n";

echo "### Bash Example\n";
echo "```bash\n";
echo "#!/bin/bash\n";
echo "\n";
echo "# Run security check and capture JSON output\n";
echo "RESULT=\$(php artisan safeguard:check --format=json)\n";
echo "\n";
echo "# Parse status using jq\n";
echo "STATUS=\$(echo \"\$RESULT\" | jq -r '.status')\n";
echo "FAILED_COUNT=\$(echo \"\$RESULT\" | jq -r '.summary.failed')\n";
echo "\n";
echo "if [ \"\$STATUS\" = \"failed\" ]; then\n";
echo "    echo \"❌ Security check failed: \$FAILED_COUNT issues found\"\n";
echo "    \n";
echo "    # Extract failed rules\n";
echo "    echo \"\$RESULT\" | jq -r '.results[] | select(.status == \"failed\") | \"- ❌ \" + .rule + \": \" + .message'\n";
echo "    \n";
echo "    exit 1\n";
echo "else\n";
echo "    echo \"✅ All security checks passed\"\n";
echo "fi\n";
echo "```\n\n";

echo "### PHP Example\n";
echo "```php\n";
echo "<?php\n";
echo "\n";
echo "// Run security check and get JSON output\n";
echo "\$output = shell_exec('php artisan safeguard:check --format=json');\n";
echo "\$result = json_decode(\$output, true);\n";
echo "\n";
echo "// Process results\n";
echo "if (\$result['status'] === 'failed') {\n";
echo "    echo \"❌ Security check failed: {\$result['summary']['failed']} issues found\\n\";\n";
echo "    \n";
echo "    // Get failed rules\n";
echo "    \$failedRules = array_filter(\$result['results'], fn(\$r) => \$r['status'] === 'failed');\n";
echo "    \n";
echo "    foreach (\$failedRules as \$rule) {\n";
echo "        echo \"- ❌ {\$rule['rule']}: {\$rule['message']}\\n\";\n";
echo "    }\n";
echo "    \n";
echo "    exit(1);\n";
echo "} else {\n";
echo "    echo \"✅ All security checks passed\\n\";\n";
echo "}\n";
echo "```\n\n";

echo "### Python Example\n";
echo "```python\n";
echo "import json\n";
echo "import subprocess\n";
echo "import sys\n";
echo "\n";
echo "# Run security check\n";
echo "result = subprocess.run(\n";
echo "    ['php', 'artisan', 'safeguard:check', '--format=json'],\n";
echo "    capture_output=True,\n";
echo "    text=True\n";
echo ")\n";
echo "\n";
echo "# Parse JSON output\n";
echo "data = json.loads(result.stdout)\n";
echo "\n";
echo "# Process results\n";
echo "if data['status'] == 'failed':\n";
echo "    print(f\"❌ Security check failed: {data['summary']['failed']} issues found\")\n";
echo "    \n";
echo "    # Show failed rules\n";
echo "    for rule in data['results']:\n";
echo "        if rule['status'] == 'failed':\n";
echo "            print(f\"- ❌ {rule['rule']}: {rule['message']}\")\n";
echo "    \n";
echo "    sys.exit(1)\n";
echo "else:\n";
echo "    print(\"✅ All security checks passed\")\n";
echo "```\n\n";

echo "## CI/CD Integration Example\n\n";

echo "### GitHub Actions\n";
echo "```yaml\n";
echo "- name: Run security checks\n";
echo "  run: |\n";
echo "    RESULT=\$(php artisan safeguard:check --format=json)\n";
echo "    echo \"\$RESULT\" > security-report.json\n";
echo "    \n";
echo "    # Check if any issues found\n";
echo "    FAILED=\$(echo \"\$RESULT\" | jq -r '.summary.failed')\n";
echo "    if [ \"\$FAILED\" -gt 0 ]; then\n";
echo "      echo \"Security issues found: \$FAILED\"\n";
echo "      exit 1\n";
echo "    fi\n";
echo "\n";
echo "- name: Upload security report\n";
echo "  uses: actions/upload-artifact@v3\n";
echo "  if: always()\n";
echo "  with:\n";
echo "    name: security-report\n";
echo "    path: security-report.json\n";
echo "```\n\n";

echo "Example completed! ✅\n";