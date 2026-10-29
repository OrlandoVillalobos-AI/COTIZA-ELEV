# -*- coding: utf-8 -*-
"""Secciones 1 a 6 del documento de requerimientos (versión 2.0).

Cambios de la versión 2.0:
 - Los cuatro perfiles anteriores se unificaron en un solo usuario: administrador de ventas.
 - Se agregó el perfil técnico, que da de alta la pieza o servicio a cotizar (solicitud).
 - Se eliminó por completo el módulo del asistente de inteligencia artificial.
"""
from build_doc import figura_flujo, figura_modulos

DATOS = {
    "cliente": "ELEVESE — empresa dedicada a la instalación, modernización y mantenimiento "
               "de equipos de elevación vertical.",
    "desarrolladora": "Equipo desarrollador del proyecto: Orlando Villalobos Gutiérrez y "
                      "Diego Emilio Alférez Vallejo (equipo académico de 2 integrantes; "
                      "el proyecto se desarrolla bajo los lineamientos de la asignatura, "
                      "sin razón social constituida).",
    "proyecto": "COTIZA-ELEV — Plataforma de cotización automatizada y gestión de servicios "
                "para equipos de elevación vertical.",
    "tipo": "Aplicación web centralizada (acceso por navegador, base de datos única, dos "
            "vistas según el rol del usuario).",
    "fecha": "29 de septiembre de 2026",
    "lugar": "León, Guanajuato, México · Septiembre de 2026",
    "version": "3.0",
}

# ═══════════════════════════════════════════════════════════════ requerimientos
GRUPOS_RF = [
    ("5.1 Módulo A — Acceso, roles y permisos", [
        ("RF-01", "El sistema debe permitir el inicio de sesión únicamente con correo "
         "electrónico y contraseña válidos registrados por el administrador de ventas.", "Alta"),
        ("RF-02", "El sistema debe bloquear la cuenta durante 15 minutos después de 5 "
         "intentos fallidos consecutivos y mostrar el motivo del bloqueo.", "Alta"),
        ("RF-03", "El sistema debe permitir recuperar la contraseña mediante un enlace enviado "
         "al correo registrado, con vigencia de 30 minutos.", "Baja"),
        ("RF-04", "El sistema debe permitir al administrador de ventas dar de alta usuarios y "
         "asignarles uno de los dos roles existentes: técnico o administrador de ventas.", "Alta"),
        ("RF-05", "El sistema debe mostrar vistas y menús diferentes según el rol: el técnico "
         "accede únicamente a clientes, activos y solicitudes de cotización, mientras que el "
         "administrador de ventas accede además a catálogo, tarifas, cotizador, propuestas y "
         "reportes.", "Alta"),
        ("RF-06", "El sistema debe impedir que el rol técnico consulte precios, tarifas, "
         "montos o cotizaciones, tanto desde el menú como escribiendo la dirección directamente "
         "en el navegador, y debe registrar el intento en la bitácora.", "Alta"),
        ("RF-07", "El sistema debe cerrar la sesión automáticamente después de 20 minutos de "
         "inactividad y avisar 1 minuto antes de hacerlo.", "Media"),
    ]),
    ("5.2 Módulo B — Clientes", [
        ("RF-08", "El sistema debe registrar clientes capturando razón social, RFC, nombre del "
         "contacto, correo, teléfono y domicilio, y debe impedir el guardado si algún campo "
         "obligatorio está vacío o el correo no tiene formato válido.", "Alta"),
        ("RF-09", "El sistema debe buscar y filtrar clientes por razón social, RFC o correo "
         "sobre una lista de hasta 5,000 registros.", "Alta"),
        ("RF-10", "El sistema debe permitir editar los datos de un cliente y conservar el "
         "historial de cambios (usuario y fecha de la modificación).", "Media"),
        ("RF-11", "El sistema debe avisar antes de guardar cuando detecte un cliente duplicado "
         "por RFC o por razón social similar, y debe permitir cancelar la captura.", "Alta"),
        ("RF-12", "El sistema debe permitir la baja lógica de un cliente conservando sus "
         "activos, solicitudes y cotizaciones históricas, sin eliminarlas de la base de datos.",
         "Media"),
    ]),
    ("5.3 Módulo C — Activos (equipos de elevación)", [
        ("RF-13", "El sistema debe registrar un activo asociado obligatoriamente a un cliente "
         "con los campos: tipo de equipo (pasajeros, carga o montaautos), marca, modelo, número "
         "de serie, capacidad en kilogramos, número de paradas, velocidad, año de instalación y "
         "ubicación. El técnico puede registrar el equipo cuando todavía no existe en el "
         "sistema.", "Alta"),
        ("RF-14", "El sistema debe validar los rangos técnicos del activo (por ejemplo, "
         "capacidad mayor que cero y número de paradas mayor o igual a 2) y avisar cuando el "
         "valor capturado esté fuera del rango esperado, sin bloquear el registro si el usuario "
         "confirma que el dato es correcto.", "Media"),
        ("RF-15", "El sistema debe mostrar el historial del activo (solicitudes y servicios "
         "previos) ordenado de la fecha más reciente a la más antigua; para el rol técnico el "
         "historial muestra tipo de servicio y fecha sin importes, y para el administrador de "
         "ventas muestra además montos, folios y cotizaciones.", "Baja"),
    ]),
    ("5.4 Módulo D — Catálogo y tarifas", [
        ("RF-16", "El sistema debe administrar el catálogo de equipos nuevos con "
         "especificaciones y costo base.", "Alta"),
        ("RF-17", "El sistema debe administrar el catálogo de servicios y refacciones con "
         "clave, descripción, unidad de medida y precio unitario.", "Alta"),
        ("RF-18", "El sistema debe manejar tarifas con fecha de inicio y fecha de fin de "
         "vigencia, y debe usar en las cotizaciones únicamente el precio vigente a la fecha de "
         "emisión.", "Alta"),
        ("RF-19", "El sistema debe permitir la actualización masiva de precios mediante la "
         "carga de un archivo CSV, validando columnas y formato antes de aplicar los cambios, y "
         "debe mostrar un resumen de cuántos registros se actualizaron y cuáles se rechazaron "
         "con su motivo.", "Media"),
    ]),
    ("5.5 Módulo E — Solicitudes de cotización (vista del técnico)", [
        ("RF-20", "El sistema debe permitir al técnico dar de alta una solicitud de cotización "
         "indicando cliente, activo, la pieza o el servicio requerido, la cantidad, la urgencia "
         "y una nota, y debe impedir el guardado si falta el cliente, el activo o la "
         "descripción de la pieza.", "Alta"),
        ("RF-21", "El sistema debe asignar a cada solicitud el estado “Pendiente de cotizar” y "
         "mostrarla en la bandeja del administrador de ventas, ordenada de la más antigua a la "
         "más reciente e indicando quién la levantó y en qué fecha.", "Alta"),
        ("RF-22", "El sistema debe permitir al técnico consultar sus propias solicitudes con su "
         "estado (pendiente, cotizada o rechazada) y la fecha del último cambio, sin mostrar "
         "precios, importes ni folios de propuesta.", "Alta"),
        ("RF-23", "El sistema debe avisar al técnico cuando ya exista una solicitud pendiente "
         "del mismo activo con la misma pieza, y debe permitirle cancelar la captura o "
         "confirmarla indicando el motivo de la repetición.", "Media"),
        ("RF-24", "El sistema debe permitir al administrador de ventas atender una solicitud "
         "marcándola como cotizada o rechazarla con un motivo escrito, y debe conservar el "
         "registro de quién la creó, quién la atendió y en qué momento.", "Alta"),
    ]),
    ("5.6 Módulo F — Cotizador (vista del administrador de ventas)", [
        ("RF-25", "El sistema debe permitir al administrador de ventas tomar una solicitud "
         "pendiente y convertirla en cotización, precargando cliente y activo, y cambiando el "
         "estado de la solicitud a “Cotizada” al guardar la cotización.", "Alta"),
        ("RF-26", "El sistema debe crear una cotización seleccionando cliente, activo y tipo de "
         "cotización (equipo nuevo, modernización, mantenimiento preventivo o mantenimiento "
         "correctivo), ya sea desde cero o a partir de una solicitud del técnico.", "Alta"),
        ("RF-27", "El sistema debe calcular las partidas de equipo nuevo y modernización a "
         "partir de las reglas del catálogo (equipo, capacidad y número de paradas) sin "
         "permitir la captura manual del precio unitario.", "Alta"),
        ("RF-28", "El sistema debe calcular automáticamente el subtotal por concepto, el "
         "descuento aplicado, el IVA y el total de la cotización, mostrando el desglose "
         "completo.", "Alta"),
        ("RF-29", "El sistema debe cotizar pólizas de mantenimiento preventivo considerando "
         "periodicidad (mensual, bimestral o trimestral) y número de visitas al año, y debe "
         "mostrar el costo por visita y el costo anual.", "Alta"),
        ("RF-30", "El sistema debe cotizar mantenimiento correctivo capturando el diagnóstico, "
         "los servicios y refacciones requeridos, y las horas de mano de obra.", "Alta"),
        ("RF-31", "El sistema debe permitir agregar, eliminar y modificar la cantidad de las "
         "partidas de una cotización, recalculando los totales de inmediato.", "Alta"),
        ("RF-32", "El sistema debe guardar la cotización en estado borrador y permitir "
         "recuperarla después para continuar su edición.", "Alta"),
        ("RF-33", "El sistema debe asignar a cada cotización el estado correspondiente "
         "(borrador, enviada, aceptada, rechazada o vencida) y la fecha de vigencia de sus "
         "precios.", "Media"),
        ("RF-34", "El sistema debe permitir duplicar una cotización previa como base para una "
         "nueva, copiando sus partidas y tomando los precios vigentes al momento de la copia.",
         "Baja"),
    ]),
    ("5.7 Módulo G — Documento y seguimiento", [
        ("RF-35", "El sistema debe generar la propuesta económica en formato PDF con plantilla "
         "corporativa que incluya logo, datos fiscales del cliente, descripción de las partidas, "
         "subtotal, IVA, total, vigencia, condiciones comerciales y espacio para firma.", "Alta"),
        ("RF-36", "El sistema debe asignar un folio consecutivo por año y tipo de documento, "
         "sin repetir números, y mostrarlo en el PDF generado.", "Alta"),
        ("RF-37", "El sistema debe permitir enviar la propuesta por correo electrónico desde la "
         "plataforma al correo registrado del cliente y guardar el registro del envío (fecha, "
         "usuario y destinatario).", "Media"),
        ("RF-38", "El sistema debe permitir registrar si la propuesta fue aceptada o rechazada "
         "por el cliente, con fecha y nota del administrador de ventas.", "Media"),
    ]),
    ("5.8 Módulo H — Reportes, administración y errores", [
        ("RF-39", "El sistema debe mostrar un tablero con el número de cotizaciones emitidas, "
         "aceptadas y rechazadas, el monto total cotizado por periodo y el número de solicitudes "
         "pendientes de atender por técnico.", "Media"),
        ("RF-40", "El sistema debe exportar un reporte de cotizaciones y de solicitudes "
         "filtrado por rango de fechas, cliente y técnico, en formato CSV o PDF.", "Media"),
        ("RF-41", "El sistema debe registrar una bitácora de auditoría con usuario, fecha y "
         "acción realizada para las operaciones críticas (alta, edición y cancelación de "
         "clientes y tarifas, creación de usuarios, emisión de propuestas y accesos denegados).",
         "Media"),
        ("RF-42", "El sistema debe centralizar el manejo de errores: ante una falla debe mostrar "
         "un mensaje en español con la causa probable y la acción sugerida, sin mostrar trazas "
         "técnicas ni datos internos, y debe registrar el evento.", "Alta"),
        ("RF-43", "El sistema debe rechazar con mensaje claro toda operación sobre un registro "
         "inexistente o ya cancelado (por ejemplo, cotizar un activo que no pertenece al cliente "
         "seleccionado, o atender una solicitud que otro usuario ya cotizó).", "Media"),
    ]),
]

GRUPOS_RNF = [
    ("6.1 Usabilidad", [
        ("RNF-01", "La interfaz debe estar completamente en español y usarse desde el "
         "navegador, sin instalación de software en el equipo del usuario."),
        ("RNF-02", "Toda acción que guarde o modifique información debe mostrar una "
         "confirmación visual (mensaje de éxito) en un máximo de 2 segundos."),
        ("RNF-03", "Los mensajes de error deben estar en español, señalar el campo o el motivo "
         "del problema y sugerir la acción para corregirlo; no deben mostrar códigos internos ni "
         "trazas técnicas."),
        ("RNF-04", "Un usuario capacitado debe poder completar una cotización de mantenimiento "
         "preventivo en 10 pasos o menos y en menos de 5 minutos; el técnico debe poder levantar "
         "una solicitud de pieza en 6 pasos o menos y en menos de 2 minutos desde su teléfono o "
         "computadora."),
    ]),
    ("6.2 Rendimiento", [
        ("RNF-05", "El tiempo de respuesta de las pantallas principales debe ser menor o igual a "
         "3 segundos en el 95 % de las peticiones, con 50 usuarios concurrentes."),
        ("RNF-06", "La generación de la propuesta en PDF debe completarse en 10 segundos o "
         "menos."),
        ("RNF-07", "Las búsquedas y filtros deben responder en 2 segundos o menos con un volumen "
         "de 5,000 clientes, 20,000 activos y 100,000 cotizaciones registradas."),
        ("RNF-08", "La bandeja de solicitudes pendientes debe listar hasta 200 solicitudes en 3 "
         "segundos o menos y reflejar el alta hecha por el técnico en un máximo de 5 segundos."),
    ]),
    ("6.3 Seguridad básica", [
        ("RNF-09", "Todo el tráfico debe viajar por HTTPS (TLS 1.2 o superior) y las "
         "contraseñas deben almacenarse con algoritmo de hash con sal (bcrypt o argon2), nunca "
         "en texto plano."),
        ("RNF-10", "La sesión debe expirar a los 20 minutos de inactividad y el cierre de sesión "
         "debe invalidar la sesión en el servidor."),
        ("RNF-11", "La autorización por rol debe validarse en el servidor en cada petición, no "
         "solo ocultando opciones en la interfaz."),
        ("RNF-12", "La vista del técnico no debe exponer precios, tarifas, márgenes ni importes "
         "de cotización en ninguna pantalla, respuesta de servicio ni archivo exportable; el "
         "sistema debe denegar esas peticiones aunque se modifique la dirección o el cuerpo de la "
         "solicitud."),
        ("RNF-13", "Toda entrada del usuario debe validarse y sanitizarse en el cliente y en el "
         "servidor para prevenir inyección SQL y XSS."),
        ("RNF-14", "El sistema debe realizar respaldo automático diario de la base de datos con "
         "retención de 30 días y una prueba de restauración mensual documentada."),
        ("RNF-15", "Los datos personales y fiscales de los clientes deben ser accesibles solo a "
         "los usuarios autorizados (el rol técnico no accede a la información fiscal) y "
         "tratarse conforme al aviso de privacidad de la empresa."),
    ]),
    ("6.4 Mantenibilidad", [
        ("RNF-16", "El código debe mantenerse en un repositorio Git con historial y mensajes de "
         "cambio descriptivos, y con una convención de estilo documentada."),
        ("RNF-17", "Las credenciales y parámetros de conexión no deben estar escritos en el "
         "código: deben leerse de un archivo de configuración fuera del repositorio."),
        ("RNF-18", "La lógica de cálculo de precios debe existir en un solo componente "
         "reutilizable; no debe duplicarse en varias pantallas del sistema."),
    ]),
    ("6.5 Modularidad", [
        ("RNF-19", "Los módulos de clientes, activos, solicitudes, catálogo, cotizaciones y "
         "documentos deben exponerse como servicios con interfaz documentada, de modo que puedan "
         "probarse de forma independiente."),
        ("RNF-20", "Las tarifas y las reglas de cálculo deben ser configurables por el "
         "administrador de ventas desde la interfaz, sin necesidad de modificar código."),
    ]),
    ("6.6 Compatibilidad", [
        ("RNF-21", "El sistema debe funcionar correctamente en las dos versiones más recientes "
         "de Chrome, Edge y Firefox, con resolución mínima de 1366 × 768 píxeles; la vista del "
         "técnico debe ser usable desde teléfono móvil."),
        ("RNF-22", "El sistema debe desplegarse en un servidor Linux con Apache o Nginx, PHP 8.x "
         "y MySQL 8 (versión y proveedor pendientes de confirmar con la empresa cliente)."),
        ("RNF-23", "La exportación de datos debe usar formatos abiertos (CSV y PDF) legibles en "
         "Excel y en cualquier lector de PDF."),
    ]),
    ("6.7 Pruebas", [
        ("RNF-24", "Cada requerimiento funcional debe tener al menos un caso de prueba "
         "documentado con su evidencia (captura de pantalla o registro de ejecución), incluyendo "
         "casos de error y casos límite."),
        ("RNF-25", "Debe existir un ambiente de pruebas separado de producción, con datos de "
         "ejemplo, y ejecutar pruebas de regresión antes de cada entrega."),
        ("RNF-26", "Debe probarse de forma explícita que el rol técnico no puede ver ni obtener "
         "precios, tarifas ni importes, con al menos un caso por cada pantalla a la que intente "
         "acceder."),
    ]),
    ("6.8 Documentación y disponibilidad", [
        ("RNF-27", "El sistema debe entregarse con manual de usuario en PDF y en español, con "
         "capturas de pantalla y una sección distinta para cada rol (técnico y administrador de "
         "ventas)."),
        ("RNF-28", "Debe existir documentación técnica: modelo de datos, diccionario de tablas, "
         "listado de servicios e instructivo de instalación."),
        ("RNF-29", "El sistema debe estar disponible al menos el 99 % del horario laboral (lunes "
         "a viernes de 8:00 a 19:00 horas), con monitoreo básico de errores."),
    ]),
]

N_RF = sum(len(f) for _, f in GRUPOS_RF)
N_RNF = sum(len(f) for _, f in GRUPOS_RNF)


NOMBRE_CORTO = {
    "A": "A. Acceso\ny roles y permisos",
    "B": "B. Clientes",
    "C": "C. Activos\n(elevadores)",
    "D": "D. Catálogo\ny tarifas",
    "E": "E. Solicitudes\ndel técnico",
    "F": "F. Cotizador",
    "G": "G. Documento\ny seguimiento",
    "H": "H. Reportes\ny administración",
}


def rangos_modulos():
    """Etiquetas cortas para la figura de módulos (el título largo va en el texto)."""
    salida = []
    for titulo, filas in GRUPOS_RF:
        letra = titulo.split("Módulo ")[1].split(" ")[0]
        salida.append((NOMBRE_CORTO[letra], "%s a %s" % (filas[0][0], filas[-1][0])))
    return salida


def validar():
    ids = [f[0] for _, filas in GRUPOS_RF for f in filas]
    esperados = ["RF-%02d" % i for i in range(1, len(ids) + 1)]
    assert ids == esperados, "numeración de RF inconsistente: %s" % ids
    ids2 = [f[0] for _, filas in GRUPOS_RNF for f in filas]
    esperados2 = ["RNF-%02d" % i for i in range(1, len(ids2) + 1)]
    assert ids2 == esperados2, "numeración de RNF inconsistente: %s" % ids2
    return ids, ids2


def build(b):
    # ══════════════════════════════════════════════════════ P
    b.h1("Presentación")
    b.p("Este documento presenta la entrega actualizada del proyecto COTIZA-ELEV, desarrollado para la "
        "empresa ELEVESE dentro de la asignatura Desarrollo de Aplicaciones y Servicios Virtuales. "
        "Reúne el documento de requerimientos corregido, las historias de usuario priorizadas, los "
        "criterios de aceptación con casos normales, límite y de error, la matriz de trazabilidad "
        "completa, la propuesta inicial de módulos del sistema y la bitácora de uso de la inteligencia "
        "artificial.")
    b.table(["Entregable de la práctica", "Dónde se encuentra"],
            [
                ("Documento corregido de requerimientos", "Secciones 5 y 6: 43 requerimientos funcionales y 29 no funcionales (versión 3.0)"),
                ("Al menos una historia por requerimiento funcional", "Sección 7: 24 historias que cubren los 43 requerimientos funcionales"),
                ("Criterios de aceptación por historia", "Sección 8: 81 criterios en formato Dado / Cuando / Entonces"),
                ("Casos normales, límite y de error", "Sección 8: cada historia tiene los tres tipos; el resumen está al inicio de la sección"),
                ("Priorización de historias", "Sección 7: prioridades Alta, Media y Baja"),
                ("Matriz simple de trazabilidad", "Sección 9: 43 filas con módulo probable"),
                ("Propuesta inicial de módulos", "Anexo A: nueve módulos, responsabilidades y respuestas a las preguntas de la práctica"),
                ("Bitácora de uso de IA", "Sección 11, con el apartado 11.6 para los prompts 1 a 4 de la práctica"),
                ("Revisión crítica y preguntas de revisión", "Secciones 12.1 y 12.2"),
            ],
            [6.2, 11.0], font_size=8.5)

    # ══════════════════════════════════════════════════════ 0
    b.h1("Datos generales del proyecto e integrantes")
    b.table(
        ["Concepto", "Detalle"],
        [
            ("Empresa cliente", DATOS["cliente"]),
            ("Empresa desarrolladora", DATOS["desarrolladora"]),
            ("Nombre del proyecto", DATOS["proyecto"]),
            ("Tipo de aplicación", DATOS["tipo"]),
            ("Materia y docente", "Desarrollo de Aplicaciones y Servicios Virtuales · "
                                  "Ing. Alejandro Montes"),
            ("Periodo de desarrollo", "8 semanas · equipo de 2 integrantes"),
            ("Versión y fecha", "Versión %s · %s" % (DATOS["version"], DATOS["fecha"])),
        ],
        [4.6, 12.4], font_size=9.5)
    b.h2("Integrantes y roles")
    b.p("La práctica propone cuatro roles de trabajo. Al ser un equipo de dos integrantes, los "
        "roles se repartieron en pares y se acordó que cada integrante revisa el trabajo del "
        "otro para que la validación no quede en la misma persona que redactó.")
    b.table(
        ["Integrante", "Roles asumidos", "Responsabilidad principal"],
        [
            ("Orlando Villalobos Gutiérrez", "Analista de requerimientos / Validador y tester",
             "Redacta el problema, el usuario objetivo, el alcance y el fuera del alcance; "
             "revisa claridad, ambigüedades, verificabilidad y criterios de aceptación."),
            ("Diego Emilio Alférez Vallejo", "Operador de IA / Documentador",
             "Ejecuta los prompts, registra las respuestas relevantes y las decisiones tomadas, "
             "y cuida el formato, la consistencia y la bitácora del documento final."),
        ],
        [4.6, 4.6, 7.8], font_size=9.5)
    b.h2("Control de cambios del documento")
    b.table(["Versión", "Fecha", "Cambios realizados", "Responsable"],
            [
                ("1.0", "29 de septiembre de 2026",
                 "Primera versión del documento de requerimientos: cuatro perfiles de usuario y "
                 "módulo de asistente de IA con sugerencias de servicios y precios.",
                 "Ambos integrantes"),
                ("2.0", "29 de septiembre de 2026",
                 "Se unificaron los cuatro perfiles de usuario en el rol administrador de "
                 "ventas; se agregó el perfil técnico con una vista propia para dar de alta las "
                 "piezas y servicios a cotizar; se eliminó por completo el módulo del asistente "
                 "de IA y se renumeraron los requerimientos, historias y criterios de "
                 "aceptación en función de los dos cambios anteriores.",
                 "Ambos integrantes"),
                ("3.0", "8 de octubre de 2026",
                 "Entrega actualizada: cada historia con casos normal, límite y de error; prioridad Baja; "
                 "matriz con módulo probable; propuesta inicial de módulos (Anexo A); revisión crítica, "
                 "preguntas de la práctica y bitácora por fase; presentación formal.",
                 "Ambos integrantes"),
            ],
            [1.8, 3.0, 8.8, 2.8], font_size=8.5, align_center_cols=(0,))

    # ══════════════════════════════════════════════════════ 1
    b.h1("Planteamiento del problema", 1)
    b.p("En esta primera parte se describe, con base en la propuesta inicial entregada al "
        "docente, cuál es el problema real de la empresa cliente. Se separa el problema de la "
        "solución técnica, porque lo que se necesita resolver no es la falta de una aplicación, "
        "sino un proceso comercial que hoy depende de archivos aislados, de llamadas y de "
        "criterios de cada persona.")

    b.h2("1.1 Problema principal")
    b.p("En ELEVESE, la elaboración de cotizaciones para el suministro de equipos nuevos, las "
        "modernizaciones y las intervenciones de mantenimiento (preventivo y correctivo) se "
        "ejecuta con procedimientos manuales y con hojas de cálculo desvinculadas entre sí. "
        "Cuando el técnico detecta en sitio que se requiere una pieza o un servicio, el aviso se "
        "hace por llamada o mensaje, y esa solicitud se pierde o se atiende tarde porque no queda "
        "registrada en ningún sistema: el asesor que cotiza no siempre sabe qué se pidió, para "
        "qué equipo ni con qué urgencia. A su vez, cada asesor conserva su propio archivo de "
        "precios, el historial de equipos instalados está repartido en expedientes físicos y en "
        "carpetas personales, y la propuesta económica se arma desde cero para cada cliente. Esta "
        "desconexión operativa provoca demoras críticas en los tiempos de respuesta comercial, "
        "discrepancias recurrentes en el costeo técnico-económico, dispersión en los expedientes "
        "de clientes y activos instalados, y una marcada carencia de estandarización en la "
        "emisión y formalización de las propuestas comerciales que se entregan al cliente.")

    b.h2("1.2 Necesidad que se busca atender")
    b.p("Se requiere una herramienta que centralice en un solo lugar la información de clientes, "
        "las especificaciones técnicas de los equipos de elevación instalados y el catálogo de "
        "equipos, servicios y refacciones con sus tarifas vigentes, y que además registre la "
        "solicitud de pieza que levanta el técnico en sitio para que el administrador de ventas "
        "la cotice sin tener que preguntar de nuevo qué se necesita. En otras palabras, la "
        "necesidad no es solo guardar datos: es conectar al técnico que detecta la falla con la "
        "persona que cotiza, reducir el tiempo de respuesta, eliminar la variación de precios y "
        "darle trazabilidad comercial a cada propuesta emitida.")

    b.h2("1.3 Usuario afectado")
    b.p("Los usuarios afectados son los técnicos que detectan en sitio las piezas y servicios "
        "necesarios, el administrador de ventas que concentra la cotización, y el personal de la "
        "empresa que da seguimiento a las propuestas. De forma indirecta también se ve afectado "
        "el cliente final, porque recibe propuestas tardías, con criterios distintos entre un "
        "asesor y otro y, en ocasiones, con precios que ya no están vigentes.")

    b.h2("1.4 Consecuencias de no resolver el problema")
    b.bullets([
        ("Solicitudes que se pierden o se atienden tarde: ", "el técnico avisa por llamada o "
         "mensaje, no queda registro de la pieza solicitada y no hay forma de saber qué quedó "
         "pendiente de cotizar."),
        ("Demoras en la respuesta comercial: ", "el cliente espera varios días por una propuesta "
         "que, con la información ya existente en la empresa, podría entregarse el mismo día."),
        ("Discrepancias en el costeo: ", "dos vendedores pueden cotizar el mismo servicio con "
         "precios distintos, lo que afecta la confianza del cliente y el margen de la empresa."),
        ("Precios desactualizados: ", "al vivir las tarifas en archivos personales, no hay forma "
         "de garantizar que la cotización use el precio vigente del catálogo."),
        ("Dispersión de expedientes: ", "no existe un historial confiable por cliente y por "
         "equipo, lo que dificulta dar seguimiento a garantías, mantenimientos previos y "
         "reclamaciones."),
        ("Propuestas sin estandarización: ", "cada cotización tiene formato, condiciones y "
         "redacción diferentes, lo que debilita la imagen institucional y la formalización "
         "legal-comercial de la oferta."),
        ("Cero trazabilidad: ", "no hay registro de qué se cotizó, cuándo se envió, si el "
         "cliente aceptó o rechazó, ni quién fue el responsable del seguimiento."),
    ])

    b.h2("1.5 Redacción mejorada del planteamiento del problema")
    b.p("ELEVESE cotiza suministro de equipos nuevos, modernizaciones y servicios de "
        "mantenimiento preventivo y correctivo mediante procedimientos manuales apoyados en "
        "hojas de cálculo desvinculadas, sin un repositorio único de clientes, activos "
        "instalados y tarifas vigentes, y sin un registro formal de las piezas y servicios que "
        "el técnico detecta en campo. Como consecuencia, las solicitudes de cotización se "
        "pierden, los tiempos de respuesta comercial se alargan, el costeo técnico-económico "
        "presenta discrepancias entre vendedores, los expedientes de clientes y equipos quedan "
        "dispersos, y las propuestas económicas se emiten sin un formato ni condiciones "
        "comerciales homogéneas. La empresa necesita un mecanismo que conecte al técnico con "
        "quien cotiza, concentre esa información y automatice la generación de la propuesta "
        "económica, de modo que cotizar sea un proceso rápido, consistente y verificable.")
    b.nota("Aclaración del equipo: el problema es la dispersión del proceso de cotización y la "
           "pérdida de las solicitudes que levanta el técnico; la aplicación web es la solución "
           "propuesta, no el problema.")

    b.h2("1.6 Contexto de la solución")
    b.p("La solución es una aplicación web centralizada con dos perfiles de usuario. El técnico registra "
        "desde el sitio la solicitud de la pieza o del servicio; el administrador de ventas la recibe, "
        "cotiza con el catálogo vigente y entrega la propuesta en PDF. Ambos perfiles usan la misma base "
        "de datos, pero cada uno ve solo la información que le corresponde. La división en módulos se "
        "describe en el Anexo A.")

    # ══════════════════════════════════════════════════════ 2
    b.h1("Usuario objetivo", 2)
    b.p("A partir de la revisión del documento se determinó que la plataforma tiene únicamente "
        "dos usuarios operativos, porque el proceso real de la empresa es así: el técnico "
        "detecta la pieza o el servicio y lo da de alta en el sistema, y el administrador de "
        "ventas recibe esa solicitud y realiza la cotización completa. Los cuatro perfiles que "
        "se habían considerado antes (asesor comercial, ingeniero de ventas técnicas, personal "
        "administrativo y administrador del sistema o gerencia comercial) se unificaron en un "
        "solo usuario, el administrador de ventas, ya que en la práctica es la misma persona "
        "quien consulta, captura, cotiza, ajusta precios y da seguimiento.")
    b.table(
        ["Perfil de usuario", "Qué necesita del sistema", "Acceso y vistas", "Nivel técnico"],
        [
            ("Técnico", "Dar de alta la pieza, refacción o servicio que detecta en sitio, "
             "asociada al cliente y al equipo, con cantidad, urgencia y una nota; y consultar "
             "después el estado de lo que pidió (pendiente, cotizada o rechazada) sin ver "
             "precios.",
             "Solo dos secciones: clientes y activos (consulta y alta del equipo) y mis "
             "solicitudes de cotización. No accede a catálogo, tarifas ni cotizaciones.",
             "Básico (oficina y campo, uso desde teléfono)"),
            ("Administrador de ventas", "Recibir las solicitudes pendientes, cotizar equipos "
             "nuevos, modernizaciones, pólizas preventivas y correctivos con los precios "
             "vigentes del catálogo, ajustar partidas, emitir la propuesta en PDF, dar "
             "seguimiento y revisar el tablero de indicadores.",
             "Acceso completo: clientes, activos, solicitudes, catálogo y tarifas, cotizador, "
             "propuestas, reportes y administración de usuarios.",
             "Medio (usuario de oficina)"),
            ("Cliente final de ELEVESE", "Beneficiario indirecto: recibe la propuesta en PDF. No "
             "usa la plataforma; únicamente se le entrega el documento generado.",
             "Sin acceso al sistema.", "No aplica"),
        ],
        [2.7, 6.0, 5.4, 3.1], font_size=8.5)
    b.nota("Los dos usuarios operativos usan la misma aplicación, pero con vistas y permisos "
           "distintos. Esa separación es un requerimiento de seguridad (RF-05, RF-06 y RNF-12): "
           "el técnico no debe ver precios ni importes en ninguna pantalla.")

    b.h2("2.1 Contexto de uso")
    b.bullets([
        "El técnico usa la aplicación desde el sitio donde atiende el equipo, con teléfono o "
        "computadora, para levantar la solicitud de la pieza o del servicio requerido.",
        "El administrador de ventas la usa en oficina para atender las solicitudes pendientes y "
        "elaborar la cotización completa.",
        "Uso concurrente: varios técnicos y vendedores pueden trabajar al mismo tiempo sobre la "
        "misma base de datos centralizada, por lo que el sistema debe soportar accesos "
        "simultáneos.",
        "Uso ubicuo: se accede desde cualquier equipo con navegador y sesión, sin instalar "
        "software local.",
        "Momento de uso: el técnico levanta la solicitud durante o inmediatamente después de la "
        "revisión del equipo; el administrador de ventas cotiza en cuanto la solicitud aparece "
        "como pendiente.",
    ])
    b.h2("2.2 Usuarios excluidos de esta versión")
    b.p("No se contempla el acceso directo del cliente final de ELEVESE (no habrá portal de "
        "autoservicio ni consulta pública de propuestas), ni usuarios sin sesión, ni personal "
        "externo de otras empresas. Esta decisión se justifica en el apartado 4 (fuera del "
        "alcance).")

    # ══════════════════════════════════════════════════════ 3
    b.h1("Alcance del proyecto", 3)
    b.p("El alcance se definió pensando en un periodo de desarrollo de 8 semanas. La propuesta "
        "inicial menciona equipos de 3 integrantes; el equipo actual está formado por 2 "
        "integrantes, por lo que se priorizó un producto mínimo viable que cubra el ciclo "
        "completo: el técnico da de alta la pieza o el servicio, el administrador de ventas "
        "recibe la solicitud, cotiza con el catálogo vigente y entrega la propuesta en PDF.")
    b.figure(figura_flujo(), "Figura 1. Flujo de cotización que la plataforma debe soportar: el "
                             "técnico levanta la solicitud y el administrador de ventas cotiza "
                             "(elaboración propia).")
    b.h2("3.1 Funcionalidades dentro del alcance")
    b.table(
        ["Módulo", "Funcionalidades incluidas", "Prioridad"],
        [
            ("A. Acceso, roles y permisos", "Inicio de sesión, dos roles con vistas "
             "diferenciadas (técnico y administrador de ventas), bloqueo de accesos no "
             "permitidos y cierre de sesión por inactividad.", "Alta"),
            ("B. Clientes", "Alta, consulta, búsqueda, edición y baja lógica de clientes, con "
             "validación de campos obligatorios y detección de duplicados por RFC o nombre.",
             "Alta"),
            ("C. Activos (equipos de elevación)", "Ficha técnica del equipo asociado al cliente "
             "(marca, modelo, tipo, capacidad, paradas, velocidad, año, ubicación, número de "
             "serie) e historial del activo con o sin importes según el rol.", "Alta"),
            ("D. Catálogo y tarifas", "Catálogo de equipos nuevos, servicios y refacciones con "
             "precio unitario, unidad de medida y vigencia; administración de tarifas y "
             "actualización masiva por archivo CSV.", "Alta"),
            ("E. Solicitudes de cotización (técnico)", "Alta de la pieza, refacción o servicio "
             "requerido por el técnico, asociada a cliente y activo, con cantidad, urgencia y "
             "nota; bandeja de solicitudes pendientes y consulta del estado por parte del "
             "técnico.", "Alta"),
            ("F. Cotizador (administrador de ventas)", "Cotización de equipo nuevo, "
             "modernización, mantenimiento preventivo (pólizas) y correctivo a partir de una "
             "solicitud del técnico o desde cero; cálculo automático de subtotales, descuentos, "
             "IVA y total; edición de partidas; guardado en borrador y duplicado de "
             "cotizaciones previas.", "Alta"),
            ("G. Documento y seguimiento", "Generación de la propuesta en PDF con plantilla "
             "corporativa y folio consecutivo, envío por correo desde la plataforma y registro "
             "del estado comercial (enviada, aceptada, rechazada, vencida).", "Alta"),
            ("H. Reportes y administración", "Tablero básico de cotizaciones emitidas, "
             "aceptadas y solicitudes pendientes; exportación de un reporte filtrado en CSV o "
             "PDF; bitácora de auditoría de acciones críticas y manejo centralizado de errores.",
             "Media"),
        ],
        [3.4, 11.1, 2.5], font_size=9, align_center_cols=(2,))
    b.nota("El módulo de asistente de inteligencia artificial que aparecía en la versión 1.0 se "
           "eliminó por completo: los precios provienen siempre del catálogo administrado por el "
           "administrador de ventas y no existía una necesidad real que justificara el "
           "asistente. La justificación quedó registrada en el apartado 4 y en la bitácora "
           "(apartado 11).")
    b.figure(figura_modulos(rangos_modulos()), "Figura 2. Módulos del alcance y requerimientos "
                                               "funcionales que los cubren (elaboración "
                                               "propia).")
    b.h2("3.2 Restricciones y supuestos del alcance")
    b.bullets([
        "Tiempo: 8 semanas de calendario académico, con dedicación parcial del equipo.",
        "Equipo: 2 integrantes (Orlando Villalobos Gutiérrez y Diego Emilio Alférez Vallejo), "
        "quienes además cursan otras asignaturas.",
        "Infraestructura supuesta: servidor con Linux, Apache o Nginx, PHP 8.x y MySQL 8, con "
        "certificado HTTPS; este supuesto debe confirmarse con la empresa cliente.",
        "Datos iniciales: la empresa proporciona el catálogo de servicios y tarifas vigentes y "
        "los datos de sus clientes en archivo; la migración de historiales antiguos no forma "
        "parte del alcance.",
        "Dos roles operativos: el proceso supone que el técnico levanta la solicitud y el "
        "administrador de ventas cotiza. No se contemplan perfiles intermedios ni aprobaciones "
        "en cadena en esta versión.",
        "Alcance funcional: se prioriza el ciclo solicitud → cotización → propuesta; las "
        "funciones administrativas avanzadas (inventario, facturación, órdenes de trabajo) "
        "quedan para una segunda fase.",
    ])

    # ══════════════════════════════════════════════════════ 4
    b.h1("Fuera del alcance", 4)
    b.p("Lo siguiente no se desarrollará en esta versión. Cada exclusión tiene una razón "
        "concreta: no cabe en las 8 semanas, no es indispensable para validar el proceso de "
        "cotización, o depende de decisiones y sistemas que no controla el equipo.")
    b.table(
        ["Funcionalidad excluida", "Justificación de la exclusión"],
        [
            ("Asistente de inteligencia artificial que sugiera servicios, refacciones o precios",
             "Se eliminó por decisión del equipo tras revisar la versión 1.0: los precios "
             "provienen siempre del catálogo administrado por el administrador de ventas, quien "
             "es la única persona que cotiza, por lo que el asistente no atendía una necesidad "
             "real y solo agregaba dependencia de un servicio externo, costo y riesgo de "
             "inventar importes."),
            ("Facturación electrónica y timbrado fiscal (CFDI)", "Requiere integración con un "
             "PAC autorizado, manejo de certificados y cumplimiento fiscal; no aporta al "
             "objetivo de cotizar y excede el tiempo disponible."),
            ("Aplicación móvil nativa (Android/iOS)", "El técnico usará el sistema desde el "
             "navegador del teléfono; desarrollar una app nativa duplicaría el trabajo y no cabe "
             "en 8 semanas."),
            ("Portal de autoservicio para el cliente final", "Cambia el modelo de usuario "
             "objetivo y abre temas de privacidad y soporte. El cliente sigue recibiendo la "
             "propuesta en PDF por correo."),
            ("Integración con ERP o sistema contable", "No se cuenta con documentación ni acceso "
             "a las interfaces del sistema contable de la empresa; se resolverá con exportación "
             "de archivos CSV."),
            ("Control de inventario, almacén y compras", "Es otro proceso de negocio. Se supone "
             "que existencias y tiempos de entrega se confirman por fuera de la plataforma."),
            ("Firma electrónica avanzada (e.firma / firma del SAT)", "La propuesta se formaliza "
             "con firma autógrafa del representante; implementar firma electrónica avanzada "
             "implica trámites y requisitos legales fuera del control del equipo."),
            ("Pago en línea o pasarela de cobro", "El cobro se realiza por los medios actuales "
             "de la empresa; integrar una pasarela agrega requisitos financieros y de seguridad "
             "no previstos."),
            ("Órdenes de trabajo en campo con consumos, fotos y rastreo de técnicos", "Requiere "
             "geolocalización y trabajo en sitio; se plantea como fase 2 porque excede el alcance "
             "y el tiempo de esta práctica. El registro de la pieza solicitada sí está incluido "
             "(módulo E)."),
            ("Multiempresa o multisucursal con catálogos separados", "La operación inicial es "
             "una sola empresa con una sola lista de precios; multiplicar catálogos complicaría "
             "la administración de tarifas."),
            ("Reportes analíticos avanzados y predicción de ventas", "Con una sola empresa y "
             "pocos meses de datos no hay información suficiente para modelos predictivos; en "
             "esta versión solo se incluye un tablero básico."),
            ("Migración histórica completa de archivos de Excel anteriores", "Los archivos "
             "antiguos tienen formatos y criterios distintos entre vendedores; solo se cargan "
             "clientes y tarifas vigentes al inicio del proyecto."),
        ],
        [6.0, 11.0], font_size=9)

    # ══════════════════════════════════════════════════════ 5
    b.h1("Requerimientos funcionales", 5)
    b.p("Los requerimientos se numeraron como RF-01, RF-02, RF-03 y se agruparon por módulo. "
        "Cada uno está redactado de forma verificable (se puede comprobar con una prueba), es "
        "unitario (no mezcla dos funciones en un mismo enunciado) y respeta el alcance definido. "
        "Se incluyen los casos normales y también el manejo de errores. La numeración se rehízo "
        "por completo en la versión 2.0, después de unificar los perfiles de usuario, agregar la "
        "vista del técnico y eliminar el módulo del asistente de IA.")
    for titulo, filas in GRUPOS_RF:
        b.h2(titulo)
        b.table(["ID", "Requerimiento funcional", "Prioridad"],
                [(i, t, p) for i, t, p in filas], [1.7, 12.8, 2.5], font_size=9,
                align_center_cols=(0, 2))
    b.nota("Total de requerimientos funcionales: %d. Del total inicial propuesto por la IA se "
           "consolidaron y eliminaron los que estaban fuera del alcance; en la versión 2.0 se "
           "quitaron los cuatro del asistente de IA y se agregaron los de solicitudes del "
           "técnico. El detalle está en la bitácora del apartado 11." % N_RF)

    # ══════════════════════════════════════════════════════ 6
    b.h1("Requerimientos no funcionales", 6)
    b.p("Los requerimientos no funcionales se redactaron con valores medibles para que el "
        "validador pueda comprobarlos, no con adjetivos. Se agrupan por categoría: usabilidad, "
        "rendimiento, seguridad básica, mantenibilidad, modularidad, compatibilidad, pruebas, y "
        "documentación y disponibilidad. Los requerimientos de la versión 1.0 que dependían del "
        "asistente de IA se eliminaron junto con ese módulo.")
    for titulo, filas in GRUPOS_RNF:
        b.h2(titulo)
        b.table(["ID", "Requerimiento no funcional"], filas, [1.9, 15.1], font_size=9,
                align_center_cols=(0,))
    b.nota("Total de requerimientos no funcionales: %d. Cada uno se puede comprobar mediante "
           "una prueba de carga, una revisión de código, una medición en el ambiente de pruebas "
           "o una prueba de permisos entre roles." % N_RNF)
