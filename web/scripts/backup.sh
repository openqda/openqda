#!/bin/bash

################################################################################
# OpenQDA Backup Script
# 
# This script creates a full backup of the OpenQDA application including:
# - Database dump
# - Storage folder (compressed archive)
# - .env configuration file
#
# Configuration can be provided via:
# 1. A backup.config file in the same directory
# 2. Environment variables
# 3. Command-line arguments
#
# Usage: ./backup.sh [options]
# Options:
#   -c, --config FILE    Path to configuration file
#   -d, --dir DIR        Backup destination directory
#   -h, --help           Show this help message
#
################################################################################

set -e  # Exit on error
set -u  # Exit on undefined variable

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WEB_DIR="$(dirname "$SCRIPT_DIR")"

# Default configuration
BACKUP_DIR="${BACKUP_DIR:-/var/backups/openqda}"
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-web}"
DB_USER="${DB_USER:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"
STORAGE_PATH="${STORAGE_PATH:-$WEB_DIR/storage}"
ENV_FILE="${ENV_FILE:-$WEB_DIR/.env}"
BACKUP_RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-30}"
LOG_FILE="${LOG_FILE:-$BACKUP_DIR/backup.log}"

# Parse command-line arguments
CONFIG_FILE=""
while [[ $# -gt 0 ]]; do
    case $1 in
        -c|--config)
            CONFIG_FILE="$2"
            shift 2
            ;;
        -d|--dir)
            BACKUP_DIR="$2"
            shift 2
            ;;
        -h|--help)
            echo "OpenQDA Backup Script"
            echo ""
            echo "Usage: $0 [options]"
            echo ""
            echo "Options:"
            echo "  -c, --config FILE    Path to configuration file"
            echo "  -d, --dir DIR        Backup destination directory"
            echo "  -h, --help           Show this help message"
            echo ""
            echo "Configuration can also be provided via environment variables or a backup.config file"
            echo "in the same directory as this script."
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

# Load configuration file if it exists
if [ -n "$CONFIG_FILE" ] && [ -f "$CONFIG_FILE" ]; then
    echo "Loading configuration from: $CONFIG_FILE"
    # shellcheck source=/dev/null
    source "$CONFIG_FILE"
elif [ -f "$SCRIPT_DIR/backup.config" ]; then
    echo "Loading configuration from: $SCRIPT_DIR/backup.config"
    # shellcheck source=/dev/null
    source "$SCRIPT_DIR/backup.config"
fi

# Function to log messages
log_message() {
    local message="$1"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp] $message" | tee -a "$LOG_FILE"
}

# Function to handle errors
error_exit() {
    local message="$1"
    log_message "ERROR: $message"
    exit 1
}

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR" || error_exit "Failed to create backup directory: $BACKUP_DIR"

# Generate timestamp for backup
TIMESTAMP=$(date '+%Y%m%d_%H%M%S')
BACKUP_NAME="openqda_backup_$TIMESTAMP"
BACKUP_PATH="$BACKUP_DIR/$BACKUP_NAME"

log_message "========================================"
log_message "Starting OpenQDA backup: $BACKUP_NAME"
log_message "========================================"

# Create temporary backup directory
mkdir -p "$BACKUP_PATH" || error_exit "Failed to create temporary backup directory: $BACKUP_PATH"

# 1. Backup Database
log_message "Backing up database: $DB_NAME"
if [ -n "$DB_PASSWORD" ]; then
    mysqldump --host="$DB_HOST" \
              --port="$DB_PORT" \
              --user="$DB_USER" \
              --password="$DB_PASSWORD" \
              --single-transaction \
              --quick \
              --lock-tables=false \
              "$DB_NAME" > "$BACKUP_PATH/database.sql" 2>> "$LOG_FILE" || error_exit "Database backup failed"
else
    mysqldump --host="$DB_HOST" \
              --port="$DB_PORT" \
              --user="$DB_USER" \
              --single-transaction \
              --quick \
              --lock-tables=false \
              "$DB_NAME" > "$BACKUP_PATH/database.sql" 2>> "$LOG_FILE" || error_exit "Database backup failed"
fi

# Compress database dump
log_message "Compressing database dump"
gzip "$BACKUP_PATH/database.sql" || error_exit "Failed to compress database dump"
log_message "Database backup completed: database.sql.gz"

# 2. Backup Storage folder
log_message "Backing up storage folder: $STORAGE_PATH"
if [ -d "$STORAGE_PATH" ]; then
    tar -czf "$BACKUP_PATH/storage.tar.gz" -C "$(dirname "$STORAGE_PATH")" "$(basename "$STORAGE_PATH")" 2>> "$LOG_FILE" || error_exit "Storage backup failed"
    log_message "Storage backup completed: storage.tar.gz"
else
    log_message "WARNING: Storage path does not exist: $STORAGE_PATH"
fi

# 3. Backup .env file
log_message "Backing up .env file: $ENV_FILE"
if [ -f "$ENV_FILE" ]; then
    cp "$ENV_FILE" "$BACKUP_PATH/.env" || error_exit "Failed to backup .env file"
    log_message ".env backup completed"
else
    log_message "WARNING: .env file does not exist: $ENV_FILE"
fi

# 4. Create backup metadata
log_message "Creating backup metadata"
cat > "$BACKUP_PATH/backup_info.txt" << EOF
OpenQDA Backup Information
==========================
Backup Date: $(date '+%Y-%m-%d %H:%M:%S')
Backup Name: $BACKUP_NAME
Database: $DB_NAME
Database Host: $DB_HOST:$DB_PORT
Storage Path: $STORAGE_PATH
Environment File: $ENV_FILE

Contents:
- database.sql.gz: MySQL database dump
- storage.tar.gz: Application storage files
- .env: Environment configuration file
- backup_info.txt: This metadata file

To restore this backup:
1. Extract database: gunzip database.sql.gz
2. Import database: mysql -u USER -p DATABASE < database.sql
3. Extract storage: tar -xzf storage.tar.gz -C /path/to/web/
4. Copy .env to application root (review and adjust settings as needed)
EOF

# 5. Create final compressed archive
log_message "Creating final compressed archive"
cd "$BACKUP_DIR"
tar -czf "$BACKUP_NAME.tar.gz" "$BACKUP_NAME" 2>> "$LOG_FILE" || error_exit "Failed to create final archive"

# Remove temporary directory
rm -rf "$BACKUP_PATH" || log_message "WARNING: Failed to remove temporary backup directory"

# Calculate backup size
BACKUP_SIZE=$(du -h "$BACKUP_NAME.tar.gz" | cut -f1)
log_message "Backup completed successfully: $BACKUP_NAME.tar.gz ($BACKUP_SIZE)"

# 6. Cleanup old backups
if [ "$BACKUP_RETENTION_DAYS" -gt 0 ]; then
    log_message "Cleaning up backups older than $BACKUP_RETENTION_DAYS days"
    find "$BACKUP_DIR" -name "openqda_backup_*.tar.gz" -type f -mtime +$BACKUP_RETENTION_DAYS -delete 2>> "$LOG_FILE"
    OLD_LOGS=$(find "$BACKUP_DIR" -name "openqda_backup_*" -type d -mtime +$BACKUP_RETENTION_DAYS 2>/dev/null | wc -l)
    if [ "$OLD_LOGS" -gt 0 ]; then
        find "$BACKUP_DIR" -name "openqda_backup_*" -type d -mtime +$BACKUP_RETENTION_DAYS -exec rm -rf {} + 2>> "$LOG_FILE"
        log_message "Cleaned up old backup directories"
    fi
fi

log_message "========================================"
log_message "Backup process completed successfully"
log_message "Backup location: $BACKUP_DIR/$BACKUP_NAME.tar.gz"
log_message "========================================"

exit 0
