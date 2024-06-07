### Debug websockets locally

**Script Name:** `start_debug_services.sh`

#### Purpose:
`start_debug_services.sh` is a script designed to start the WebSocket server and queue worker for local debugging purposes. It reads the WebSocket server hostname from the `.env` file, ensuring the configuration is dynamic and flexible. This script is not intended for use in production environments.

#### Prerequisites:
- Ensure the `.env` file contains the `APP_HOST` entry.
- Make sure you have the necessary permissions to execute the script.
- Ensure that your environment is set up for running Laravel commands.
- Make sure you set in the `.env` `QUEUE_CONNECTION` to `redis` if you have it configured, or `database`.
#### .env Configuration:
Add or edit the following line to your `.env` file if it does not exist:

```env
APP_HOST=web.test
QUEUE_CONNECTION=database
```


#### Usage:

1. **Make the script executable:**
   ```bash
   chmod +x start_debug_services.sh
   ```

2. **Run the script:**
   ```bash
   ./start_debug_services.sh
   ```

### Important Notes:
- **Not for Production:** This script is intended for local debugging and should not be used in production environments.
- **Background Processes:** The WebSocket server and queue worker will run in the background. The script will clean up these processes upon exiting.
