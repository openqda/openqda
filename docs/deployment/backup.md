# Backup and Recovery

OpenQDA includes a comprehensive backup and restore solution that allows you to create scheduled backups of all critical application data and restore them when needed. This page covers the setup and usage of the automated backup and restore system.

## Overview

The backup solution includes two main scripts:

- **`backup.sh`**: Creates complete backups with optional remote transfer via SCP
- **`restore.sh`**: Restores backups with flexible options for selective restoration

The backup script creates a complete snapshot of your OpenQDA installation, including:

- **Database**: Complete MySQL database dump (compressed)
- **Storage**: All uploaded files and application data
- **.env File**: Environment configuration
- **Metadata**: Backup information for easy restoration
- **Remote Transfer**: Optional automatic transfer to remote backup server via SCP

The restore script provides:

- **Selective Restoration**: Choose to restore database only, storage only, or both
- **Non-Overwrite Storage**: Existing files are preserved during restoration
- **Safe .env Handling**: Existing configuration files are backed up before restoration

## Quick Start

1. Navigate to the scripts directory:
   ```bash
   cd /path/to/openqda/web/scripts
   ```

2. Copy the configuration template:
   ```bash
   cp backup.config.example backup.config
   ```

3. Edit the configuration with your database credentials:
   ```bash
   nano backup.config
   ```

4. Test the backup script:
   ```bash
   ./backup.sh
   ```

## Configuration

The backup script can be configured in three ways (in order of precedence):

1. **Command-line arguments**: Override specific settings
2. **Configuration file**: `backup.config` in the scripts directory
3. **Environment variables**: Set before running the script

### Configuration Options

#### Basic Configuration

| Option | Default | Description |
|--------|---------|-------------|
| `BACKUP_DIR` | `/var/backups/openqda` | Directory where backups are stored |
| `DB_HOST` | `localhost` | MySQL server hostname |
| `DB_PORT` | `3306` | MySQL server port |
| `DB_NAME` | `web` | Database name |
| `DB_USER` | `root` | MySQL username |
| `DB_PASSWORD` | (empty) | MySQL password |
| `STORAGE_PATH` | `../storage` | Path to storage directory |
| `ENV_FILE` | `../.env` | Path to .env file |
| `BACKUP_RETENTION_DAYS` | `30` | Days to keep old backups |

#### Remote Backup Configuration (Optional)

| Option | Default | Description |
|--------|---------|-------------|
| `REMOTE_BACKUP_ENABLED` | `false` | Enable/disable remote backup via SCP |
| `REMOTE_BACKUP_HOST` | (empty) | Remote server hostname or IP |
| `REMOTE_BACKUP_USER` | (empty) | SSH username for remote server |
| `REMOTE_BACKUP_PATH` | (empty) | Destination path on remote server |
| `REMOTE_BACKUP_PORT` | `22` | SSH port for remote server |
| `REMOTE_BACKUP_KEY` | (empty) | Path to SSH private key (optional) |
| `DB_NAME` | `web` | Database name |
| `DB_USER` | `root` | MySQL username |
| `DB_PASSWORD` | (empty) | MySQL password |
| `STORAGE_PATH` | `../storage` | Path to storage directory |
| `ENV_FILE` | `../.env` | Path to .env file |
| `BACKUP_RETENTION_DAYS` | `30` | Days to keep old backups |

## Automated Backups with Cron

To schedule automated backups, use cron. Here are common scheduling patterns:

### Daily at Midnight

```bash
crontab -e
```

Add this line:
```bash
0 0 * * * /path/to/openqda/web/scripts/backup.sh >> /var/log/openqda-backup.log 2>&1
```

### Other Schedules

**Every 6 hours:**
```bash
0 */6 * * * /path/to/openqda/web/scripts/backup.sh
```

**Weekly on Sunday at 2 AM:**
```bash
0 2 * * 0 /path/to/openqda/web/scripts/backup.sh
```

**Monthly on the 1st at 3 AM:**
```bash
0 3 1 * * /path/to/openqda/web/scripts/backup.sh
```

## Remote Backups via SCP

The backup script can automatically transfer backups to a remote server via SCP after local backup completes.

### Setup Remote Backups

1. **Set up SSH key authentication** (recommended):
   ```bash
   ssh-keygen -t rsa -b 4096
   ssh-copy-id backup-user@remote-server
   ```

2. **Configure in backup.config**:
   ```bash
   REMOTE_BACKUP_ENABLED=true
   REMOTE_BACKUP_HOST="backup.example.com"
   REMOTE_BACKUP_USER="backup-user"
   REMOTE_BACKUP_PATH="/backups/openqda"
   REMOTE_BACKUP_PORT="22"
   ```

3. **Test the connection**:
   ```bash
   ssh backup-user@backup.example.com "ls -la /backups/openqda"
   ```

4. **Run backup** - Transfer happens automatically after local backup

**Important**: Remote backup failure does not affect local backup. The local backup remains available even if remote transfer fails.

## Backup Structure

Each backup creates a timestamped archive:

```
openqda_backup_YYYYMMDD_HHMMSS.tar.gz
```

The archive contains:
- `database.sql.gz`: Compressed database dump
- `storage.tar.gz`: Compressed storage directory
- `.env`: Environment configuration
- `backup_info.txt`: Backup metadata

## Restoring from Backup

### Using the Restore Script (Recommended)

The `restore.sh` script provides flexible restoration options:

#### Full Restore (Database and Storage)

```bash
cd /path/to/openqda/web/scripts
./restore.sh /var/backups/openqda/openqda_backup_20260108_000000.tar.gz
```

#### Restore Database Only

```bash
./restore.sh --db-only --db-password=secret backup.tar.gz
```

#### Restore Storage Only

```bash
./restore.sh --storage-only backup.tar.gz
```

#### Custom Configuration

```bash
./restore.sh \
  --db-host db.example.com \
  --db-name production_db \
  --db-user admin \
  --db-password secret \
  --storage-path /var/www/openqda/storage \
  backup.tar.gz
```

#### Restore Script Options

| Option | Description |
|--------|-------------|
| `--db-only` | Restore only the database |
| `--storage-only` | Restore only storage files |
| `--skip-db` | Skip database restoration |
| `--skip-storage` | Skip storage restoration |
| `--db-host HOST` | Database host (default: localhost) |
| `--db-name NAME` | Database name (default: web) |
| `--db-user USER` | Database user (default: root) |
| `--db-password PASS` | Database password |
| `--storage-path PATH` | Path to restore storage |
| `--force-env` | Overwrite existing .env file |

#### Restore Features

- **Selective restoration**: Choose what to restore (database, storage, or both)
- **Non-overwrite storage**: Existing files in storage are preserved automatically
- **Safe .env handling**: Existing .env files are backed up with timestamp before restoration

### Manual Restore (Alternative)

If you prefer manual restoration:

### Step 1: Extract the Backup

```bash
tar -xzf openqda_backup_YYYYMMDD_HHMMSS.tar.gz
cd openqda_backup_YYYYMMDD_HHMMSS
```

### Step 2: Restore the Database

```bash
gunzip database.sql.gz
mysql -u root -p web < database.sql
```

### Step 3: Restore Storage

```bash
tar -xzf storage.tar.gz -C /path/to/openqda/web/
```

### Step 4: Restore Configuration

```bash
cp .env /path/to/openqda/web/.env
```

**Important:** Review the `.env` file and adjust settings as needed (URLs, paths, etc.)

### Step 5: Set Permissions

```bash
cd /path/to/openqda/web
chown -R www-data:www-data storage
chmod -R 775 storage
```

## Security Best Practices

### Protect Backup Files

Restrict access to the backup directory:
```bash
chmod 700 /var/backups/openqda
```

### Secure Configuration

Protect the configuration file containing credentials:
```bash
chmod 600 /path/to/openqda/web/scripts/backup.config
```

### Encrypt Backups

For sensitive data, encrypt backups after creation:
```bash
gpg --symmetric --cipher-algo AES256 openqda_backup_*.tar.gz
```

### Off-Site Backups

Regularly copy backups to a remote location:
```bash
rsync -avz /var/backups/openqda/ user@remote:/backups/openqda/
```

## Monitoring and Maintenance

### Check Backup Logs

```bash
tail -f /var/backups/openqda/backup.log
```

### Verify Backup Integrity

```bash
tar -tzf openqda_backup_YYYYMMDD_HHMMSS.tar.gz
```

### Manual Cleanup

Remove old backups manually:
```bash
find /var/backups/openqda -name "openqda_backup_*.tar.gz" -mtime +60 -delete
```

### Test Restores

Regularly test your backups by performing a restore to a test environment to ensure they work correctly.

## Troubleshooting

### Permission Errors

Ensure the user running the script has:
- Read access to storage directory and .env file
- Write access to backup directory
- MySQL credentials to dump the database

### Database Connection Errors

Test database connection:
```bash
mysql -h localhost -u root -p -e "SHOW DATABASES;"
```

### Storage Space Issues

Monitor available disk space:
```bash
df -h /var/backups/openqda
```

## Additional Resources

For more detailed information, see:
- [Manual Installation Guide](../installation/manual.md)
- [Deployment Guide](./deployment.md)
