# SSL Certificate Issues with WebSocket Server

## WebSocket Server Won't Accept Certificate (But Web Server Does)

### The Problem

Your web server (Apache/Nginx) works fine with your SSL certificate, but the WebSocket server throws certificate errors and won't establish secure connections.

### Why This Happens

The WebSocket server uses GuzzleHttp, which relies on PHP cURL for SSL verification. Unlike typical web servers, cURL needs the **complete certificate chain** - not just your server certificate.

Most certificate providers only give you the server certificate (`server.cer`). This works for web servers but fails for GuzzleHttp's SSL verification. You also need the intermediate certificate to build the full chain.

### The Fix

**1. Create the fullchain certificate:**

```bash
cat server.cer intermediate.cer > fullchain.cer
```

Order matters: server certificate first, then intermediate.

**2. Update your `.env` file:**

```dotenv
LARAVEL_WEBSOCKETS_SSL_LOCAL_CERT="/absolute/path/to/fullchain.cer"
LARAVEL_WEBSOCKETS_SSL_LOCAL_PK="/absolute/path/to/certificate.key.pem"
LARAVEL_WEBSOCKETS_SSL_CAFILE="/absolute/path/to/fullchain.cer"
```

Use absolute paths, not relative ones.

**3. Clear Laravel's cache:**

```bash
php artisan optimize:clear
```

**4. Restart the WebSocket service:**

```bash
sudo supervisorctl restart websockets
```

Or if running manually:
```bash
php artisan reverb:start
```

### Verification

Check your error logs - they should be clean now. Test your real-time features to confirm WebSocket connections work.

## Additional Notes

- Let's Encrypt provides fullchain certificates automatically, so you won't hit this issue with them
- File permissions matter: 644 for certificates, 600 for private keys
- Always use absolute paths in `.env` for certificate files

## Related Documentation

- [Manual Installation - SSL Configuration](../installation/manual.md#real-time-configuration)
- [Deployment Guide](../deployment/deployment.md)
