"""Normalize pinned upstream snapshots and export reviewed WordPress import rows."""
import argparse
from collections import Counter
import hashlib
import json
from pathlib import Path
import re
import unicodedata

MIR = "https://huggingface.co/datasets/HiTZ/MedExpQA"
COMMENTS = "https://github.com/ixa-ehu/antidote-casimedicos"
MEDBENCH = "https://github.com/Precisa-Saude/medbench-brasil"
INEP = "https://www.gov.br/inep/pt-br/areas-de-atuacao/avaliacao-e-exames-educacionais/revalida/provas-e-gabaritos"
FIELDS = ("stem", "options", "key", "explanation", "images")
IMAGE = re.compile(r"imagen adjunta|imagen vinculada|(?:en|de) la imagen|figura adjunta|(?:véase|ver) (?:la )?(?:figura|imagen)|\.(?:jpg|png)", re.I)


def read(path):
    with path.open(encoding="utf-8") as handle:
        if path.suffix == ".jsonl":
            return [json.loads(line) for line in handle if line.strip()]
        return json.load(handle)


def write(path, value):
    path.write_text(json.dumps(value, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")


def fingerprint(text):
    normalized = unicodedata.normalize("NFKD", text.casefold())
    return hashlib.sha256("".join(c for c in normalized if c.isalnum()).encode()).hexdigest()


def normalize_mir(row, revision, split):
    keys = sorted(row["options"], key=int)
    if keys == ["1", "2", "3", "4", "5"] and row["options"]["5"] is None:
        keys.pop()
    choice = str(row["correct_option"])
    original = "https://www.sanidad.gob.es/"
    return {
        "source_id": f"medexpqa-es-{row['id']}",
        "dataset_id": str(row["id"]), "year": row["year"], "edition": str(row["year"]),
        "official_id": str(row["question_id_specific"]), "source_revision": revision, "source_file": split,
        "exam": "MIR", "country": "ES", "lang": "es", "category": "MIR (España)",
        "subject": row["type"].strip(), "topic": row["type"].strip(), "type": "mcq",
        "stem": row["full_question"], "options": [row["options"][k] for k in keys],
        "key": chr(65 + keys.index(choice)) if choice in keys else "",
        "explanation": row.get("full_answer", ""),
        "reasons": [row.get("explanations", {}).get(k, {}).get("text", "") for k in keys],
        "requires_media": bool(IMAGE.search(row["full_question"])), "annulled": False,
        "source": MIR, "url": MIR, "original_source": original,
        "provenance": {"stem": original, "options": original, "key": MIR,
                       "explanation": COMMENTS, "images": original},
        "dataset_license": "CC-BY-4.0",
    }


def normalize_revalida(row, edition, revision):
    keys = sorted(row["options"])
    choice = row["correct"]
    specialty = ", ".join(row.get("specialty", []))
    return {
        "source_id": row["id"], "dataset_id": row["id"], "year": edition["year"], "edition": edition["id"],
        "official_id": str(row["number"]), "source_revision": revision, "source_file": edition["id"],
        "exam": "Revalida", "country": "BR", "lang": "pt", "category": "Revalida (Brasil)",
        "subject": specialty, "topic": specialty, "type": "mcq", "stem": row["stem"],
        "options": [row["options"][k] for k in keys],
        "key": chr(65 + keys.index(choice)) if choice in keys else "",
        "explanation": "", "reasons": [""] * len(keys),
        "requires_media": bool(row.get("hasImage") or row.get("hasTable")), "annulled": row.get("annulled", False),
        "source": MEDBENCH, "url": INEP, "original_source": edition.get("source", INEP),
        "provenance": {"stem": INEP, "options": INEP, "key": MEDBENCH, "explanation": None, "images": INEP},
        "dataset_license": "Apache-2.0",
    }


def assess(row, review, duplicate, ambiguous_id):
    if duplicate or row["annulled"] or row["requires_media"]:
        return "EXCLUDED", [reason for flag, reason in (
            (duplicate, "duplicate_in_batch"), (row["annulled"], "annulled"), (row["requires_media"], "media_required")
        ) if flag]
    options = row["options"]
    if (len(options) not in (4, 5) or not all(isinstance(option, str) and option.strip() for option in options)
            or len({fingerprint(option) for option in options}) != len(options)
            or not row["key"] or not row["stem"].strip()):
        return "EXCLUDED", ["invalid_question"]
    issues = []
    if ambiguous_id and not review.get("official_version"):
        issues.append("official_id_ambiguous")
    if review.get("transcription_note"):
        issues.append("transcription_discrepancy")
    if not row["subject"] or not row["topic"]:
        issues.append("classification_missing")
    if not (row["explanation"] or "").strip() or not all(isinstance(reason, str) and reason.strip() for reason in row["reasons"]):
        issues.append("explanation_missing")
    key = review.get("official_key")
    if not review.get("official_key_url") or not key:
        issues.append("official_key_unverified")
    elif key != row["key"]:
        issues.append("official_key_mismatch")
    if not review.get("editorial_reviewed"):
        issues.append("editorial_review_pending")
    rights = review.get("rights", {})
    if not all(isinstance(rights.get(field), dict) and rights[field].get("evidence")
               and rights[field].get("license") and row["provenance"].get(field) for field in FIELDS):
        issues.append("rights_unverified")
    return ("NEEDS_REVIEW" if issues else "READY"), issues


def run(args):
    approvals = read(args.reviews) if args.reviews else {}
    secondary = read(args.drbode) if args.drbode else []
    cross = {fingerprint(row["enunciado"]) for row in secondary if row["origem"] == "INEP"}
    rows = []
    for path in sorted(args.mir.glob("*.jsonl")):
        rows.extend(normalize_mir(row, args.mir_revision, path.name) for row in read(path)
                    if row["lang"] == "es")
    for path in sorted(args.revalida.glob("*.json")):
        edition = read(path)
        if edition["id"].startswith("revalida-"):
            rows.extend(normalize_revalida(row, edition, args.revalida_revision) for row in edition["questions"])
    if {row["exam"] for row in rows} != {"MIR", "Revalida"}:
        raise ValueError("Both source snapshots must contain questions")
    args.output.mkdir(parents=True, exist_ok=True)
    counts, reasons, ready_count = Counter(), Counter(), Counter()
    report, ready = [], []
    seen_ids, seen_text = set(), set()
    official_versions = {}
    for row in rows:
        official_id = row["exam"], row["edition"], row["official_id"]
        official_versions.setdefault(official_id, set()).add(fingerprint(row["stem"]))
    for row in rows:
        ident = row["source_id"]
        text_key = row["exam"], fingerprint(row["stem"])
        duplicate = ident in seen_ids or text_key in seen_text
        seen_ids.add(ident)
        seen_text.add(text_key)
        review = approvals.get(ident, {})
        official_id = row["exam"], row["edition"], row["official_id"]
        if row["exam"] == "Revalida" and review.get("explanation_source"):
            row["explanation"] = review.get("explanation", "")
            provided = review.get("reasons", [])
            if isinstance(provided, list) and len(provided) == len(row["options"]):
                row["reasons"] = provided
            row["provenance"]["explanation"] = review["explanation_source"]
        status, issues = assess(row, review, duplicate, len(official_versions[official_id]) > 1)
        row["review_status"] = status
        row["rights"] = review.get("rights", {})
        if review.get("booklet_url"):
            row["original_source"] = review["booklet_url"]
            row["provenance"].update(stem=review["booklet_url"], options=review["booklet_url"])
        if review.get("official_version"):
            row["official_version"] = review["official_version"]
        if review.get("official_key_url"):
            row["provenance"]["key"] = review["official_key_url"]
        cross_match = row["exam"] == "Revalida" and text_key[1] in cross
        counts[(row["exam"], status)] += 1
        reasons.update((row["exam"], issue) for issue in issues)
        if cross_match:
            reasons[(row["exam"], "drbode_exact_stem_overlap")] += 1
        report.append({key: row[key] for key in (
            "source_id", "dataset_id", "exam", "country", "lang", "year", "edition", "official_id", "source_revision",
            "source_file", "original_source", "provenance", "dataset_license", "review_status", "rights"
        )} | {"issues": issues, "options_count": len(row["options"]), "cross_dataset_overlap": cross_match,
             "stem_sha256": text_key[1], "official_version": row.get("official_version"),
             "transcription_note": review.get("transcription_note"),
             "official_key_checked": bool(review.get("official_key_url") and review.get("official_key") == row["key"])})
        if status == "READY" and ready_count[row["exam"]] < args.limit:
            ready.append(row)
            ready_count[row["exam"]] += 1
    write(args.output / "validation.json", report)
    write(args.output / "wordpress-ready.json", ready)
    summary = {exam: {status: counts[exam, status] for status in ("READY", "NEEDS_REVIEW", "EXCLUDED")}
               | {"exported": ready_count[exam], "issues": {label: count for (bank, label), count in reasons.items() if bank == exam}}
               for exam in ("MIR", "Revalida")}
    write(args.output / "summary.json", summary)
    print(json.dumps(summary, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    cli = argparse.ArgumentParser(description=__doc__)
    for name in ("mir", "revalida", "output"):
        cli.add_argument(f"--{name}", type=Path, required=True)
    for name in ("mir-revision", "revalida-revision"):
        cli.add_argument(f"--{name}", required=True)
    cli.add_argument("--drbode", type=Path)
    cli.add_argument("--reviews", type=Path)
    cli.add_argument("--limit", type=int, default=15)
    run(cli.parse_args())
