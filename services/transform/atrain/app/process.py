import re

def post_process(files_path: str) -> str:
    with open(files_path, 'r') as file:
      file_data = file.read()
    file_data = re.sub(pattern=r'(\[\d\d:)', repl='\n\\1', string=file_data)
    file_data = f"{file_data}\n\n{disclaimer}"
    with open(files_path, 'w') as file:
      file.write(file_data)

disclaimer = "Transcribed with aTrain. The aTrain license applies to this material: https://raw.githubusercontent.com/JuergenFleiss/aTrain/main/LICENSE"
