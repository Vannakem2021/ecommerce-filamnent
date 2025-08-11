#!/bin/bash

# Phase 2 Execution Script for Variant Migration
# Usage: ./scripts/run-phase2.sh [options]

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Default options
BATCH_SIZE=100
DRY_RUN=false
FORCE=false
RESUME=false

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
    echo "  --batch-size=N    Number of records to process per batch (default: 100)"
    echo "  --dry-run         Show what would be done without executing"
    echo "  --force          Skip confirmation prompts"
    echo "  --resume         Resume from last failed migration"
    echo "  --help           Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0                          # Run with defaults"
    echo "  $0 --dry-run               # Test run without changes"
    echo "  $0 --batch-size=50 --force # Smaller batches, no prompts"
    echo "  $0 --resume                # Resume from failure"
}

# Parse command line arguments
parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --batch-size=*)
                BATCH_SIZE="${1#*=}"
                shift
                ;;
            --dry-run)
                DRY_RUN=true
                shift
                ;;
            --force)
                FORCE=true
                shift
                ;;
            --resume)
                RESUME=true
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
    log "🔍 Checking Phase 2 prerequisites..."
    
    # Check if we're in the right directory
    if [ ! -f "$PROJECT_DIR/artisan" ]; then
        error "Laravel project not found. Please run this script from the project root."
        exit 1
    fi
    
    # Check if Phase 1 was completed
    cd "$PROJECT_DIR"
    if ! php artisan tinker --execute="
        use Illuminate\Support\Facades\Schema;
        echo (Schema::hasColumn('products', 'migrated_to_json') && 
              Schema::hasColumn('product_variants', 'migrated_to_json') && 
              Schema::hasTable('variant_migration_audit')) ? 'OK' : 'FAIL';
    " 2>/dev/null | grep -q "OK"; then
        error "Phase 1 setup is incomplete. Please run Phase 1 first."
        exit 1
    fi
    
    log "✅ Prerequisites check passed"
}

# Function to create pre-migration backup
create_pre_migration_backup() {
    log "💾 Creating pre-migration backup..."
    
    if [ -f "$SCRIPT_DIR/backup-database.sh" ]; then
        bash "$SCRIPT_DIR/backup-database.sh"
    else
        warning "Backup script not found. Proceeding without backup."
        if [ "$FORCE" = false ]; then
            read -p "Continue without backup? (y/N): " -n 1 -r
            echo
            if [[ ! $REPLY =~ ^[Yy]$ ]]; then
                error "Migration aborted."
                exit 1
            fi
        fi
    fi
    
    log "✅ Backup completed"
}

# Function to validate current state
validate_current_state() {
    log "🔍 Validating current system state..."
    
    cd "$PROJECT_DIR"
    
    # Run validation with export
    local validation_file="storage/migration-docs/pre-phase2-validation.json"
    if ! php artisan validate:variant-data --export="$validation_file"; then
        warning "Pre-migration validation found issues."
        
        if [ "$FORCE" = false ]; then
            read -p "Continue with migration despite validation issues? (y/N): " -n 1 -r
            echo
            if [[ ! $REPLY =~ ^[Yy]$ ]]; then
                error "Migration aborted due to validation issues."
                exit 1
            fi
        fi
    fi
    
    log "✅ Current state validated"
}

# Function to display migration plan
display_migration_plan() {
    log ""
    log "📋 PHASE 2 MIGRATION PLAN"
    log "========================="
    
    cd "$PROJECT_DIR"
    
    # Get migration statistics
    local total_products=$(php artisan tinker --execute="
        use App\Models\Product;
        echo Product::where('has_variants', true)->count();
    " 2>/dev/null | tail -n 1)
    
    local total_variants=$(php artisan tinker --execute="
        use App\Models\ProductVariant;
        echo ProductVariant::count();
    " 2>/dev/null | tail -n 1)
    
    local already_migrated=$(php artisan tinker --execute="
        use App\Models\Product;
        echo Product::where('migrated_to_json', true)->count();
    " 2>/dev/null | tail -n 1)
    
    echo "Products with variants: $total_products"
    echo "Total variants: $total_variants"
    echo "Already migrated: $already_migrated"
    echo "Batch size: $BATCH_SIZE"
    
    if [ "$DRY_RUN" = true ]; then
        echo "Mode: DRY RUN (no changes will be made)"
    elif [ "$RESUME" = true ]; then
        echo "Mode: RESUME from last failure"
    else
        echo "Mode: FULL MIGRATION"
    fi
    
    log ""
}

# Function to run the migration
run_migration() {
    log "🚀 Starting Phase 2 data migration..."
    
    cd "$PROJECT_DIR"
    
    # Build command arguments
    local cmd_args="migrate:variant-data-to-json --batch-size=$BATCH_SIZE"
    
    if [ "$DRY_RUN" = true ]; then
        cmd_args="$cmd_args --dry-run"
    fi
    
    if [ "$FORCE" = true ]; then
        cmd_args="$cmd_args --force"
    fi
    
    if [ "$RESUME" = true ]; then
        cmd_args="$cmd_args --resume"
    fi
    
    # Execute migration
    if php artisan $cmd_args; then
        log "✅ Migration command completed successfully"
    else
        error "Migration command failed"
        return 1
    fi
}

# Function to validate migrated data
validate_migrated_data() {
    if [ "$DRY_RUN" = true ]; then
        info "Skipping validation for dry run"
        return 0
    fi
    
    log "🔍 Validating migrated data..."
    
    cd "$PROJECT_DIR"
    
    # Run post-migration validation
    local validation_file="storage/migration-docs/post-phase2-validation.json"
    if php artisan validate:variant-data --export="$validation_file"; then
        log "✅ Post-migration validation passed"
    else
        warning "⚠️ Post-migration validation found issues. Please review."
        return 1
    fi
}

# Function to generate migration report
generate_migration_report() {
    if [ "$DRY_RUN" = true ]; then
        info "Skipping report generation for dry run"
        return 0
    fi
    
    log "📝 Generating migration report..."
    
    cd "$PROJECT_DIR"
    
    # Generate updated configuration documentation
    php artisan document:variant-config --format=json --output="storage/migration-docs/"
    php artisan document:variant-config --format=markdown --output="storage/migration-docs/"
    
    log "✅ Migration report generated"
}

# Function to display completion summary
display_completion_summary() {
    log ""
    log "📊 PHASE 2 COMPLETION SUMMARY"
    log "============================="
    
    if [ "$DRY_RUN" = true ]; then
        echo "✅ Dry run completed successfully"
        echo "📄 No actual changes were made"
        echo ""
        echo "Ready to run actual migration:"
        echo "  ./scripts/run-phase2.sh --force"
    else
        echo "✅ Data migration completed"
        echo "✅ Validation completed"
        echo "✅ Reports generated"
        echo ""
        echo "📄 Generated files:"
        echo "  - Validation reports: storage/migration-docs/"
        echo "  - Configuration docs: storage/migration-docs/"
        echo ""
        echo "🎯 Ready for Phase 3!"
        echo ""
        echo "Next steps:"
        echo "1. Review validation reports"
        echo "2. Test frontend functionality"
        echo "3. Verify JSON data structure"
        echo "4. Proceed with Phase 3 when ready"
    fi
    
    echo ""
    info "To rollback Phase 2: php artisan rollback:variant-migration --phase=phase_2"
    log "============================="
}

# Function to handle errors
handle_error() {
    local exit_code=$?
    local line_number=$1
    
    error "An error occurred in Phase 2 execution at line $line_number (exit code: $exit_code)"
    warning "Phase 2 may be partially completed. Please review and consider rollback if needed."
    
    if [ "$DRY_RUN" = false ]; then
        warning "Check audit logs: SELECT * FROM variant_migration_audit WHERE phase = 'phase_2' AND status = 'failed'"
    fi
    
    exit $exit_code
}

# Set error trap
trap 'handle_error $LINENO' ERR

# Main execution
main() {
    parse_arguments "$@"
    
    log "🚀 Starting Phase 2: Data Migration"
    log "=================================="
    
    check_prerequisites
    
    if [ "$DRY_RUN" = false ] && [ "$RESUME" = false ]; then
        create_pre_migration_backup
        validate_current_state
    fi
    
    display_migration_plan
    
    # Confirm execution if not forced
    if [ "$FORCE" = false ] && [ "$DRY_RUN" = false ]; then
        read -p "Proceed with Phase 2 migration? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            log "Migration cancelled by user."
            exit 0
        fi
    fi
    
    run_migration
    validate_migrated_data
    generate_migration_report
    display_completion_summary
    
    log "🎉 Phase 2 completed successfully!"
}

# Run main function with all arguments
main "$@"
