#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Verifica que el indice apunte a la pagina correcta en el PDF final."""
import subprocess
import sys

from docx import Document

DOCX = sys.argv[1]
PDF = sys.argv[2]
TXT = "/tmp/verif.txt"

d = Document(DOCX)
filas = [(r.cells[0].text.strip(), r.cells[1].text.strip()) for r in d.tables[1].rows[1:]]
subprocess.run(["pdftotext", "-layout", PDF, TXT], check=True)
paginas = open(TXT, encoding="utf-8").read().split("\f")
ok = True
for et, pag in filas:
    p = int(pag)
    if not any(l.strip().startswith(et) for l in paginas[p - 1].split("\n")):
        ok = False
        print("MISMATCH:", et, "->", pag)
print("indice correcto:", ok, "| secciones:", len(filas), "| paginas:", len(paginas) - 1)
