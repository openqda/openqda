from flask import Flask, request, jsonify, send_file
import subprocess
import os
import tempfile
from werkzeug.utils import secure_filename
import logging
from logging.handlers import TimedRotatingFileHandler

# Set up the logging
log_file = 'logfile.log'
logger = logging.getLogger('MyLogger')
logger.setLevel(logging.INFO)  # Capture all messages of level INFO and above

# Create a handler for rotating log files every 7 days
handler = TimedRotatingFileHandler(log_file, when="D", interval=7, backupCount=1)
formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
handler.setFormatter(formatter)
logger.addHandler(handler)



app = Flask(__name__)

# Allowed IP Address and Secret Password
# IP address filtering is also done server side
ALLOWED_IP = ''
SECRET_PASSWORD = ''

@app.route("/convert", methods=["POST"])
def convert_rtf_to_html():

    try:
        # IP address filtering
        if request.remote_addr not in ALLOWED_IP:
            app.logger.info(f"Access denied - IP not allowed: {request.remote_addr}")
            logger.info(f"Access denied - IP not allowed: {request.remote_addr}")
            return jsonify({"error": "Access denied - wrong ip"}), 403

        received_password = request.form.get("password")

        # Check for secret password
        if request.form.get("password") != SECRET_PASSWORD:
            app.logger.info(
                f"Invalid password attempt from IP {request.remote_addr}: {received_password}"
            )
            return jsonify({"error": "Invalid password"}), 403

        # Check if the post request has the file part
        if "file" not in request.files:
            return jsonify({"error": "No file part"}), 400

        file = request.files["file"]

        # If the user does not select a file, the browser submits an
        # empty file without a filename.
        if file.filename == "":
            return jsonify({"error": "No selected file"}), 400

        if file:

            logger.info(f"File {file.filename} processed")
            # Create a temporary directory to store the uploaded file and the output HTML
            with tempfile.TemporaryDirectory() as temp_dir:
                # Save the uploaded file to the temporary directory
                filename = secure_filename(file.filename)
                file_path = os.path.join(temp_dir, filename)
                file.save(file_path)

                # Define the output HTML file path
                output_html = os.path.join(
                    temp_dir, os.path.splitext(filename)[0] + ".html"
                )

                # Run the script to convert the file
                # subprocess.run(["unoconv", "-f", "html", "-o", output_html, file_path])
                subprocess.run(['pandoc', '-s', '-o', output_html, file_path])

                # Check if the conversion was successful and return the result
                if os.path.exists(output_html):
                    return send_file(output_html, as_attachment=True)
                else:
                    return jsonify({"error": "Conversion failed"}), 500

            # Return an error response or handle the error as needed
            return jsonify({"error": "Internal server error"}), 500
    except Exception as e:
        # Log the error with traceback
        logger.error(f"An error occurred: {str(e)}")
        logger.error(traceback.format_exc())  # This logs the full traceback

        # Return an error response or handle the error as needed
        return jsonify({"error": "Internal server error"}), 500


if __name__ == "__main__":
    app.run(debug=True, host="0.0.0.0", port=8000)