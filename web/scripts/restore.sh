#!/bin/bash

################################################################################
# OpenQDA Restore Script
# 
# This script restores an OpenQDA backup created by backup.sh including:
# - Database restoration (optional)
# - Storage folder restoration (optional, non-overwrite)
# - .env configuration file
#
# The script automatically detects if it's running inside a Docker container
# and adjusts the default database host accordingly:
# - Inside Docker: defaults to "mysql" (container name)
# - Outside Docker: defaults to "localhost"
#
# Usage: ./restore.sh [options] BACKUP_FILE
# Options:
#   --db-only           Restore only the database
#   --storage-only      Restore only storage files
#   --skip-db           Skip database restoration
#   --skip-storage      Skip storage restoration
#   --db-host HOST      Database host (default: auto-detected)
#   --db-port PORT      Database port (default: 3306)
#   --db-name NAME      Database name (default: web)
#   --db-user USER      Database user (default: root)
#   --db-password PASS  Database password (optional)
#   --storage-path PATH Path to restore storage (default: ../storage)
#   --env-path PATH     Path to restore .env file (default: ../.env)
#   --force-env         Overwrite existing .env file
#   -h, --help          Show this help message
#
################################################################################

set -e  # Exit on error
set -u  # Exit on undefined variable

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WEB_DIR="$(dirname "$SCRIPT_DIR")"

# Detect if running inside Docker container
# If inside Docker and mysql container exists, use "mysql" as default host
DEFAULT_DB_HOST="localhost"
if [ -f /.dockerenv ] && getent hosts mysql >/dev/null 2>&1; then
    DEFAULT_DB_HOST="mysql"
fi

# Default configuration
RESTORE_DB=true
RESTORE_STORAGE=true
DB_HOST="$DEFAULT_DB_HOST"
DB_PORT="3306"
DB_NAME="web"
DB_USER="root"
DB_PASSWORD=""
STORAGE_PATH="$WEB_DIR/storage"
ENV_PATH="$WEB_DIR/.env"
FORCE_ENV=false
BACKUP_FILE=""

# Function to show help
show_help() {
    echo "OpenQDA Restore Script"
    echo ""
    echo "Usage: $0 [options] BACKUP_FILE"
    echo ""
    echo "Options:"
    echo "  --db-only           Restore only the database"
    echo "  --storage-only      Restore only storage files"
    echo "  --skip-db           Skip database restoration"
    echo "  --skip-storage      Skip storage restoration"
    echo "  --db-host HOST      Database host (default: auto-detected, 'mysql' in Docker, 'localhost' otherwise)"
    echo "  --db-port PORT      Database port (default: 3306)"
    echo "  --db-name NAME      Database name (default: web)"
    echo "  --db-user USER      Database user (default: root)"
    echo "  --db-password PASS  Database password (optional)"
    echo "  --storage-path PATH Path to restore storage (default: ../storage)"
    echo "  --env-path PATH     Path to restore .env file (default: ../.env)"
    echo "  --force-env         Overwrite existing .env file"
    echo "  -h, --help          Show this help message"
    echo ""
    echo "Example:"
    echo "  $0 openqda_backup_20260108_000000.tar.gz"
    echo "  $0 --db-only --db-password=secret backup.tar.gz"
    echo "  $0 --storage-only backup.tar.gz"
    exit 0
}

# Parse command-line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --db-only)
            RESTORE_DB=true
            RESTORE_STORAGE=false
            shift
            ;;
        --storage-only)
            RESTORE_DB=false
            RESTORE_STORAGE=true
            shift
            ;;
        --skip-db)
            RESTORE_DB=false
            shift
            ;;
        --skip-storage)
            RESTORE_STORAGE=false
            shift
            ;;
        --db-host)
            DB_HOST="$2"
            shift 2
            ;;
        --db-port)
            DB_PORT="$2"
            shift 2
            ;;
        --db-name)
            DB_NAME="$2"
            shift 2
            ;;
        --db-user)
            DB_USER="$2"
            shift 2
            ;;
        --db-password)
            DB_PASSWORD="$2"
            shift 2
            ;;
        --storage-path)
            STORAGE_PATH="$2"
            shift 2
            ;;
        --env-path)
            ENV_PATH="$2"
            shift 2
            ;;
        --force-env)
            FORCE_ENV=true
            shift
            ;;
        -h|--help)
            show_help
            ;;
        -*)
            echo "Unknown option: $1"
            echo "Use --help for usage information"
            exit 1
            ;;
        *)
            if [ -z "$BACKUP_FILE" ]; then
                BACKUP_FILE="$1"
            else
                echo "Error: Multiple backup files specified"
                exit 1
            fi
            shift
            ;;
    esac
done

# Check if backup file is provided
if [ -z "$BACKUP_FILE" ]; then
    echo "Error: No backup file specified"
    echo "Use --help for usage information"
    exit 1
fi

# Check if backup file exists
if [ ! -f "$BACKUP_FILE" ]; then
    echo "Error: Backup file not found: $BACKUP_FILE"
    exit 1
fi

# Function to log messages
log_message() {
    local message="$1"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp] $message"
}

# Function to handle errors
error_exit() {
    local message="$1"
    log_message "ERROR: $message"
    exit 1
}

log_message "========================================"
log_message "Starting OpenQDA restore from: $(basename "$BACKUP_FILE")"
log_message "========================================"

# Create temporary directory for extraction
TEMP_DIR=$(mktemp -d)
trap "rm -rf $TEMP_DIR" EXIT

log_message "Extracting backup archive to temporary directory..."
tar -xzf "$BACKUP_FILE" -C "$TEMP_DIR" || error_exit "Failed to extract backup archive"

# Find the backup directory (should be the only directory in temp)
BACKUP_DIR=$(find "$TEMP_DIR" -mindepth 1 -maxdepth 1 -type d | head -n 1)

if [ -z "$BACKUP_DIR" ]; then
    error_exit "No backup directory found in archive"
fi

log_message "Backup directory: $(basename "$BACKUP_DIR")"

# Display backup information if available
if [ -f "$BACKUP_DIR/backup_info.txt" ]; then
    log_message "Backup information:"
    cat "$BACKUP_DIR/backup_info.txt" | grep -E "^(Backup Date|Database|Storage Path)" || true
    echo ""
fi

# 1. Restore Database
if [ "$RESTORE_DB" = true ]; then
    log_message "Restoring database: $DB_NAME"
    
    if [ ! -f "$BACKUP_DIR/database.sql.gz" ]; then
        log_message "WARNING: Database backup not found, skipping database restoration"
    else
        # Decompress database dump
        log_message "Decompressing database dump..."
        gunzip -c "$BACKUP_DIR/database.sql.gz" > "$TEMP_DIR/database.sql" || error_exit "Failed to decompress database dump"
        
        # Import database
        log_message "Importing database to $DB_NAME on $DB_HOST..."
        if [ -n "$DB_PASSWORD" ]; then
            MYSQL_PWD="$DB_PASSWORD" mysql --host="$DB_HOST" \
                          --port="$DB_PORT" \
                          --user="$DB_USER" \
                          "$DB_NAME" < "$TEMP_DIR/database.sql" || error_exit "Database restoration failed"
        else
            mysql --host="$DB_HOST" \
                  --port="$DB_PORT" \
                  --user="$DB_USER" \
                  "$DB_NAME" < "$TEMP_DIR/database.sql" || error_exit "Database restoration failed"
        fi
        
        log_message "Database restored successfully"
    fi
else
    log_message "Skipping database restoration (as requested)"
fi

# 2. Restore Storage
if [ "$RESTORE_STORAGE" = true ]; then
    log_message "Restoring storage folder to: $STORAGE_PATH"
    
    if [ ! -f "$BACKUP_DIR/storage.tar.gz" ]; then
        log_message "WARNING: Storage backup not found, skipping storage restoration"
    else
        # Create storage parent directory if it doesn't exist
        mkdir -p "$(dirname "$STORAGE_PATH")"
        
        # Extract storage with --keep-old-files to prevent overwriting
        log_message "Extracting storage files (existing files will not be overwritten)..."
        tar -xzf "$BACKUP_DIR/storage.tar.gz" -C "$(dirname "$STORAGE_PATH")" --keep-old-files 2>/dev/null || {
            # If --keep-old-files causes issues, try with --skip-old-files
            log_message "Using alternative extraction method..."
            tar -xzf "$BACKUP_DIR/storage.tar.gz" -C "$(dirname "$STORAGE_PATH")" --skip-old-files 2>/dev/null || {
                # Fall back to manual check
                log_message "Extracting with manual conflict checking..."
                TEMP_STORAGE="$TEMP_DIR/storage_temp"
                mkdir -p "$TEMP_STORAGE"
                tar -xzf "$BACKUP_DIR/storage.tar.gz" -C "$TEMP_STORAGE" || error_exit "Failed to extract storage backup"
                
                # Copy files that don't exist
                if [ -d "$TEMP_STORAGE/storage" ]; then
                    rsync -av --ignore-existing "$TEMP_STORAGE/storage/" "$STORAGE_PATH/" || error_exit "Failed to restore storage files"
                fi
            }
        }
        
        log_message "Storage restored successfully (existing files preserved)"
    fi
else
    log_message "Skipping storage restoration (as requested)"
fi

# 3. Restore .env file
if [ -f "$BACKUP_DIR/.env" ]; then
    if [ -f "$ENV_PATH" ] && [ "$FORCE_ENV" = false ]; then
        log_message "WARNING: .env file already exists at $ENV_PATH"
        log_message "Backup .env saved to $ENV_PATH.backup-$(date +%Y%m%d_%H%M%S)"
        cp "$BACKUP_DIR/.env" "$ENV_PATH.backup-$(date +%Y%m%d_%H%M%S)"
        log_message "Review the backed up .env and manually merge if needed"
    else
        log_message "Restoring .env file to: $ENV_PATH"
        cp "$BACKUP_DIR/.env" "$ENV_PATH" || error_exit "Failed to restore .env file"
        log_message ".env file restored successfully"
        log_message "IMPORTANT: Review .env file and adjust settings as needed (URLs, paths, etc.)"
    fi
else
    log_message "WARNING: .env file not found in backup"
fi

log_message "========================================"
log_message "Restore process completed successfully"
log_message "========================================"

# Display post-restore instructions
echo ""
echo "Post-restore checklist:"
echo "  1. Review and adjust .env file settings if needed"
echo "  2. Set proper permissions on storage directory:"
echo "     chown -R www-data:www-data $STORAGE_PATH"
echo "     chmod -R 775 $STORAGE_PATH"
echo "  3. Clear application cache: php artisan cache:clear"
echo "  4. Test the application thoroughly"
echo ""

exit 0
