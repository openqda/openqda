# Install OpenQDA manually

This guide is intended for users/developers, who want to install OpenQDA locally without using Docker.

## Set up Laravel

OpenQDA is built with Laravel. Therefore, you need to install Laravel first.

Please visit https://laravel.com/ and follow their installation guide.

## Setup MySQL

The default Laravel installation comes with SQLite, however we intend to use MySQl. You will therefore have to install
the MySQL database as well.

Please visit https://www.mysql.com/ and follow their installation guide.

## Install Dependencies

Once you have sucessfully installed Laravel and MySql you should execute the following commands in the `web` directory:

- `php artisan key:generate` - Generates a
  unique `APP_KEY`. [Learn more](https://medium.com/@kirinyetbrian/unlocking-the-importance-of-laravels-app-key-protecting-your-application-s-security-and-1302b98e4e72)
  about it.
- `composer install` - Installs PHP dependencies.
- `npm install` - Installs Node.js packages.
- `npm run dev` - Runs Vite locally for development.
- `npm run build` - Compiles assets for production.

## Database and Application Setup

1. Run `php artisan migrate` to create database tables.
2. Customize and run the seeder ( `database/seeders`) to populate the database with initial data
   via `php artisan db:seed`.
3. create a HMAC
   key, [there are several ways to do it](https://unix.stackexchange.com/questions/610039/how-to-do-hmacsha256-using-openssl-from-terminal).
   This is used by ALTCHA.
4. set the `ALTCHA_HMAC_KEY`.

`RTFENDPOINT` and `RTFPASSWORD` are necessary only if you set your `.env` file to "Production", otherwise it will use
the internal python script called `convert_rtf_to_html_locally.py`.

Now you should be able to run OpenQDA, but you still need further steps to enable the websocket server and enable the
collaboration tools.

## Real-Time Configuration

1. Configure [Laravel Reverb](https://reverb.laravel.com/) for real-time capabilities by setting the following in
   your `.env`:

```
REVERB_APP_ID=my-app-id
REVERB_APP_KEY=my-app-key
REVERB_APP_SECRET=my-app-secret
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

2. Start the WebSocket server with `php artisan reverb:start`.
3. Use `php artisan queue:work --queue=conversion,default`. OpenQDA
   uses [Laravel Queues](https://laravel.com/docs/11.x/queues) and [Laravel Echo](https://github.com/laravel/echo) to
   process the presence of users and convert files on production. For the online presence we use the "default" queue,
   for converting rtf files we use the "conversion" queue.

## SSL Configuration

For local development, you can use self-signed certificates, but on a production system it is crucial to use valid SSL
certificates.

### Requirements

You need three certificates:

- The server certificate (often named `cert.pem` or `server.cer`)
- The private key (often named `privkey.pem` or `certificate.key.pem`)
- The CAfile (often named `fullchain.pem` or `fullchain.cer`)
- The private key password (if your private key is password-protected)

You can get these by your Domain provider or by using [Let's Encrypt](https://letsencrypt.org/). Please note, that this
guide won't cover the generation of SSL certificates or the usage of Let's Encrypt but a manual installation.

### Apache

If you are using Apache, then you need to configure your `VirtualHost` file, which is usually located
under `/etc/apache2/sites-available/your-site.conf` (on Ubuntu/Debian systems).

There, you need to edit the `<VirtualHost *:443>` section to include the following lines:

```
<VirtualHost *:443>
    # ... other config
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /path/to/server.crt.pem
    SSLCertificateKeyFile /path/to/privkey.key.pem
    SSLCertificateChainFile /path/to/fullchain.ca.pem
</VirtualHost>    
```

> These paths should be absolute paths to your certificate files.

If you haven't enabled ssl in Apache yet, you can do so by running:

```bash
sudo a2enmod ssl
```

If your private key is password-protected, you need to add the following line to your Apache configuration:

```bash
sudo systemd-tty-ask-password-agent
```

Finally, restart Apache.

```bash
sudo systemctl restart apache2
```

Test your website under https to ensure the SSL configuration is working correctly.

### Websockets

The websocket server also needs to be configured to use SSL. This is, because the WebSocket server runs separately from
your web server (Apache/Nginx)
and could theoretically server more than one application.

Edit your `.env` production file with the absolute path of your certificate, private key and CAfile:

```
LARAVEL_WEBSOCKETS_SSL_LOCAL_CERT=/path/to/server.crt.pem
LARAVEL_WEBSOCKETS_SSL_LOCAL_PK=/path/to/privkey.key.pem
LARAVEL_WEBSOCKETS_SSL_CAFILE=/path/to/fullchain.ca.pem
```

**Important:** The WebSocket server requires the **fullchain certificate** (server + intermediate certificates), not
just the server certificate. If you encounter SSL issues where your web server works but WebSockets fail, see
the [SSL Certificate Troubleshooting Guide](../troubleshooting/ssl-certificates.md).

Once you have edited the `.env` file, clear the Laravel cache:

```bash
php artisan optimize:clear
```

Then restart the WebSocket server:

```bash
sudo supervisorctl restart websockets
```

and start/restart the reverb service:

```bash
php artisan reverb:start # or restart
```

## Mail logging

OpenQDA sends E-Mails on various occasions. For local email logging, consider using [MailTrap](https://mailtrap.io/) (
free with a usage limit)
or [Laravel Herd](https://herd.laravel.com/) (paid service).
