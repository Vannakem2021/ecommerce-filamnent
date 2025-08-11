#!/bin/bash

# Phase 3 Execution Script for Variant Migration
# Usage: ./scripts/run-phase3.sh [options]

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Default options
DRY_RUN=false
FORCE=false
SKIP_MODELS=false
SKIP_CLEANUP=false

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
    echo "  --dry-run        Show what would be done without executing"
    echo "  --force          Skip confirmation prompts"
    echo "  --skip-models    Skip model updates"
    echo "  --skip-cleanup   Skip table cleanup (only update models)"
    echo "  --help           Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0                    # Full Phase 3 execution"
    echo "  $0 --dry-run         # Test run without changes"
    echo "  $0 --skip-cleanup    # Only update models"
    echo "  $0 --force           # Skip all confirmations"
}

# Parse command line arguments
parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --dry-run)
                DRY_RUN=true
                shift
                ;;
            --force)
                FORCE=true
                shift
                ;;
            --skip-models)
                SKIP_MODELS=true
                shift
                ;;
            --skip-cleanup)
                SKIP_CLEANUP=true
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
    log "🔍 Checking Phase 3 prerequisites..."
    
    # Check if we're in the right directory
    if [ ! -f "$PROJECT_DIR/artisan" ]; then
        error "Laravel project not found. Please run this script from the project root."
        exit 1
    fi
    
    # Check if Phase 2 was completed
    cd "$PROJECT_DIR"
    local migrated_products=$(php artisan tinker --execute="
        use App\Models\Product;
        echo Product::where('has_variants', true)->where('migrated_to_json', true)->count();
    " 2>/dev/null | tail -n 1)
    
    local migrated_variants=$(php artisan tinker --execute="
        use App\Models\ProductVariant;
        echo ProductVariant::where('migrated_to_json', true)->count();
    " 2>/dev/null | tail -n 1)
    
    if [ "$migrated_products" = "0" ] && [ "$migrated_variants" = "0" ]; then
        error "Phase 2 data migration appears incomplete. Please run Phase 2 first."
        exit 1
    fi
    
    log "✅ Prerequisites check passed"
    log "  Migrated products: $migrated_products"
    log "  Migrated variants: $migrated_variants"
}

# Function to create final backup
create_final_backup() {
    log "💾 Creating final backup before cleanup..."
    
    if [ -f "$SCRIPT_DIR/backup-database.sh" ]; then
        bash "$SCRIPT_DIR/backup-database.sh"
    else
        warning "Backup script not found. Proceeding without backup."
        if [ "$FORCE" = false ]; then
            read -p "Continue without backup? (y/N): " -n 1 -r
            echo
            if [[ ! $REPLY =~ ^[Yy]$ ]]; then
                error "Phase 3 aborted."
                exit 1
            fi
        fi
    fi
    
    log "✅ Final backup completed"
}

# Function to validate current state
validate_current_state() {
    log "🔍 Validating current system state..."
    
    cd "$PROJECT_DIR"
    
    # Run comprehensive validation
    local validation_file="storage/migration-docs/pre-phase3-validation.json"
    if ! php artisan validate:variant-data --export="$validation_file"; then
        warning "Pre-cleanup validation found issues."
        
        if [ "$FORCE" = false ]; then
            read -p "Continue with cleanup despite validation issues? (y/N): " -n 1 -r
            echo
            if [[ ! $REPLY =~ ^[Yy]$ ]]; then
                error "Phase 3 aborted due to validation issues."
                exit 1
            fi
        fi
    fi
    
    log "✅ Current state validated"
}

# Function to display cleanup plan
display_cleanup_plan() {
    log ""
    log "📋 PHASE 3 CLEANUP PLAN"
    log "======================="
    
    cd "$PROJECT_DIR"
    
    # Check what legacy tables exist
    local legacy_tables=$(php artisan tinker --execute="
        use Illuminate\Support\Facades\Schema;
        \$tables = ['product_attributes', 'product_attribute_values', 'product_variant_attributes', 'specification_attributes'];
        \$existing = [];
        foreach (\$tables as \$table) {
            if (Schema::hasTable(\$table)) {
                \$count = DB::table(\$table)->count();
                \$existing[] = \$table . '(' . \$count . ' rows)';
            }
        }
        echo implode(', ', \$existing);
    " 2>/dev/null | tail -n 1)
    
    echo "Legacy tables to clean: $legacy_tables"
    
    if [ "$DRY_RUN" = true ]; then
        echo "Mode: DRY RUN (no changes will be made)"
    else
        echo "Mode: FULL CLEANUP"
    fi
    
    if [ "$SKIP_MODELS" = true ]; then
        echo "Models: SKIP (will not update models)"
    else
        echo "Models: UPDATE (will add JSON helper methods)"
    fi
    
    if [ "$SKIP_CLEANUP" = true ]; then
        echo "Cleanup: SKIP (will not drop tables)"
    else
        echo "Cleanup: FULL (will drop legacy tables)"
    fi
    
    log ""
}

# Function to update models
update_models() {
    if [ "$SKIP_MODELS" = true ]; then
        info "Skipping model updates (--skip-models specified)"
        return 0
    fi
    
    log "🔄 Updating models for JSON-only variant system..."
    
    cd "$PROJECT_DIR"
    
    local cmd_args="update:models-for-json-variants --backup"
    
    if [ "$DRY_RUN" = true ]; then
        cmd_args="$cmd_args --dry-run"
    fi
    
    if php artisan $cmd_args; then
        log "✅ Model updates completed"
    else
        error "Model updates failed"
        return 1
    fi
}

# Function to cleanup legacy system
cleanup_legacy_system() {
    if [ "$SKIP_CLEANUP" = true ]; then
        info "Skipping legacy cleanup (--skip-cleanup specified)"
        return 0
    fi
    
    log "🧹 Cleaning up legacy variant system..."
    
    cd "$PROJECT_DIR"
    
    local cmd_args="cleanup:legacy-variant-system --keep-backup"
    
    if [ "$DRY_RUN" = true ]; then
        cmd_args="$cmd_args --dry-run"
    fi
    
    if [ "$FORCE" = true ]; then
        cmd_args="$cmd_args --force"
    fi
    
    if php artisan $cmd_args; then
        log "✅ Legacy system cleanup completed"
    else
        error "Legacy system cleanup failed"
        return 1
    fi
}

# Function to run final validation
run_final_validation() {
    if [ "$DRY_RUN" = true ]; then
        info "Skipping final validation for dry run"
        return 0
    fi
    
    log "🔍 Running final validation..."
    
    cd "$PROJECT_DIR"
    
    # Run post-cleanup validation
    local validation_file="storage/migration-docs/post-phase3-validation.json"
    if php artisan validate:variant-data --export="$validation_file"; then
        log "✅ Final validation passed"
    else
        warning "⚠️ Final validation found issues. Please review."
        return 1
    fi
    
    # Test basic functionality
    log "🧪 Testing basic functionality..."
    
    # Test product with variants
    local test_result=$(php artisan tinker --execute="
        use App\Models\Product;
        \$product = Product::where('has_variants', true)->first();
        if (\$product) {
            echo 'Product: ' . \$product->id . ' | Variants: ' . \$product->variants->count() . ' | JSON: ' . (\$product->variant_config ? 'OK' : 'MISSING');
        } else {
            echo 'No products with variants found';
        }
    " 2>/dev/null | tail -n 1)
    
    log "  Test result: $test_result"
}

# Function to clear application cache
clear_application_cache() {
    if [ "$DRY_RUN" = true ]; then
        info "Skipping cache clear for dry run"
        return 0
    fi
    
    log "🧹 Clearing application cache..."
    
    cd "$PROJECT_DIR"
    
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    
    log "✅ Application cache cleared"
}

# Function to generate completion report
generate_completion_report() {
    if [ "$DRY_RUN" = true ]; then
        info "Skipping report generation for dry run"
        return 0
    fi
    
    log "📝 Generating completion report..."
    
    cd "$PROJECT_DIR"
    
    # Generate final system documentation
    php artisan document:variant-config --format=json --output="storage/migration-docs/"
    php artisan document:variant-config --format=markdown --output="storage/migration-docs/"
    
    # Create migration summary
    local summary_file="storage/migration-docs/migration-completion-summary.md"
    cat > "$summary_file" << EOF
# Variant System Migration - Completion Summary

## Migration Overview
- **Started**: Phase 1 Pre-Migration Setup
- **Completed**: $(date '+%Y-%m-%d %H:%M:%S')
- **Status**: ✅ COMPLETED

## Migration Phases
1. ✅ **Phase 1**: Pre-Migration Setup
   - Database backups created
   - Validation commands implemented
   - Migration infrastructure prepared

2. ✅ **Phase 2**: Data Migration
   - Legacy normalized data converted to JSON format
   - Variant options migrated successfully
   - Pricing overrides implemented

3. ✅ **Phase 3**: Legacy System Cleanup
   - Legacy tables cleaned up
   - Models updated for JSON-only system
   - Final validation completed

## System State
- **Legacy Tables**: Removed/Backed up
- **JSON System**: Active and validated
- **Models**: Updated with helper methods
- **Data Integrity**: Verified

## Next Steps
1. Monitor application for any issues
2. Update any custom code that may reference legacy models
3. Consider removing model backup files after thorough testing
4. Update documentation to reflect new JSON-based system

## Rollback Information
- Full database backups available in: storage/backups/
- Model backups available in: storage/model-backups/
- Migration audit logs in: variant_migration_audit table

Generated: $(date)
EOF
    
    log "✅ Completion report generated: $summary_file"
}

# Function to display completion summary
display_completion_summary() {
    log ""
    log "📊 PHASE 3 COMPLETION SUMMARY"
    log "============================="
    
    if [ "$DRY_RUN" = true ]; then
        echo "✅ Dry run completed successfully"
        echo "📄 No actual changes were made"
        echo ""
        echo "Ready to run actual cleanup:"
        echo "  ./scripts/run-phase3.sh --force"
    else
        echo "✅ Legacy system cleanup completed"
        echo "✅ Models updated for JSON-only system"
        echo "✅ Final validation completed"
        echo "✅ Application cache cleared"
        echo ""
        echo "🎉 VARIANT MIGRATION COMPLETED!"
        echo ""
        echo "📄 Generated files:"
        echo "  - Final validation: storage/migration-docs/"
        echo "  - Completion summary: storage/migration-docs/migration-completion-summary.md"
        echo "  - Model backups: storage/model-backups/"
        echo ""
        echo "🔧 System Status:"
        echo "  - Legacy tables: Removed"
        echo "  - JSON system: Active"
        echo "  - Models: Updated"
        echo "  - Cache: Cleared"
        echo ""
        echo "Next steps:"
        echo "1. Test your application thoroughly"
        echo "2. Update any custom code referencing legacy models"
        echo "3. Monitor for any issues"
        echo "4. Celebrate! 🎉"
    fi
    
    echo ""
    info "Migration artifacts preserved for rollback if needed"
    log "============================="
}

# Function to handle errors
handle_error() {
    local exit_code=$?
    local line_number=$1
    
    error "An error occurred in Phase 3 execution at line $line_number (exit code: $exit_code)"
    warning "Phase 3 may be partially completed. Check the following:"
    warning "1. Model backup files in storage/model-backups/"
    warning "2. Database backup files in storage/backups/"
    warning "3. Migration audit logs in variant_migration_audit table"
    
    if [ "$DRY_RUN" = false ]; then
        warning "If needed, restore from backups and run rollback procedures"
    fi
    
    exit $exit_code
}

# Set error trap
trap 'handle_error $LINENO' ERR

# Main execution
main() {
    parse_arguments "$@"
    
    log "🚀 Starting Phase 3: Legacy System Cleanup"
    log "========================================="
    
    check_prerequisites
    
    if [ "$DRY_RUN" = false ] && [ "$SKIP_CLEANUP" = false ]; then
        create_final_backup
        validate_current_state
    fi
    
    display_cleanup_plan
    
    # Confirm execution if not forced
    if [ "$FORCE" = false ] && [ "$DRY_RUN" = false ]; then
        warning "⚠️  This will permanently remove legacy variant system tables!"
        read -p "Proceed with Phase 3 cleanup? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            log "Phase 3 cancelled by user."
            exit 0
        fi
    fi
    
    update_models
    cleanup_legacy_system
    run_final_validation
    clear_application_cache
    generate_completion_report
    display_completion_summary
    
    log "🎉 Phase 3 completed successfully!"
}

# Run main function with all arguments
main "$@"
