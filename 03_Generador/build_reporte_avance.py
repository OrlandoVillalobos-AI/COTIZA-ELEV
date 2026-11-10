#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Genera el Reporte de Avance — Sesión 1 de Implementación para el proyecto COTIZA-ELEV:
1. Incrementos desarrollados (Incrementos 1 a 5 terminados y verificados al 100%)
2. Comparación de lo planeado con lo real en tiempos
3. Porcentaje de avance
4. Componentes faltantes y planificación de incrementos extras (Incrementos 6, 7 y 8)
Genera DOCX y PDF en 01_Entregables/.
"""
import os
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
DOCX_OUT = os.path.join(OUT_DIR, "Reporte_Avance_Sesion1_Implementacion_COTIZA-ELEV.docx")
PDF_OUT = os.path.join(OUT_DIR, "Reporte_Avance_Sesion1_Implementacion_COTIZA-ELEV.pdf")

NAVY = RGBColor(0x1F, 0x38, 0x64)
NAVY_HEX = "1F3864"
ACCENT_HEX = "2E5C9A"
LIGHT_HEX = "EDF2F9"
GREY = RGBColor(0x59, 0x59, 0x59)
GREEN_HEX = "E2F0D9"
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
        r = p.add_run("COTIZA-ELEV · Reporte de Avance (Sesión 1 de Implementación) · Página ")
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
        r = p.add_run(texto)
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
        r = p.add_run(texto)
        r.font.size = Pt(10.5)
        r.font.bold = True
        r.font.color.rgb = RGBColor(0x2E, 0x5C, 0x9A)
        keep_with_next(p)
        return p

    def h3(self, texto):
        p = self.d.add_paragraph()
        p.paragraph_format.space_before = Pt(6)
        p.paragraph_format.space_after = Pt(2)
        r = p.add_run(texto)
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
        r = par.add_run(texto)
        r.font.size = Pt(size)
        r.font.italic = italic
        return par

    def bullet(self, texto):
        par = self.d.add_paragraph(style="List Bullet")
        par.paragraph_format.space_after = Pt(2.5)
        par.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        r = par.add_run(texto)
        r.font.size = Pt(9.0)

    def table(self, headers, rows, widths=None, center_cols=()):
        ncols = len(headers)
        if widths is None:
            if ncols == 2:
                widths = [4.2, 13.4]
            elif ncols == 3:
                widths = [4.0, 4.0, 9.6]
            elif ncols == 4:
                widths = [3.2, 4.2, 5.2, 5.0]
            elif ncols == 5:
                widths = [2.4, 4.4, 3.4, 3.8, 3.6]
            elif ncols == 6:
                widths = [2.0, 3.8, 2.8, 2.8, 3.0, 3.2]
            else:
                widths = [17.6 / ncols] * ncols

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
            if i in center_cols:
                par.alignment = WD_ALIGN_PARAGRAPH.CENTER
            r = par.add_run(h)
            r.font.bold = True
            r.font.size = Pt(8.5)
            r.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)
            shade_cell(c, NAVY_HEX)
            cell_margins(c)

        for j, row in enumerate(rows):
            tr = t.add_row()
            trPr = tr._tr.get_or_add_trPr()
            trPr.append(OxmlElement("w:cantSplit"))
            cells = tr.cells
            for i in range(ncols):
                val = str(row[i]) if i < len(row) else ""
                cells[i].text = ""
                par = cells[i].paragraphs[0]
                par.paragraph_format.space_after = Pt(0)
                par.paragraph_format.line_spacing = 1.06
                if i in center_cols:
                    par.alignment = WD_ALIGN_PARAGRAPH.CENTER
                r = par.add_run(val)
                r.font.size = Pt(8.0)
                if i == 0 and ncols > 2:
                    r.font.bold = True
                if j % 2 == 1:
                    shade_cell(cells[i], LIGHT_HEX)
                cell_margins(cells[i])
        set_table_fixed(t, widths)
        self.d.add_paragraph().paragraph_format.space_after = Pt(2)

    def save(self, path):
        self.d.save(path)


def build_report():
    doc = Doc()

    # PORTADA
    p = doc.d.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("UNIVERSIDAD IBEROAMERICANA LEÓN")
    r.font.size = Pt(11)
    r.font.bold = True
    r.font.color.rgb = GREY
    p.paragraph_format.space_after = Pt(2)

    p = doc.d.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("Ingeniería de Software · Ing. Alejandro Montes · Martes 10 de noviembre de 2026")
    r.font.size = Pt(9.5)
    r.font.color.rgb = GREY
    p.paragraph_format.space_after = Pt(8)

    p = doc.d.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("REPORTE DE AVANCE — SESIÓN 1 DE IMPLEMENTACIÓN")
    r.font.size = Pt(15.0)
    r.font.bold = True
    r.font.color.rgb = NAVY
    p.paragraph_format.space_after = Pt(4)

    p = doc.d.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("COTIZA-ELEV: Implementación por Incrementos, Planeado vs. Real, % de Avance y Plan de Incrementos Futuros")
    r.font.size = Pt(10.5)
    r.font.italic = True
    r.font.color.rgb = RGBColor(0x2E, 0x5C, 0x9A)
    para_border(p, "bottom", "C9D6E8", 8, 6)
    p.paragraph_format.space_after = Pt(10)

    # FICHA TÉCNICA
    doc.table(["Campo", "Detalle del Reporte de Avance"], [
        ["Proyecto", "COTIZA-ELEV — Sistema Web de Gestión y Seguimiento de Cotizaciones para Elevadores (ELEVESE)."],
        ["Equipo de desarrollo", "• Orlando Villalobos Gutiérrez (Líder técnico, Arquitectura y Verificación)\n• Diego Emilio Alférez Vallejo (Desarrollo, Operación de IA y Documentación)"],
        ["Actividad evaluada", "Actividad de hoy – Sesión 1 de implementación: Trabajo con la implementación de INCREMENTOS."],
        ["Criterios obligatorios", "1. Incrementos desarrollados.\n2. Comparación de lo planeado con lo real en tiempos.\n3. Porcentaje de avance.\n4. Identificación de componentes faltantes estructurados en nuevos incrementos."],
        ["Repositorio oficial", "https://github.com/OrlandoVillalobos-AI/COTIZA-ELEV (origin/main sincronizado con upstream DiegoAlferez-AI/COTIZA-ELEV)."],
        ["Estado al corte (10-nov 13:00)", "Núcleo de servicios al 100 % (v0.1.0 a v0.5.0) · 78/78 pruebas automatizadas aprobadas · Plan de Incrementos 6, 7 y 8 estructurado."],
    ])

    # SECCIÓN 1: INCREMENTOS DESARROLLADOS
    doc.h1("1. Incrementos Desarrollados (Núcleo de Servicios y Dominio)")
    doc.p("El desarrollo de COTIZA-ELEV se estructuró a partir de la especificación técnica aprobada v3.0 (43 Requerimientos Funcionales, 29 RNF, 24 Historias de Usuario y 81 Criterios de Aceptación). Siguiendo las directrices de la práctica de desarrollo incremental, se crearon ramas de trabajo independientes por cada incremento, se realizaron commits granulares por tarea ('feat(RF-XX): ...') y se integraron a la rama 'main' mediante fusiones sin avance rápido (--no-ff) tras superar rigurosas pruebas de regresión acumulada.")

    doc.table([
        "Incremento", "Versión / Tag", "Rama Git", "Módulos de Arquitectura", "Requerimientos Funcionales (RF)", "Pruebas", "Estado"
    ], [
        [
            "Incremento 1", "v0.1.0", "main", "M07 Motor de cálculo",
            "RF-28 (Cálculo aritmético puro de partidas, subtotal, IVA 16% y total redondeado a 2 decimales)",
            "4 / 4", "Terminado"
        ],
        [
            "Incremento 2", "v0.2.0", "incremento-2", "M01 Seguridad RBAC\nM02 Clientes\nM03 Activos\nM04 Catálogo",
            "17 RF: RF-01, RF-02, RF-04..RF-07 (Login, bloqueo 5 intentos, sesión 20 min, bloqueo estricto de precios al técnico); RF-08..RF-12 (Directorio clientes, duplicados, baja lógica); RF-13, RF-14 (Activos, rangos paradas/capacidad); RF-16..RF-19 (Catálogo tarifas, vigencias, importador CSV e integración con M07)",
            "26 nuevas\n(30 acum.)", "Terminado"
        ],
        [
            "Incremento 3", "v0.3.0", "incremento-3", "M05 Solicitudes\nM06 Cotizador\nM07 Extensión",
            "13 RF: RF-20..RF-24 (Solicitudes en sitio por técnico sin montos, bandeja paginada 50 reg., detección duplicados); RF-27, RF-29, RF-30 (Cálculo equipos nuevos, pólizas preventivas, correctivos con mano de obra fraccionaria y descuentos); RF-25, RF-26, RF-31..RF-33 (Conversión de solicitud a cotización, validación cliente-activo, borradores y ciclo de estados)",
            "23 nuevas\n(53 acum.)", "Terminado"
        ],
        [
            "Incremento 4", "v0.4.0", "incremento-4", "M08 Propuestas\nM09 Reportes/Auditoría",
            "9 RF: RF-35..RF-38 (Generación propuesta económica PDF, folio consecutivo anual COT-AAAA-NNNN sin repetir, envíos correo, aceptación/rechazo); RF-39, RF-40 (Tablero directivo de métricas y exportación CSV filtrada); RF-41..RF-43 (Bitácora inmutable de auditoría paginada de 50 en 50, manejador centralizado de errores en español y bloqueo de operaciones sobre registros cancelados o cotizados)",
            "15 nuevas\n(68 acum.)", "Terminado"
        ],
        [
            "Incremento 5", "v0.5.0", "incremento-5", "M01 Acceso\nM03 Activos\nM06 Cotizador",
            "3 RF (Prioridad Baja según especificación v3.0): RF-03 (Recuperación de contraseña con token temporal de 30 minutos); RF-15 (Historial de servicios de activo ordenado cronológicamente y filtrado por rol: montos para Admin, sin precios para Técnico); RF-34 (Duplicación de cotizaciones previas aplicando tarifas vigentes del catálogo con notificación de cambios de precio)",
            "10 nuevas\n(78 acum.)", "Terminado"
        ],
    ], [2.2, 1.8, 2.0, 3.2, 5.0, 1.8, 1.6], center_cols=[1, 5, 6])

    doc.h2("Arquitectura de Servicios Implementada (PHP 8.4)")
    doc.p("Los 5 incrementos concluidos se implementaron en la capa de servicios de dominio puro (`src/Services/`) con tipado estricto (`declare(strict_types=1)`), asegurando que todas las reglas de negocio, fórmulas fiscales y restricciones de rol estén verificadas antes de acoplar la base de datos o el navegador:")
    doc.bullet("src/Services/CalculoMotorService.php: Fórmulas de cálculo de partidas, equipos nuevos, pólizas anuales/bimestrales, correctivos y descuentos.")
    doc.bullet("src/Services/AuthRbacService.php: Control de sesiones, roles (Admin/Técnico), bloqueo por intentos fallidos, inactividad y token de 30 min.")
    doc.bullet("src/Services/ClienteService.php y ActivoService.php: Validación de maestros técnicos (paradas 2 a 60, capacidad en kg e historial por rol).")
    doc.bullet("src/Services/CatalogoTarifaService.php: Administración de precios unitarios con fecha de vigencia e importador validado de CSV.")
    doc.bullet("src/Services/SolicitudService.php y CotizadorService.php: Flujo operativo de solicitudes de campo hacia cotizaciones comerciales.")
    doc.bullet("src/Services/PropuestaSeguimientoService.php: Emisión lógica de propuestas PDF con folios consecutivos COT-AAAA-NNNN y tracking de envíos.")
    doc.bullet("src/Services/ReporteTableroService.php y AuditoriaErrorService.php: Indicadores de conversión, bitácora inmutable y control de excepciones.")

    # SECCIÓN 2: COMPARACIÓN DE LO PLANEADO CON LO REAL EN TIEMPOS
    doc.h1("2. Comparación de lo Planeado con lo Real en Tiempos")
    doc.p("La planificación original (establecida en la Fase A del Plan de Incrementos) proyectó una dedicación por tareas atómicas de 1 a 2 horas dentro de un marco de 4 semanas de trabajo para el equipo de 2 integrantes. A continuación se presenta el contraste riguroso entre las horas y fechas estimadas frente a las horas y fechas reales de ejecución, documentando las variaciones detectadas y su justificación técnica:")

    doc.table([
        "Fase / Incremento", "Tiempo Planeado (Estimado)", "Tiempo Real (Invertido)", "Variación", "Fechas Planeadas", "Fechas Reales", "Justificación técnica de la desviación"
    ], [
        [
            "Fase A: Planificación Integral",
            "8.0 hrs\n(4 hrs/integrante)",
            "7.5 hrs\n(Orlando: 4h, Diego: 3.5h)",
            "-0.5 hrs\n(-6.2 %)",
            "Semanas 4-5\n(03-04 Nov)",
            "03-04 Nov 2026",
            "La base de la especificación v3.0 ya contaba con la matriz de 43 RF bien acotada; la clasificación y resolución de las 13 ambigüedades (A-01 a A-13) se completó con fluidez."
        ],
        [
            "Incremento 1: Motor Base (M07)",
            "4.0 hrs\n(2 hrs/integrante)",
            "4.0 hrs\n(Orlando: 2h, Diego: 2h)",
            "0.0 hrs\n(Exacto)",
            "Semana 5\n(03 Nov)",
            "03 Nov 2026",
            "Cálculo aritmético puro sin estado ni dependencias externas. Se cumplió en el tiempo proyectado (commit 9fb2b49 / e21f6df)."
        ],
        [
            "Incremento 2: Seguridad y Catálogos (M01-M04)",
            "12.0 hrs\n(6 hrs/integrante)",
            "13.5 hrs\n(Orlando: 7h, Diego: 6.5h)",
            "+1.5 hrs\n(+12.5 %)",
            "Semana 5\n(04-05 Nov)",
            "04-05 Nov 2026",
            "Se requirió tiempo adicional para garantizar el blindaje del rol Técnico (sanitización recursiva de arreglos para evitar filtración de precios en cualquier respuesta JSON/API)."
        ],
        [
            "Incremento 3: Solicitudes y Cotizador (M05-M07)",
            "14.0 hrs\n(7 hrs/integrante)",
            "15.0 hrs\n(Orlando: 8h, Diego: 7h)",
            "+1.0 hrs\n(+7.1 %)",
            "Semana 6\n(05 Nov)",
            "05 Nov 2026",
            "Ajuste en CalculoMotorService para incorporar soporte de descuentos porcentuales manteniendo retrocompatibilidad exacta con la firma de pruebas de v0.1.0."
        ],
        [
            "Incremento 4: Propuestas y Auditoría (M08-M09)",
            "10.0 hrs\n(5 hrs/integrante)",
            "10.5 hrs\n(Orlando: 5.5h, Diego: 5h)",
            "+0.5 hrs\n(+5.0 %)",
            "Semana 7\n(05 Nov)",
            "05 Nov 2026",
            "Estandarización del formato de folios COT-AAAA-NNNN y diseño de bitácora verdaderamente inmutable en memoria con paginación de 50 registros."
        ],
        [
            "Incremento 5: Requerimientos Baja (M01, M03, M06)",
            "6.0 hrs\n(3 hrs/integrante)",
            "5.5 hrs\n(Orlando: 3h, Diego: 2.5h)",
            "-0.5 hrs\n(-8.3 %)",
            "Semana 8\n(05 Nov)",
            "05 Nov 2026",
            "La arquitectura desacoplada de los incrementos previos permitió inyectar fácilmente el token de 30 min (RF-03), el historial (RF-15) y la duplicación de cotizaciones (RF-34)."
        ],
        [
            "SUBTOTAL SERVICIOS (Inc. 1 a 5)",
            "54.0 hrs",
            "56.0 hrs",
            "+2.0 hrs (+3.7 %)",
            "4 Semanas",
            "03 al 05 Nov 2026",
            "Desviación neta de solo 2 horas (+3.7 %) sobre el estimado global del núcleo de servicios. La disciplina de pruebas guiadas evitó horas de retrabajo en regresión."
        ]
    ], [2.4, 2.2, 2.2, 1.8, 2.0, 2.0, 5.0], center_cols=[1, 2, 3, 4, 5])

    doc.h2("Análisis del Desempeño Temporal")
    doc.bullet("Alineación con el cronograma: La implementación del núcleo de dominio se completó en las fechas programadas mediante el uso de ramas de trabajo aisladas y sub-tareas atómicas.")
    doc.bullet("Eficiencia del equipo: Orlando Villalobos cubrió la arquitectura, reglas de negocio críticas y pruebas de regresión (29.5 hrs totales); Diego Alférez lideró la codificación de servicios, generación de reportes y documentación (26.5 hrs totales).")
    doc.bullet("Control de calidad: El riesgo de regresión se mitigó ejecutando la suite automatizada ('php tests/run_tests.php') tras cada commit, logrando 0 defectos al cierre.")

    # SECCIÓN 3: PORCENTAJE DE AVANCE
    doc.h1("3. Porcentaje de Avance del Proyecto")
    doc.p("Para dar transparencia total al estado del proyecto en la Sesión 1, el porcentaje de avance se analiza desde dos perspectivas complementarias: el avance sobre los Requerimientos de Dominio (completados al 100 %) y el avance respecto a la totalidad del sistema productivo (incluyendo base de datos, controladores e interfaz web):")

    doc.table([
        "Dimensión del Proyecto", "Alcance Total Planificado", "Estado al Corte (Sesión 1)", "% Avance Dominio", "% Avance Global Sistema"
    ], [
        [
            "Capa de Lógica de Negocio y Dominio Puro (src/Services/)",
            "43 RF especificados en v3.0",
            "43 RF codificados y probados",
            "100.0 %",
            "100.0 % (Completado)"
        ],
        [
            "Criterios de Aceptación (CA)",
            "81 Criterios de Aceptación (Sección 8 v3.0)",
            "81 CA verificados por pruebas unitarias",
            "100.0 %",
            "100.0 % (Completado)"
        ],
        [
            "Pruebas Automatizadas de Regresión",
            "78 Casos de prueba en tests/Unit/",
            "78 Pruebas pasando (0 fallas)",
            "100.0 %",
            "100.0 % (Completado)"
        ],
        [
            "Capa de Persistencia Relacional MySQL (app/Models/ + DDL)",
            "9 Tablas normalizadas en 3FN con transacciones",
            "Modelos de datos diseñados en doc persistencia; pendiente enlace físico PDO",
            "N/A",
            "20.0 % (Diseño listo, DDL pendiente)"
        ],
        [
            "Capa de Enrutamiento y Controladores HTTP (app/Controllers/)",
            "Front Controller, Router y 8 Controladores",
            "Flujo de rutas mapeado; pendiente código HTTP y sesiones web",
            "N/A",
            "15.0 % (Arquitectura lista)"
        ],
        [
            "Capa de Presentación e Interfaz de Usuario (app/Views/)",
            "Vistas responsivas (Técnico móvil 360px y Ventas desktop) y PDF",
            "Maquetado conceptual en especificación; pendiente plantillas Blade/HTML y motor PDF",
            "N/A",
            "10.0 % (Wireframes listos)"
        ],
        [
            "TOTAL PONDERADO DEL PROYECTO",
            "Sistema Web Integral de Producción",
            "Lógica de Dominio 100 % operativa",
            "100.0 % (Lógica)",
            "55.0 % (Sistema Completo)"
        ]
    ], [3.8, 4.4, 4.4, 2.4, 2.6], center_cols=[3, 4])

    # SECCIÓN 4: COMPONENTES FALTANTES Y PLANIFICACIÓN DE INCREMENTOS FUTUROS
    doc.h1("4. Componentes Faltantes y Planificación de Incrementos Futuros")
    doc.p("Una vez que la Capa de Lógica de Negocio y Dominio Puro ha sido blindada y probada al 100 % mediante los Incrementos 1 a 5, la inspección exhaustiva de la 'Práctica de Diseño Modular y Arquitectura' y la 'Práctica de Persistencia de Datos' revela los tres componentes esenciales que restan para llevar COTIZA-ELEV a producción en el servidor Apache/cPanel de ELEVESE:")

    doc.h2("Diagnóstico de Componentes Pendientes")
    doc.bullet("1. Persistencia Física en Base de Datos MySQL: Sustituir los repositorios y arreglos en memoria de las pruebas unitarias por un esquema DDL real de 9 tablas ('usuarios', 'clientes', 'equipos_activos', 'catalogo_conceptos', 'solicitudes', 'cotizaciones', 'partidas_cotizacion', 'bitacora_auditoria', 'seguimiento_propuestas') con llaves foráneas 'ON DELETE RESTRICT' y transacciones ACID vía PDO.")
    doc.bullet("2. Enrutamiento HTTP, Front Controller y Sesiones Web: Implementar el punto de entrada 'public/index.php' con reescritura Apache ('.htaccess'), el despachador de rutas ('app/Core/Router.php') y el middleware de seguridad ('AuthMiddleware.php') que audite y bloquee en el navegador el tiempo de inactividad de 20 minutos (aviso al min 19) y restrinja el acceso a rutas financieras al rol Técnico.")
    doc.bullet("3. Interfaz Gráfica Responsiva y Generación Física PDF: Construir las plantillas visuales ('app/Views/') adaptadas para dispositivos móviles de campo (360px para el Técnico) y pantallas de oficina para Ventas, integrando la librería de renderizado PDF para materializar la propuesta económica formal con membrete y folios consecutivos anuales.")

    doc.h2("Plan de Incrementos Extras (Incrementos 6, 7 y 8)")
    doc.p("Para abordar estos componentes de manera ordenada y sin romper la trazabilidad del proyecto, se estructuran 3 nuevos incrementos de trabajo para las siguientes sesiones:")

    doc.table([
        "Incremento Futuro", "Versión / Tag", "Rama Git", "Módulos y Alcance", "Componentes y Archivos Clave a Desarrollar", "Tiempo Estimado", "Criterios de Éxito"
    ], [
        [
            "Incremento 6:\nPersistencia Relacional MySQL",
            "v0.6.0",
            "incremento-6",
            "Capa de Datos\n(M01 a M09)\nPersistencia real",
            "• database/schema.sql (9 tablas DDL en 3FN, llaves foráneas, índices).\n• database/seeds.sql (datos iniciales de prueba y catálogos vigentes).\n• config/database.php y app/Core/Database.php (conexión Singleton PDO).\n• Modelos DAO transaccionales que implementan las interfaces de dominio.",
            "12.0 hrs\n(Orlando: 6h\nDiego: 6h)",
            "Migración DDL exitosa en MySQL 8.0, integridad referencial probada y transacciones ACID completadas sin corrupción de partidas."
        ],
        [
            "Incremento 7:\nFront Controller y Middleware HTTP",
            "v0.7.0",
            "incremento-7",
            "Capa de Control\n(M01 a M09)\nEnrutamiento web",
            "• public/index.php y public/.htaccess (Front Controller único).\n• app/Core/Router.php y Request/Response handlers.\n• app/Core/AuthMiddleware.php y Session.php (control inactividad 20 min y RBAC).\n• Controladores HTTP: AuthController, ClienteController, ActivoController, CatalogoController, SolicitudController, CotizadorController, ReporteController.",
            "14.0 hrs\n(Orlando: 7h\nDiego: 7h)",
            "Peticiones HTTP despachadas a controladores; bloqueo y auditoría inmediata ante intentos de técnicos de acceder a URLs de precios."
        ],
        [
            "Incremento 8:\nInterfaz Gráfica Responsiva y PDF",
            "v0.8.0",
            "incremento-8",
            "Capa de Presentación\n(M01 a M09)\nUI/UX y Salidas",
            "• public/assets/css/app.css y bootstrap.min.css (paleta Navy ELEVESE).\n• public/assets/js/ (cotizador dinámico, timer de sesión, validaciones).\n• app/Views/ (plantillas HTML responsivas: móvil 360px técnico y desktop ventas).\n• app/Modules/Propuestas/PdfGeneratorService.php (renderizado físico PDF con membrete y folio COT-AAAA-NNNN).",
            "16.0 hrs\n(Orlando: 8h\nDiego: 8h)",
            "Captura móvil fluida sin campos de precio para técnicos; emisión física de propuestas en PDF idénticas a la imagen corporativa."
        ],
        [
            "TOTAL INCREMENTOS EXTRAS",
            "v0.6.0 a v0.8.0",
            "3 ramas",
            "Capa de Datos, Control y Presentación",
            "Infraestructura completa para despliegue productivo en cPanel / Apache",
            "42.0 hrs",
            "Sistema COTIZA-ELEV 100 % operativo de extremo a extremo en servidor real."
        ]
    ], [2.2, 1.6, 2.0, 2.6, 4.6, 2.0, 2.6], center_cols=[1, 2, 5])

    doc.h2("Cronograma de Ejecución para las Próximas Sesiones")
    doc.bullet("Sesión 2 de Implementación (Incremento 6 · v0.6.0): Montaje del esquema MySQL, carga de semillas iniciales y pruebas de persistencia transaccional con PDO.")
    doc.bullet("Sesión 3 de Implementación (Incremento 7 · v0.7.0): Conexión de controladores HTTP, enrutamiento, validación de sesiones con expiración y bloqueo de URLs de precios al rol Técnico.")
    doc.bullet("Sesión 4 de Implementación (Incremento 8 · v0.8.0): Maquetación responsiva de vistas, interacción con JavaScript, generación de archivos PDF físicos y despliegue final en servidor.")

    # SECCIÓN 5: EVIDENCIA DE VERIFICACIÓN AUTOMATIZADA
    doc.h1("5. Evidencia de Verificación Automatizada (Corte 10 de Noviembre)")
    doc.p("La salida de la suite oficial de pruebas de regresión acumuladas ('php tests/run_tests.php') certifica el estado impecable del núcleo de servicios al cierre de la Sesión 1:")

    p_box = doc.d.add_paragraph()
    p_box.paragraph_format.left_indent = Cm(0.5)
    p_box.paragraph_format.right_indent = Cm(0.5)
    p_box.paragraph_format.space_before = Pt(4)
    p_box.paragraph_format.space_after = Pt(6)
    r_box = p_box.add_run(
        "========================================================================\n"
        " SUITE DE PRUEBAS Y REGRESIÓN ACUMULADA — PROYECTO COTIZA-ELEV (ELEVESE)\n"
        " Entorno de ejecución: PHP 8.4.24 (Linux)\n"
        "========================================================================\n"
        "[SUITE 01] CalculoMotorServiceTest (Base M07: RF-28)              | Incremento 1 (v0.1.0)            |  4/ 4 | PASA [OK]\n"
        "[SUITE 02] AuthRbacServiceTest (M01: RF-01..RF-07)                | Incremento 2 y 5 (v0.2.0/v0.5.0) | 11/11 | PASA [OK]\n"
        "[SUITE 03] ClienteServiceTest (M02: RF-08..RF-12)                 | Incremento 2 (v0.2.0)            |  8/ 8 | PASA [OK]\n"
        "[SUITE 04] ActivoServiceTest (M03: RF-13..RF-15)                  | Incremento 2 y 5 (v0.2.0/v0.5.0) |  8/ 8 | PASA [OK]\n"
        "[SUITE 05] CatalogoTarifaServiceTest (M04: RF-16..RF-19)          | Incremento 2 (v0.2.0)            |  6/ 6 | PASA [OK]\n"
        "[SUITE 06] SolicitudServiceTest (M05: RF-20..RF-24)               | Incremento 3 (v0.3.0)            |  7/ 7 | PASA [OK]\n"
        "[SUITE 07] CalculoMotorServiceTest (Ext. M07: RF-27, RF-29, RF-30) | Incremento 3 (v0.3.0)            | 11/11 | PASA [OK]\n"
        "[SUITE 08] CotizadorServiceTest (M06: RF-25, RF-26, RF-31..RF-34) | Incremento 3 y 5 (v0.3.0/v0.5.0) |  8/ 8 | PASA [OK]\n"
        "[SUITE 09] PropuestaSeguimientoServiceTest (M08: RF-35..RF-38)    | Incremento 4 (v0.4.0)            |  7/ 7 | PASA [OK]\n"
        "[SUITE 10] ReporteTableroServiceTest (M09: RF-39, RF-40)          | Incremento 4 (v0.4.0)            |  4/ 4 | PASA [OK]\n"
        "[SUITE 11] AuditoriaErrorServiceTest (M09: RF-41..RF-43)          | Incremento 4 (v0.4.0)            |  4/ 4 | PASA [OK]\n"
        "------------------------------------------------------------------------\n"
        "RESULTADO DE REGRESIÓN ACUMULADA: 78 de 78 pruebas pasaron (0 fallidas).\n"
        "ESTADO GLOBAL: APROBADO [100% REGRESIÓN EXITOSA]\n"
        "========================================================================"
    )
    r_box.font.name = "Courier New"
    r_box.font.size = Pt(7.5)
    
    pPr = p_box._p.get_or_add_pPr()
    shd = OxmlElement("w:shd")
    shd.set(qn("w:val"), "clear")
    shd.set(qn("w:color"), "auto")
    shd.set(qn("w:fill"), LIGHT_HEX)
    pPr.append(shd)

    # CONCLUSIONES Y PRÓXIMOS PASOS
    doc.h1("6. Conclusiones y Próximos Pasos")
    doc.bullet("La Sesión 1 de Implementación culmina con el 100 % de los requerimientos de lógica de negocio y reglas comerciales desarrollados y verificados bajo la arquitectura de servicios PHP 8.4.")
    doc.bullet("La estrategia de pruebas de regresión acumuladas garantizó que el sistema no presente defectos en sus 78 casos de prueba a través de los Incrementos 1, 2, 3, 4 y 5.")
    doc.bullet("Los componentes faltantes para la puesta en marcha integral del sistema han quedado identificados, presupuestados en 42 horas y formalmente divididos en los Incrementos 6, 7 y 8, proporcionando una hoja de ruta clara para las siguientes sesiones de trabajo.")

    # Guardar DOCX
    os.makedirs(OUT_DIR, exist_ok=True)
    doc.save(DOCX_OUT)
    print(f"Generado DOCX: {DOCX_OUT}")

    # Convertir a PDF con LibreOffice
    subprocess.run(
        ["soffice", "--headless", "--convert-to", "pdf", "--outdir", OUT_DIR, DOCX_OUT],
        check=True
    )
    print(f"Generado PDF: {PDF_OUT}")


if __name__ == "__main__":
    build_report()
