#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Genera el Documento de Requerimientos (COTIZA-ELEV) en .docx -> .pdf.

Uso:  python3 build_doc.py [paginas.json]
      Si se pasa paginas.json (mapa etiqueta->pagina) se rellenan los numeros
      del indice.
"""
import json
import os
import sys
from io import BytesIO

import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
from matplotlib.patches import FancyBboxPatch, FancyArrowPatch

from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Pt, RGBColor, Emu

NAVY = RGBColor(0x1F, 0x38, 0x64)
NAVY_HEX = "1F3864"
ACCENT_HEX = "2E5C9A"
LIGHT_HEX = "EDF2F9"
GREY = RGBColor(0x59, 0x59, 0x59)
BODY_FONT = "Arial"

HERE = os.path.dirname(os.path.abspath(__file__))
OUT_DIR = os.path.join(os.path.dirname(HERE), "01_Entregables")
DOCX = os.path.join(OUT_DIR, "Entrega_Actualizada_COTIZA-ELEV_v3.docx")


# ---------------------------------------------------------------- utilidades XML
def shade_cell(cell, hex_fill):
    tcPr = cell._tc.get_or_add_tcPr()
    shd = OxmlElement("w:shd")
    shd.set(qn("w:val"), "clear")
    shd.set(qn("w:color"), "auto")
    shd.set(qn("w:fill"), hex_fill)
    tcPr.append(shd)


def cell_margins(cell, top=60, bottom=60, left=90, right=90):
    tcPr = cell._tc.get_or_add_tcPr()
    mar = OxmlElement("w:tcMar")
    for tag, val in (("top", top), ("left", left), ("bottom", bottom), ("right", right)):
        el = OxmlElement("w:" + tag)
        el.set(qn("w:w"), str(val))
        el.set(qn("w:type"), "dxa")
        mar.append(el)
    tcPr.append(mar)


def para_border(paragraph, position="bottom", color=NAVY_HEX, size=12, space=4):
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


# ---------------------------------------------------------------- figuras
def figura_flujo():
    etapas = [
        ("1. El técnico detecta\nla pieza o el servicio", "Módulo E",
         "En sitio: equipo, pieza\no servicio requerido"),
        ("2. Alta de la\nsolicitud", "Módulo E",
         "Cliente, activo, pieza,\ncantidad, urgencia y nota"),
        ("3. El administrador\nde ventas la recibe", "Módulo E / F",
         "Bandeja de pendientes:\nquién la levantó y cuándo"),
        ("4. Cotización con el\ncatálogo vigente", "Módulo F",
         "Partidas, subtotal,\ndescuento, IVA y total"),
        ("5. Propuesta en PDF\ny seguimiento", "Módulo G",
         "Folio, plantilla\ncorporativa y estado\ncomercial"),
    ]
    fig, ax = plt.subplots(figsize=(11.5, 3.3))
    ax.set_xlim(0, 11.5)
    ax.set_ylim(0, 3.3)
    ax.axis("off")
    w, h, y = 2.02, 1.25, 1.45
    for i, (titulo, modulo, detalle) in enumerate(etapas):
        x = 0.25 + i * 2.27
        caja = FancyBboxPatch((x, y), w, h, boxstyle="round,pad=0.06,rounding_size=0.10",
                              linewidth=1.2, edgecolor="#1F3864", facecolor="#EDF2F9")
        ax.add_patch(caja)
        ax.text(x + w / 2, y + h - 0.26, titulo, ha="center", va="center",
                fontsize=9.5, fontweight="bold", color="#1F3864")
        ax.text(x + w / 2, y + 0.36, detalle, ha="center", va="center",
                fontsize=8.2, color="#333333")
        ax.text(x + w / 2, y + h + 0.28, modulo, ha="center", va="center",
                fontsize=7.5, color="#7A7A7A")
        if i < len(etapas) - 1:
            ax.add_patch(FancyArrowPatch((x + w + 0.02, y + h / 2), (x + w + 0.23, y + h / 2),
                                         arrowstyle="-|>", mutation_scale=13,
                                         linewidth=1.2, color="#2E5C9A"))
    ax.text(5.75, 0.35, "Todo el flujo queda registrado en una sola base de datos centralizada, "
                        "sin llamadas ni avisos por mensaje",
            ha="center", va="center", fontsize=8.5, style="italic", color="#595959")
    fig.tight_layout()
    buf = BytesIO()
    fig.savefig(buf, format="png", dpi=200, bbox_inches="tight", facecolor="white")
    plt.close(fig)
    buf.seek(0)
    return buf


def figura_modulos(modulos):
    """modulos: lista de (titulo, rango de requerimientos)."""
    n = len(modulos)
    cols = 4
    filas = (n + cols - 1) // cols
    alto_celda = 1.62
    fig, ax = plt.subplots(figsize=(11.0, 1.35 + alto_celda * filas))
    ancho_total = 11.0
    ax.set_xlim(0, ancho_total)
    ax.set_ylim(0, 1.35 + alto_celda * filas)
    ax.axis("off")
    ax.text(ancho_total / 2, 1.35 + alto_celda * filas - 0.32,
            "Base de datos centralizada (clientes, activos, solicitudes, catálogo, cotizaciones)",
            ha="center", va="center", fontsize=8.6, color="#FFFFFF",
            bbox=dict(boxstyle="round,pad=0.45", facecolor="#1F3864", edgecolor="none"))
    bw, bh = 2.42, 1.30
    for i, (titulo, rango) in enumerate(modulos):
        col, fila = i % cols, i // cols
        x = 0.35 + col * 2.70
        y = 2.35 - fila * alto_celda
        ax.add_patch(FancyBboxPatch((x, y), bw, bh,
                                    boxstyle="round,pad=0.05,rounding_size=0.10",
                                    linewidth=1.2, edgecolor="#2E5C9A", facecolor="#EDF2F9"))
        ax.text(x + bw / 2, y + bh - 0.42, titulo, ha="center", va="center",
                fontsize=8.0, fontweight="bold", color="#1F3864", linespacing=1.35)
        ax.text(x + bw / 2, y + 0.28, rango, ha="center", va="center",
                fontsize=8.0, color="#595959")
        ax.add_patch(FancyArrowPatch((x + bw / 2, y + bh + 0.02), (x + bw / 2, y + bh + 0.35),
                                     arrowstyle="-|>", mutation_scale=10,
                                     linewidth=0.9, color="#8FA8C8"))
    fig.tight_layout()
    buf = BytesIO()
    fig.savefig(buf, format="png", dpi=200, bbox_inches="tight", facecolor="white")
    plt.close(fig)
    buf.seek(0)
    return buf


# ---------------------------------------------------------------- constructor
class Doc:
    def __init__(self):
        self.d = Document()
        self.toc = []
        self._setup_styles()
        self._setup_footer()

    # -- estilos base
    def _setup_styles(self):
        st = self.d.styles["Normal"]
        st.font.name = BODY_FONT
        st.font.size = Pt(10.5)
        st.element.rPr.rFonts.set(qn("w:eastAsia"), BODY_FONT)
        pf = st.paragraph_format
        pf.line_spacing = 1.15
        pf.space_after = Pt(6)
        for s in self.d.sections:
            s.top_margin = Cm(2.0)
            s.bottom_margin = Cm(1.9)
            s.left_margin = Cm(2.0)
            s.right_margin = Cm(2.0)

    def _setup_footer(self):
        sec = self.d.sections[0]
        p = sec.footer.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        r = p.add_run("COTIZA-ELEV · Entrega actualizada v3.0 · Página ")
        r.font.size = Pt(8)
        r.font.color.rgb = GREY
        add_field(p, "PAGE")
        r2 = p.add_run(" de ")
        r2.font.size = Pt(8)
        r2.font.color.rgb = GREY
        add_field(p, "NUMPAGES")
        for run in p.runs:
            run.font.size = Pt(8)
            run.font.color.rgb = GREY
        settings = self.d.settings.element
        uf = OxmlElement("w:updateFields")
        uf.set(qn("w:val"), "true")
        settings.append(uf)

    # -- elementos
    def h1(self, texto, numero=None, toc=True):
        etiqueta = ("%s. %s" % (numero, texto)) if numero is not None else texto
        p = self.d.add_paragraph()
        p.paragraph_format.space_before = Pt(16)
        p.paragraph_format.space_after = Pt(8)
        r = p.add_run(etiqueta)
        r.font.size = Pt(14)
        r.font.bold = True
        r.font.color.rgb = NAVY
        para_border(p, "bottom", NAVY_HEX, 8, 3)
        keep_with_next(p)
        if toc:
            self.toc.append(etiqueta)
        return p

    def h2(self, texto):
        p = self.d.add_paragraph()
        p.paragraph_format.space_before = Pt(11)
        p.paragraph_format.space_after = Pt(5)
        r = p.add_run(texto)
        r.font.size = Pt(11.5)
        r.font.bold = True
        r.font.color.rgb = RGBColor(0x2E, 0x5C, 0x9A)
        keep_with_next(p)
        return p

    def h3(self, texto):
        p = self.d.add_paragraph()
        p.paragraph_format.space_before = Pt(8)
        p.paragraph_format.space_after = Pt(3)
        r = p.add_run(texto)
        r.font.size = Pt(10.5)
        r.font.bold = True
        r.font.color.rgb = RGBColor(0x33, 0x33, 0x33)
        keep_with_next(p)
        return p

    def p(self, texto, align="justify", size=10.5, italic=False, space_after=6):
        par = self.d.add_paragraph()
        par.alignment = {"justify": WD_ALIGN_PARAGRAPH.JUSTIFY,
                         "center": WD_ALIGN_PARAGRAPH.CENTER,
                         "left": WD_ALIGN_PARAGRAPH.LEFT}[align]
        par.paragraph_format.space_after = Pt(space_after)
        r = par.add_run(texto)
        r.font.size = Pt(size)
        r.font.italic = italic
        return par

    def nota(self, texto):
        par = self.d.add_paragraph()
        par.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        par.paragraph_format.left_indent = Cm(0.4)
        par.paragraph_format.space_before = Pt(2)
        par.paragraph_format.space_after = Pt(8)
        r = par.add_run(texto)
        r.font.size = Pt(9.5)
        r.font.italic = True
        r.font.color.rgb = GREY
        return par

    def bullets(self, items, numbered=False):
        for it in items:
            par = self.d.add_paragraph(style="List Number" if numbered else "List Bullet")
            par.paragraph_format.space_after = Pt(3)
            par.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
            if isinstance(it, tuple):
                r = par.add_run(it[0] + " ")
                r.font.bold = True
                r.font.size = Pt(10.5)
                r2 = par.add_run(it[1])
                r2.font.size = Pt(10.5)
            else:
                r = par.add_run(it)
                r.font.size = Pt(10.5)
        return None

    def table(self, headers, rows, widths, font_size=9.5, header_size=9.5, zebra=True,
              align_center_cols=()):
        t = self.d.add_table(rows=1, cols=len(headers))
        t.style = "Table Grid"
        t.alignment = WD_TABLE_ALIGNMENT.CENTER
        for i, h in enumerate(headers):
            c = t.rows[0].cells[i]
            c.text = ""
            par = c.paragraphs[0]
            par.paragraph_format.space_after = Pt(0)
            r = par.add_run(h)
            r.font.bold = True
            r.font.size = Pt(header_size)
            r.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)
            shade_cell(c, NAVY_HEX)
            cell_margins(c)
        for j, row in enumerate(rows):
            cells = t.add_row().cells
            for i, val in enumerate(row):
                cells[i].text = ""
                par = cells[i].paragraphs[0]
                par.paragraph_format.space_after = Pt(0)
                par.paragraph_format.line_spacing = 1.0
                if i in align_center_cols:
                    par.alignment = WD_ALIGN_PARAGRAPH.CENTER
                partes = str(val).split("\n")
                for k, parte in enumerate(partes):
                    r = par.add_run(parte)
                    r.font.size = Pt(font_size)
                    if i == 0 and len(headers) > 2:
                        r.font.bold = True
                    if k < len(partes) - 1:
                        r.add_break()
                if zebra and j % 2 == 1:
                    shade_cell(cells[i], LIGHT_HEX)
                cell_margins(cells[i])
        set_table_fixed(t, [w * 17.2 / sum(widths) for w in widths])
        self.d.add_paragraph().paragraph_format.space_after = Pt(2)
        return t

    def caption(self, texto):
        par = self.d.add_paragraph()
        par.alignment = WD_ALIGN_PARAGRAPH.CENTER
        par.paragraph_format.space_after = Pt(10)
        r = par.add_run(texto)
        r.font.size = Pt(9)
        r.font.italic = True
        r.font.color.rgb = GREY
        return par

    def figure(self, buf, caption, width_cm=16.4):
        par = self.d.add_paragraph()
        par.alignment = WD_ALIGN_PARAGRAPH.CENTER
        par.paragraph_format.space_before = Pt(6)
        par.paragraph_format.space_after = Pt(2)
        par.add_run().add_picture(buf, width=Cm(width_cm))
        self.caption(caption)

    def page_break(self):
        self.d.add_paragraph().add_run().add_break(WD_BREAK.PAGE)

    def save(self, path):
        self.d.save(path)


def portada(doc, datos):
    p = doc.d.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("DESARROLLO DE APLICACIONES Y SERVICIOS VIRTUALES")
    r.font.size = Pt(11)
    r.font.bold = True
    r.font.color.rgb = GREY
    p.paragraph_format.space_after = Pt(2)

    p = doc.d.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("Ingeniería de Software · Práctica 1: definición de requerimientos con apoyo de la IA")
    r.font.size = Pt(9.5)
    r.font.color.rgb = GREY
    p.paragraph_format.space_after = Pt(24)

    p = doc.d.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("DOCUMENTO DE ANÁLISIS Y\nESPECIFICACIÓN DE REQUERIMIENTOS")
    r.font.size = Pt(21)
    r.font.bold = True
    r.font.color.rgb = NAVY
    p.paragraph_format.space_after = Pt(10)

    p = doc.d.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run(datos["proyecto"])
    r.font.size = Pt(12.5)
    r.font.italic = True
    r.font.color.rgb = RGBColor(0x2E, 0x5C, 0x9A)
    para_border(p, "bottom", "C9D6E8", 8, 8)
    p.paragraph_format.space_after = Pt(26)

    doc.table(["Concepto", "Detalle"], [
        ("Empresa cliente", datos["cliente"]),
        ("Empresa desarrolladora", datos["desarrolladora"]),
        ("Nombre del proyecto", datos["proyecto"]),
        ("Tipo de aplicación", datos["tipo"]),
        ("Materia", "Desarrollo de Aplicaciones y Servicios Virtuales"),
        ("Docente", "Ing. Alejandro Montes"),
        ("Periodo de desarrollo", "8 semanas · equipo de 2 integrantes"),
        ("Versión del documento", datos["version"]),
        ("Fecha de entrega", datos["fecha"]),
    ], [4.6, 12.4], font_size=10, header_size=10)

    doc.d.add_paragraph()
    p = doc.d.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run(datos["lugar"])
    r.font.size = Pt(10)
    r.font.color.rgb = GREY
    doc.page_break()


def indice(doc, page_map):
    doc.h1("Índice", toc=False)
    filas = []
    for etiqueta in doc.toc:
        filas.append((etiqueta, str(page_map.get(etiqueta, "—"))))
    doc.table(["Sección", "Página"], filas, [14.4, 2.6], font_size=10,
              header_size=10, align_center_cols=(1,))
    doc.page_break()


def main():
    page_map = {}
    if len(sys.argv) > 1 and os.path.exists(sys.argv[1]):
        with open(sys.argv[1], encoding="utf-8") as fh:
            page_map = json.load(fh)

    import content_a
    import content_b

    content_a.validar()
    content_b.validar()
    print("Validación: %d RF, %d RNF, %d HU, %d CA, %d filas de trazabilidad"
          % (content_a.N_RF, content_a.N_RNF, content_b.N_HU, content_b.N_CA,
             len(content_b.TRAZA)))

    doc = Doc()
    datos = content_a.DATOS
    portada(doc, datos)

    # el indice se arma en dos pasos: primero recolectamos las etiquetas
    doc.toc = []
    cuerpo = Doc.__new__(Doc)  # no-op para claridad
    # Construimos el cuerpo en un Doc auxiliar solo para conocer las etiquetas
    aux = Doc()
    content_a.build(aux)
    content_b.build(aux)
    etiquetas = list(aux.toc)

    doc.toc = etiquetas
    indice(doc, page_map)

    doc.toc = []
    content_a.build(doc)
    content_b.build(doc)

    doc.save(DOCX)
    print("OK ->", DOCX)
    print("Secciones en el indice:", len(etiquetas))


if __name__ == "__main__":
    main()
