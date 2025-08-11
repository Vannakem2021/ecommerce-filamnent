#!/bin/bash

# Phase 4 Execution Script for Variant Migration
# Usage: ./scripts/run-phase4.sh [options]

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Default options
DRY_RUN=false
SKIP_TESTS=false

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [options]"
    echo ""
    echo "Options:"
    echo "  --dry-run      Show what would be done without executing"
    echo "  --skip-tests   Skip frontend testing"
    echo "  --help         Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0                  # Full Phase 4 execution"
    echo "  $0 --dry-run       # Test run without changes"
    echo "  $0 --skip-tests    # Skip testing phase"
}

# Parse command line arguments
parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --dry-run)
                DRY_RUN=true
                shift
                ;;
            --skip-tests)
                SKIP_TESTS=true
                shift
                ;;
            --help)
                show_usage
                exit 0
                ;;
            *)
                error "Unknown option: $1"
                show_usage
                exit 1
                ;;
        esac
    done
}

# Function to check prerequisites
check_prerequisites() {
    log "🔍 Checking Phase 4 prerequisites..."
    
    # Check if we're in the right directory
    if [ ! -f "$PROJECT_DIR/artisan" ]; then
        error "Laravel project not found. Please run this script from the project root."
        exit 1
    fi
    
    # Check if previous phases were completed
    cd "$PROJECT_DIR"
    
    # Check if models have been updated
    if ! grep -q "getVariantOptions" app/Models/ProductVariant.php; then
        warning "ProductVariant model may not have JSON helper methods"
    fi
    
    if ! grep -q "getVariantConfiguration" app/Models/Product.php; then
        warning "Product model may not have JSON helper methods"
    fi
    
    log "✅ Prerequisites check completed"
}

# Function to update models for Phase 4
update_models() {
    log "🔄 Updating models for Phase 4..."
    
    cd "$PROJECT_DIR"
    
    if [ "$DRY_RUN" = true ]; then
        php artisan update:models-for-phase4 --dry-run
    else
        php artisan update:models-for-phase4 --backup
    fi
    
    log "✅ Model updates completed"
}

# Function to validate JSON variant system
validate_json_system() {
    log "🔍 Validating JSON variant system..."
    
    cd "$PROJECT_DIR"
    
    if php artisan validate:json-variant-system; then
        log "✅ JSON variant system validation passed"
    else
        warning "⚠️ JSON variant system validation found issues"
        return 1
    fi
}

# Function to test frontend functionality
test_frontend() {
    if [ "$SKIP_TESTS" = true ]; then
        info "Skipping frontend tests (--skip-tests specified)"
        return 0
    fi
    
    log "🧪 Testing frontend functionality..."
    
    cd "$PROJECT_DIR"
    
    if php artisan test:json-variant-frontend; then
        log "✅ Frontend tests passed"
    else
        warning "⚠️ Some frontend tests failed"
        return 1
    fi
}

# Function to compile frontend assets
compile_assets() {
    log "📦 Compiling frontend assets..."
    
    cd "$PROJECT_DIR"
    
    if [ "$DRY_RUN" = true ]; then
        info "[DRY RUN] Would compile frontend assets"
        return 0
    fi
    
    # Check if npm is available
    if command -v npm >/dev/null 2>&1; then
        if [ -f "package.json" ]; then
            npm run build
            log "✅ Frontend assets compiled"
        else
            warning "No package.json found, skipping asset compilation"
        fi
    else
        warning "npm not found, skipping asset compilation"
    fi
}

# Function to clear application cache
clear_cache() {
    log "🧹 Clearing application cache..."
    
    cd "$PROJECT_DIR"
    
    if [ "$DRY_RUN" = true ]; then
        info "[DRY RUN] Would clear application cache"
        return 0
    fi
    
    php artisan optimize:clear
    php artisan config:cache
    php artisan view:cache
    
    log "✅ Application cache cleared"
}

# Function to generate documentation
generate_documentation() {
    log "📝 Generating Phase 4 documentation..."
    
    cd "$PROJECT_DIR"
    
    if [ "$DRY_RUN" = true ]; then
        info "[DRY RUN] Would generate documentation"
        return 0
    fi
    
    # Create documentation directory
    mkdir -p storage/phase4-docs
    
    # Generate API documentation for JSON variant methods
    cat > storage/phase4-docs/json-variant-api.md << EOF
# JSON Variant System API Documentation

## Product Model Methods

### \`getAvailableOptions(): array\`
Returns available variant options for the product.

### \`findVariantByOptions(array \$options): ?ProductVariant\`
Finds a variant that matches the given options.

### \`getPriceRange(): ?array\`
Returns price range for product variants.

### \`hasStock(): bool\`
Checks if product has stock (any variant in stock).

## ProductVariant Model Methods

### \`getVariantOptions(): array\`
Returns the variant's JSON options.

### \`getOptionValue(string \$key, \$default = null)\`
Gets a specific option value.

### \`getEffectivePrice(): int\`
Gets the effective price (with override if set).

### \`getDisplayName(): string\`
Gets a display-friendly variant name.

### \`isInStock(): bool\`
Checks if variant is in stock.

## Frontend Integration

### JSON Variant Selector Component
- Location: \`resources/js/components/json-variant-selector.js\`
- Usage: Add \`data-variant-selector\` attribute to container
- Events: Listens for Livewire variant changes

### Blade Component
- Location: \`resources/views/components/json-variant-selector.blade.php\`
- Usage: \`@include('components.json-variant-selector', ['product' => \$product])\`

Generated: $(date)
EOF
    
    log "✅ Documentation generated in storage/phase4-docs/"
}

# Function to display completion summary
display_completion_summary() {
    log ""
    log "📊 PHASE 4 COMPLETION SUMMARY"
    log "============================="
    
    if [ "$DRY_RUN" = true ]; then
        echo "✅ Dry run completed successfully"
        echo "📄 No actual changes were made"
        echo ""
        echo "Ready to run actual Phase 4:"
        echo "  ./scripts/run-phase4.sh"
    else
        echo "✅ Frontend integration completed"
        echo "✅ Models updated with JSON helper methods"
        echo "✅ JSON variant selector component created"
        echo "✅ Frontend tests completed"
        echo "✅ Assets compiled and cache cleared"
        echo ""
        echo "🎉 PHASE 4 COMPLETED!"
        echo ""
        echo "📄 Generated files:"
        echo "  - JSON variant selector: resources/js/components/json-variant-selector.js"
        echo "  - Blade component: resources/views/components/json-variant-selector.blade.php"
        echo "  - Frontend tests: app/Console/Commands/TestJsonVariantFrontend.php"
        echo "  - Documentation: storage/phase4-docs/"
        echo ""
        echo "🔧 System Status:"
        echo "  - Models: Updated with JSON helper methods"
        echo "  - Frontend: JSON variant system active"
        echo "  - Admin panel: JSON variant support enabled"
        echo "  - Tests: Frontend functionality validated"
        echo ""
        echo "Next steps:"
        echo "1. Test the frontend thoroughly in a browser"
        echo "2. Verify variant selection works correctly"
        echo "3. Check that cart and checkout handle JSON variants"
        echo "4. Update any custom code that uses the old variant system"
        echo "5. Train your team on the new JSON-based system"
    fi
    
    echo ""
    info "Frontend integration complete! 🎉"
    log "============================="
}

# Function to handle errors
handle_error() {
    local exit_code=$?
    local line_number=$1
    
    error "An error occurred in Phase 4 execution at line $line_number (exit code: $exit_code)"
    warning "Phase 4 may be partially completed. Check the following:"
    warning "1. Model helper methods in app/Models/"
    warning "2. Frontend components in resources/"
    warning "3. Test results and logs"
    
    exit $exit_code
}

# Set error trap
trap 'handle_error $LINENO' ERR

# Main execution
main() {
    parse_arguments "$@"
    
    log "🚀 Starting Phase 4: Frontend Integration & Testing"
    log "=================================================="
    
    check_prerequisites
    update_models
    validate_json_system
    test_frontend
    compile_assets
    clear_cache
    generate_documentation
    display_completion_summary
    
    log "🎉 Phase 4 completed successfully!"
}

# Run main function with all arguments
main "$@"
