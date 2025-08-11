#!/bin/bash

# Database Backup Script for Variant Migration
# Usage: ./scripts/backup-database.sh [environment]

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="$PROJECT_DIR/storage/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# Environment (default to local)
ENV=${1:-local}

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging function
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

# Validate required environment variables
if [ -z "$DB_DATABASE" ] || [ -z "$DB_USERNAME" ]; then
    error "Database configuration missing in .env file"
    exit 1
fi

# Create backup directory
mkdir -p "$BACKUP_DIR"
mkdir -p "$BACKUP_DIR/pre_migration"
mkdir -p "$BACKUP_DIR/legacy_tables"

log "🚀 Starting database backup for variant migration..."
log "Environment: $ENV"
log "Database: $DB_DATABASE"
log "Backup directory: $BACKUP_DIR"

# Function to create full database backup
create_full_backup() {
    local backup_file="$BACKUP_DIR/pre_migration/full_backup_${TIMESTAMP}.sql"
    
    log "📦 Creating full database backup..."
    
    # Build mysqldump command
    local dump_cmd="mysqldump"
    
    if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "127.0.0.1" ] && [ "$DB_HOST" != "localhost" ]; then
        dump_cmd="$dump_cmd -h $DB_HOST"
    fi
    
    if [ -n "$DB_PORT" ] && [ "$DB_PORT" != "3306" ]; then
        dump_cmd="$dump_cmd -P $DB_PORT"
    fi
    
    dump_cmd="$dump_cmd -u $DB_USERNAME"
    
    if [ -n "$DB_PASSWORD" ]; then
        dump_cmd="$dump_cmd -p$DB_PASSWORD"
    else
        dump_cmd="$dump_cmd -p"
    fi
    
    # Add mysqldump options
    dump_cmd="$dump_cmd --single-transaction --routines --triggers --events --set-gtid-purged=OFF"
    dump_cmd="$dump_cmd $DB_DATABASE"
    
    # Execute backup
    if eval "$dump_cmd" > "$backup_file"; then
        log "✅ Full backup created: $(basename "$backup_file")"
        log "   Size: $(du -h "$backup_file" | cut -f1)"
    else
        error "Failed to create full backup"
        exit 1
    fi
}

# Function to create legacy tables backup
create_legacy_backup() {
    local backup_file="$BACKUP_DIR/legacy_tables/legacy_tables_${TIMESTAMP}.sql"
    
    log "📋 Creating legacy tables backup..."
    
    # Legacy tables to backup
    local tables=(
        "product_attributes"
        "product_attribute_values" 
        "product_variant_attributes"
        "specification_attributes"
        "specification_attribute_options"
        "product_specification_values"
        "variant_specification_values"
    )
    
    # Build mysqldump command for specific tables
    local dump_cmd="mysqldump"
    
    if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "127.0.0.1" ] && [ "$DB_HOST" != "localhost" ]; then
        dump_cmd="$dump_cmd -h $DB_HOST"
    fi
    
    if [ -n "$DB_PORT" ] && [ "$DB_PORT" != "3306" ]; then
        dump_cmd="$dump_cmd -P $DB_PORT"
    fi
    
    dump_cmd="$dump_cmd -u $DB_USERNAME"
    
    if [ -n "$DB_PASSWORD" ]; then
        dump_cmd="$dump_cmd -p$DB_PASSWORD"
    else
        dump_cmd="$dump_cmd -p"
    fi
    
    # Add tables and options
    dump_cmd="$dump_cmd --single-transaction --no-create-db"
    dump_cmd="$dump_cmd $DB_DATABASE ${tables[*]}"
    
    # Check if tables exist before backing up
    local existing_tables=()
    for table in "${tables[@]}"; do
        if mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "USE $DB_DATABASE; SHOW TABLES LIKE '$table';" 2>/dev/null | grep -q "$table"; then
            existing_tables+=("$table")
        else
            warning "Table '$table' does not exist, skipping..."
        fi
    done
    
    if [ ${#existing_tables[@]} -eq 0 ]; then
        warning "No legacy tables found to backup"
        return 0
    fi
    
    # Update command with existing tables only
    dump_cmd="mysqldump"
    
    if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "127.0.0.1" ] && [ "$DB_HOST" != "localhost" ]; then
        dump_cmd="$dump_cmd -h $DB_HOST"
    fi
    
    if [ -n "$DB_PORT" ] && [ "$DB_PORT" != "3306" ]; then
        dump_cmd="$dump_cmd -P $DB_PORT"
    fi
    
    dump_cmd="$dump_cmd -u $DB_USERNAME"
    
    if [ -n "$DB_PASSWORD" ]; then
        dump_cmd="$dump_cmd -p$DB_PASSWORD"
    else
        dump_cmd="$dump_cmd -p"
    fi
    
    dump_cmd="$dump_cmd --single-transaction --no-create-db"
    dump_cmd="$dump_cmd $DB_DATABASE ${existing_tables[*]}"
    
    # Execute backup
    if eval "$dump_cmd" > "$backup_file"; then
        log "✅ Legacy tables backup created: $(basename "$backup_file")"
        log "   Tables backed up: ${existing_tables[*]}"
        log "   Size: $(du -h "$backup_file" | cut -f1)"
    else
        error "Failed to create legacy tables backup"
        exit 1
    fi
}

# Function to create backup metadata
create_backup_metadata() {
    local metadata_file="$BACKUP_DIR/backup_metadata_${TIMESTAMP}.json"
    
    log "📝 Creating backup metadata..."
    
    # Get database information
    local db_version=$(mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT VERSION();" 2>/dev/null | tail -n 1)
    local table_count=$(mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "USE $DB_DATABASE; SHOW TABLES;" 2>/dev/null | wc -l)
    local product_count=$(mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "USE $DB_DATABASE; SELECT COUNT(*) FROM products WHERE has_variants = 1;" 2>/dev/null | tail -n 1)
    local variant_count=$(mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "USE $DB_DATABASE; SELECT COUNT(*) FROM product_variants;" 2>/dev/null | tail -n 1)
    
    # Create metadata JSON
    cat > "$metadata_file" << EOF
{
    "backup_info": {
        "timestamp": "$TIMESTAMP",
        "environment": "$ENV",
        "created_by": "$(whoami)",
        "hostname": "$(hostname)",
        "backup_purpose": "Pre-migration backup for variant system simplification"
    },
    "database_info": {
        "name": "$DB_DATABASE",
        "host": "$DB_HOST",
        "port": "$DB_PORT",
        "version": "$db_version",
        "total_tables": $((table_count - 1))
    },
    "migration_data": {
        "products_with_variants": $product_count,
        "total_variants": $variant_count,
        "backup_files": [
            "pre_migration/full_backup_${TIMESTAMP}.sql",
            "legacy_tables/legacy_tables_${TIMESTAMP}.sql"
        ]
    },
    "restore_commands": {
        "full_restore": "mysql -u \$DB_USERNAME -p\$DB_PASSWORD \$DB_DATABASE < storage/backups/pre_migration/full_backup_${TIMESTAMP}.sql",
        "legacy_tables_restore": "mysql -u \$DB_USERNAME -p\$DB_PASSWORD \$DB_DATABASE < storage/backups/legacy_tables/legacy_tables_${TIMESTAMP}.sql"
    }
}
EOF
    
    log "✅ Backup metadata created: $(basename "$metadata_file")"
}

# Function to verify backup integrity
verify_backups() {
    log "🔍 Verifying backup integrity..."
    
    local full_backup="$BACKUP_DIR/pre_migration/full_backup_${TIMESTAMP}.sql"
    local legacy_backup="$BACKUP_DIR/legacy_tables/legacy_tables_${TIMESTAMP}.sql"
    
    # Check if files exist and are not empty
    if [ ! -f "$full_backup" ] || [ ! -s "$full_backup" ]; then
        error "Full backup file is missing or empty"
        exit 1
    fi
    
    if [ ! -f "$legacy_backup" ] || [ ! -s "$legacy_backup" ]; then
        warning "Legacy backup file is missing or empty (this may be normal if no legacy tables exist)"
    fi
    
    # Check if SQL files are valid (contain expected headers)
    if ! head -10 "$full_backup" | grep -q "mysqldump"; then
        error "Full backup file appears to be corrupted"
        exit 1
    fi
    
    log "✅ Backup integrity verified"
}

# Function to display backup summary
display_summary() {
    log "📊 Backup Summary:"
    echo "=================================="
    echo "Timestamp: $TIMESTAMP"
    echo "Environment: $ENV"
    echo "Database: $DB_DATABASE"
    echo ""
    echo "Backup files created:"
    
    if [ -f "$BACKUP_DIR/pre_migration/full_backup_${TIMESTAMP}.sql" ]; then
        echo "  ✅ Full backup: $(du -h "$BACKUP_DIR/pre_migration/full_backup_${TIMESTAMP}.sql" | cut -f1)"
    fi
    
    if [ -f "$BACKUP_DIR/legacy_tables/legacy_tables_${TIMESTAMP}.sql" ]; then
        echo "  ✅ Legacy tables: $(du -h "$BACKUP_DIR/legacy_tables/legacy_tables_${TIMESTAMP}.sql" | cut -f1)"
    fi
    
    if [ -f "$BACKUP_DIR/backup_metadata_${TIMESTAMP}.json" ]; then
        echo "  ✅ Metadata: $(basename "$BACKUP_DIR/backup_metadata_${TIMESTAMP}.json")"
    fi
    
    echo ""
    echo "Backup location: $BACKUP_DIR"
    echo "=================================="
}

# Main execution
main() {
    # Check if mysql is available
    if ! command -v mysql &> /dev/null; then
        error "MySQL client is not installed or not in PATH"
        exit 1
    fi
    
    if ! command -v mysqldump &> /dev/null; then
        error "mysqldump is not installed or not in PATH"
        exit 1
    fi
    
    # Test database connection
    log "🔌 Testing database connection..."
    if ! mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "USE $DB_DATABASE; SELECT 1;" &> /dev/null; then
        error "Cannot connect to database. Please check your credentials."
        exit 1
    fi
    
    log "✅ Database connection successful"
    
    # Create backups
    create_full_backup
    create_legacy_backup
    create_backup_metadata
    verify_backups
    display_summary
    
    log "🎉 Database backup completed successfully!"
    log "💡 To restore: Use the commands in backup_metadata_${TIMESTAMP}.json"
}

# Run main function
main "$@"
