from aTrain_core import transcribe, check_inputs, outputs, globals, load_resources
from dotenv import dotenv_values
import os

config = dotenv_values('.env')

def install():
    model = config['WHISPER_MODEL']
    model_path = os.path.join(globals.ATRAIN_DIR, "models", model)
    print(f"Install: check if model exists in {model_path}")

    if os.path.isdir(model_path):
        print(f"Install: model {model} exists, skip download")
    else:
        print(f"Install: install model {model}")
        load_resources.get_model(model)

install()
