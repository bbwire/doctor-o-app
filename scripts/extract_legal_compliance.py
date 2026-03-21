#!/usr/bin/env python3
"""
Extract Terms of Service, Privacy Policy, and Contact information from
dr_o_legal_compliance_documents.docx into docs/legal/ and pwa/content/legal/.
"""
from __future__ import annotations

import re
import sys
import zipfile
import xml.etree.ElementTree as ET
from pathlib import Path

NS = "{http://schemas.openxmlformats.org/wordprocessingml/2006/main}"


def normalize_text(s: str) -> str:
    """Replace common Word oddities and private-use chars for clean UTF-8."""
    s = s.replace("\u00a0", " ")  # nbsp
    s = s.replace("\u2013", "-")  # en dash
    s = s.replace("\u2014", "—")  # em dash
    s = s.replace("\u2018", "'").replace("\u2019", "'")
    s = s.replace("\u201c", '"').replace("\u201d", '"')
    # Dr.O style private-use or replacement chars sometimes appear as �
    s = s.replace("\ufffd", "")
    # Checkbox / form symbols that break Windows cp1252 console
    for c in ("\u2610", "\u2611", "\u2612", "\u25a1", "\u25aa"):
        s = s.replace(c, "[ ]")
    return s


def extract_paragraphs(path: Path) -> list[str]:
    with zipfile.ZipFile(path, "r") as z:
        xml = z.read("word/document.xml")
    root = ET.fromstring(xml)
    paras: list[str] = []
    for p in root.iter(NS + "p"):
        texts: list[str] = []
        for t in p.iter(NS + "t"):
            if t.text:
                texts.append(t.text)
            if t.tail:
                texts.append(t.tail)
        line = normalize_text("".join(texts)).strip()
        if line:
            paras.append(line)
    return paras


def join_paras(paras: list[str]) -> str:
    return "\n\n".join(paras)


def split_documents(paras: list[str]) -> tuple[str, str, str]:
    """Returns (terms_md, privacy_md, consent_md_or_empty)."""
    full = join_paras(paras)
    # Markers as in the Word export
    m2 = re.search(r"\nDocument 2:\s*Privacy Policy\n", full, re.I)
    m3 = re.search(r"\nDocument 3:\s*Patient Consent Form\n", full, re.I)
    if not m2 or not m3:
        raise SystemExit("Could not find Document 2 / Document 3 markers in extracted text.")

    terms = full[: m2.start()].strip()
    privacy = full[m2.end() : m3.start()].strip()
    consent = full[m3.end() :].strip()
    return terms, privacy, consent


def extract_contact_block(terms: str, privacy: str) -> str:
    """Pull CONTACT US sections from Terms and Privacy into one markdown file."""
    lines: list[str] = [
        "# Contact information — Dr. O Virtual Consultations",
        "",
        "The following is consolidated from the Terms of Service and Privacy Policy.",
        "",
    ]
    # Section 13 in Terms
    idx = terms.upper().find("13. CONTACT US")
    if idx != -1:
        chunk = terms[idx:]
        end = chunk.find("\n\n14.")
        if end == -1:
            end = chunk.find("\n\nDocument 2:")
        block = chunk[:end] if end != -1 else chunk[:2000]
        lines.append("## From Terms of Service (Section 13)")
        lines.append("")
        lines.append(block.strip())
        lines.append("")

    idx = privacy.upper().find("13. CONTACT US")
    if idx != -1:
        chunk = privacy[idx:]
        end = chunk.find("\n\n")
        # take until next numbered section or end
        for stop in ("\n\n14.", "\n\nBy using"):
            j = chunk.find(stop)
            if j != -1:
                end = min(end if end < len(chunk) else len(chunk), j)
        block = chunk[:4000]
        lines.append("## From Privacy Policy (Section 13)")
        lines.append("")
        lines.append(block.strip())
        lines.append("")

    lines.extend(
        [
            "## Quick reference",
            "",
            "| Channel | Details |",
            "| --- | --- |",
            "| Telephone | 0782937255, 0753937255 |",
            "| Email | omonadego@live.com |",
            "| Website | dro.com |",
            "",
            "**Data controller / operator:** Dr. Alfonse Omona Degozone, Consultant Surgeon, Masaka Regional Referral Hospital.",
            "",
        ]
    )
    return "\n".join(lines)


def main() -> None:
    repo = Path(__file__).resolve().parent.parent
    docx = repo / "dr_o_legal_compliance_documents.docx"
    if not docx.exists():
        print("Missing:", docx, file=sys.stderr)
        sys.exit(1)

    paras = extract_paragraphs(docx)
    terms, privacy, consent = split_documents(paras)

    docs_legal = repo / "docs" / "legal"
    pwa_content = repo / "pwa" / "content" / "legal"
    docs_legal.mkdir(parents=True, exist_ok=True)
    pwa_content.mkdir(parents=True, exist_ok=True)

    contact_md = extract_contact_block(terms, privacy)

    for dest in (docs_legal, pwa_content):
        (dest / "terms-of-service.md").write_text(terms + "\n", encoding="utf-8")
        (dest / "privacy-policy.md").write_text(privacy + "\n", encoding="utf-8")
        (dest / "contact-information.md").write_text(contact_md + "\n", encoding="utf-8")
        (dest / "patient-consent-form.md").write_text(consent + "\n", encoding="utf-8")

    print("Wrote:")
    for name in (
        "terms-of-service.md",
        "privacy-policy.md",
        "contact-information.md",
        "patient-consent-form.md",
    ):
        print(" ", docs_legal / name)
        print(" ", pwa_content / name)


if __name__ == "__main__":
    main()
