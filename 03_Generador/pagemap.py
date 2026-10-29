#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Extrae el mapa etiqueta -> numero de pagina del PDF renderizado."""
import json
import os
import re
import subprocess
import sys

import unicodedata  # noqa: F401
from docx import Document

DOCX = sys.argv[1]
PDF = sys.argv[2]
TXT = "/tmp/pagemap.txt"
JSON_OUT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "paginas.json")

subprocess.run(["pdftotext", "-layout", PDF, TXT], check=True)
texto = open(TXT, encoding="utf-8").read()
paginas = texto.split("\f")

doc = Document(DOCX)
tab = doc.tables[1]  # 0 = datos de portada, 1 = indice
etiquetas = [r.cells[0].text.strip() for r in tab.rows[1:]]

# la pagina del indice: la que contiene el encabezado "Indice" con su tabla
pag_indice = 1
for i, p in enumerate(paginas):
    if re.search(r"^\s*Índice\s*$", p, re.M):
        pag_indice = i
        break

mapa = {}
for et in etiquetas:
    objetivo = et.strip()
    encontrada = None
    for i, p in enumerate(paginas):
        if i <= pag_indice:
            continue
        for linea in p.split("\n"):
            if linea.strip().startswith(objetivo):
                encontrada = i + 1
                break
        if encontrada:
            break
    mapa[et] = encontrada

with open(JSON_OUT, "w", encoding="utf-8") as fh:
    json.dump(mapa, fh, ensure_ascii=False, indent=1)

faltan = [k for k, v in mapa.items() if v is None]
print("paginas totales:", len(paginas) - (1 if not paginas[-1].strip() else 0))
print("etiquetas:", len(etiquetas), "sin encontrar:", faltan)
for k, v in mapa.items():
    print("  %-60s -> %s" % (k[:58], v))
