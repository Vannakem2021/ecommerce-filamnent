#!/bin/bash

# Rollback Script for Variant Migration
# Usage: ./scripts/rollback-migration.sh [phase] [backup_timestamp]

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="$PROJECT_DIR/storage/backups"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
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

# Load environment variables
if [ -f "$PROJECT_DIR/.env" ]; then
    source "$PROJECT_DIR/.env"
else
    error ".env file not found in project root"
    exit 1
fi

# Function to show usage
show_usage() {
    echo "Usage: $0 [phase] [backup_timestamp]"
    echo ""
    echo "Parameters:"
    echo "  phase             Migration phase to rollback (phase_1, phase_2, etc.)"
    echo "  backup_timestamp  Timestamp of backup to restore (optional, will prompt if not provided)"
    echo ""
    echo "Examples:"
    echo "  $0 phase_1"
    echo "  $0 phase_2 20250810_160000"
    echo ""
    echo "Available backups:"
    ls -la "$BACKUP_DIR/pre_migration/" 2>/dev/null | grep ".sql" | tail -5 || echo "  No backups found"
}

# Function to list available backups
list_backups() {
    log "📋 Available backups:"
    if [ -d "$BACKUP_DIR/pre_migration" ]; then
        find "$BACKUP_DIR/pre_migration" -name "*.sql" -type f -printf "%f\n" | sort -r | head -10
    else
        warning "No backup directory found"
    fi
}

# Function to confirm rollback
confirm_rollback() {
    local phase=$1
    local backup_file=$2
    
    warning "⚠️  ROLLBACK CONFIRMATION ⚠️"
    echo "=================================="
    echo "Phase: $phase"
    echo "Backup file: $(basename "$backup_file")"
    echo "Database: $DB_DATABASE"
    echo ""
    echo "This will:"
    echo "1. Restore database from backup"
    echo "2. Reset migration status"
    echo "3. Clear migrated data"
    echo ""
    echo "⚠️  ALL CHANGES MADE DURING MIGRATION WILL BE LOST ⚠️"
    echo ""
    
    read -p "Are you absolutely sure you want to proceed? (type 'CONFIRM' to continue): " confirmation
    
    if [ "$confirmation" != "CONFIRM" ]; then
        log "Rollback cancelled by user"
        exit 0
    fi
}

# Function to create pre-rollback backup
create_pre_rollback_backup() {
    local timestamp=$(date +"%Y%m%d_%H%M%S")
    local backup_file="$BACKUP_DIR/pre_rollback/pre_rollback_${timestamp}.sql"
    
    log "💾 Creating pre-rollback backup..."
    
    mkdir -p "$BACKUP_DIR/pre_rollback"
    
    # Build mysqldump command
    local dump_cmd="mysqldump -u $DB_USERNAME"
    
    if [ -n "$DB_PASSWORD" ]; then
        dump_cmd="$dump_cmd -p$DB_PASSWORD"
    else
        dump_cmd="$dump_cmd -p"
    fi
    
    if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "127.0.0.1" ] && [ "$DB_HOST" != "localhost" ]; then
        dump_cmd="$dump_cmd -h $DB_HOST"
    fi
    
    if [ -n "$DB_PORT" ] && [ "$DB_PORT" != "3306" ]; then
        dump_cmd="$dump_cmd -P $DB_PORT"
    fi
    
    dump_cmd="$dump_cmd --single-transaction $DB_DATABASE"
    
    if eval "$dump_cmd" > "$backup_file"; then
        log "✅ Pre-rollback backup created: $(basename "$backup_file")"
    else
        error "Failed to create pre-rollback backup"
        exit 1
    fi
}

# Function to restore database
restore_database() {
    local backup_file=$1
    
    log "🔄 Restoring database from backup..."
    
    if [ ! -f "$backup_file" ]; then
        error "Backup file not found: $backup_file"
        exit 1
    fi
    
    # Build mysql restore command
    local restore_cmd="mysql -u $DB_USERNAME"
    
    if [ -n "$DB_PASSWORD" ]; then
        restore_cmd="$restore_cmd -p$DB_PASSWORD"
    else
        restore_cmd="$restore_cmd -p"
    fi
    
    if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "127.0.0.1" ] && [ "$DB_HOST" != "localhost" ]; then
        restore_cmd="$restore_cmd -h $DB_HOST"
    fi
    
    if [ -n "$DB_PORT" ] && [ "$DB_PORT" != "3306" ]; then
        restore_cmd="$restore_cmd -P $DB_PORT"
    fi
    
    restore_cmd="$restore_cmd $DB_DATABASE"
    
    if eval "$restore_cmd < $backup_file"; then
        log "✅ Database restored successfully"
    else
        error "Failed to restore database"
        exit 1
    fi
}

# Function to reset migration flags
reset_migration_flags() {
    local phase=$1
    
    log "🔄 Resetting migration flags..."
    
    # Reset product migration flags
    mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "
        USE $DB_DATABASE;
        UPDATE products SET migrated_to_json = FALSE WHERE migrated_to_json = TRUE;
    " 2>/dev/null
    
    # Reset variant migration flags
    mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "
        USE $DB_DATABASE;
        UPDATE product_variants SET migrated_to_json = FALSE WHERE migrated_to_json = TRUE;
    " 2>/dev/null
    
    log "✅ Migration flags reset"
}

# Function to run Laravel migrations rollback
rollback_laravel_migrations() {
    local phase=$1
    
    log "📦 Rolling back Laravel migrations..."
    
    cd "$PROJECT_DIR"
    
    case $phase in
        "phase_1")
            # Rollback Phase 1 migrations
            php artisan migrate:rollback --step=3
            ;;
        "phase_2")
            # Rollback Phase 2 migrations
            php artisan migrate:rollback --step=5
            ;;
        *)
            warning "No specific migration rollback defined for phase: $phase"
            ;;
    esac
    
    log "✅ Laravel migrations rolled back"
}

# Function to clear migration audit logs
clear_migration_logs() {
    local phase=$1
    
    log "🧹 Clearing migration audit logs..."
    
    mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "
        USE $DB_DATABASE;
        DELETE FROM variant_migration_audit WHERE phase = '$phase';
    " 2>/dev/null || warning "Could not clear migration logs (table may not exist)"
    
    log "✅ Migration logs cleared"
}

# Function to verify rollback
verify_rollback() {
    local phase=$1
    
    log "🔍 Verifying rollback..."
    
    # Run validation command
    cd "$PROJECT_DIR"
    php artisan validate:variant-data --export="storage/migration-docs/post-rollback-validation.json"
    
    # Check for migration flags
    local migrated_products=$(mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "
        USE $DB_DATABASE;
        SELECT COUNT(*) FROM products WHERE migrated_to_json = TRUE;
    " 2>/dev/null | tail -n 1)
    
    local migrated_variants=$(mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "
        USE $DB_DATABASE;
        SELECT COUNT(*) FROM product_variants WHERE migrated_to_json = TRUE;
    " 2>/dev/null | tail -n 1)
    
    if [ "$migrated_products" = "0" ] && [ "$migrated_variants" = "0" ]; then
        log "✅ Rollback verification passed"
        return 0
    else
        warning "⚠️ Rollback verification found remaining migration flags"
        return 1
    fi
}

# Function to display rollback summary
display_summary() {
    local phase=$1
    local backup_file=$2
    
    log ""
    log "📊 ROLLBACK SUMMARY"
    log "=================================="
    echo "Phase: $phase"
    echo "Backup used: $(basename "$backup_file")"
    echo "Database: $DB_DATABASE"
    echo "Completed at: $(date)"
    echo ""
    echo "✅ System has been rolled back to pre-migration state"
    echo ""
    echo "Next steps:"
    echo "1. Review validation report: storage/migration-docs/post-rollback-validation.json"
    echo "2. Verify application functionality"
    echo "3. Address any issues found during migration"
    log "=================================="
}

# Main function
main() {
    local phase=$1
    local backup_timestamp=$2
    
    # Validate parameters
    if [ -z "$phase" ]; then
        show_usage
        exit 1
    fi
    
    # Check if mysql is available
    if ! command -v mysql &> /dev/null; then
        error "MySQL client is not installed or not in PATH"
        exit 1
    fi
    
    # List backups if no timestamp provided
    if [ -z "$backup_timestamp" ]; then
        log "📋 Available backups:"
        list_backups
        echo ""
        read -p "Enter backup timestamp (from filename): " backup_timestamp
        
        if [ -z "$backup_timestamp" ]; then
            error "Backup timestamp is required"
            exit 1
        fi
    fi
    
    # Construct backup file path
    local backup_file="$BACKUP_DIR/pre_migration/full_backup_${backup_timestamp}.sql"
    
    if [ ! -f "$backup_file" ]; then
        error "Backup file not found: $backup_file"
        list_backups
        exit 1
    fi
    
    # Confirm rollback
    confirm_rollback "$phase" "$backup_file"
    
    # Execute rollback
    log "🚀 Starting rollback for $phase..."
    
    create_pre_rollback_backup
    restore_database "$backup_file"
    reset_migration_flags "$phase"
    rollback_laravel_migrations "$phase"
    clear_migration_logs "$phase"
    
    # Verify rollback
    if verify_rollback "$phase"; then
        display_summary "$phase" "$backup_file"
        log "🎉 Rollback completed successfully!"
    else
        warning "⚠️ Rollback completed but verification failed. Please review manually."
    fi
}

# Run main function with all arguments
main "$@"
