# Install OpenQDA manually

This guide is intended for users/developers, who want to install OpenQDA locally without using Docker.


## Set up Laravel

OpenQDA is built with Laravel.
Therefore, you need to install Laravel first.

Please visit https://laravel.com/ and follow their installation guide.

## Setup MySQL

The default Laravel installation comes with SQLite, however
we intend to use MySQl. You will therefore have to install
the MySQL database as well.

Please visit https://www.mysql.com/ and follow their installation guide.

## Install Dependencies

Once you have sucessfully installed Laravel and MySql you should
execute the following commands in the `web` directory:

- `php artisan key:generate` - Generates a unique `APP_KEY`. [Learn more](https://medium.com/@kirinyetbrian/unlocking-the-importance-of-laravels-app-key-protecting-your-application-s-security-and-1302b98e4e72) about it.
- `composer install` - Installs PHP dependencies.
- `npm install` - Installs Node.js packages.
- `npm run dev` - Runs Vite locally for development.
- `npm run build` - Compiles assets for production.

## Database and Application Setup
1. Run `php artisan migrate` to create database tables.
2. Customize and run the seeder ( `database/seeders`) to populate the database with initial data via `php artisan db:seed`.
3. create a HMAC key, [there are several ways to do it](https://unix.stackexchange.com/questions/610039/how-to-do-hmacsha256-using-openssl-from-terminal). This is used by ALTCHA.
4. set the `ALTCHA_HMAC_KEY`.

`RTFENDPOINT` and `RTFPASSWORD` are necessary only if you set your `.env` file to "Production", otherwise it will use the internal python script called `convert_rtf_to_html_locally.py`.

Now you should be able to run OpenQDA, but you still need further steps to enable the websocket server and enable the collaboration tools.

## Real-Time Configuration
1. Configure [Laravel Reverb](https://reverb.laravel.com/) for real-time capabilities by setting the following in your `.env`:
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
3. Use `php artisan queue:work --queue=conversion,default`.
   OpenQDA uses [Laravel Queues](https://laravel.com/docs/11.x/queues) and [Laravel Echo](https://github.com/laravel/echo) to process the presence of users and convert files on production.
   For the online presence we use the "default" queue, for converting rtf files we use the "conversion" queue.

On Production, please make sure to install SSL certificates correctly on your web engine (apache, nginx, etc.) as well as our `.env` production file with the absolute path of your certificate, private key and CAfile:

```
LARAVEL_WEBSOCKETS_SSL_LOCAL_CERT=
LARAVEL_WEBSOCKETS_SSL_LOCAL_PK=
LARAVEL_WEBSOCKETS_SSL_CAFILE=
```

**Important:** The WebSocket server requires the **fullchain certificate** (server + intermediate certificates), not just the server certificate. If you encounter SSL issues where your web server works but WebSockets fail, see the [SSL Certificate Troubleshooting Guide](../troubleshooting/ssl-certificates.md).


## Mail logging

OpenQDA sends E-Mails on various occasions.
For local email logging, consider using [MailTrap](https://mailtrap.io/) (free with a usage limit)
or [Laravel Herd](https://herd.laravel.com/) (paid service).
