#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Genera la Bitácora de Avances del Proyecto COTIZA-ELEV:
Desde el punto cero (después de la propuesta inicial) hasta la generación de los RF y la arquitectura modular.
"""
import os
import sys
from docx import Document
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Pt, RGBColor

HERE = os.path.dirname(os.path.abspath(__file__))
OUT_DIR = os.path.join(os.path.dirname(HERE), "01_Entregables")
DOCX_OUT = os.path.join(OUT_DIR, "Bitacora_Avances_COTIZA-ELEV.docx")
PDF_OUT = os.path.join(OUT_DIR, "Bitacora_Avances_COTIZA-ELEV.pdf")

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


def cell_margins(cell, top=55, bottom=55, left=80, right=80):
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
        st.font.size = Pt(10)
        st.element.rPr.rFonts.set(qn("w:eastAsia"), BODY_FONT)
        pf = st.paragraph_format
        pf.line_spacing = 1.15
        pf.space_after = Pt(5)
        for s in self.d.sections:
            s.top_margin = Cm(2.0)
            s.bottom_margin = Cm(1.9)
            s.left_margin = Cm(2.0)
            s.right_margin = Cm(2.0)

    def _setup_footer(self):
        sec = self.d.sections[0]
        p = sec.footer.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        r = p.add_run("COTIZA-ELEV · Bitácora de Avances del Proyecto · Página ")
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

    def h1(self, texto, numero=None, page_break_before=False):
        etiqueta = ("%s. %s" % (numero, texto)) if numero is not None else texto
        p = self.d.add_paragraph()
        if page_break_before:
            p.paragraph_format.page_break_before = True
        p.paragraph_format.space_before = Pt(12)
        p.paragraph_format.space_after = Pt(5)
        r = p.add_run(etiqueta)
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

    def p(self, texto, align="justify", size=9.5, italic=False, space_after=4):
        par = self.d.add_paragraph()
        par.alignment = {"justify": WD_ALIGN_PARAGRAPH.JUSTIFY,
                         "center": WD_ALIGN_PARAGRAPH.CENTER,
                         "left": WD_ALIGN_PARAGRAPH.LEFT}[align]
        par.paragraph_format.space_after = Pt(space_after)
        r = par.add_run(texto)
        r.font.size = Pt(size)
        r.font.italic = italic
        return par

    def bullets(self, items):
        for it in items:
            par = self.d.add_paragraph(style="List Bullet")
            par.paragraph_format.space_after = Pt(2.5)
            par.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
            if isinstance(it, tuple):
                r = par.add_run(it[0] + " ")
                r.font.bold = True
                r.font.size = Pt(9.5)
                r2 = par.add_run(it[1])
                r2.font.size = Pt(9.5)
            else:
                r = par.add_run(it)
                r.font.size = Pt(9.5)

    def table(self, headers, rows, widths, font_size=8.5, header_size=9, zebra=True,
              align_center_cols=()):
        t = self.d.add_table(rows=1, cols=len(headers))
        t.style = "Table Grid"
        t.alignment = WD_TABLE_ALIGNMENT.CENTER
        for i, h in enumerate(headers):
            c = t.rows[0].cells[i]
            c.text = ""
            par = c.paragraphs[0]
            par.paragraph_format.space_after = Pt(0)
            if i in align_center_cols:
                par.alignment = WD_ALIGN_PARAGRAPH.CENTER
            r = par.add_run(h)
            r.font.bold = True
            r.font.size = Pt(header_size)
            r.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)
            shade_cell(c, NAVY_HEX)
            cell_margins(c)
        for j, row in enumerate(rows):
            tr = t.add_row()
            trPr = tr._tr.get_or_add_trPr()
            trPr.append(OxmlElement("w:cantSplit"))
            cells = tr.cells
            for i, val in enumerate(row):
                cells[i].text = ""
                par = cells[i].paragraphs[0]
                par.paragraph_format.space_after = Pt(0)
                par.paragraph_format.line_spacing = 1.08
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

    def page_break(self):
        self.d.add_paragraph().add_run().add_break(WD_BREAK.PAGE)

    def save(self, path):
        self.d.save(path)


def main():
    doc = Doc()

    # ENCABEZADO / PORTADA COMPACTA
    p = doc.d.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("UNIVERSIDAD IBEROAMERICANA LEÓN")
    r.font.size = Pt(11)
    r.font.bold = True
    r.font.color.rgb = GREY
    p.paragraph_format.space_after = Pt(2)

    p = doc.d.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("Desarrollo de Aplicaciones y Servicios Virtuales · Ing. Alejandro Montes")
    r.font.size = Pt(9.5)
    r.font.color.rgb = GREY
    p.paragraph_format.space_after = Pt(12)

    p = doc.d.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("BITÁCORA GENERAL DE AVANCES DEL PROYECTO")
    r.font.size = Pt(16)
    r.font.bold = True
    r.font.color.rgb = NAVY
    p.paragraph_format.space_after = Pt(4)

    p = doc.d.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("COTIZA-ELEV — Desde el Punto Cero (post-propuesta) hasta los RF y la Arquitectura Modular")
    r.font.size = Pt(11)
    r.font.italic = True
    r.font.color.rgb = RGBColor(0x2E, 0x5C, 0x9A)
    para_border(p, "bottom", "C9D6E8", 8, 6)
    p.paragraph_format.space_after = Pt(14)

    doc.table(["Concepto", "Detalle"], [
        ("Proyecto", "COTIZA-ELEV — Plataforma de cotización automatizada y gestión de servicios para equipos de elevación vertical."),
        ("Cliente", "ELEVESE (Instalación, modernización y mantenimiento de equipos de elevación vertical)."),
        ("Integrantes", "Orlando Villalobos Gutiérrez y Diego Emilio Alférez Vallejo."),
        ("Roles del equipo", "• Orlando Villalobos Gutiérrez: Analista de requerimientos, Arquitecto de software y Validador.\n• Diego Emilio Alférez Vallejo: Operador de IA, Diseñador técnico y Documentador."),
        ("Tramo documentado", "Del cierre de la Propuesta Inicial (septiembre 2026) al cierre de los Requerimientos Funcionales y Diseño de Arquitectura Modular (octubre 2026)."),
    ], [4.2, 13.0], font_size=9.0, header_size=9.0)

    # 1. RESUMEN DEL RECORRIDO
    doc.h1("Resumen del recorrido del proyecto", 1)
    doc.p("Esta bitácora registra en orden cronológico todos los avances, ajustes y decisiones técnicas que hicimos sobre el proyecto COTIZA-ELEV a partir de la entrega de la Propuesta Inicial (Punto 0). El recorrido muestra cómo pasamos de una idea preliminar general a un sistema acotado, con roles de usuario reales, requerimientos verificables y una arquitectura modular lista para codificar.")

    # 2. TABLA CRONOLÓGICA DE AVANCES
    doc.h1("Bitácora cronológica de avances (Punto 0 a Arquitectura Modular)", 2)

    avances = [
        ("Punto 0\nPost-Propuesta Inicial\n(Septiembre 2026)",
         "• Revisión de la Propuesta Inicial entregada al docente.\n• Asignación de los 4 roles de la práctica en pares entre Orlando y Diego.\n• Definición de la regla de trabajo: verificar todo dato o norma antes de incluirlo.",
         "• Se detectó que la propuesta inicial mezclaba el problema con la solución ('hacer una app web con asistente inteligente').\n• Se acordó separar el problema operativo de la herramienta tecnológica.",
         "Punto de partida formal para el documento de requerimientos."),

        ("Fase 1\nRefinamiento del Problema\n(v1.0)",
         "• Desglose del problema en 5 apartados: problema principal, necesidad, usuario afectado, consecuencias y redacción mejorada.\n• Ejecución del Prompt 5.1 y depuración manual.",
         "• Se eliminaron datos inventados por la IA (promedio de 48 horas para cotizar, una norma oficial inexistente y el nombre falso 'Ascensores del Bajío').\n• Se sumó el problema real de campo: las piezas que el técnico avisa por llamada o mensaje se pierden antes de cotizarse.",
         "Planteamiento del problema validado con la operación real de ELEVESE."),

        ("Fase 2\nAjuste de Usuarios y Permisos por Rol\n(Paso a v2.0)",
         "• Revisión de los 4 perfiles planteados originalmente (asesor comercial, ing. de ventas, administrativo y gerencia).\n• Redefinición del flujo operativo entre campo y oficina.",
         "• Unificación de los 4 perfiles de oficina en un solo rol: Administrador de ventas.\n• Creación del rol Técnico para dar de alta en sitio la pieza o servicio a cotizar.\n• Regla de seguridad: el técnico solo ve el estado de su solicitud y nunca ve precios, tarifas ni montos.",
         "Modelo definitivo de 2 usuarios operativos con vistas separadas y cliente final como beneficiario indirecto."),

        ("Fase 3\nAlcance y Exclusiones para 8 Semanas\n(v1.0 a v2.0)",
         "• Delimitación de las funcionalidades dentro y fuera del alcance para un equipo de 2 integrantes en 8 semanas.\n• Registro de justificación por cada exclusión.",
         "• Eliminación total del módulo de Asistente de IA que venía de la propuesta y la v1.0 (los precios salen siempre del catálogo vigente y el asistente agregaba riesgo de inventar importes).\n• Exclusión de CFDI, app móvil nativa, WhatsApp API, inventario, firma electrónica SAT y login con redes.",
         "Alcance congelado en 8 bloques funcionales centrados en el ciclo: Solicitud → Cotización → Propuesta PDF."),

        ("Fase 4\nGeneración y Depuración de RF y RNF\n(v1.0 a v3.0)",
         "• Redacción de los Requerimientos Funcionales (RF) numerados y agrupados por módulo.\n• Redacción de Requerimientos No Funcionales (RNF) en 8 categorías con métricas medibles.",
         "• La IA propuso 52 RF con duplicados y funciones fuera de alcance; se consolidaron a 39 en v1.0 y quedaron en 43 RF en v2.0/v3.0 tras quitar los 4 del asistente de IA y agregar las solicitudes del técnico y el bloqueo de precios por URL (RF-05, RF-06).\n• En los 29 RNF se cambiaron adjetivos ('rápido', 'fácil') por métricas (≤ 3 s, 50 usuarios, sesión de 20 min, RNF-12 y RNF-26).",
         "43 RF y 29 RNF unitarios, numerados sin huecos y comprobables."),

        ("Fase 5\nHistorias, Criterios y Matriz de Trazabilidad\n(v2.0 a v3.0)",
         "• Redacción de Historias de Usuario (Como / quiero / para) con prioridad y estimación en días.\n• Redacción de Criterios de Aceptación (Dado / Cuando / Entonces).\n• Construcción de la matriz de trazabilidad y registro de riesgos/ambigüedades.",
         "• Se pasó de 12 HU iniciales a 24 HU (16 Alta, 5 Media y 3 Baja para fase 2: HU-02, HU-09 y HU-20).\n• Se completaron 81 CA asegurando en cada historia al menos un caso Normal (31), uno Límite (24) y uno de Error (26).\n• Se corrigieron con script 5 relaciones cruzadas erróneas en la matriz y se documentaron 12 riesgos y 13 ambigüedades.",
         "Entrega Actualizada v3.0 completa (37 páginas) con validación automática de cobertura."),

        ("Fase 6\nInicio de la Arquitectura Modular\n(Diseño Modular)",
         "• Transición de los 8 grupos de requerimientos a módulos de software con entradas, salidas y responsabilidades.\n• Diseño del diagrama por capas y estructura inicial de carpetas y archivos.",
         "• Se rechazó la propuesta de la IA de usar microservicios con Docker, RabbitMQ y machine learning por sobreingeniería.\n• Se adoptó una arquitectura monolítica modular en 3 capas (PHP 8 / MySQL en cPanel) dividida en 9 módulos (M01 a M09).\n• Se partió el cotizador para aislar el Motor de Cálculo (M07) como componente matemático puro sin acceso directo a base de datos.",
         "Arquitectura modular de 9 módulos, árbol de archivos (public/, app/Core, app/Modules, storage/, tests/) y trazabilidad RF-Módulo al 100%."),
    ]

    doc.table(
        ["Etapa / Hito", "Avances y actividades realizadas", "Decisiones técnicas y correcciones sobre la IA", "Resultado alcanzado"],
        avances,
        [2.7, 4.7, 5.8, 4.0],
        font_size=8.2,
        header_size=8.5,
    )

    # 3. EVOLUCIÓN CUANTITATIVA ENTRE VERSIONES
    doc.h1("Evolución de versiones y métricas del proyecto", 3)
    doc.p("El trabajo entre la propuesta inicial y el diseño modular pasó por tres versiones formales del documento de requerimientos antes de entrar de lleno al entregable de arquitectura:")

    evolucion = [
        ("Propuesta Inicial (Punto 0)", "Septiembre 2026", "4 perfiles de oficina (sin técnico)", "Incluido como idea general", "Planteamiento preliminar de 1 página"),
        ("Versión 1.0", "29 sep 2026", "4 perfiles de oficina", "Incluido (4 RF, 1 RNF, 3 HU)", "39 RF · 22 HU (borrador inicial tras depurar los 52 RF de la IA)"),
        ("Versión 2.0", "29 sep 2026", "2 roles: Administrador de ventas y Técnico", "Eliminado por completo", "43 RF · 29 RNF · 24 HU · 72 CA (nuevo módulo de solicitudes y bloqueo de precios)"),
        ("Versión 3.0 (Vigente)", "8 oct 2026", "2 roles operativos + cliente sin acceso", "Eliminado (registrado en bitácora y exclusiones)", "43 RF · 29 RNF · 24 HU · 81 CA (N/L/E completos) · Matriz de 43 filas · 9 módulos en Anexo A"),
        ("Práctica de Diseño Modular", "Octubre 2026", "2 roles con control RBAC en servidor y BD", "Descartado (junto con microservicios y RabbitMQ)", "9 módulos (M01–M09) en 3 capas · Diagrama arquitectónico · Árbol de directorios PHP/MySQL"),
    ]

    doc.table(
        ["Versión / Entregable", "Fecha", "Perfiles de usuario", "Estado del Asistente de IA", "Métricas y contenido clave"],
        evolucion,
        [3.0, 2.1, 3.2, 3.6, 5.3],
        font_size=8.3,
        header_size=8.5,
    )

    # 4. TRANSICIÓN DE REQUERIMIENTOS A LOS 9 MÓDULOS DE ARQUITECTURA
    doc.h1("Paso de los bloques de requerimientos (RF) a la Arquitectura Modular", 4)
    doc.p("Al empezar a generar la arquitectura modular a partir de los 43 RF, el cambio técnico más importante fue pasar de los 8 bloques temáticos del documento de requerimientos (A a H) a 9 módulos de software (M01 a M09), separando el cálculo matemático de la captura comercial para evitar un módulo sobrecargado:")

    modulos_map = [
        ("M01 Acceso y seguridad", "Capa de Datos Base y Administración", "RF-01 a RF-07", "Autenticación, sesión de 20 min, bloqueo de 15 min tras 5 intentos y filtro RBAC por rol."),
        ("M02 Clientes", "Capa de Datos Base y Administración", "RF-08 a RF-12", "Alta, búsqueda sobre 5,000 registros, control de duplicados por RFC y baja lógica."),
        ("M03 Activos (Elevadores)", "Capa de Datos Base y Administración", "RF-13 a RF-15", "Ficha técnica del elevador, validación de rangos (kg, paradas) e historial con/sin montos según rol."),
        ("M04 Catálogo y tarifas", "Capa de Datos Base y Administración", "RF-16 a RF-19", "Equipos nuevos, refacciones, servicios, vigencias de precios e importador masivo CSV."),
        ("M05 Solicitudes (Técnico)", "Capa de Flujo Operativo y Comercial", "RF-20 a RF-24", "Captura de pieza/servicio en campo desde el móvil, aviso de duplicados y bandeja de pendientes sin precios."),
        ("M06 Cotizador", "Capa de Flujo Operativo y Comercial", "RF-25, RF-26, RF-31 a RF-34", "Conversión de solicitud a cotización, manejo de partidas, borradores, duplicación y estados comerciales."),
        ("M07 Motor de cálculo", "Capa de Flujo Operativo y Comercial", "RF-27 a RF-30", "Separado del bloque F: lógica pura para calcular paradas/capacidad, pólizas, correctivos, descuentos e IVA (16%)."),
        ("M08 Propuesta PDF y seguimiento", "Capa de Salidas, Control y Soporte", "RF-35 a RF-38", "Generación de PDF con plantilla corporativa, folio consecutivo anual, envío por correo y cierre."),
        ("M09 Reportes, auditoría y errores", "Capa de Salidas, Control y Soporte", "RF-39 a RF-43", "Tablero de indicadores, exportación CSV/PDF, bitácora inmutable y manejador central de errores."),
    ]

    doc.table(
        ["Módulo de arquitectura", "Capa asignada", "RF cubiertos", "Responsabilidad delimitada en el diseño"],
        modulos_map,
        [3.5, 3.6, 2.6, 7.5],
        font_size=8.3,
        header_size=8.5,
    )

    # 5. APORTACIÓN DE LOS INTEGRANTES
    doc.h1("Participación y firma de los integrantes", 5)
    doc.bullets([
        ("Orlando Villalobos Gutiérrez:",
         "Revisión crítica del planteamiento del problema y alcance, definición de la regla de negocio para el rol técnico sin acceso a precios, validación de los 43 RF y 81 criterios de aceptación (casos normales, límite y de error), diseño de la arquitectura en 3 capas y definición de la estructura de carpetas del proyecto."),
        ("Diego Emilio Alférez Vallejo:",
         "Operación y registro de los prompts en cada fase, depuración de las respuestas de la IA, consolidación de la matriz de trazabilidad de 43 filas, separación del Motor de Cálculo (M07) respecto al Cotizador (M06) y documentación de las bitácoras y entregables."),
    ])

    doc.save(DOCX_OUT)
    print("DOCX generado:", DOCX_OUT)


if __name__ == "__main__":
    main()
