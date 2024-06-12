from faster_whisper.audio import decode_audio
from aTrain_core import globals

def get_audio_length(audio_file):
    audio_array = decode_audio(audio_file, sampling_rate=globals.SAMPLING_RATE)
    return int(len(audio_array)/globals.SAMPLING_RATE)
