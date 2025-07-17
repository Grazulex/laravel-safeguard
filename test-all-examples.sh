#!/bin/bash

#==============================================================================
# Laravel Safeguard Examples Test Script
#
# This script tests all examples in the examples/ directory to ensure
# they are functional and up-to-date.
#==============================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Counters
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Helper functions
log() {
    echo -e "${BLUE}[$(date '+%H:%M:%S')]${NC} $1"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
    ((PASSED_TESTS++))
}

error() {
    echo -e "${RED}❌ $1${NC}"
    ((FAILED_TESTS++))
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

test_example() {
    local name="$1"
    local command="$2"
    local expected_exit_code="${3:-0}"
    
    ((TOTAL_TESTS++))
    
    log "Testing: $name"
    
    if [[ "$command" == *.php ]]; then
        # PHP file
        if php "$command" > /dev/null 2>&1; then
            success "$name"
        else
            error "$name - PHP execution failed"
        fi
    elif [[ "$command" == *.sh ]]; then
        # Shell script
        if chmod +x "$command" && "$command" --help > /dev/null 2>&1; then
            success "$name"
        else
            error "$name - Shell script failed"
        fi
    elif [[ "$command" == *.yml || "$command" == *.yaml ]]; then
        # YAML file - just check syntax
        if command -v yamllint > /dev/null 2>&1; then
            if yamllint "$command" > /dev/null 2>&1; then
                success "$name"
            else
                error "$name - YAML syntax invalid"
            fi
        else
            # Use python to check YAML if yamllint not available
            if python3 -c "import yaml; yaml.safe_load(open('$command'))" > /dev/null 2>&1; then
                success "$name"
            else
                error "$name - YAML syntax invalid"
            fi
        fi
    else
        # Generic file - just check if it exists and is readable
        if [[ -r "$command" ]]; then
            success "$name"
        else
            error "$name - File not readable"
        fi
    fi
}

# Main test execution
main() {
    echo
    log "🧪 Testing Laravel Safeguard Examples"
    echo "======================================"
    echo
    
    # Test basic usage examples
    log "Testing basic usage examples..."
    test_example "Simple Check Example" "examples/basic-usage/simple-check.php"
    test_example "Environment Specific Example" "examples/basic-usage/environment-specific.php"
    test_example "JSON Output Example" "examples/basic-usage/json-output.php"
    echo
    
    # Test custom rules examples
    log "Testing custom rules examples..."
    test_example "Database Security Rule" "examples/custom-rules/DatabaseSecurityRule.php"
    echo
    
    # Test configuration examples
    log "Testing configuration examples..."
    test_example "Production Config" "examples/configuration/production-config.php"
    test_example "Development Config" "examples/configuration/development-config.php"
    echo
    
    # Test scripts
    log "Testing utility scripts..."
    test_example "Pre-Deploy Check Script" "examples/scripts/pre-deploy-check.sh"
    echo
    
    # Test CI/CD examples
    log "Testing CI/CD examples..."
    test_example "GitHub Actions Workflow" "examples/ci-cd/github-actions/security.yml"
    test_example "GitLab CI Pipeline" "examples/ci-cd/gitlab-ci/.gitlab-ci.yml"
    echo
    
    # Test documentation files
    log "Testing documentation files..."
    for doc_file in docs/*.md; do
        if [[ -f "$doc_file" ]]; then
            name=$(basename "$doc_file" .md)
            test_example "Documentation: $name" "$doc_file"
        fi
    done
    echo
    
    # Test README files
    log "Testing README files..."
    test_example "Main README" "README.md"
    test_example "Examples README" "examples/README.md"
    test_example "Documentation README" "docs/README.md"
    echo
    
    # Summary
    echo "======================================"
    log "Test Summary"
    echo "  Total tests: $TOTAL_TESTS"
    echo "  Passed: $PASSED_TESTS"
    echo "  Failed: $FAILED_TESTS"
    
    if [[ $FAILED_TESTS -eq 0 ]]; then
        success "All examples are working correctly! 🎉"
        exit 0
    else
        error "$FAILED_TESTS examples failed"
        exit 1
    fi
}

# Check if we're in the right directory
if [[ ! -d "examples" || ! -d "docs" ]]; then
    error "Please run this script from the laravel-safeguard root directory"
    exit 1
fi

# Run tests
main "$@"