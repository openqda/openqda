#!/bin/bash

################################################################################
# OpenQDA Backup Pull Script
# 
# This script pulls (downloads) OpenQDA backups from a remote server via SCP.
# It allows you to fetch backups from a remote backup server instead of pushing
# backups to it.
#
# Configuration can be provided via:
# 1. A backup.config file in the same directory
# 2. Environment variables
# 3. Command-line arguments
#
# Usage: ./pull.sh [options]
# Options:
#   -c, --config FILE    Path to configuration file
#   -d, --dir DIR        Local destination directory for pulled backups
#   -l, --list           List available backups on remote server
#   -n, --name NAME      Specific backup name to pull
#   --latest             Pull the latest backup
#   --all                Pull all backups from remote server
#   -h, --help           Show this help message
#
################################################################################

set -e  # Exit on error
set -u  # Exit on undefined variable

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Default configuration
BACKUP_DIR="${BACKUP_DIR:-/var/backups/openqda}"
LOG_FILE="${LOG_FILE:-$BACKUP_DIR/pull.log}"

# Remote backup configuration
REMOTE_BACKUP_HOST="${REMOTE_BACKUP_HOST:-}"
REMOTE_BACKUP_USER="${REMOTE_BACKUP_USER:-}"
REMOTE_BACKUP_PATH="${REMOTE_BACKUP_PATH:-}"
REMOTE_BACKUP_PORT="${REMOTE_BACKUP_PORT:-22}"
REMOTE_BACKUP_KEY="${REMOTE_BACKUP_KEY:-}"

# Operation mode
LIST_MODE=false
PULL_LATEST=false
PULL_ALL=false
BACKUP_NAME=""

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
        -l|--list)
            LIST_MODE=true
            shift
            ;;
        -n|--name)
            BACKUP_NAME="$2"
            shift 2
            ;;
        --latest)
            PULL_LATEST=true
            shift
            ;;
        --all)
            PULL_ALL=true
            shift
            ;;
        -h|--help)
            echo "OpenQDA Backup Pull Script"
            echo ""
            echo "Usage: $0 [options]"
            echo ""
            echo "Options:"
            echo "  -c, --config FILE    Path to configuration file"
            echo "  -d, --dir DIR        Local destination directory for pulled backups"
            echo "  -l, --list           List available backups on remote server"
            echo "  -n, --name NAME      Specific backup name to pull"
            echo "  --latest             Pull the latest backup"
            echo "  --all                Pull all backups from remote server"
            echo "  -h, --help           Show this help message"
            echo ""
            echo "Configuration can also be provided via environment variables or a backup.config file"
            echo "in the same directory as this script."
            echo ""
            echo "Required configuration:"
            echo "  REMOTE_BACKUP_HOST   - Remote server hostname or IP"
            echo "  REMOTE_BACKUP_USER   - SSH username for remote server"
            echo "  REMOTE_BACKUP_PATH   - Path to backups on remote server"
            echo ""
            echo "Optional configuration:"
            echo "  REMOTE_BACKUP_PORT   - SSH port (default: 22)"
            echo "  REMOTE_BACKUP_KEY    - Path to SSH private key"
            echo "  BACKUP_DIR           - Local destination directory (default: /var/backups/openqda)"
            echo ""
            echo "Examples:"
            echo "  $0 --list                              # List available backups"
            echo "  $0 --latest                            # Pull the latest backup"
            echo "  $0 --name openqda_backup_20260108_000000.tar.gz"
            echo "  $0 --all                               # Pull all backups"
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
    local timestamp
    timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp] $message" | tee -a "$LOG_FILE"
}

# Function to handle errors
error_exit() {
    local message="$1"
    log_message "ERROR: $message"
    exit 1
}

# Validate remote backup configuration
if [ -z "$REMOTE_BACKUP_HOST" ] || [ -z "$REMOTE_BACKUP_USER" ] || [ -z "$REMOTE_BACKUP_PATH" ]; then
    error_exit "Remote backup configuration incomplete. Required: REMOTE_BACKUP_HOST, REMOTE_BACKUP_USER, REMOTE_BACKUP_PATH"
fi

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR" || error_exit "Failed to create backup directory: $BACKUP_DIR"

# Build SSH/SCP command base
SSH_CMD="ssh -p $REMOTE_BACKUP_PORT"
SCP_CMD="scp -P $REMOTE_BACKUP_PORT"

# Add SSH key if specified
if [ -n "$REMOTE_BACKUP_KEY" ]; then
    SSH_CMD="$SSH_CMD -i $REMOTE_BACKUP_KEY"
    SCP_CMD="$SCP_CMD -i $REMOTE_BACKUP_KEY"
fi

# Add compression for SCP
SCP_CMD="$SCP_CMD -C"

# Function to list remote backups
list_remote_backups() {
    log_message "Listing backups on remote server: $REMOTE_BACKUP_USER@$REMOTE_BACKUP_HOST:$REMOTE_BACKUP_PATH"
    
    # List backup files on remote server
    if $SSH_CMD "$REMOTE_BACKUP_USER@$REMOTE_BACKUP_HOST" "ls -lh '$REMOTE_BACKUP_PATH'/openqda_backup_*.tar.gz 2>/dev/null" 2>> "$LOG_FILE"; then
        echo ""
        log_message "Available backups listed above"
    else
        log_message "WARNING: No backups found or unable to access remote directory"
        return 1
    fi
}

# Function to get latest backup name
get_latest_backup() {
    local latest=""
    latest=$($SSH_CMD "$REMOTE_BACKUP_USER@$REMOTE_BACKUP_HOST" "ls -t '$REMOTE_BACKUP_PATH'/openqda_backup_*.tar.gz 2>/dev/null | head -n 1" 2>> "$LOG_FILE")
    
    if [ -z "$latest" ]; then
        error_exit "No backups found on remote server"
    fi
    
    # Extract just the filename
    basename "$latest"
}

# Function to pull a specific backup
# Returns: 0 = success (downloaded), 2 = skipped (already exists), 1 = failed
pull_backup() {
    local backup_file="$1"
    local remote_file="$REMOTE_BACKUP_PATH/$backup_file"
    local local_file="$BACKUP_DIR/$backup_file"
    
    log_message "Pulling backup: $backup_file"
    log_message "From: $REMOTE_BACKUP_USER@$REMOTE_BACKUP_HOST:$remote_file"
    log_message "To: $local_file"
    
    # Check if file already exists locally
    if [ -f "$local_file" ]; then
        log_message "WARNING: Backup already exists locally: $local_file"
        log_message "Skipping download (file already present)"
        return 2
    fi
    
    # Pull the backup
    if $SCP_CMD "$REMOTE_BACKUP_USER@$REMOTE_BACKUP_HOST:'$remote_file'" "$local_file" 2>> "$LOG_FILE"; then
        # Calculate backup size
        BACKUP_SIZE=$(du -h "$local_file" | cut -f1)
        log_message "Successfully pulled backup: $backup_file ($BACKUP_SIZE)"
        return 0
    else
        log_message "ERROR: Failed to pull backup: $backup_file"
        return 1
    fi
}

# Function to pull all backups
pull_all_backups() {
    log_message "Pulling all backups from remote server..."
    
    # Get list of all backup files
    local backup_list=""
    backup_list=$($SSH_CMD "$REMOTE_BACKUP_USER@$REMOTE_BACKUP_HOST" "ls '$REMOTE_BACKUP_PATH'/openqda_backup_*.tar.gz 2>/dev/null" 2>> "$LOG_FILE")
    
    if [ -z "$backup_list" ]; then
        error_exit "No backups found on remote server"
    fi
    
    local success_count=0
    local skip_count=0
    local fail_count=0
    
    while IFS= read -r remote_file; do
        local backup_file
        backup_file=$(basename "$remote_file")
        
        pull_backup "$backup_file"
        local result=$?
        
        if [ $result -eq 0 ]; then
            success_count=$((success_count + 1))
        elif [ $result -eq 2 ]; then
            skip_count=$((skip_count + 1))
        else
            fail_count=$((fail_count + 1))
        fi
    done <<< "$backup_list"
    
    log_message "========================================"
    log_message "Pull all backups completed"
    log_message "Successfully pulled: $success_count"
    log_message "Skipped (already present): $skip_count"
    log_message "Failed: $fail_count"
    log_message "========================================"
}

# Main execution logic
log_message "========================================"
log_message "OpenQDA Backup Pull Script Started"
log_message "========================================"

if [ "$LIST_MODE" = true ]; then
    # List mode
    list_remote_backups
    exit 0
elif [ "$PULL_ALL" = true ]; then
    # Pull all backups
    pull_all_backups
    exit 0
elif [ "$PULL_LATEST" = true ]; then
    # Pull latest backup
    log_message "Fetching latest backup from remote server..."
    BACKUP_NAME=$(get_latest_backup)
    log_message "Latest backup: $BACKUP_NAME"
    pull_backup "$BACKUP_NAME"
    result=$?
    if [ $result -eq 0 ]; then
        log_message "========================================"
        log_message "Latest backup pulled successfully"
        log_message "Location: $BACKUP_DIR/$BACKUP_NAME"
        log_message "========================================"
        exit 0
    elif [ $result -eq 2 ]; then
        log_message "========================================"
        log_message "Latest backup already exists locally"
        log_message "Location: $BACKUP_DIR/$BACKUP_NAME"
        log_message "========================================"
        exit 0
    else
        error_exit "Failed to pull latest backup"
    fi
elif [ -n "$BACKUP_NAME" ]; then
    # Pull specific backup
    pull_backup "$BACKUP_NAME"
    result=$?
    if [ $result -eq 0 ]; then
        log_message "========================================"
        log_message "Backup pulled successfully"
        log_message "Location: $BACKUP_DIR/$BACKUP_NAME"
        log_message "========================================"
        exit 0
    elif [ $result -eq 2 ]; then
        log_message "========================================"
        log_message "Backup already exists locally"
        log_message "Location: $BACKUP_DIR/$BACKUP_NAME"
        log_message "========================================"
        exit 0
    else
        error_exit "Failed to pull backup: $BACKUP_NAME"
    fi
else
    # No operation specified
    echo "Error: No operation specified"
    echo "Use --list, --latest, --all, or --name to specify what to pull"
    echo "Use --help for usage information"
    exit 1
fi
