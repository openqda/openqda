# OpenQDA Deployment

This guide describes how to deploy OpenQDA to your custom infrastructure.
Currently, there are two options for deployment:

## Backup and Recovery

OpenQDA includes a comprehensive backup script that can be scheduled to run automatically. The backup script creates a complete snapshot of your application data including database, storage files, and configuration. For detailed information on setting up automated backups, see the [Backup Script Documentation](../../web/scripts/README.md).


## Via Docker

> Please note, that deployment via Docker is different from the Docker setup,
described in the [docker installation guide for local development!](../installation/docker.md)!

We will provide a comprehensive guide very soon. 
Until then, we would like to point you to the following resources:

- https://docs.docker.com/guides/frameworks/laravel/
- https://github.com/laradock/laradock

## Manual

Manual installation has great overlap with [the manual development installation](../installation/manual.md).
We will soon provide this guid to cover topics, specific to the production installation (webserver, certificates etc.).

### SSL Certificate Configuration

For SSL certificate setup with the WebSocket server, refer to:
- [SSL Certificate Troubleshooting Guide](../troubleshooting/ssl-certificates.md) - Essential reading if your WebSocket server has certificate issues
- [Manual Installation - Real-Time Configuration](../installation/manual.md#real-time-configuration) - Initial SSL setup instructions
