# OpenQDA Backup and Restore Scripts

This directory contains scripts for backing up and restoring OpenQDA application data.

## Overview

The backup solution includes three main scripts:

- **`backup.sh`**: Creates complete backups with optional remote transfer via SCP
- **`restore.sh`**: Restores backups with flexible options for selective restoration
- **`pull.sh`**: Pulls (downloads) backups from a remote server via SCP

### Backup Script

Creates a complete backup of your OpenQDA installation, including:

- **Database dump**: Complete MySQL database export (compressed with gzip)
- **Storage folder**: All application files in the `storage` directory (compressed tar archive)
- **.env file**: Environment configuration file
- **Backup metadata**: Information about the backup for easy restoration
- **Remote transfer**: Optional automatic transfer to remote backup server via SCP

### Restore Script

Restores data from backups with the following features:

- **Selective restoration**: Choose to restore database only, storage only, or both
- **Non-overwrite storage**: Existing files in storage are preserved during restoration
- **Safe .env handling**: Existing .env files are backed up before restoration

### Pull Script

**Run on the backup server** to pull backups from the application server with the following features:

- **Security-focused**: Application server doesn't need credentials to backup server
- **List remote backups**: View available backups on the application server
- **Pull latest backup**: Download the most recent backup
- **Pull specific backup**: Download a backup by name
- **Pull all backups**: Download all backups from the application server
- **Skip existing**: Automatically skips backups that already exist locally

This approach is more secure than pushing backups, as a compromised application server cannot compromise the backup server.

## Installation

1. Copy the configuration templates:
   ```bash
   cp backup.config.example backup.config
   cp pull.config.example pull.config  # If using pull-based backups
   ```

2. Edit `backup.config` with your specific settings:
   ```bash
   nano backup.config
   ```

3. If using pull-based backups, edit `pull.config` on your backup server:
   ```bash
   nano pull.config
   ```

4. Ensure the scripts are executable:
   ```bash
   chmod +x backup.sh restore.sh pull.sh
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

#### Backup Configuration

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

#### Remote Backup Configuration (Optional)

| Option | Environment Variable | Default | Description |
|--------|---------------------|---------|-------------|
| Remote Backup Enabled | `REMOTE_BACKUP_ENABLED` | `false` | Enable/disable remote backup via SCP |
| Remote Host | `REMOTE_BACKUP_HOST` | (empty) | Remote server hostname or IP |
| Remote User | `REMOTE_BACKUP_USER` | (empty) | SSH username for remote server |
| Remote Path | `REMOTE_BACKUP_PATH` | (empty) | Destination path on remote server |
| Remote Port | `REMOTE_BACKUP_PORT` | `22` | SSH port for remote server |
| SSH Key | `REMOTE_BACKUP_KEY` | (empty) | Path to SSH private key (optional) |

#### Backup File Group Permission (Optional)

| Option | Environment Variable | Default | Description |
|--------|---------------------|---------|-------------|
| Group Permission | `BACKUP_FILE_GROUP_PERMISSION` | (empty) | Group name to set on backup files for pull-based backups |

This setting is useful when using pull-based backups where a backup server connects to the application server to fetch backups. When configured, the backup script will set the group ownership of the created backup file to the specified group and ensure it's group-readable. The backup user on the backup server should be a member of this group.

**Example configuration:**
```bash
BACKUP_FILE_GROUP_PERMISSION="backup-group"
```

**Setup steps:**
1. Create a group on the application server: `sudo groupadd backup-group`
2. Add the backup script user to the group: `sudo usermod -a -G backup-group www-data`
3. Add the backup server's SSH user to the group: `sudo usermod -a -G backup-group backup-reader`
4. Configure the option in `backup.config`

### Setting Up Remote Backups

To enable automatic remote backup transfer via SCP:

1. **Set up SSH key authentication** (recommended):
   ```bash
   ssh-keygen -t rsa -b 4096
   ssh-copy-id user@remote-server
   ```

2. **Configure in backup.config**:
   ```bash
   REMOTE_BACKUP_ENABLED=true
   REMOTE_BACKUP_HOST="backup.example.com"
   REMOTE_BACKUP_USER="backup-user"
   REMOTE_BACKUP_PATH="/backups/openqda"
   REMOTE_BACKUP_PORT="22"
   # Optional: REMOTE_BACKUP_KEY="/home/user/.ssh/id_rsa"
   ```

3. **Test the connection**:
   ```bash
   ssh -p 22 backup-user@backup.example.com "ls -la /backups/openqda"
   ```

4. **Run backup** - The backup will be transferred automatically after local backup completes

**Note**: Remote backup failure does not affect local backup. The local backup will remain available even if remote transfer fails.

## Setting Up Automated Backups with Cron

### Daily Backup at Midnight

1. Edit your crontab:
   ```bash
   crontab -e
   ```

2. Add the following line for daily backup at midnight:
   ```bash
   0 0 * * * /path/to/openqda/web/scripts/backup.sh >> /var/log/openqda-backup.log 2>&1
   ```

### Alternative Schedules

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

### Using a Configuration File with Cron

```bash
0 0 * * * /path/to/openqda/web/scripts/backup.sh --config /etc/openqda/backup.config
```

### Setting Environment Variables in Cron

You can also set environment variables in your crontab:

```bash
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

## Pulling Backups from Application Server

### Using the Pull Script

**Important**: The `pull.sh` script is designed to run **ON THE BACKUP SERVER** to pull backups **FROM THE APPLICATION SERVER**. This provides better security than pushing backups, as the application server does not need write credentials to the backup server.

#### Security Benefits

- **No backup server credentials on application server**: If the application server is compromised, the backup server remains secure
- **Read-only access**: Application server only needs to allow SSH read access to backup files
- **Backup server controls access**: Backup server actively pulls backups on its own schedule

#### Setup

1. **On the application server**: Ensure backups are created locally by `backup.sh` (disable remote push if needed)
2. **On the backup server**: Install `pull.sh` and configure access to the application server

#### List Available Backups

View all backups available on the application server:

```bash
./pull.sh --list
```

#### Pull the Latest Backup

Download the most recent backup:

```bash
./pull.sh --latest
```

#### Pull a Specific Backup

Download a backup by name:

```bash
./pull.sh --name openqda_backup_20260108_000000.tar.gz
```

#### Pull All Backups

Download all available backups from the application server:

```bash
./pull.sh --all
```

#### Custom Configuration

Use a custom configuration file or local destination directory:

```bash
./pull.sh --config /path/to/pull.config --dir /custom/backup/location --latest
```

#### Pull Script Options

| Option | Description |
|--------|-------------|
| `-c, --config FILE` | Path to configuration file |
| `-d, --dir DIR` | Local destination directory for pulled backups |
| `-l, --list` | List available backups on application server |
| `-n, --name NAME` | Specific backup name to pull |
| `--latest` | Pull the latest backup |
| `--all` | Pull all backups from application server |
| `-h, --help` | Show help message |

#### Pull Script Features

- **Security-focused**: Application server doesn't need backup server credentials
- **Smart duplicate detection**: Automatically skips backups that already exist locally
- **Compressed transfer**: Uses SCP compression for faster transfers
- **SSH key support**: Can use SSH keys for authentication
- **Detailed logging**: All operations are logged to `pull.log`
- **Error handling**: Continues with remaining backups if one fails

#### Required Configuration for Pull Script

The pull script requires configuration for accessing the application server:

| Option | Environment Variable | Description |
|--------|---------------------|-------------|
| Application Server Host | `APP_SERVER_HOST` | Application server hostname or IP |
| Application Server User | `APP_SERVER_USER` | SSH username for application server |
| Application Server Path | `APP_SERVER_BACKUP_PATH` | Path to backups on application server |
| Application Server Port | `APP_SERVER_PORT` | SSH port (default: 22) |
| SSH Key | `APP_SERVER_KEY` | Path to SSH private key (optional) |

**Example workflow:**

1. **On application server**: Run backups regularly with `backup.sh` (with remote push disabled)
2. **On backup server**: Configure `pull.config` with application server details
3. **On backup server**: List available backups: `./pull.sh --list`
4. **On backup server**: Pull the latest backup: `./pull.sh --latest`
5. **Optional**: Restore if needed: `./restore.sh /var/backups/openqda/openqda_backup_*.tar.gz`

## Restoring from Backup

### Using the Restore Script

The `restore.sh` script provides flexible restoration options:

#### Basic Restore (Database and Storage)

```bash
./restore.sh openqda_backup_20260108_000000.tar.gz
```

#### Restore Database Only

```bash
./restore.sh --db-only --db-password=secret openqda_backup_20260108_000000.tar.gz
```

#### Restore Storage Only

```bash
./restore.sh --storage-only openqda_backup_20260108_000000.tar.gz
```

#### Skip Database or Storage

```bash
# Skip database restoration
./restore.sh --skip-db openqda_backup_20260108_000000.tar.gz

# Skip storage restoration
./restore.sh --skip-storage openqda_backup_20260108_000000.tar.gz
```

#### Custom Paths and Settings

```bash
./restore.sh \
  --db-host db.example.com \
  --db-name production_db \
  --db-user admin \
  --db-password secret \
  --storage-path /var/www/openqda/storage \
  --env-path /var/www/openqda/.env \
  backup.tar.gz
```

#### Force .env Overwrite

```bash
./restore.sh --force-env openqda_backup_20260108_000000.tar.gz
```

### Restore Script Features

- **Docker and Host Support**: Automatically detects Docker environment and connects to the correct MySQL instance
  - When running inside a Docker container (e.g., `laravel.test`), defaults to `mysql` container
  - When running on host system, defaults to `localhost`
  - Can be overridden with `--db-host` option
- **Selective restoration**: Choose what to restore (database, storage, or both)
- **Non-overwrite storage**: Existing files in storage are preserved automatically
- **Safe .env handling**: Existing .env files are backed up with timestamp before restoration
- **Post-restore checklist**: Displays important steps after restoration completes

### Restoring in Docker Environment

When using Docker Compose for development, you can restore backups from within the Laravel container:

```bash
# Enter the Laravel container
docker exec -it laravel.test bash

# Navigate to scripts directory
cd /var/www/html/scripts

# Run restore (automatically detects mysql container)
./restore.sh openqda_backup_20260108_000000.tar.gz

# Or explicitly specify the mysql container
./restore.sh --db-host mysql openqda_backup_20260108_000000.tar.gz
```

The script automatically detects that it's running inside Docker and uses the `mysql` container name as the database host.

### Manual Restore (Alternative)

If you prefer manual restoration:

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
