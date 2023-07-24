import json
import os
from pathlib import Path
import sys

current = os.path.dirname(os.path.realpath(__file__))
parent = os.path.dirname(current)

# setting path
sys.path.append(parent)
import main

data_dir = Path(parent) / "data"
data_dir.mkdir(exist_ok=True)

with open(data_dir / "participants.json", "w") as p:
    p.write(json.dumps(main.get_participants()))

with open(data_dir / "forms.json", "w") as p:
    forms = main.get_forms()
    p.write(json.dumps(forms))

for form in forms.keys():
    with open(data_dir / f"questions_{form}.json", "w") as p:
        questions = main.get_questions(form)
        p.write(json.dumps(questions))
