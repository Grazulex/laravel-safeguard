#!/bin/bash

#==============================================================================
# Laravel Safeguard Pre-Deployment Security Check Script
#
# This script runs comprehensive security checks before deployment.
# It's designed to be used in CI/CD pipelines or manual deployment processes.
#
# Usage:
#   ./pre-deploy-check.sh [environment] [options]
#
# Examples:
#   ./pre-deploy-check.sh production
#   ./pre-deploy-check.sh staging --verbose
#   ./pre-deploy-check.sh production --format=json --output=report.json
#==============================================================================

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Configuration
ENVIRONMENT=${1:-production}
FAIL_ON_ERROR=${SAFEGUARD_FAIL_ON_ERROR:-true}
FORMAT=${SAFEGUARD_FORMAT:-cli}
VERBOSE=${SAFEGUARD_VERBOSE:-false}
OUTPUT_FILE=${SAFEGUARD_OUTPUT_FILE:-}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --env=*)
            ENVIRONMENT="${1#*=}"
            shift
            ;;
        --format=*)
            FORMAT="${1#*=}"
            shift
            ;;
        --output=*)
            OUTPUT_FILE="${1#*=}"
            shift
            ;;
        --no-fail)
            FAIL_ON_ERROR="false"
            shift
            ;;
        --verbose|-v)
            VERBOSE="true"
            shift
            ;;
        --help|-h)
            echo "Usage: $0 [environment] [options]"
            echo ""
            echo "Options:"
            echo "  --env=ENV        Environment to check (default: production)"
            echo "  --format=FORMAT  Output format: cli, json (default: cli)"
            echo "  --output=FILE    Save output to file"
            echo "  --no-fail        Don't exit on security failures"
            echo "  --verbose, -v    Verbose output"
            echo "  --help, -h       Show this help"
            echo ""
            echo "Environment variables:"
            echo "  SAFEGUARD_FAIL_ON_ERROR  Fail on errors (default: true)"
            echo "  SAFEGUARD_FORMAT         Output format (default: cli)"
            echo "  SAFEGUARD_VERBOSE        Verbose output (default: false)"
            echo "  SAFEGUARD_OUTPUT_FILE    Output file path"
            exit 0
            ;;
        *)
            if [[ -z "$ENVIRONMENT" ]]; then
                ENVIRONMENT="$1"
            fi
            shift
            ;;
    esac
done

#==============================================================================
# Helper Functions
#==============================================================================

log() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

info() {
    echo -e "${PURPLE}ℹ️  $1${NC}"
}

verbose() {
    if [[ "$VERBOSE" == "true" ]]; then
        echo -e "${BLUE}[VERBOSE]${NC} $1"
    fi
}

#==============================================================================
# Pre-flight Checks
#==============================================================================

check_requirements() {
    log "Checking requirements..."
    
    # Check if artisan is available
    if ! command -v php &> /dev/null; then
        error "PHP is not installed or not in PATH"
        exit 1
    fi
    
    # Check if Laravel Safeguard is installed
    if ! php artisan list | grep -q "safeguard:check"; then
        error "Laravel Safeguard is not installed or not properly configured"
        info "Run: composer require --dev grazulex/laravel-safeguard"
        exit 1
    fi
    
    # Check if .env file exists
    if [[ ! -f ".env" ]]; then
        warning ".env file not found - some checks may fail"
    fi
    
    success "Requirements check passed"
}

#==============================================================================
# Security Check Functions
#==============================================================================

run_security_check() {
    log "Running security checks for environment: $ENVIRONMENT"
    
    # Build command
    local cmd="php artisan safeguard:check --env=$ENVIRONMENT"
    
    if [[ "$FORMAT" == "json" ]]; then
        cmd="$cmd --format=json"
    fi
    
    if [[ "$FAIL_ON_ERROR" == "true" ]]; then
        cmd="$cmd --fail-on-error"
    fi
    
    # Add CI flag for non-interactive environments
    if [[ -n "$CI" || -n "$GITHUB_ACTIONS" || -n "$GITLAB_CI" ]]; then
        cmd="$cmd --ci"
    fi
    
    # Add details flag for verbose mode or when not in JSON format
    if [[ "$VERBOSE" == "true" ]] || [[ "$FORMAT" != "json" ]]; then
        cmd="$cmd --details"
    fi
    
    verbose "Running command: $cmd"
    
    # Run the security check
    local output
    local exit_code=0
    
    if output=$(eval "$cmd" 2>&1); then
        verbose "Security check completed successfully"
    else
        exit_code=$?
        verbose "Security check failed with exit code: $exit_code"
    fi
    
    # Save output to file if specified
    if [[ -n "$OUTPUT_FILE" ]]; then
        echo "$output" > "$OUTPUT_FILE"
        info "Output saved to: $OUTPUT_FILE"
    fi
    
    # Display output
    echo "$output"
    
    return $exit_code
}

parse_json_results() {
    local json_output="$1"
    
    if ! command -v jq &> /dev/null; then
        warning "jq not available - cannot parse detailed JSON results"
        return
    fi
    
    local status
    local total
    local passed
    local failed
    
    status=$(echo "$json_output" | jq -r '.status // "unknown"')
    total=$(echo "$json_output" | jq -r '.summary.total // 0')
    passed=$(echo "$json_output" | jq -r '.summary.passed // 0')
    failed=$(echo "$json_output" | jq -r '.summary.failed // 0')
    
    log "Security Check Summary:"
    echo "  Status: $status"
    echo "  Total checks: $total"
    echo "  Passed: $passed"
    echo "  Failed: $failed"
    
    if [[ "$failed" -gt 0 ]]; then
        log "Failed checks:"
        echo "$json_output" | jq -r '.results[] | select(.status == "failed") | "  - " + .rule + ": " + .message'
    fi
}

#==============================================================================
# Notification Functions
#==============================================================================

send_slack_notification() {
    local webhook_url="$SLACK_WEBHOOK_URL"
    local status="$1"
    local environment="$2"
    local failed_count="$3"
    
    if [[ -z "$webhook_url" ]]; then
        verbose "No Slack webhook URL configured"
        return
    fi
    
    local color="good"
    local message="✅ Security audit passed for $environment environment"
    
    if [[ "$status" == "failed" ]]; then
        color="danger"
        message="🚨 Security audit failed for $environment environment: $failed_count issues found"
    fi
    
    local payload=$(cat <<EOF
{
    "attachments": [
        {
            "color": "$color",
            "title": "Laravel Safeguard Security Audit",
            "text": "$message",
            "fields": [
                {
                    "title": "Environment",
                    "value": "$environment",
                    "short": true
                },
                {
                    "title": "Status",
                    "value": "$status",
                    "short": true
                }
            ]
        }
    ]
}
EOF
)
    
    if curl -X POST -H 'Content-type: application/json' \
        --data "$payload" \
        "$webhook_url" &> /dev/null; then
        verbose "Slack notification sent successfully"
    else
        warning "Failed to send Slack notification"
    fi
}

#==============================================================================
# Main Execution
#==============================================================================

main() {
    echo
    log "🔐 Laravel Safeguard Pre-Deployment Security Check"
    echo "=================================================="
    echo
    
    log "Configuration:"
    echo "  Environment: $ENVIRONMENT"
    echo "  Format: $FORMAT"
    echo "  Fail on error: $FAIL_ON_ERROR"
    echo "  Verbose: $VERBOSE"
    if [[ -n "$OUTPUT_FILE" ]]; then
        echo "  Output file: $OUTPUT_FILE"
    fi
    echo
    
    # Run pre-flight checks
    check_requirements
    echo
    
    # Run security checks
    local security_output
    local security_exit_code=0
    
    if security_output=$(run_security_check); then
        success "Security checks completed successfully"
    else
        security_exit_code=$?
        if [[ "$FAIL_ON_ERROR" == "true" ]]; then
            error "Security checks failed"
        else
            warning "Security checks completed with issues (not failing due to --no-fail)"
        fi
    fi
    
    echo
    
    # Parse results if JSON format
    if [[ "$FORMAT" == "json" ]]; then
        parse_json_results "$security_output"
        echo
    fi
    
    # Send notifications
    if [[ -n "$SLACK_WEBHOOK_URL" ]]; then
        local status="passed"
        local failed_count="0"
        
        if [[ $security_exit_code -ne 0 ]]; then
            status="failed"
            if [[ "$FORMAT" == "json" ]] && command -v jq &> /dev/null; then
                failed_count=$(echo "$security_output" | jq -r '.summary.failed // "unknown"')
            else
                failed_count="unknown"
            fi
        fi
        
        send_slack_notification "$status" "$ENVIRONMENT" "$failed_count"
    fi
    
    # Final status
    if [[ $security_exit_code -eq 0 ]]; then
        success "✅ Security checks passed! Environment is safe for deployment."
        echo
        log "You can proceed with deployment to $ENVIRONMENT"
    else
        if [[ "$FAIL_ON_ERROR" == "true" ]]; then
            error "❌ Security checks failed! Deployment blocked."
            echo
            log "Please fix the security issues before deploying to $ENVIRONMENT"
            log "Run 'php artisan safeguard:check --env=$ENVIRONMENT' for details"
            exit 1
        else
            warning "⚠️  Security checks completed with issues"
            echo
            log "Review the issues above before deploying to $ENVIRONMENT"
        fi
    fi
}

# Execute main function
main "$@"