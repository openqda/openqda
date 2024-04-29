# RTF to HTML Converter Service

This Flask application provides a service to convert RTF files to HTML.
It's designed to receive RTF files via HTTP POST requests, convert them to HTML using the `unoconv` tool, and return the resulting HTML file.

## Features

- Converts RTF files to HTML.
- Accepts files via HTTP POST requests.
- IP address filtering for security.
- Secret password protection.

## Requirements

- Python 3
- Flask
- unoconv
- libreoffice

## Local development

- the scripts is called directly in a local machine, but you need to install the requirements first.

```php
// in SourceController.php - convertFileToHtmlLocally method
$scriptPath = base_path('../service-convert-rtf-to-html/convert_rtf_to_html_locally.py');
```

## Production Setup

1. **Install Python 3 and Flask**: Ensure you have Python 3 installed. Install Flask using pip:

   ```bash
   pip install Flask
   ```

2. **Install unoconv**: `unoconv` is required for converting files. Install it on your system:

   ```bash
   sudo apt-get install unoconv
   ```

   (The installation command might vary depending on your operating system.)


3. **Install libreoffice**: `libreoffice` is required for converting files. Install it on your system:

   ```bash
   sudo apt-get install libreoffice
   ```

   (The installation command might vary depending on your operating system.)

4. **Configuration**:

   - Set the `ALLOWED_IP` and `SECRET_PASSWORD` variables in `connect.py` to restrict access to the service.

## Running the Application

To run the application, use the following command from the directory where `connect.py` is located:

```bash
python connect.py
```

The service will start on `http://0.0.0.0:8000`.

## Usage

Send a POST request to `http://server_ip:8000/convertrtftohtml` with the following parameters:

- A file with key `file`.
- A password with key `password`.

Example using `curl`:

```bash
curl -F "file=@/path/to/file.rtf" \
     -F "password=your_secret_password" \
     http://server_ip:8000/convertrtftohtml
```

Replace `/path/to/file.rtf` with the path to your RTF file and `your_secret_password` with the configured secret password.

## Security Note

This application is intended for use in a controlled environment. Ensure proper security measures are in place, including firewall configurations and secure handling of the secret password.

On production a firewall has been placed to accept connections only from one IP address.
