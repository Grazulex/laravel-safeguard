# CI/CD Integration Guide

Laravel Safeguard is designed to integrate seamlessly with continuous integration and deployment pipelines.

## GitHub Actions

### Basic Security Workflow

Create `.github/workflows/security.yml`:

```yaml
name: Security Audit

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  security:
    runs-on: ubuntu-latest
    
    steps:
    - name: Checkout code
      uses: actions/checkout@v4
      
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.3'
        extensions: mbstring, xml, ctype, iconv, intl
        
    - name: Cache Composer packages
      id: composer-cache
      uses: actions/cache@v3
      with:
        path: vendor
        key: ${{ runner.os }}-php-${{ hashFiles('**/composer.lock') }}
        restore-keys: |
          ${{ runner.os }}-php-
          
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
      
    - name: Create .env file
      run: |
        cp .env.example .env
        php artisan key:generate
        
    - name: Run security checks
      run: php artisan safeguard:check --ci --fail-on-error
```

### Advanced Security Workflow

```yaml
name: Advanced Security Audit

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  security:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        environment: [staging, production]
        
    steps:
    - name: Checkout code
      uses: actions/checkout@v4
      
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.3'
        
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
      
    - name: Setup environment
      run: |
        cp .env.example .env
        php artisan key:generate
        
    - name: Run security checks for ${{ matrix.environment }}
      run: |
        php artisan safeguard:check \
          --env=${{ matrix.environment }} \
          --format=json \
          --fail-on-error > security-report-${{ matrix.environment }}.json
          
    - name: Upload security report
      uses: actions/upload-artifact@v3
      if: always()
      with:
        name: security-report-${{ matrix.environment }}
        path: security-report-${{ matrix.environment }}.json
        
    - name: Comment PR with results
      if: github.event_name == 'pull_request'
      uses: actions/github-script@v6
      with:
        script: |
          const fs = require('fs');
          const report = JSON.parse(fs.readFileSync('security-report-${{ matrix.environment }}.json', 'utf8'));
          
          const comment = `
          ## Security Audit Results (${{ matrix.environment }})
          
          **Status:** ${report.status === 'passed' ? '✅ Passed' : '❌ Failed'}
          **Environment:** ${report.environment}
          **Total Checks:** ${report.summary.total}
          **Passed:** ${report.summary.passed}
          **Failed:** ${report.summary.failed}
          
          ${report.results.filter(r => r.status === 'failed').map(r => 
            `- ❌ **${r.rule}**: ${r.message}`
          ).join('\n')}
          `;
          
          github.rest.issues.createComment({
            issue_number: context.issue.number,
            owner: context.repo.owner,
            repo: context.repo.repo,
            body: comment
          });
```

## GitLab CI

### Basic Pipeline

Create `.gitlab-ci.yml`:

```yaml
stages:
  - test
  - security

variables:
  COMPOSER_CACHE_DIR: "$CI_PROJECT_DIR/.composer-cache"

cache:
  paths:
    - .composer-cache/
    - vendor/

before_script:
  - php -v
  - composer install --prefer-dist --no-progress --no-interaction

test:
  stage: test
  script:
    - php artisan test

security_audit:
  stage: security
  script:
    - cp .env.example .env
    - php artisan key:generate
    - php artisan safeguard:check --ci --fail-on-error
  artifacts:
    when: always
    reports:
      junit: security-report.xml
    paths:
      - security-report.json
  only:
    - main
    - develop
    - merge_requests
```

### Multi-Environment Pipeline

```yaml
stages:
  - test
  - security

.security_template: &security_template
  script:
    - cp .env.example .env
    - php artisan key:generate
    - |
      php artisan safeguard:check \
        --env=${ENVIRONMENT} \
        --format=json \
        --fail-on-error > security-report-${ENVIRONMENT}.json
  artifacts:
    when: always
    paths:
      - security-report-*.json

security_staging:
  <<: *security_template
  stage: security
  variables:
    ENVIRONMENT: staging
  only:
    - develop

security_production:
  <<: *security_template
  stage: security
  variables:
    ENVIRONMENT: production
  only:
    - main
```

## Jenkins

### Declarative Pipeline

Create `Jenkinsfile`:

```groovy
pipeline {
    agent any
    
    environment {
        COMPOSER_HOME = "${WORKSPACE}/.composer"
    }
    
    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }
        
        stage('Install Dependencies') {
            steps {
                sh 'composer install --no-interaction --prefer-dist'
            }
        }
        
        stage('Setup Environment') {
            steps {
                sh 'cp .env.example .env'
                sh 'php artisan key:generate'
            }
        }
        
        stage('Security Audit') {
            parallel {
                stage('Staging Security') {
                    steps {
                        sh '''
                            php artisan safeguard:check \
                                --env=staging \
                                --format=json \
                                --fail-on-error > security-staging.json
                        '''
                    }
                    post {
                        always {
                            archiveArtifacts artifacts: 'security-staging.json'
                        }
                    }
                }
                
                stage('Production Security') {
                    steps {
                        sh '''
                            php artisan safeguard:check \
                                --env=production \
                                --format=json \
                                --fail-on-error > security-production.json
                        '''
                    }
                    post {
                        always {
                            archiveArtifacts artifacts: 'security-production.json'
                        }
                    }
                }
            }
        }
    }
    
    post {
        always {
            publishHTML([
                allowMissing: false,
                alwaysLinkToLastBuild: true,
                keepAll: true,
                reportDir: '.',
                reportFiles: 'security-*.json',
                reportName: 'Security Report'
            ])
        }
        failure {
            emailext (
                subject: "Security Audit Failed: ${env.JOB_NAME} - ${env.BUILD_NUMBER}",
                body: "Security audit failed. Please check the Jenkins build for details.",
                to: "${env.CHANGE_AUTHOR_EMAIL}"
            )
        }
    }
}
```

## Azure DevOps

Create `azure-pipelines.yml`:

```yaml
trigger:
  branches:
    include:
      - main
      - develop

pr:
  branches:
    include:
      - main

pool:
  vmImage: 'ubuntu-latest'

variables:
  phpVersion: '8.3'

stages:
- stage: SecurityAudit
  displayName: 'Security Audit'
  jobs:
  - job: SecurityCheck
    displayName: 'Run Security Checks'
    strategy:
      matrix:
        staging:
          environment: 'staging'
        production:
          environment: 'production'
    steps:
    - task: UsePhpVersion@0
      inputs:
        versionSpec: '$(phpVersion)'
        
    - script: composer install --no-interaction --prefer-dist
      displayName: 'Install Composer dependencies'
      
    - script: |
        cp .env.example .env
        php artisan key:generate
      displayName: 'Setup environment'
      
    - script: |
        php artisan safeguard:check \
          --env=$(environment) \
          --format=json \
          --fail-on-error > $(Agent.TempDirectory)/security-$(environment).json
      displayName: 'Run security checks for $(environment)'
      
    - task: PublishBuildArtifacts@1
      condition: always()
      inputs:
        pathToPublish: '$(Agent.TempDirectory)/security-$(environment).json'
        artifactName: 'security-report-$(environment)'
```

## Bitbucket Pipelines

Create `bitbucket-pipelines.yml`:

```yaml
image: php:8.3-cli

pipelines:
  default:
    - step:
        name: Security Audit
        caches:
          - composer
        script:
          - apt-get update && apt-get install -y git zip unzip
          - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
          - composer install --no-interaction
          - cp .env.example .env
          - php artisan key:generate
          - php artisan safeguard:check --ci --fail-on-error
        artifacts:
          - security-report.json
          
  branches:
    main:
      - step:
          name: Production Security Audit
          script:
            - composer install --no-interaction
            - cp .env.example .env
            - php artisan key:generate
            - php artisan safeguard:check --env=production --fail-on-error
            
  pull-requests:
    '**':
      - step:
          name: Security Audit (PR)
          script:
            - composer install --no-interaction
            - cp .env.example .env
            - php artisan key:generate
            - php artisan safeguard:check --ci
```

## Docker Integration

### Dockerfile for Security Checks

```dockerfile
FROM php:8.3-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-scripts --no-autoloader --no-dev

# Copy application code
COPY . .

# Generate autoloader and run security checks
RUN composer dump-autoload --optimize && \
    cp .env.example .env && \
    php artisan key:generate && \
    php artisan safeguard:check --ci --fail-on-error
```

### Docker Compose for CI

```yaml
version: '3.8'

services:
  security-check:
    build: .
    volumes:
      - .:/app
    environment:
      - APP_ENV=testing
    command: >
      sh -c "
        composer install --no-interaction &&
        cp .env.example .env &&
        php artisan key:generate &&
        php artisan safeguard:check --ci --fail-on-error
      "
```

## Pre-deployment Scripts

### Bash Script

Create `scripts/pre-deploy-security.sh`:

```bash
#!/bin/bash

set -e

echo "🔐 Running pre-deployment security checks..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
ENVIRONMENT=${1:-production}
FAIL_ON_ERROR=${2:-true}

echo "Environment: $ENVIRONMENT"
echo "Fail on error: $FAIL_ON_ERROR"
echo ""

# Run security checks
if [ "$FAIL_ON_ERROR" = "true" ]; then
    if php artisan safeguard:check --env="$ENVIRONMENT" --ci --fail-on-error; then
        echo -e "${GREEN}✅ Security checks passed! Safe to deploy.${NC}"
        exit 0
    else
        echo -e "${RED}❌ Security checks failed! Deployment blocked.${NC}"
        echo ""
        echo "Please fix the security issues before deploying."
        echo "Run 'php artisan safeguard:check --env=$ENVIRONMENT' for details."
        exit 1
    fi
else
    php artisan safeguard:check --env="$ENVIRONMENT" --ci
    echo -e "${YELLOW}⚠️  Security checks completed (warnings allowed).${NC}"
fi
```

### PowerShell Script

Create `scripts/pre-deploy-security.ps1`:

```powershell
param(
    [string]$Environment = "production",
    [bool]$FailOnError = $true
)

Write-Host "🔐 Running pre-deployment security checks..." -ForegroundColor Blue
Write-Host "Environment: $Environment"
Write-Host "Fail on error: $FailOnError"
Write-Host ""

try {
    if ($FailOnError) {
        $result = php artisan safeguard:check --env=$Environment --ci --fail-on-error
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ Security checks passed! Safe to deploy." -ForegroundColor Green
            exit 0
        } else {
            Write-Host "❌ Security checks failed! Deployment blocked." -ForegroundColor Red
            Write-Host ""
            Write-Host "Please fix the security issues before deploying."
            Write-Host "Run 'php artisan safeguard:check --env=$Environment' for details."
            exit 1
        }
    } else {
        php artisan safeguard:check --env=$Environment --ci
        Write-Host "⚠️  Security checks completed (warnings allowed)." -ForegroundColor Yellow
    }
} catch {
    Write-Host "❌ Error running security checks: $_" -ForegroundColor Red
    exit 1
}
```

## Integration Best Practices

### 1. Environment-Specific Checks

Run different rule sets for different environments:

```bash
# Development/Testing
php artisan safeguard:check --env=local

# Staging
php artisan safeguard:check --env=staging --fail-on-error

# Production
php artisan safeguard:check --env=production --fail-on-error
```

### 2. Gradual Implementation

Start with warnings, then gradually increase severity:

```php
// Week 1: Warnings only
'rules' => [
    'env_debug_false_in_production' => true, // Warning level
],

// Week 2: Upgrade to errors
'rules' => [
    'env_debug_false_in_production' => true, // Error level
],

// Week 3: Block deployment on failures
// Use --fail-on-error flag
```

### 3. Caching for Performance

Cache security check results in CI:

```yaml
# GitHub Actions example
- name: Cache security results
  uses: actions/cache@v3
  with:
    path: security-cache/
    key: ${{ runner.os }}-security-${{ hashFiles('config/safeguard.php') }}
```

### 4. Parallel Execution

Run security checks in parallel with other tests:

```yaml
jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        include:
          - test-type: "unit"
            command: "php artisan test --testsuite=Unit"
          - test-type: "security"
            command: "php artisan safeguard:check --ci"
```

### 5. Report Generation

Generate and store security reports:

```bash
# Generate JSON report
php artisan safeguard:check --format=json > security-report.json

# Convert to other formats if needed
# (using additional tools)
```

## Troubleshooting CI/CD

### Common Issues

1. **Missing .env file**: Ensure you copy `.env.example` to `.env`
2. **Missing APP_KEY**: Run `php artisan key:generate`
3. **Permission issues**: Check file permissions on storage directories
4. **Composer dependencies**: Ensure all dependencies are installed

### Debug Mode

Enable debug mode for detailed output:

```bash
php artisan safeguard:check --env=production -vvv
```

### Environment Variables

Set environment-specific variables in CI:

```bash
export APP_ENV=testing
export APP_DEBUG=false
export APP_KEY=base64:...
```

## Related Documentation

- [Commands Reference](commands.md) - All available CLI commands
- [Configuration Guide](configuration.md) - Configure security rules
- [Output Formats](output-formats.md) - JSON and CLI output options