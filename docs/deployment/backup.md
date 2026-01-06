# Backup and Recovery

OpenQDA includes a comprehensive backup solution that allows you to create scheduled backups of all critical application data. This page covers the setup and usage of the automated backup system.

## Overview

The backup script (`web/scripts/backup.sh`) creates a complete snapshot of your OpenQDA installation, including:

- **Database**: Complete MySQL database dump (compressed)
- **Storage**: All uploaded files and application data
- **.env File**: Environment configuration
- **Metadata**: Backup information for easy restoration

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

## Automated Backups with Cron

To schedule automated backups, use cron. Here are common scheduling patterns:

### Daily at Midnight

```bash
crontab -e
```

Add this line:
```cron
0 0 * * * /path/to/openqda/web/scripts/backup.sh >> /var/log/openqda-backup.log 2>&1
```

### Other Schedules

**Every 6 hours:**
```cron
0 */6 * * * /path/to/openqda/web/scripts/backup.sh
```

**Weekly on Sunday at 2 AM:**
```cron
0 2 * * 0 /path/to/openqda/web/scripts/backup.sh
```

**Monthly on the 1st at 3 AM:**
```cron
0 3 1 * * /path/to/openqda/web/scripts/backup.sh
```

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
- [Backup Script README](../../web/scripts/README.md)
- [Manual Installation Guide](../installation/manual.md)
- [Deployment Guide](./deployment.md)
