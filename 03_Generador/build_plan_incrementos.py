#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Genera el entregable formal de la Práctica Guiada: Plan de Incrementos (COTIZA-ELEV)
a partir de docs/plan_incrementos.md hacia:
  - 01_Entregables/Practica_Plan_Incrementos_COTIZA-ELEV.docx
  - 01_Entregables/Practica_Plan_Incrementos_COTIZA-ELEV.pdf
  - Practica_Plan_Incrementos_COTIZA-ELEV.pdf (copia en raíz)
  - Reporte_Practica_Plan_Incrementos_COTIZA-ELEV.html
"""
import os
import re
import shutil
import subprocess
import sys
from docx import Document
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Pt, RGBColor

HERE = os.path.dirname(os.path.abspath(__file__))
REPO = os.path.dirname(HERE)
OUT_DIR = os.path.join(REPO, "01_Entregables")
PLAN_MD = os.path.join(REPO, "docs", "plan_incrementos.md")
DOCX_OUT = os.path.join(OUT_DIR, "Practica_Plan_Incrementos_COTIZA-ELEV.docx")
PDF_OUT = os.path.join(OUT_DIR, "Practica_Plan_Incrementos_COTIZA-ELEV.pdf")
PDF_ROOT = os.path.join(REPO, "Practica_Plan_Incrementos_COTIZA-ELEV.pdf")
HTML_OUT = os.path.join(REPO, "Reporte_Practica_Plan_Incrementos_COTIZA-ELEV.html")

NAVY = RGBColor(0x1F, 0x38, 0x64)
NAVY_HEX = "1F3864"
ACCENT_HEX = "2E5C9A"
LIGHT_HEX = "EDF2F9"
GREY = RGBColor(0x59, 0x59, 0x59)
BODY_FONT = "Arial"


def shade_cell(cell, hex_fill):
    tcPr = cell._tc.get_or_add_tcPr()
    shd = OxmlElement("w:shd")
    shd.set(qn("w:val"), "clear")
    shd.set(qn("w:color"), "auto")
    shd.set(qn("w:fill"), hex_fill)
    tcPr.append(shd)


def cell_margins(cell, top=50, bottom=50, left=70, right=70):
    tcPr = cell._tc.get_or_add_tcPr()
    mar = OxmlElement("w:tcMar")
    for tag, val in (("top", top), ("left", left), ("bottom", bottom), ("right", right)):
        el = OxmlElement("w:" + tag)
        el.set(qn("w:w"), str(val))
        el.set(qn("w:type"), "dxa")
        mar.append(el)
    tcPr.append(mar)


def para_border(paragraph, position="bottom", color=NAVY_HEX, size=10, space=3):
    pPr = paragraph._p.get_or_add_pPr()
    borders = OxmlElement("w:pBdr")
    el = OxmlElement("w:" + position)
    el.set(qn("w:val"), "single")
    el.set(qn("w:sz"), str(size))
    el.set(qn("w:space"), str(space))
    el.set(qn("w:color"), color)
    borders.append(el)
    pPr.append(borders)


def keep_with_next(paragraph):
    pPr = paragraph._p.get_or_add_pPr()
    el = OxmlElement("w:keepNext")
    pPr.append(el)


def add_field(paragraph, instr):
    run = paragraph.add_run()
    fld = OxmlElement("w:fldSimple")
    fld.set(qn("w:instr"), instr)
    r = OxmlElement("w:r")
    t = OxmlElement("w:t")
    t.text = "1"
    r.append(t)
    fld.append(r)
    paragraph._p.append(fld)
    return run


def set_table_fixed(table, widths_cm):
    table.autofit = False
    tblPr = table._tbl.tblPr
    layout = OxmlElement("w:tblLayout")
    layout.set(qn("w:type"), "fixed")
    tblPr.append(layout)
    total = int(sum(widths_cm) * 567)
    tblW = tblPr.find(qn("w:tblW"))
    if tblW is None:
        tblW = OxmlElement("w:tblW")
        tblPr.append(tblW)
    tblW.set(qn("w:w"), str(total))
    tblW.set(qn("w:type"), "dxa")
    grid = table._tbl.find(qn("w:tblGrid"))
    if grid is not None:
        table._tbl.remove(grid)
    grid = OxmlElement("w:tblGrid")
    for w in widths_cm:
        gc = OxmlElement("w:gridCol")
        gc.set(qn("w:w"), str(int(w * 567)))
        grid.append(gc)
    table._tbl.insert(list(table._tbl).index(tblPr) + 1, grid)
    for row in table.rows:
        for cell, w in zip(row.cells, widths_cm):
            cell.width = Cm(w)
            tcPr = cell._tc.get_or_add_tcPr()
            tcW = tcPr.find(qn("w:tcW"))
            if tcW is None:
                tcW = OxmlElement("w:tcW")
                tcPr.append(tcW)
            tcW.set(qn("w:w"), str(int(w * 567)))
            tcW.set(qn("w:type"), "dxa")


def clean_md(text):
    text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)
    text = re.sub(r"`(.*?)`", r"\1", text)
    text = re.sub(r"\*(.*?)\*", r"\1", text)
    return text.strip()


class Doc:
    def __init__(self):
        self.d = Document()
        self._setup_styles()
        self._setup_footer()

    def _setup_styles(self):
        st = self.d.styles["Normal"]
        st.font.name = BODY_FONT
        st.font.size = Pt(9.5)
        st.element.rPr.rFonts.set(qn("w:eastAsia"), BODY_FONT)
        pf = st.paragraph_format
        pf.line_spacing = 1.15
        pf.space_after = Pt(4)
        for s in self.d.sections:
            s.top_margin = Cm(1.9)
            s.bottom_margin = Cm(1.8)
            s.left_margin = Cm(1.8)
            s.right_margin = Cm(1.8)

    def _setup_footer(self):
        sec = self.d.sections[0]
        p = sec.footer.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        r = p.add_run("COTIZA-ELEV · Práctica Guiada: Plan de Incrementos · Página ")
        r.font.size = Pt(8)
        r.font.color.rgb = GREY
        add_field(p, "PAGE")
        r2 = p.add_run(" de ")
        r2.font.size = Pt(8)
        r2.font.color.rgb = GREY
        add_field(p, "NUMPAGES")
        settings = self.d.settings.element
        uf = OxmlElement("w:updateFields")
        uf.set(qn("w:val"), "true")
        settings.append(uf)

    def h1(self, texto, page_break_before=False):
        p = self.d.add_paragraph()
        if page_break_before:
            p.paragraph_format.page_break_before = True
        p.paragraph_format.space_before = Pt(12)
        p.paragraph_format.space_after = Pt(5)
        r = p.add_run(clean_md(texto))
        r.font.size = Pt(12.5)
        r.font.bold = True
        r.font.color.rgb = NAVY
        para_border(p, "bottom", NAVY_HEX, 8, 3)
        keep_with_next(p)
        return p

    def h2(self, texto):
        p = self.d.add_paragraph()
        p.paragraph_format.space_before = Pt(9)
        p.paragraph_format.space_after = Pt(3)
        r = p.add_run(clean_md(texto))
        r.font.size = Pt(10.5)
        r.font.bold = True
        r.font.color.rgb = RGBColor(0x2E, 0x5C, 0x9A)
        keep_with_next(p)
        return p

    def h3(self, texto):
        p = self.d.add_paragraph()
        p.paragraph_format.space_before = Pt(6)
        p.paragraph_format.space_after = Pt(2)
        r = p.add_run(clean_md(texto))
        r.font.size = Pt(9.5)
        r.font.bold = True
        r.font.color.rgb = NAVY
        keep_with_next(p)
        return p

    def p(self, texto, align="justify", size=9.0, italic=False, space_after=4):
        par = self.d.add_paragraph()
        par.alignment = {"justify": WD_ALIGN_PARAGRAPH.JUSTIFY,
                         "center": WD_ALIGN_PARAGRAPH.CENTER,
                         "left": WD_ALIGN_PARAGRAPH.LEFT}[align]
        par.paragraph_format.space_after = Pt(space_after)
        r = par.add_run(clean_md(texto))
        r.font.size = Pt(size)
        r.font.italic = italic
        return par

    def bullet(self, texto):
        par = self.d.add_paragraph(style="List Bullet")
        par.paragraph_format.space_after = Pt(2.5)
        par.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        r = par.add_run(clean_md(texto))
        r.font.size = Pt(9.0)

    def table(self, headers, rows):
        ncols = len(headers)
        if ncols == 2:
            widths = [4.2, 13.4]
            fsize = 8.5
        elif ncols == 3:
            widths = [3.8, 3.0, 10.8]
            fsize = 8.2
        elif ncols == 4:
            widths = [2.4, 5.2, 5.8, 4.2]
            fsize = 8.0
        elif ncols == 5:
            widths = [2.0, 3.0, 4.2, 4.2, 4.2]
            fsize = 7.8
        elif ncols == 6:
            widths = [2.2, 2.2, 3.4, 3.8, 3.0, 3.0]
            fsize = 7.5
        elif ncols == 7:
            widths = [1.8, 2.2, 1.6, 3.4, 3.4, 3.4, 1.8]
            fsize = 7.2
        else:
            widths = [17.6 / ncols] * ncols
            fsize = 7.0

        t = self.d.add_table(rows=1, cols=ncols)
        t.style = "Table Grid"
        t.alignment = WD_TABLE_ALIGNMENT.CENTER
        tr0Pr = t.rows[0]._tr.get_or_add_trPr()
        tr0Pr.append(OxmlElement("w:tblHeader"))
        tr0Pr.append(OxmlElement("w:cantSplit"))

        for i, h in enumerate(headers):
            c = t.rows[0].cells[i]
            c.text = ""
            par = c.paragraphs[0]
            par.paragraph_format.space_after = Pt(0)
            r = par.add_run(clean_md(h))
            r.font.bold = True
            r.font.size = Pt(fsize + 0.3)
            r.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)
            shade_cell(c, NAVY_HEX)
            cell_margins(c)

        for j, row in enumerate(rows):
            tr = t.add_row()
            trPr = tr._tr.get_or_add_trPr()
            trPr.append(OxmlElement("w:cantSplit"))
            cells = tr.cells
            for i in range(ncols):
                val = row[i] if i < len(row) else ""
                cells[i].text = ""
                par = cells[i].paragraphs[0]
                par.paragraph_format.space_after = Pt(0)
                par.paragraph_format.line_spacing = 1.06
                r = par.add_run(clean_md(val))
                r.font.size = Pt(fsize)
                if i == 0:
                    r.font.bold = True
                if j % 2 == 1:
                    shade_cell(cells[i], LIGHT_HEX)
                cell_margins(cells[i])
        set_table_fixed(t, widths)
        self.d.add_paragraph().paragraph_format.space_after = Pt(2)

    def save(self, path):
        self.d.save(path)


def build_docx_from_md():
    doc = Doc()

    # Portada
    p = doc.d.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("UNIVERSIDAD IBEROAMERICANA LEÓN")
    r.font.size = Pt(11)
    r.font.bold = True
    r.font.color.rgb = GREY
    p.paragraph_format.space_after = Pt(2)

    p = doc.d.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("Ingeniería de Software · Ing. Alejandro Montes · 4 de noviembre de 2026")
    r.font.size = Pt(9.5)
    r.font.color.rgb = GREY
    p.paragraph_format.space_after = Pt(10)

    p = doc.d.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("PRÁCTICA GUIADA: PLAN DE INCREMENTOS")
    r.font.size = Pt(15.5)
    r.font.bold = True
    r.font.color.rgb = NAVY
    p.paragraph_format.space_after = Pt(4)

    p = doc.d.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("COTIZA-ELEV — Planificación Incremental (Fase A), Desarrollo por Ramas (Fase B) y Verificación y Regresión (Fase C)")
    r.font.size = Pt(10.5)
    r.font.italic = True
    r.font.color.rgb = RGBColor(0x2E, 0x5C, 0x9A)
    para_border(p, "bottom", "C9D6E8", 8, 6)
    p.paragraph_format.space_after = Pt(10)

    doc.table(["Campo", "Detalle del Entregable"], [
        ["Proyecto", "COTIZA-ELEV — Sistema Web de Gestión y Seguimiento de Cotizaciones para Elevadores (ELEVESE)."],
        ["Integrantes y roles", "• Orlando Villalobos Gutiérrez (Líder técnico, Arquitecto y Validador)\n• Diego Emilio Alférez Vallejo (Operador de IA, Desarrollador y Documentador)"],
        ["Repositorio GitHub", "https://github.com/OrlandoVillalobos-AI/COTIZA-ELEV (sincronizado con DiegoAlferez-AI/COTIZA-ELEV)"],
        ["Tags y ramas publicadas", "Tags en main: v0.1.0, v0.2.0, v0.3.0, v0.4.0, v0.5.0 · Ramas: incremento-2, incremento-3, incremento-4, incremento-5"],
        ["Resultado de regresión", "78 / 78 pruebas automatizadas aprobadas (100 %) ejecutadas con php tests/run_tests.php."],
    ])

    with open(PLAN_MD, "r", encoding="utf-8") as f:
        lines = f.read().splitlines()

    i = 0
    n = len(lines)
    while i < n:
        line = lines[i].strip()
        if not line or line == "---":
            i += 1
            continue
        if line.startswith("# "):
            i += 1
            continue
        if line.startswith("## "):
            doc.h1(line[3:], page_break_before=False)
            i += 1
            continue
        if line.startswith("### "):
            doc.h2(line[4:])
            i += 1
            continue
        if line.startswith("#### ") or line.startswith("##### "):
            lvl = 5 if line.startswith("##### ") else 4
            doc.h3(line[lvl + 1:])
            i += 1
            continue
        if line.startswith("|"):
            tbl_lines = []
            while i < n and lines[i].strip().startswith("|"):
                tbl_lines.append(lines[i].strip())
                i += 1
            if len(tbl_lines) >= 2:
                headers = [c.strip() for c in tbl_lines[0].strip("|").split("|")]
                rows = []
                for tl in tbl_lines[2:]:
                    cols = [c.strip() for c in tl.strip("|").split("|")]
                    rows.append(cols)
                doc.table(headers, rows)
            continue
        if line.startswith("- "):
            doc.bullet(line[2:])
            i += 1
            continue
        if line.startswith("> "):
            doc.p(line[2:], italic=True)
            i += 1
            continue
        doc.p(line)
        i += 1

    os.makedirs(OUT_DIR, exist_ok=True)
    doc.save(DOCX_OUT)
    print(f"Generado DOCX: {DOCX_OUT}")

    subprocess.run(
        ["soffice", "--headless", "--convert-to", "pdf", "--outdir", OUT_DIR, DOCX_OUT],
        check=True,
    )
    shutil.copy2(PDF_OUT, PDF_ROOT)
    print(f"Generado PDF: {PDF_OUT} y copia en {PDF_ROOT}")


def build_html_from_md():
    with open(PLAN_MD, "r", encoding="utf-8") as f:
        md_lines = f.read().splitlines()

    html_body = []
    i = 0
    n = len(md_lines)

    def fmt_inline(s):
        s = s.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")
        s = re.sub(r"\*\*(.*?)\*\*", r"<strong>\1</strong>", s)
        s = re.sub(r"`(.*?)`", r"<code>\1</code>", s)
        s = re.sub(r"\*(.*?)\*", r"<em>\1</em>", s)
        return s

    while i < n:
        line = md_lines[i].strip()
        if not line:
            i += 1
            continue
        if line == "---":
            html_body.append("<hr/>")
            i += 1
            continue
        if line.startswith("# "):
            html_body.append(f"<h1>{fmt_inline(line[2:])}</h1>")
            i += 1
            continue
        if line.startswith("## "):
            html_body.append(f"<h2>{fmt_inline(line[3:])}</h2>")
            i += 1
            continue
        if line.startswith("### "):
            html_body.append(f"<h3>{fmt_inline(line[4:])}</h3>")
            i += 1
            continue
        if line.startswith("#### ") or line.startswith("##### "):
            lvl = 5 if line.startswith("##### ") else 4
            html_body.append(f"<h4>{fmt_inline(line[lvl+1:])}</h4>")
            i += 1
            continue
        if line.startswith("|"):
            tbl_lines = []
            while i < n and md_lines[i].strip().startswith("|"):
                tbl_lines.append(md_lines[i].strip())
                i += 1
            if len(tbl_lines) >= 2:
                headers = [fmt_inline(c.strip()) for c in tbl_lines[0].strip("|").split("|")]
                html_body.append("<table><thead><tr>" + "".join(f"<th>{h}</th>" for h in headers) + "</tr></thead><tbody>")
                for tl in tbl_lines[2:]:
                    cols = [fmt_inline(c.strip()) for c in tl.strip("|").split("|")]
                    html_body.append("<tr>" + "".join(f"<td>{c}</td>" for c in cols) + "</tr>")
                html_body.append("</tbody></table>")
            continue
        if line.startswith("- "):
            html_body.append("<ul>")
            while i < n and md_lines[i].strip().startswith("- "):
                html_body.append(f"<li>{fmt_inline(md_lines[i].strip()[2:])}</li>")
                i += 1
            html_body.append("</ul>")
            continue
        if line.startswith("> "):
            html_body.append(f"<blockquote>{fmt_inline(line[2:])}</blockquote>")
            i += 1
            continue
        html_body.append(f"<p>{fmt_inline(line)}</p>")
        i += 1

    html_doc = f"""<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<title>Práctica Guiada: Plan de Incrementos — COTIZA-ELEV</title>
<style>
  body {{ font-family: Arial, Helvetica, sans-serif; color: #1e293b; max-width: 1120px; margin: 28px auto; padding: 0 20px; line-height: 1.5; }}
  h1 {{ color: #1f3864; border-bottom: 3px solid #1f3864; padding-bottom: 8px; font-size: 24px; }}
  h2 {{ color: #1f3864; border-bottom: 2px solid #cbd5e1; padding-bottom: 5px; margin-top: 28px; font-size: 19px; }}
  h3 {{ color: #2e5c9a; margin-top: 20px; font-size: 16px; }}
  h4 {{ color: #1f3864; margin-top: 14px; font-size: 14px; }}
  table {{ border-collapse: collapse; width: 100%; margin: 12px 0 18px 0; font-size: 12.5px; }}
  th {{ background: #1f3864; color: #ffffff; text-align: left; padding: 7px 9px; border: 1px solid #cbd5e1; }}
  td {{ padding: 6px 9px; border: 1px solid #cbd5e1; vertical-align: top; }}
  tr:nth-child(even) td {{ background: #f8fafc; }}
  code {{ background: #f1f5f9; padding: 1px 5px; border-radius: 4px; font-family: monospace; font-size: 12px; }}
  blockquote {{ background: #edf2f9; border-left: 4px solid #2e5c9a; margin: 10px 0; padding: 8px 14px; }}
</style>
</head>
<body>
{"".join(html_body)}
</body>
</html>
"""
    with open(HTML_OUT, "w", encoding="utf-8") as f:
        f.write(html_doc)
    print(f"Generado HTML: {HTML_OUT}")


if __name__ == "__main__":
    build_docx_from_md()
    build_html_from_md()
