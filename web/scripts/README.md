# OpenQDA Backup Script

This directory contains scripts for backing up OpenQDA application data.

## Overview

The `backup.sh` script creates a complete backup of your OpenQDA installation, including:

- **Database dump**: Complete MySQL database export (compressed with gzip)
- **Storage folder**: All application files in the `storage` directory (compressed tar archive)
- **.env file**: Environment configuration file
- **Backup metadata**: Information about the backup for easy restoration

## Installation

1. Copy the configuration template:
   ```bash
   cp backup.config.example backup.config
   ```

2. Edit `backup.config` with your specific settings:
   ```bash
   nano backup.config
   ```

3. Ensure the script is executable:
   ```bash
   chmod +x backup.sh
   ```

## Usage

### Basic Usage

Run the backup script manually:

```bash
./backup.sh
```

### With Custom Configuration File

```bash
./backup.sh --config /path/to/custom/config
```

### With Custom Backup Directory

```bash
./backup.sh --dir /custom/backup/location
```

### Using Environment Variables

```bash
BACKUP_DIR=/tmp/backups DB_PASSWORD=secret ./backup.sh
```

## Configuration

Configuration can be provided in three ways (in order of precedence):

1. **Command-line arguments**: `--config`, `--dir`
2. **Configuration file**: `backup.config` in the same directory as the script
3. **Environment variables**: Set before running the script

### Configuration Options

| Option | Environment Variable | Default | Description |
|--------|---------------------|---------|-------------|
| Backup Directory | `BACKUP_DIR` | `/var/backups/openqda` | Directory where backups are stored |
| Database Host | `DB_HOST` | `localhost` | MySQL server hostname |
| Database Port | `DB_PORT` | `3306` | MySQL server port |
| Database Name | `DB_NAME` | `web` | Name of the database to backup |
| Database User | `DB_USER` | `root` | MySQL username |
| Database Password | `DB_PASSWORD` | (empty) | MySQL password (optional) |
| Storage Path | `STORAGE_PATH` | `../storage` | Path to the storage directory |
| Environment File | `ENV_FILE` | `../.env` | Path to the .env file |
| Retention Days | `BACKUP_RETENTION_DAYS` | `30` | Days to keep old backups (0 = keep all) |
| Log File | `LOG_FILE` | `$BACKUP_DIR/backup.log` | Path to the log file |

## Setting Up Automated Backups with Cron

### Daily Backup at Midnight

1. Edit your crontab:
   ```bash
   crontab -e
   ```

2. Add the following line for daily backup at midnight:
   ```cron
   0 0 * * * /path/to/openqda/web/scripts/backup.sh >> /var/log/openqda-backup.log 2>&1
   ```

### Alternative Schedules

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

### Using a Configuration File with Cron

```cron
0 0 * * * /path/to/openqda/web/scripts/backup.sh --config /etc/openqda/backup.config
```

### Setting Environment Variables in Cron

You can also set environment variables in your crontab:

```cron
BACKUP_DIR=/mnt/backups
DB_PASSWORD=mypassword
0 0 * * * /path/to/openqda/web/scripts/backup.sh
```

## Backup Output

The script creates a timestamped backup archive:

```
openqda_backup_YYYYMMDD_HHMMSS.tar.gz
```

Example: `openqda_backup_20260106_000000.tar.gz`

### Contents of the Archive

```
openqda_backup_YYYYMMDD_HHMMSS/
├── database.sql.gz         # Compressed database dump
├── storage.tar.gz          # Compressed storage directory
├── .env                    # Environment configuration
└── backup_info.txt         # Backup metadata
```

## Restoring from Backup

1. **Extract the backup archive:**
   ```bash
   tar -xzf openqda_backup_YYYYMMDD_HHMMSS.tar.gz
   cd openqda_backup_YYYYMMDD_HHMMSS
   ```

2. **Restore the database:**
   ```bash
   gunzip database.sql.gz
   mysql -u root -p web < database.sql
   ```

3. **Restore the storage directory:**
   ```bash
   tar -xzf storage.tar.gz -C /path/to/openqda/web/
   ```

4. **Restore the .env file:**
   ```bash
   cp .env /path/to/openqda/web/.env
   ```
   
   **Important:** Review the .env file and adjust any settings that may have changed (URLs, paths, etc.)

5. **Set proper permissions:**
   ```bash
   cd /path/to/openqda/web
   chown -R www-data:www-data storage
   chmod -R 775 storage
   ```

## Security Considerations

1. **Protect your backups**: Ensure backup directory has restricted permissions:
   ```bash
   chmod 700 /var/backups/openqda
   ```

2. **Secure the configuration**: If storing passwords in `backup.config`, restrict access:
   ```bash
   chmod 600 backup.config
   ```

3. **Encrypt backups**: For sensitive data, consider encrypting backups:
   ```bash
   # After backup completes
   gpg --symmetric --cipher-algo AES256 openqda_backup_*.tar.gz
   ```

4. **Off-site backups**: Regularly copy backups to a remote location:
   ```bash
   rsync -avz /var/backups/openqda/ user@remote-server:/backups/openqda/
   ```

## Troubleshooting

### Permission Errors

Ensure the user running the script has:
- Read access to the storage directory and .env file
- Write access to the backup directory
- MySQL credentials to dump the database

### Database Connection Errors

Verify database credentials and that MySQL is accessible:
```bash
mysql -h localhost -u root -p -e "SHOW DATABASES;"
```

### Storage Space

Monitor available disk space in the backup directory:
```bash
df -h /var/backups/openqda
```

### Check Logs

Review the backup log for errors:
```bash
tail -f /var/backups/openqda/backup.log
```

## Maintenance

### Manual Cleanup

Remove backups older than 60 days:
```bash
find /var/backups/openqda -name "openqda_backup_*.tar.gz" -type f -mtime +60 -delete
```

### Check Backup Integrity

Verify a backup archive:
```bash
tar -tzf openqda_backup_YYYYMMDD_HHMMSS.tar.gz
```

### Test Restore

Regularly test your backups by performing a restore to a test environment to ensure they work correctly.

## Support

For issues or questions, please refer to:
- [OpenQDA Documentation](https://openqda.github.io/openqda/)
- [GitHub Issues](https://github.com/openqda/openqda/issues)
- [Email Support](mailto:openqda@uni-bremen.de)
