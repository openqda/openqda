import subprocess
import os
import sys

def convert_rtf_to_html(rtf_path, output_dir):
    # Ensure the output directory exists
    if not os.path.exists(output_dir):
        os.makedirs(output_dir)

    # Remove the original extension and add .html
    file_root, _ = os.path.splitext(os.path.basename(rtf_path))
    output_html = os.path.join(output_dir, file_root + '.html')

    # Run unoconv to convert the file
    result = subprocess.run(['/usr/local/bin/unoconv', '-f', 'html', '-o', output_html, rtf_path])

    # Check if the conversion was successful
    if result.returncode == 0:
        print(f"Converted HTML file saved as: {output_html}")
    else:
        print(f"Error in conversion. Return code: {result.returncode}")

# Check if the correct number of arguments are passed
if len(sys.argv) != 3:
    print("Usage: python convert_rtf_to_html.py [rtf_path] [output_dir]")
    sys.exit(1)

# Get the file path and output directory from command line arguments
input_rtf_path = sys.argv[1]
output_directory = sys.argv[2]

# Call the function with the provided arguments
convert_rtf_to_html(input_rtf_path, output_directory)
