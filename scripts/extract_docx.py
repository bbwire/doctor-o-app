#!/usr/bin/env python3
"""Extract plain text paragraphs from a .docx file."""
import sys
import zipfile
import xml.etree.ElementTree as ET

def main(path: str) -> None:
    ns = "{http://schemas.openxmlformats.org/wordprocessingml/2006/main}"
    with zipfile.ZipFile(path, "r") as z:
        xml = z.read("word/document.xml")
    root = ET.fromstring(xml)
    paras = []
    for p in root.iter(ns + "p"):
        texts = []
        for t in p.iter(ns + "t"):
            if t.text:
                texts.append(t.text)
            if t.tail:
                texts.append(t.tail)
        line = "".join(texts).strip()
        if line:
            paras.append(line)
    for line in paras:
        print(line)
        print()

if __name__ == "__main__":
    main(sys.argv[1])
