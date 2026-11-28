"""Extract the repository's authorized practice items without executing HTML."""
import argparse
import base64
import json
import re
from pathlib import Path

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument("html", type=Path)
args = parser.parse_args()
plugin = Path(__file__).resolve().parents[1]

source = args.html.read_text(encoding="utf-8")
match = re.search(r"^const Q=(\[.*?\]);\s*$", source, re.MULTILINE)
if not match:
    parser.error("The HTML question data was not found.")

items = json.loads(match.group(1))
bank = []
seen = set()
images = {}
for item in items:
    number, page, stem, options, answers = (item[key] for key in ("id", "page", "q", "opts", "ans"))
    if (
        type(number) is not int or number < 1 or number in seen
        or type(page) is not int or page < 1
        or not isinstance(stem, str) or not stem.strip()
        or len(options) != 4 or any(not isinstance(option, str) or not option.strip() for option in options)
        or len(answers) != 1 or type(answers[0]) is not int or not 0 <= answers[0] < len(options)
        or item["fix"] not in (0, 1)
    ):
        raise ValueError(f"Invalid question: {number}")
    seen.add(number)
    row = {
        "id": f"html-{number}",
        "subject": "Banco general de medicina",
        "stem": stem,
        "options": options,
        "key": answers[0],
        "source": f"Banco educativo EstudiaMed · pregunta {number} · pág. {page}",
        "fixedOrder": bool(item["fix"]),
    }
    if "img" in item:
        prefix, encoded = item["img"].split(",", 1)
        image = base64.b64decode(encoded, validate=True)
        if prefix != "data:image/jpeg;base64" or not image.startswith(b"\xff\xd8") or not image.endswith(b"\xff\xd9"):
            raise ValueError(f"Invalid JPEG: {number}")
        row["image"] = f"{number}.jpg"
        images[row["image"]] = image
    bank.append(row)

# Serve the bank only through PHP so the static web server cannot expose answer keys.
payload = json.dumps(bank, ensure_ascii=False, indent=2)
(plugin / "data/html-practice.php").write_text(
    "<?php\ndefined( 'ABSPATH' ) || exit;\n\nreturn json_decode( <<<'ESTUDIAMED_BANK'\n"
    + payload + "\nESTUDIAMED_BANK\n, true );\n",
    encoding="utf-8",
)
for name, image in images.items():
    (plugin / "assets/question-images" / name).write_bytes(image)
print(f"Exported {len(bank)} questions and {len(images)} images.")
