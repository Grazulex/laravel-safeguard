#!/bin/bash

#==============================================================================
# Laravel Safeguard Security Report Generator
#
# This script generates comprehensive security reports using the detailed
# output features of Laravel Safeguard. It supports multiple formats and
# can generate reports for different environments.
#
# Usage:
#   ./security-report-generator.sh [environment] [options]
#
# Examples:
#   ./security-report-generator.sh production
#   ./security-report-generator.sh staging --format=html
#   ./security-report-generator.sh production --all-details --output-dir=reports
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
OUTPUT_DIR=${SAFEGUARD_OUTPUT_DIR:-./security-reports}
FORMAT=${SAFEGUARD_REPORT_FORMAT:-all}
ALL_DETAILS=${SAFEGUARD_ALL_DETAILS:-false}
TIMESTAMP=$(date '+%Y%m%d_%H%M%S')

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --env=*)
            ENVIRONMENT="${1#*=}"
            shift
            ;;
        --output-dir=*)
            OUTPUT_DIR="${1#*=}"
            shift
            ;;
        --format=*)
            FORMAT="${1#*=}"
            shift
            ;;
        --all-details)
            ALL_DETAILS="true"
            shift
            ;;
        --help|-h)
            echo "Usage: $0 [environment] [options]"
            echo ""
            echo "Options:"
            echo "  --env=ENV           Environment to check (default: production)"
            echo "  --output-dir=DIR    Output directory (default: ./security-reports)"
            echo "  --format=FORMAT     Report format: json, html, markdown, all (default: all)"
            echo "  --all-details       Include details for all checks, not just failures"
            echo "  --help, -h          Show this help"
            echo ""
            echo "Environment variables:"
            echo "  SAFEGUARD_OUTPUT_DIR      Output directory"
            echo "  SAFEGUARD_REPORT_FORMAT   Report format"
            echo "  SAFEGUARD_ALL_DETAILS     Include all details"
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

#==============================================================================
# Report Generation Functions
#==============================================================================

setup_output_directory() {
    log "Setting up output directory: $OUTPUT_DIR"
    
    mkdir -p "$OUTPUT_DIR"
    
    if [[ ! -w "$OUTPUT_DIR" ]]; then
        error "Output directory is not writable: $OUTPUT_DIR"
        exit 1
    fi
    
    success "Output directory ready"
}

generate_json_report() {
    local output_file="$OUTPUT_DIR/security-report-${ENVIRONMENT}-${TIMESTAMP}.json"
    
    log "Generating JSON report..."
    
    local cmd="php artisan safeguard:check --env=$ENVIRONMENT --format=json"
    
    if [[ "$ALL_DETAILS" == "true" ]]; then
        cmd="$cmd --show-all"
    fi
    
    if eval "$cmd" > "$output_file"; then
        success "JSON report generated: $output_file"
        echo "$output_file"
    else
        error "Failed to generate JSON report"
        return 1
    fi
}

generate_markdown_report() {
    local json_file="$1"
    local output_file="$OUTPUT_DIR/security-report-${ENVIRONMENT}-${TIMESTAMP}.md"
    
    if [[ ! -f "$json_file" ]]; then
        error "JSON file not found: $json_file"
        return 1
    fi
    
    log "Generating Markdown report..."
    
    # Check if jq is available
    if ! command -v jq &> /dev/null; then
        warning "jq not available - cannot generate Markdown report"
        return 1
    fi
    
    # Start generating markdown
    cat > "$output_file" << EOF
# 🔐 Laravel Safeguard Security Report

**Environment:** $ENVIRONMENT  
**Generated:** $(date)  
**Timestamp:** $TIMESTAMP

EOF
    
    # Extract summary information
    local status
    local total
    local passed
    local failed
    
    status=$(jq -r '.status // "unknown"' "$json_file")
    total=$(jq -r '.summary.total // 0' "$json_file")
    passed=$(jq -r '.summary.passed // 0' "$json_file")
    failed=$(jq -r '.summary.failed // 0' "$json_file")
    
    cat >> "$output_file" << EOF
## 📊 Summary

| Metric | Value |
|--------|-------|
| **Overall Status** | $status |
| **Total Checks** | $total |
| **Passed** | $passed |
| **Failed** | $failed |

EOF
    
    # Add failed checks section if any
    if [[ "$failed" -gt 0 ]]; then
        cat >> "$output_file" << EOF
## ❌ Failed Checks

EOF
        
        jq -r '.results[] | select(.status == "failed") | "### " + .rule + "\n\n" + "**Message:** " + .message + "\n\n" + "**Severity:** " + .severity + "\n\n" + (if .details.recommendation then "**💡 Recommendation:** " + .details.recommendation + "\n\n" else "" end) + (if .details.security_impact then "**⚠️ Security Impact:** " + .details.security_impact + "\n\n" else "" end) + (if .details.current_setting then "**⚙️ Current Setting:** " + .details.current_setting + "\n\n" else "" end) + "---\n"' "$json_file" >> "$output_file"
    fi
    
    # Add passed checks section
    cat >> "$output_file" << EOF

## ✅ Passed Checks

EOF
    
    jq -r '.results[] | select(.status == "passed") | "- **" + .rule + ":** " + .message' "$json_file" >> "$output_file"
    
    # Add detailed information if requested
    if [[ "$ALL_DETAILS" == "true" ]]; then
        cat >> "$output_file" << EOF

## 📋 Detailed Information

<details>
<summary>Click to expand full report details</summary>

\`\`\`json
$(jq '.' "$json_file")
\`\`\`

</details>

EOF
    fi
    
    # Add footer
    cat >> "$output_file" << EOF

---

*Report generated by Laravel Safeguard Security Report Generator*  
*For more information, visit: https://github.com/Grazulex/laravel-safeguard*
EOF
    
    success "Markdown report generated: $output_file"
    echo "$output_file"
}

generate_html_report() {
    local json_file="$1"
    local markdown_file="$2"
    local output_file="$OUTPUT_DIR/security-report-${ENVIRONMENT}-${TIMESTAMP}.html"
    
    log "Generating HTML report..."
    
    # Check if pandoc is available for markdown to HTML conversion
    if command -v pandoc &> /dev/null && [[ -f "$markdown_file" ]]; then
        if pandoc "$markdown_file" -o "$output_file" \
            --metadata title="Laravel Safeguard Security Report" \
            --css=<(cat << 'EOF'
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
h1, h2, h3 { color: #2563eb; }
table { border-collapse: collapse; width: 100%; margin: 20px 0; }
th, td { border: 1px solid #e5e7eb; padding: 12px; text-align: left; }
th { background-color: #f3f4f6; }
.failed { color: #dc2626; }
.passed { color: #059669; }
.warning { color: #d97706; }
code { background-color: #f3f4f6; padding: 2px 4px; border-radius: 3px; }
pre { background-color: #f3f4f6; padding: 16px; border-radius: 6px; overflow-x: auto; }
details { margin: 20px 0; }
summary { cursor: pointer; padding: 10px; background-color: #f3f4f6; border-radius: 6px; }
EOF
        ); then
            success "HTML report generated: $output_file"
            echo "$output_file"
        else
            warning "Failed to convert markdown to HTML with pandoc"
            return 1
        fi
    else
        # Fallback: Generate basic HTML
        cat > "$output_file" << EOF
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Safeguard Security Report</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1, h2, h3 { color: #2563eb; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #e5e7eb; padding: 12px; text-align: left; }
        th { background-color: #f3f4f6; }
        .failed { color: #dc2626; }
        .passed { color: #059669; }
        .warning { color: #d97706; }
        pre { background-color: #f3f4f6; padding: 16px; border-radius: 6px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔐 Laravel Safeguard Security Report</h1>
    <p><strong>Environment:</strong> $ENVIRONMENT</p>
    <p><strong>Generated:</strong> $(date)</p>
    
    <h2>📊 Summary</h2>
    <table>
        <tr><th>Metric</th><th>Value</th></tr>
        <tr><td>Overall Status</td><td class="$(jq -r '.status' "$json_file")">$(jq -r '.status' "$json_file")</td></tr>
        <tr><td>Total Checks</td><td>$(jq -r '.summary.total' "$json_file")</td></tr>
        <tr><td>Passed</td><td>$(jq -r '.summary.passed' "$json_file")</td></tr>
        <tr><td>Failed</td><td>$(jq -r '.summary.failed' "$json_file")</td></tr>
    </table>
    
    <h2>📋 Full Report</h2>
    <pre>$(jq '.' "$json_file")</pre>
</body>
</html>
EOF
        
        success "Basic HTML report generated: $output_file"
        echo "$output_file"
    fi
}

generate_summary_report() {
    local reports=("$@")
    local summary_file="$OUTPUT_DIR/security-summary-${ENVIRONMENT}-${TIMESTAMP}.txt"
    
    log "Generating summary report..."
    
    cat > "$summary_file" << EOF
Laravel Safeguard Security Report Summary
=========================================

Environment: $ENVIRONMENT
Generated: $(date)
Timestamp: $TIMESTAMP

Generated Reports:
EOF
    
    for report in "${reports[@]}"; do
        echo "  - $(basename "$report")" >> "$summary_file"
    done
    
    cat >> "$summary_file" << EOF

Report Locations:
  Directory: $OUTPUT_DIR

Usage:
  - View JSON report for programmatic processing
  - View Markdown report for human-readable format
  - View HTML report in web browser
  - Share summary with team members

Next Steps:
  1. Review failed checks and implement fixes
  2. Run checks again to verify fixes
  3. Integrate into CI/CD pipeline
  4. Schedule regular security audits

EOF
    
    success "Summary report generated: $summary_file"
    echo "$summary_file"
}

#==============================================================================
# Main Execution
#==============================================================================

main() {
    echo
    log "🔐 Laravel Safeguard Security Report Generator"
    echo "==============================================="
    echo
    
    log "Configuration:"
    echo "  Environment: $ENVIRONMENT"
    echo "  Output directory: $OUTPUT_DIR"
    echo "  Format: $FORMAT"
    echo "  All details: $ALL_DETAILS"
    echo "  Timestamp: $TIMESTAMP"
    echo
    
    # Setup
    setup_output_directory
    echo
    
    # Check if Laravel Safeguard is available
    if ! php artisan list | grep -q "safeguard:check"; then
        error "Laravel Safeguard is not installed or not properly configured"
        exit 1
    fi
    
    # Generate reports
    local generated_reports=()
    
    # JSON report (always generated first as it's used by other formats)
    if [[ "$FORMAT" == "json" || "$FORMAT" == "all" ]]; then
        if json_file=$(generate_json_report); then
            generated_reports+=("$json_file")
        fi
    fi
    
    # Markdown report
    if [[ "$FORMAT" == "markdown" || "$FORMAT" == "all" ]] && [[ -n "$json_file" ]]; then
        if markdown_file=$(generate_markdown_report "$json_file"); then
            generated_reports+=("$markdown_file")
        fi
    fi
    
    # HTML report
    if [[ "$FORMAT" == "html" || "$FORMAT" == "all" ]] && [[ -n "$json_file" ]]; then
        if html_file=$(generate_html_report "$json_file" "$markdown_file"); then
            generated_reports+=("$html_file")
        fi
    fi
    
    echo
    
    # Generate summary
    if summary_file=$(generate_summary_report "${generated_reports[@]}"); then
        generated_reports+=("$summary_file")
    fi
    
    echo
    
    # Final status
    if [[ ${#generated_reports[@]} -gt 0 ]]; then
        success "✅ Security report generation completed successfully!"
        echo
        log "Generated reports:"
        for report in "${generated_reports[@]}"; do
            echo "  📄 $(basename "$report")"
        done
        echo
        log "Reports are available in: $OUTPUT_DIR"
        
        # Open reports if on macOS/Linux with GUI
        if command -v open &> /dev/null && [[ -n "$html_file" ]]; then
            read -p "Open HTML report in browser? (y/N): " -n 1 -r
            echo
            if [[ $REPLY =~ ^[Yy]$ ]]; then
                open "$html_file"
            fi
        fi
    else
        error "❌ No reports were generated"
        exit 1
    fi
}

# Execute main function
main "$@"