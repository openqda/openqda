import re
import math

def post_process(files_path: str) -> str:
    with open(files_path, 'r') as file:
      file_data = file.read()

    try:
        file_data = replace(file_data)
    except Exception as e:
        file_data = replace_content_fallback(file_data)
        print(e)

    file_data = f"{file_data}<br>{disclaimer}"

    with open(files_path, 'w') as file:
      file.write(file_data)

speaker_pattern = r'(SPEAKER_\d\d)'
timestamp_pattern = r'(\[\d\d:\d\d:\d\d\])'
disclaimer = "<br><br><br>Transcribed with aTrain. The aTrain license applies to this material: https://raw.githubusercontent.com/JuergenFleiss/aTrain/main/LICENSE"

def replace_content_fallback(file_data) -> str:
    file_data = re.sub(pattern=speaker_pattern, repl='<br><br><h3>\\1</h3>', string=file_data)
    file_data = re.sub(pattern=timestamp_pattern, repl='<br><strong>\\1</strong>', string=file_data)
    return file_data

def replace(file_data) -> str:
    num_speakers = len(re.findall(speaker_pattern, file_data))
    speakers_increment = math.floor(255/num_speakers) if num_speakers > 0 else 1
    print('speakers', num_speakers)
    print('increment', speakers_increment)

    split = re.split(speaker_pattern, file_data)
    speakers = {}
    color = 0
    for i in range(len(split)):
        speaker = split[i]
        # if current is a speaker then define the current color
        # and replace the current with the h3 pattern
        if re.fullmatch(speaker_pattern, speaker):
            if not speaker in speakers:
                speakers[speaker] = len(speakers) * speakers_increment
            color = speakers[speaker]
            split[i] = f"<br><br><strong style='background-color: hsl({color}, 100%, 25%); color: rgb(255, 255, 255);'>{speaker}</strong>"
        # if this is not a speaker then we iterate
        # and color all timestamps for this speaker
        else:
            split[i] = colorize_timestamps(split[i], color)
    return ''.join(split)

def colorize_timestamps(text, color) -> str:
    split = re.split(timestamp_pattern, text)
    for i in range(len(split)):
        entry = split[i]
        if re.match(timestamp_pattern, entry):
            split[i] = f"<br><strong style='color: hsl({color}, 100%, 25%);'>{entry}</strong>"
    return ''.join(split)
