#!/usr/bin/env python
import subprocess
import os
import sys

def convert_rtf_to_html(source_path, output_dir):
    """Convert a document file to HTML using pandoc. The rtf in the name is a holdover from unoconv usage.
    Args:
        source_path (str): Path to the source RTF file.
        output_dir (str): Directory where the output HTML file will be saved.
    """
    print(f"Start conversion: {source_path} to HTML")

    # Ensure the output directory exists
    if not os.path.exists(output_dir):
        print(f"Create new output path: {output_dir}")
        os.makedirs(output_dir)

    # check if file is utf-8 encoded
    print(f"Check if file is utf-8 encoded: {source_path}")
    with open(source_path, 'rb') as f:
        raw_data = f.read()
        try:
            raw_data.decode('utf-8')
        except UnicodeDecodeError:
            print("The file is not UTF-8 encoded.")
            sys.exit(1)


    # Remove the original extension and add .html
    print(f"Prepare conversion")
    file_root, _ = os.path.splitext(os.path.basename(source_path))
    output_html = os.path.join(output_dir, file_root + '.html')

    # Run unoconv to convert the file
    # result = subprocess.run(['unoconv', '-f', 'html', '-o', output_html, source_path])
    print(f"Run conversion")
    result = subprocess.run(['pandoc', '-s', '-o', output_html, source_path])

    # TODO: post clean html document against XSS attacks
    print(f"Post clean converted html document")
    # ...

    # Check if the conversion was successful
    print(f"Check conversion result: {result.returncode}")
    if result.returncode == 0:
        print(f"Converted HTML file saved as: {output_html}")
    else:
        print(f"Error in conversion. Return code: {result.returncode}; cause: {result.stderr}")

# Check if the correct number of arguments are passed
if len(sys.argv) != 3:
    print("Usage: python convert_rtf_to_html.py [source_path] [output_dir]")
    sys.exit(1)

# Get the file path and output directory from command line arguments
input_source_path = sys.argv[1]
output_directory = sys.argv[2]

# Call the function with the provided arguments
convert_rtf_to_html(input_source_path, output_directory)
