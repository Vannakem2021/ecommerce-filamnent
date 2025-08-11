#!/bin/bash

# Phase 1 Execution Script for Variant Migration
# Usage: ./scripts/run-phase1.sh

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

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

# Function to check prerequisites
check_prerequisites() {
    log "🔍 Checking prerequisites..."
    
    # Check if we're in the right directory
    if [ ! -f "$PROJECT_DIR/artisan" ]; then
        error "Laravel project not found. Please run this script from the project root."
        exit 1
    fi
    
    # Check if required commands exist
    command -v php >/dev/null 2>&1 || { error "PHP is required but not installed."; exit 1; }
    command -v mysql >/dev/null 2>&1 || { error "MySQL client is required but not installed."; exit 1; }
    command -v mysqldump >/dev/null 2>&1 || { error "mysqldump is required but not installed."; exit 1; }
    
    # Check Laravel environment
    cd "$PROJECT_DIR"
    if ! php artisan --version >/dev/null 2>&1; then
        error "Laravel environment is not properly configured."
        exit 1
    fi
    
    log "✅ Prerequisites check passed"
}

# Function to create backup
create_backup() {
    log "💾 Creating database backup..."
    
    if [ -f "$SCRIPT_DIR/backup-database.sh" ]; then
        bash "$SCRIPT_DIR/backup-database.sh"
    else
        error "Backup script not found. Please ensure backup-database.sh exists."
        exit 1
    fi
    
    log "✅ Backup completed"
}

# Function to validate current data
validate_data() {
    log "🔍 Validating current variant data..."
    
    cd "$PROJECT_DIR"
    if ! php artisan validate:variant-data --export="storage/migration-docs/pre-migration-validation.json"; then
        warning "Data validation found issues. Please review before continuing."
        read -p "Continue with migration? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            error "Migration aborted by user."
            exit 1
        fi
    fi
    
    log "✅ Data validation completed"
}

# Function to document current configuration
document_configuration() {
    log "📝 Documenting current variant configuration..."
    
    cd "$PROJECT_DIR"
    php artisan document:variant-config --format=json --output="storage/migration-docs/"
    php artisan document:variant-config --format=markdown --output="storage/migration-docs/"
    
    log "✅ Configuration documented"
}

# Function to run migration setup
run_migration_setup() {
    log "🚀 Running migration setup..."
    
    cd "$PROJECT_DIR"
    
    # Run preparation migrations
    info "Running preparation migrations..."
    php artisan migrate --path=database/migrations/2025_08_10_160234_enhance_products_for_json_attributes.php
    php artisan migrate --path=database/migrations/2025_08_10_160243_enhance_product_variants_for_json_options.php
    php artisan migrate --path=database/migrations/2025_08_10_160248_create_variant_migration_audit.php
    
    log "✅ Migration setup completed"
}

# Function to verify setup
verify_setup() {
    log "🔍 Verifying migration setup..."
    
    cd "$PROJECT_DIR"
    
    # Check if new columns exist
    info "Checking database schema..."
    if ! php artisan tinker --execute="Schema::hasColumn('products', 'migrated_to_json') && Schema::hasColumn('product_variants', 'migrated_to_json') && Schema::hasTable('variant_migration_audit') ? 'OK' : 'FAIL'" | grep -q "OK"; then
        error "Migration setup verification failed. Database schema is not ready."
        exit 1
    fi
    
    # Test commands
    info "Testing migration commands..."
    php artisan validate:variant-data --export="storage/migration-docs/post-setup-validation.json"
    
    log "✅ Setup verification passed"
}

# Function to display summary
display_summary() {
    log ""
    log "📊 PHASE 1 COMPLETION SUMMARY"
    log "=================================="
    echo "✅ Database backup created"
    echo "✅ Data validation completed"
    echo "✅ Configuration documented"
    echo "✅ Migration setup completed"
    echo "✅ Setup verification passed"
    echo ""
    echo "📄 Generated files:"
    echo "  - Database backup: storage/backups/"
    echo "  - Validation reports: storage/migration-docs/"
    echo "  - Configuration docs: storage/migration-docs/"
    echo ""
    echo "🎯 Ready for Phase 2!"
    echo ""
    echo "Next steps:"
    echo "1. Review validation reports"
    echo "2. Verify backup integrity"
    echo "3. Proceed with Phase 2 when ready"
    echo ""
    info "To rollback Phase 1: php artisan rollback:variant-migration --phase=phase_1"
    log "=================================="
}

# Function to handle errors
handle_error() {
    local exit_code=$?
    local line_number=$1
    
    error "An error occurred in Phase 1 execution at line $line_number (exit code: $exit_code)"
    warning "Phase 1 may be partially completed. Please review and consider rollback if needed."
    
    exit $exit_code
}

# Set error trap
trap 'handle_error $LINENO' ERR

# Main execution
main() {
    log "🚀 Starting Phase 1: Pre-Migration Setup"
    log "========================================"
    
    check_prerequisites
    create_backup
    validate_data
    document_configuration
    run_migration_setup
    verify_setup
    display_summary
    
    log "🎉 Phase 1 completed successfully!"
}

# Run main function
main "$@"
