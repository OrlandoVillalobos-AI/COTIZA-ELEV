# -*- coding: utf-8 -*-
"""Secciones 7 a 14 del documento de requerimientos (versión 2.0)."""
from content_a import N_RF, N_RNF

# (id, módulo, rol, quiero, para, [RF], prioridad, días, [(ca, tipo, Dado, Cuando, Entonces)])
HUS = [
    ("HU-01", "A", "usuario de la plataforma (técnico o administrador de ventas)",
     "iniciar sesión con mi correo y mi contraseña",
     "entrar únicamente si tengo una cuenta autorizada", ["RF-01", "RF-02"], "Alta", 2, [
        ("CA-01.1", "Caso exitoso",
         "que mi cuenta está activa y escribo correo y contraseña correctos",
         "presiono el botón Iniciar sesión",
         "el sistema me lleva a la vista que corresponde a mi rol y muestra mi nombre"),
        ("CA-01.2", "Datos incorrectos",
         "que escribo una contraseña equivocada",
         "presiono el botón Iniciar sesión",
         "el sistema muestra “Correo o contraseña incorrectos” sin decir cuál dato falló y no me deja entrar"),
        ("CA-01.3", "Error esperado / caso límite",
         "que acumulo 5 intentos fallidos consecutivos",
         "intento iniciar sesión por sexta vez",
         "el sistema bloquea mi cuenta durante 15 minutos y me muestra el tiempo que falta para volver a intentarlo"),
     ]),
    ("HU-02", "A", "usuario de la plataforma",
     "recuperar mi contraseña por correo electrónico",
     "volver a entrar cuando olvide mi contraseña sin pedir ayuda a otra persona", ["RF-03"], "Media", 1, [
        ("CA-02.1", "Caso exitoso",
         "que mi correo está registrado y olvidé mi contraseña",
         "solicito recuperarla y abro el enlace antes de 30 minutos",
         "el sistema me permite definir una contraseña nueva y me deja iniciar sesión con ella"),
        ("CA-02.2", "Caso límite",
         "que recibí el enlace de recuperación hace más de 30 minutos",
         "abro el enlace",
         "el sistema muestra “El enlace expiró, solicita uno nuevo” y no permite cambiar la contraseña"),
        ("CA-02.3", "Datos incorrectos / seguridad",
         "que escribo un correo que no está registrado",
         "solicito la recuperación",
         "el sistema responde “Si el correo está registrado recibirás un enlace” sin revelar si la cuenta existe"),
     ]),
    ("HU-03", "A", "técnico",
     "que mi cuenta solo me muestre las secciones que necesito y que no me deje ver precios ni cotizaciones",
     "hacer mi trabajo sin exponer información que no me corresponde",
     ["RF-05", "RF-06"], "Alta", 3, [
        ("CA-03.1", "Caso exitoso",
         "que soy técnico y entro con mi cuenta",
         "abro el menú principal",
         "solo veo las secciones Clientes, Activos y Mis solicitudes de cotización, y no aparece el catálogo de precios ni el cotizador"),
        ("CA-03.2", "Error esperado / seguridad",
         "que soy técnico y escribo directo en la barra del navegador la dirección del cotizador o del catálogo de tarifas",
         "el sistema recibe la petición",
         "el sistema responde “No tienes permisos para esta sección”, no muestra ningún precio ni monto y registra el intento en la bitácora"),
        ("CA-03.3", "Caso límite / vista por rol",
         "que el administrador de ventas entra con su cuenta al mismo sistema",
         "abre el cotizador",
         "ve el cotizador, el catálogo y los importes completos, lo que confirma que la restricción aplica solo al rol técnico"),
     ]),
    ("HU-04", "A", "administrador de ventas",
     "dar de alta a los usuarios y asignarles su rol",
     "controlar quién entra al sistema y qué información puede ver cada persona", ["RF-04"], "Alta", 2, [
        ("CA-04.1", "Caso exitoso",
         "que doy de alta a un técnico con su correo y su rol",
         "el técnico inicia sesión por primera vez",
         "el sistema le pide cambiar la contraseña inicial y lo dirige a la vista del técnico"),
        ("CA-04.2", "Datos incorrectos",
         "que doy de alta un usuario con un correo que ya está registrado",
         "presiono Guardar",
         "el sistema muestra “Ese correo ya tiene una cuenta” y no crea el usuario duplicado"),
        ("CA-04.3", "Caso límite",
         "que cambio el rol de un técnico a administrador de ventas",
         "el usuario vuelve a iniciar sesión",
         "el sistema le aplica la nueva vista con los módulos de cotización y precios y registra el cambio en la bitácora"),
     ]),
    ("HU-05", "A", "usuario de la plataforma",
     "que la sesión se cierre sola cuando dejo de usar el sistema",
     "que nadie use mi cuenta si dejo abierta la computadora del taller o de la oficina", ["RF-07"], "Media", 1, [
        ("CA-05.1", "Caso exitoso",
         "que llevo 19 minutos sin usar la plataforma con la sesión abierta",
         "el sistema detecta la inactividad",
         "un minuto antes me avisa que la sesión se cerrará y al minuto 20 me saca y me pide entrar de nuevo"),
        ("CA-05.2", "Caso límite",
         "que estoy capturando una cotización y el sistema avisa del cierre por inactividad",
         "muevo el mouse o presiono una tecla",
         "el sistema cancela el cierre, mantiene la sesión y conserva lo que ya tenía capturado"),
        ("CA-05.3", "Error esperado / seguridad",
         "que la sesión ya se cerró por inactividad",
         "presiono el botón Regresar del navegador",
         "el sistema no muestra la pantalla anterior y me manda a la pantalla de inicio de sesión"),
     ]),
    ("HU-06", "B", "administrador de ventas",
     "registrar los datos de un cliente nuevo",
     "tener su información completa y lista para cotizar sin volver a capturarla después",
     ["RF-08", "RF-11"], "Alta", 2, [
        ("CA-06.1", "Caso exitoso",
         "que tengo los datos del cliente (razón social, RFC, contacto, correo, teléfono y domicilio)",
         "los capturo y guardo",
         "el sistema muestra “Cliente guardado correctamente” con su número de cliente y queda disponible para cotizar"),
        ("CA-06.2", "Datos incorrectos",
         "que dejo el RFC vacío o escribo un correo sin arroba",
         "presiono Guardar",
         "el sistema marca con rojo los campos con problema, explica el motivo y no guarda el registro"),
        ("CA-06.3", "Error esperado / caso límite",
         "que ya existe un cliente con el mismo RFC",
         "presiono Guardar",
         "el sistema avisa “Ya existe un cliente con ese RFC” y me ofrece verlo o cancelar la captura sin duplicar el registro"),
     ]),
    ("HU-07", "B", "administrador de ventas",
     "buscar, editar y dar de baja clientes conservando su historial",
     "corregir datos y dejar de ver clientes inactivos sin perder lo que ya se le cotizó",
     ["RF-09", "RF-10", "RF-12"], "Alta", 3, [
        ("CA-07.1", "Caso exitoso",
         "que existen 800 clientes registrados y escribo “elev” en el buscador",
         "el sistema ejecuta la búsqueda",
         "en menos de 2 segundos muestra la lista de clientes coincidentes y me deja abrir su ficha"),
        ("CA-07.2", "Caso exitoso / trazabilidad",
         "que estoy corrigiendo el teléfono de un cliente",
         "guardo el cambio",
         "el sistema guarda el dato nuevo y registra en el historial quién lo modificó y en qué fecha"),
        ("CA-07.3", "Caso límite",
         "que doy de baja lógica a un cliente que ya tenía 4 cotizaciones y 1 activo",
         "confirmo la baja",
         "el cliente deja de aparecer en las búsquedas nuevas, pero sus activos y sus 4 cotizaciones siguen consultables en el historial"),
     ]),
    ("HU-08", "C", "técnico",
     "registrar la ficha técnica del equipo cuando todavía no existe en el sistema",
     "poder levantar la solicitud de la pieza de ese equipo sin depender de la oficina",
     ["RF-13", "RF-14"], "Alta", 3, [
        ("CA-08.1", "Caso exitoso",
         "que el cliente ya está registrado y no existe el equipo, pero tengo sus datos",
         "capturo tipo, marca, modelo, serie, capacidad, paradas, velocidad, año y ubicación, y guardo",
         "el sistema guarda el activo ligado al cliente y me deja continuar con la solicitud de la pieza"),
        ("CA-08.2", "Datos fuera de rango",
         "que capturo capacidad 0 kg o 1 parada",
         "intento guardar",
         "el sistema muestra “El valor está fuera del rango esperado (2 a 40 paradas)” y me deja corregir o confirmar el dato"),
        ("CA-08.3", "Error esperado",
         "que intento guardar un equipo sin haber seleccionado cliente",
         "presiono Guardar",
         "el sistema muestra “Selecciona el cliente al que pertenece el equipo” y no crea el registro"),
     ]),
    ("HU-09", "C", "técnico",
     "consultar el historial del equipo sin ver precios",
     "saber qué servicios ya se le hicieron y no repetir solicitudes que ya se atendieron",
     ["RF-15"], "Media", 2, [
        ("CA-09.1", "Caso exitoso / vista del técnico",
         "que el equipo tiene 3 servicios previos registrados",
         "abro la pestaña Historial del activo",
         "el sistema lista tipo de servicio, fecha y estado, en orden de la fecha más reciente a la más antigua, sin mostrar ningún importe ni folio"),
        ("CA-09.2", "Caso exitoso / vista del administrador",
         "que el administrador de ventas abre el historial del mismo equipo",
         "consulta la pestaña Historial",
         "el sistema muestra además los montos cotizados, los folios de propuesta y el estado comercial de cada cotización"),
        ("CA-09.3", "Caso límite y error esperado",
         "que abro el historial de un equipo recién registrado o de un equipo dado de baja",
         "el sistema recibe la petición",
         "si el equipo no tiene movimientos muestra “Este activo todavía no tiene servicios registrados”; si fue dado de baja muestra “El activo no existe o fue dado de baja”"),
     ]),
    ("HU-10", "D", "administrador de ventas",
     "administrar el catálogo de equipos, servicios y tarifas con su vigencia",
     "que la cotización siempre use los precios autorizados y vigentes de la empresa",
     ["RF-16", "RF-17", "RF-18"], "Alta", 4, [
        ("CA-10.1", "Caso exitoso",
         "que doy de alta el servicio “Mantenimiento preventivo por visita” con precio 1,850.00 y unidad “visita”",
         "guardo el registro",
         "el servicio queda disponible en el cotizador con ese precio y unidad, y el técnico no lo ve en su vista"),
        ("CA-10.2", "Datos incorrectos",
         "que intento guardar un precio en cero o negativo",
         "presiono Guardar",
         "el sistema muestra “El precio debe ser mayor que cero” y no guarda la tarifa"),
        ("CA-10.3", "Caso límite / vigencia",
         "que programé una tarifa nueva con fecha de inicio del próximo mes",
         "genero una cotización hoy",
         "la cotización usa la tarifa vigente hoy, muestra su fecha de vigencia y no aplica la tarifa futura"),
     ]),
    ("HU-11", "D", "administrador de ventas",
     "actualizar varios precios de una sola vez cargando un archivo CSV",
     "evitar capturar precio por precio cuando la lista de tarifas cambia", ["RF-19"], "Media", 3, [
        ("CA-11.1", "Caso exitoso",
         "que preparé un CSV con las columnas clave y precio, con 40 registros correctos",
         "lo cargo y confirmo la actualización",
         "el sistema muestra “38 registros actualizados y 2 rechazados” y lista el motivo de cada rechazo (fila y columna)"),
        ("CA-11.2", "Datos incorrectos",
         "que el archivo no trae la columna “precio”",
         "lo cargo",
         "el sistema muestra “Falta la columna obligatoria: precio” y no aplica ningún cambio al catálogo"),
        ("CA-11.3", "Caso límite",
         "que el archivo solo tiene el renglón de encabezados o está vacío",
         "lo cargo",
         "el sistema muestra “El archivo no contiene registros para procesar” y no modifica ninguna tarifa"),
     ]),
    ("HU-12", "E", "técnico",
     "dar de alta la pieza, refacción o servicio que se necesita cotizar",
     "que la solicitud quede registrada con el cliente y el equipo correctos y no se pierda en una llamada",
     ["RF-20", "RF-23"], "Alta", 3, [
        ("CA-12.1", "Caso exitoso",
         "que estoy frente al equipo y detecto que se requiere una refacción",
         "selecciono cliente y activo, describo la pieza, indico cantidad y urgencia, y guardo",
         "el sistema registra la solicitud como “Pendiente de cotizar”, me muestra el número de solicitud y la deja visible para el administrador de ventas"),
        ("CA-12.2", "Error esperado",
         "que intento guardar la solicitud sin describir la pieza o sin seleccionar el activo",
         "presiono Enviar solicitud",
         "el sistema marca los campos faltantes, explica el motivo y no crea la solicitud"),
        ("CA-12.3", "Caso límite / duplicado",
         "que ya existe una solicitud pendiente del mismo equipo con la misma pieza",
         "presiono Enviar solicitud",
         "el sistema avisa “Ya existe una solicitud pendiente de esa pieza para este equipo” y me deja cancelar o confirmar escribiendo el motivo"),
     ]),
    ("HU-13", "E", "técnico",
     "consultar el estado de mis solicitudes sin ver precios",
     "saber si lo que pedí ya se cotizó, si sigue pendiente o si fue rechazado",
     ["RF-22"], "Alta", 2, [
        ("CA-13.1", "Caso exitoso",
         "que he levantado 5 solicitudes en la semana",
         "abro Mis solicitudes",
         "el sistema lista mis 5 solicitudes con cliente, equipo, pieza, fecha y estado (pendiente, cotizada o rechazada), sin mostrar importes"),
        ("CA-13.2", "Caso exitoso / cambio de estado",
         "que el administrador de ventas cotizó una de mis solicitudes",
         "vuelvo a abrir Mis solicitudes",
         "la solicitud aparece como “Cotizada” con la fecha del cambio, y sigue sin mostrar el monto"),
        ("CA-13.3", "Caso límite / rechazo",
         "que el administrador de ventas rechazó mi solicitud con un motivo",
         "abro el detalle de esa solicitud",
         "el sistema muestra el estado “Rechazada”, la fecha y el motivo escrito por el administrador de ventas"),
     ]),
    ("HU-14", "E/F", "administrador de ventas",
     "ver las solicitudes pendientes del técnico y convertir una en cotización",
     "atender lo que se pidió en campo en el orden en que llegó y sin volver a preguntar qué se necesita",
     ["RF-21", "RF-24", "RF-25"], "Alta", 4, [
        ("CA-14.1", "Caso exitoso",
         "que hay 6 solicitudes pendientes levantadas por distintos técnicos",
         "abro la bandeja de solicitudes",
         "el sistema las lista de la más antigua a la más reciente indicando cliente, equipo, pieza, técnico y fecha, en 3 segundos o menos"),
        ("CA-14.2", "Caso exitoso / decisión",
         "que atiendo una solicitud marcándola como cotizada y rechazo otra escribiendo el motivo",
         "guardo la decisión",
         "el sistema cambia los estados, guarda quién atendió cada una y con qué fecha, y el técnico ve el resultado en su vista"),
        ("CA-14.3", "Caso exitoso / conversión",
         "que elijo la opción Convertir en cotización sobre una solicitud pendiente",
         "el sistema abre el cotizador",
         "la cotización se abre con el cliente y el activo precargados y, al guardarla, la solicitud cambia automáticamente a “Cotizada”"),
     ]),
    ("HU-15", "F", "administrador de ventas",
     "cotizar el suministro de un equipo nuevo o una modernización",
     "entregar al cliente un precio calculado con el catálogo de la empresa, sin capturarlo a mano",
     ["RF-26", "RF-27"], "Alta", 4, [
        ("CA-15.1", "Caso exitoso",
         "que seleccioné cliente, activo y tipo de cotización “equipo nuevo”",
         "confirmo las características del equipo",
         "el sistema agrega las partidas con el precio del catálogo y el campo de precio unitario queda bloqueado para edición"),
        ("CA-15.2", "Error esperado",
         "que selecciono un activo que pertenece a otro cliente",
         "intento continuar con la cotización",
         "el sistema muestra “El activo seleccionado no pertenece al cliente” y no permite avanzar"),
        ("CA-15.3", "Caso límite",
         "que el equipo tiene una combinación de capacidad y paradas que no está en el catálogo",
         "el sistema intenta calcular la partida",
         "el sistema avisa “No hay precio para esa combinación; selecciona otro modelo o solicita el alta de la tarifa” y no inventa un importe"),
     ]),
    ("HU-16", "F", "administrador de ventas",
     "ver el desglose de subtotal, descuento, IVA y total de la cotización",
     "explicarle al cliente cómo se forma el precio y evitar errores de cálculo", ["RF-28"], "Alta", 2, [
        ("CA-16.1", "Caso exitoso",
         "que la cotización tiene 3 partidas por 50,000.00 y un descuento del 10 %",
         "abro la sección de totales",
         "el sistema muestra subtotal 50,000.00, descuento 5,000.00, IVA 7,200.00 y total 52,200.00"),
        ("CA-16.2", "Datos incorrectos",
         "que capturo un descuento de 60,000.00 sobre un subtotal de 50,000.00",
         "el sistema recalcula",
         "el sistema muestra “El descuento no puede ser mayor que el subtotal” y no muestra un total negativo"),
        ("CA-16.3", "Caso límite",
         "que aplico un descuento del 100 %",
         "confirmo el descuento",
         "el sistema pide confirmación, deja el total en 0.00 con IVA 0.00 y registra el descuento en la cotización"),
     ]),
    ("HU-17", "F", "administrador de ventas",
     "cotizar una póliza de mantenimiento preventivo con periodicidad y número de visitas",
     "ofrecer al cliente el costo por visita y el costo anual del servicio", ["RF-29"], "Alta", 3, [
        ("CA-17.1", "Caso exitoso",
         "que elijo periodicidad bimestral y 6 visitas al año para un equipo",
         "confirmo la póliza",
         "el sistema muestra el costo por visita, el número de visitas y el costo anual del mantenimiento"),
        ("CA-17.2", "Datos incorrectos",
         "que elijo periodicidad mensual y dejo el número de visitas en 0",
         "intento continuar",
         "el sistema muestra “El número de visitas debe ser al menos 1” y no genera la póliza"),
        ("CA-17.3", "Caso límite",
         "que elijo periodicidad bimestral con 6 visitas y el servicio cuesta 1,850.00 por visita",
         "el sistema calcula el anual",
         "el sistema muestra costo anual de 11,100.00 (6 × 1,850.00) y avisa que la periodicidad y las visitas no coinciden exactamente"),
     ]),
    ("HU-18", "F", "administrador de ventas",
     "cotizar un servicio de mantenimiento correctivo con diagnóstico, refacciones y mano de obra",
     "documentar por escrito qué falla se atiende y qué se cobra por ello", ["RF-30"], "Alta", 3, [
        ("CA-18.1", "Caso exitoso",
         "que capturo el diagnóstico, 2 refacciones y 4.5 horas de mano de obra",
         "guardo la cotización",
         "el sistema agrega las partidas, calcula la mano de obra por las horas capturadas y muestra el total del servicio"),
        ("CA-18.2", "Error esperado",
         "que quiero continuar sin diagnóstico y sin ninguna partida",
         "presiono Siguiente",
         "el sistema muestra “El mantenimiento correctivo requiere un diagnóstico y al menos una partida” y no avanza"),
        ("CA-18.3", "Caso límite",
         "que capturo 0.5 horas de mano de obra",
         "el sistema calcula el importe",
         "el sistema acepta la fracción de hora y calcula el importe proporcional a la tarifa por hora"),
     ]),
    ("HU-19", "F", "administrador de ventas",
     "editar las partidas de una cotización, guardarla como borrador y controlar su estado",
     "ajustar cantidades mientras hablo con el cliente sin perder lo capturado",
     ["RF-31", "RF-32", "RF-33"], "Alta", 4, [
        ("CA-19.1", "Caso exitoso",
         "que la cotización tiene 3 partidas y cambio la cantidad de 2 a 5 en una de ellas",
         "guardo el cambio",
         "el sistema recalcula subtotal, IVA y total de inmediato y muestra el mensaje de confirmación"),
        ("CA-19.2", "Caso límite / estados",
         "que la cotización está en borrador y tiene una vigencia de 15 días",
         "envío la propuesta y pasan los 15 días sin respuesta",
         "el sistema cambia el estado de borrador a enviada y después a vencida, sin borrar el documento generado"),
        ("CA-19.3", "Caso límite / borrador",
         "que guardo como borrador una cotización a la que le faltan datos del cliente",
         "presiono Guardar borrador",
         "el sistema la guarda marcada como “incompleta” y no permite enviarla hasta completar los faltantes"),
     ]),
    ("HU-20", "F", "administrador de ventas",
     "duplicar una cotización anterior como base para una nueva",
     "no volver a capturar todas las partidas cuando se cotiza el mismo tipo de equipo a otro cliente",
     ["RF-34"], "Media", 2, [
        ("CA-20.1", "Caso exitoso",
         "que abro la cotización de hace dos semanas y elijo Duplicar",
         "confirmo la copia",
         "el sistema crea una cotización nueva con las mismas partidas, el cliente que yo seleccione y los precios vigentes de hoy"),
        ("CA-20.2", "Caso límite / aviso de cambio de precio",
         "que la cotización original tenía un precio de 98,000.00 y hoy el catálogo marca 104,500.00",
         "confirmo la copia",
         "el sistema muestra el aviso de que usará los precios vigentes: antes 98,000.00, ahora 104,500.00"),
        ("CA-20.3", "Error esperado",
         "que abro un enlace de una cotización que ya fue eliminada",
         "intento duplicarla",
         "el sistema muestra “La cotización ya no existe” y no genera ninguna copia"),
     ]),
    ("HU-21", "G", "administrador de ventas",
     "generar la propuesta económica en PDF con folio y plantilla corporativa",
     "entregar un documento formal y uniforme al cliente, sin armarlo en Word cada vez",
     ["RF-35", "RF-36"], "Alta", 4, [
        ("CA-21.1", "Caso exitoso",
         "que la cotización está completa y con precios vigentes",
         "presiono Generar PDF",
         "en 10 segundos o menos el sistema descarga la propuesta con logo, datos fiscales, partidas, desglose, vigencia, condiciones y espacio para firma"),
        ("CA-21.2", "Caso límite / folio",
         "que ya se emitieron 12 propuestas este año",
         "genero la número 13",
         "el PDF muestra el folio consecutivo correspondiente al año en curso y el sistema no repite folios al mismo tiempo"),
        ("CA-21.3", "Error esperado",
         "que el generador de PDF falla por un error del servidor",
         "presiono Generar PDF",
         "el sistema muestra “No se pudo generar el documento, inténtalo de nuevo” sin mostrar el error técnico y registra el evento"),
     ]),
    ("HU-22", "G", "administrador de ventas",
     "enviar la propuesta por correo y registrar si el cliente aceptó o rechazó",
     "tener claro qué se envió, cuándo y en qué quedó cada oportunidad",
     ["RF-37", "RF-38"], "Media", 3, [
        ("CA-22.1", "Caso exitoso",
         "que la propuesta ya está generada y el cliente tiene correo registrado",
         "presiono Enviar por correo",
         "el sistema envía el PDF al correo del cliente y guarda fecha, usuario y destinatario en el registro de la cotización"),
        ("CA-22.2", "Datos incorrectos",
         "que el cliente no tiene correo registrado",
         "presiono Enviar por correo",
         "el sistema muestra “El cliente no tiene correo registrado; captúralo antes de enviar” y no envía nada"),
        ("CA-22.3", "Caso límite",
         "que el cliente responde rechazando la propuesta",
         "registro el resultado como Rechazada con una nota",
         "el sistema cambia el estado, guarda la fecha y el motivo, y la cotización deja de contar como pendiente en el tablero"),
     ]),
    ("HU-23", "H", "administrador de ventas",
     "ver un tablero con cotizaciones, solicitudes pendientes y monto cotizado, y exportar el reporte",
     "medir el trabajo del área y detectar qué solicitudes de campo siguen sin cotizar",
     ["RF-39", "RF-40"], "Media", 3, [
        ("CA-23.1", "Caso exitoso",
         "que selecciono el periodo del mes en curso",
         "abro el tablero",
         "el sistema muestra cotizaciones emitidas, aceptadas y rechazadas, el monto cotizado del periodo y las solicitudes pendientes por técnico"),
        ("CA-23.2", "Caso límite",
         "que el periodo consultado no tiene movimientos",
         "abro el tablero",
         "el sistema muestra los indicadores en cero con el mensaje “No hay cotizaciones en el periodo seleccionado”"),
        ("CA-23.3", "Caso exitoso / exportación",
         "que filtro por técnico y rango de fechas",
         "presiono Exportar CSV",
         "el sistema descarga un archivo con folio, fecha, cliente, técnico, monto y estado, abrible en Excel"),
     ]),
    ("HU-24", "H", "administrador de ventas",
     "consultar la bitácora de acciones críticas y que los errores se expliquen en español",
     "poder investigar quién cambió un precio, quién intentó ver información restringida y qué falló en el sistema",
     ["RF-41", "RF-42", "RF-43"], "Media", 3, [
        ("CA-24.1", "Caso exitoso / auditoría",
         "que un técnico intentó abrir el catálogo de precios y otro usuario modificó una tarifa",
         "consulto la bitácora",
         "el sistema muestra usuario, fecha, hora y acción de ambos eventos en orden cronológico, sin poder editar ni borrar esos registros"),
        ("CA-24.2", "Error esperado",
         "que ocurre una falla al guardar por pérdida de conexión con la base de datos",
         "el usuario presiona Guardar",
         "el sistema muestra “No se pudo guardar por un problema de conexión, inténtalo de nuevo en unos segundos” y registra el evento en la bitácora"),
        ("CA-24.3", "Caso límite",
         "que intento atender una solicitud que otro usuario ya cotizó",
         "presiono Convertir en cotización",
         "el sistema muestra “Esta solicitud ya fue cotizada por otro usuario” con la fecha, y no genera una segunda cotización"),
     ]),
]

# ---------------------------------------------------------------- versión 3.0
# Ajustes de la entrega actualizada (práctica de historias y trazabilidad):
#   - cada historia tiene al menos un caso Normal, uno Límite y uno de Error;
#   - el tipo de caso se normaliza a Normal / Límite / Error;
#   - prioridades Alta / Media / Baja;
#   - la matriz incluye el módulo probable del sistema.

_TIPO = {
    "CA-01.1": "Normal", "CA-01.2": "Error", "CA-01.3": "Límite",
    "CA-02.1": "Normal", "CA-02.2": "Límite", "CA-02.3": "Error",
    "CA-03.1": "Normal", "CA-03.2": "Error", "CA-03.3": "Límite",
    "CA-04.1": "Normal", "CA-04.2": "Error", "CA-04.3": "Límite",
    "CA-05.1": "Normal", "CA-05.2": "Límite", "CA-05.3": "Error",
    "CA-06.1": "Normal", "CA-06.2": "Error", "CA-06.3": "Error", "CA-06.4": "Límite",
    "CA-07.1": "Normal", "CA-07.2": "Normal", "CA-07.3": "Límite", "CA-07.4": "Error",
    "CA-08.1": "Normal", "CA-08.2": "Error", "CA-08.3": "Error", "CA-08.4": "Límite",
    "CA-09.1": "Normal", "CA-09.2": "Normal", "CA-09.3": "Límite", "CA-09.4": "Error",
    "CA-10.1": "Normal", "CA-10.2": "Error", "CA-10.3": "Límite",
    "CA-11.1": "Normal", "CA-11.2": "Error", "CA-11.3": "Límite",
    "CA-12.1": "Normal", "CA-12.2": "Error", "CA-12.3": "Límite",
    "CA-13.1": "Normal", "CA-13.2": "Normal", "CA-13.3": "Error", "CA-13.4": "Límite",
    "CA-14.1": "Normal", "CA-14.2": "Normal", "CA-14.3": "Normal",
    "CA-14.4": "Límite", "CA-14.5": "Error",
    "CA-15.1": "Normal", "CA-15.2": "Error", "CA-15.3": "Límite",
    "CA-16.1": "Normal", "CA-16.2": "Error", "CA-16.3": "Límite",
    "CA-17.1": "Normal", "CA-17.2": "Error", "CA-17.3": "Límite",
    "CA-18.1": "Normal", "CA-18.2": "Error", "CA-18.3": "Límite",
    "CA-19.1": "Normal", "CA-19.2": "Límite", "CA-19.3": "Error",
    "CA-20.1": "Normal", "CA-20.2": "Límite", "CA-20.3": "Error",
    "CA-21.1": "Normal", "CA-21.2": "Límite", "CA-21.3": "Error",
    "CA-22.1": "Normal", "CA-22.2": "Error", "CA-22.3": "Límite", "CA-22.4": "Normal",
    "CA-23.1": "Normal", "CA-23.2": "Límite", "CA-23.3": "Normal", "CA-23.4": "Error",
    "CA-24.1": "Normal", "CA-24.2": "Error", "CA-24.3": "Límite",
}

# Criterios reescritos para completar la cobertura Normal / Límite / Error.
_REEMPLAZO = {
    "CA-03.3": ("que el técnico abre la aplicación desde un teléfono con pantalla de 360 píxeles "
                "de ancho", "abre el menú principal",
                "las secciones permitidas se muestran completas, sin desplazamiento horizontal ni "
                "opciones ocultas por el diseño"),
    "CA-09.3": ("que abro el historial de un equipo recién registrado que todavía no tiene "
                "servicios", "consulto la pestaña Historial",
                "el sistema muestra “Este activo todavía no tiene servicios registrados” sin "
                "marcarlo como error"),
    "CA-13.3": ("que el técnico intenta abrir por URL una solicitud registrada por otro técnico",
                "el sistema recibe la petición",
                "el sistema responde “No tienes permisos para ver esta solicitud” y no muestra su "
                "contenido"),
    "CA-22.3": ("que la propuesta ya fue enviada una vez al cliente",
                "presiono Enviar por correo por segunda vez",
                "el sistema envía de nuevo el documento y conserva los dos envíos con su fecha, "
                "sin sobrescribir el primero"),
    "CA-24.3": ("que la bitácora contiene 100,000 registros", "filtro por usuario y rango de fechas",
                "el sistema devuelve el resultado en 2 segundos o menos, en bloques de 50 registros"),
}

# Criterios nuevos por historia.
_NUEVOS = {
    "HU-06": [("CA-06.4", "que capturo una razón social de 300 caracteres, que es el máximo del campo",
               "guardo el cliente", "el sistema la guarda completa y no la corta ni la altera")],
    "HU-07": [("CA-07.4", "que cambio el RFC de un cliente por el RFC de otro cliente ya registrado",
               "presiono Guardar",
               "el sistema muestra “Ese RFC ya pertenece a otro cliente” y conserva el RFC anterior")],
    "HU-08": [("CA-08.4", "que capturo exactamente 2 paradas, que es el límite inferior del rango",
               "guardo el activo", "el sistema lo acepta sin mostrar aviso de rango")],
    "HU-09": [("CA-09.4", "que abro el historial de un equipo dado de baja",
               "el sistema recibe la petición",
               "el sistema muestra “El activo no existe o fue dado de baja” y no muestra información")],
    "HU-13": [("CA-13.4", "que no he levantado ninguna solicitud",
               "abro Mis solicitudes",
               "el sistema muestra “No tienes solicitudes registradas” sin mostrar error")],
    "HU-14": [("CA-14.4", "que la bandeja tiene 200 solicitudes pendientes",
               "la abro", "la lista se muestra en 3 segundos o menos, con paginación de 50 solicitudes"),
              ("CA-14.5", "que otro administrador de ventas ya convirtió en cotización la solicitud que "
                          "tengo abierta",
               "presiono Convertir en cotización",
               "el sistema muestra “Esta solicitud ya fue cotizada por otro usuario” con la fecha y no "
               "genera una segunda cotización")],
    "HU-22": [("CA-22.4", "que el cliente respondió por correo que acepta la propuesta",
               "registro la aceptación con fecha y nota",
               "la cotización cambia a Aceptada y deja de contar como pendiente en el tablero")],
    "HU-23": [("CA-23.4", "que selecciono un periodo cuya fecha final es anterior a la inicial",
               "presiono Consultar",
               "el sistema muestra “La fecha final no puede ser anterior a la inicial” y no consulta datos")],
}

# Requerimientos cubiertos por historia (ajustados en la versión 3.0).
_RF_HU = {
    "HU-14": ["RF-21", "RF-24", "RF-25", "RF-43"],
    "HU-24": ["RF-41", "RF-42"],
}

# Prioridad Baja: mejoras que pueden pasar a la fase 2 sin afectar el ciclo solicitud → propuesta.
_PRIO = {"HU-02": "Baja", "HU-09": "Baja", "HU-20": "Baja"}


def _aplicar_v3(historias):
    salida = []
    for hu, mod, rol, quiero, para, rf, pri, dias, cas in historias:
        cs = []
        for ca, tipo, d, c, e in cas:
            if ca in _REEMPLAZO:
                d, c, e = _REEMPLAZO[ca]
            cs.append((ca, _TIPO[ca], d, c, e))
        for ca, d, c, e in _NUEVOS.get(hu, []):
            cs.append((ca, _TIPO[ca], d, c, e))
        salida.append((hu, mod, rol, quiero, para, _RF_HU.get(hu, rf), _PRIO.get(hu, pri), dias, cs))
    return salida


HUS = _aplicar_v3(HUS)

# Módulos del sistema (propuesta inicial de diseño, Anexo A).
MODULOS = {
    "M01": "M01 Acceso y seguridad",
    "M02": "M02 Clientes",
    "M03": "M03 Activos",
    "M04": "M04 Catálogo y tarifas",
    "M05": "M05 Solicitudes",
    "M06": "M06 Cotizador",
    "M07": "M07 Motor de cálculo",
    "M08": "M08 Propuesta PDF y seguimiento",
    "M09": "M09 Reportes, auditoría y errores",
}

# (RF, HU, CA, prueba sugerida, módulo probable)
TRAZA = [
    ("RF-01", "HU-01", "CA-01.1", "Iniciar sesión con credenciales válidas de cada rol", "M01"),
    ("RF-02", "HU-01", "CA-01.3", "Provocar 5 intentos fallidos y comprobar el bloqueo de 15 minutos", "M01"),
    ("RF-03", "HU-02", "CA-02.1 / CA-02.2", "Solicitar recuperación y usar el enlace antes y después de 30 minutos", "M01"),
    ("RF-04", "HU-04", "CA-04.1 / CA-04.2", "Dar de alta usuarios con cada rol y repetir un correo ya registrado", "M01"),
    ("RF-05", "HU-03", "CA-03.1 / CA-03.3", "Entrar con cada rol, comparar menú y secciones; probar vista de 360 px", "M01"),
    ("RF-06", "HU-03", "CA-03.2", "Escribir por URL las pantallas de precios con el rol técnico", "M01"),
    ("RF-07", "HU-05", "CA-05.1 / CA-05.2 / CA-05.3", "Dejar la sesión inactiva 20 minutos y usar Regresar tras el cierre", "M01"),
    ("RF-08", "HU-06", "CA-06.1 / CA-06.2 / CA-06.4", "Capturar cliente válido, con campos vacíos y con razón social de 300 caracteres", "M02"),
    ("RF-09", "HU-07", "CA-07.1", "Buscar con texto parcial sobre 5,000 clientes y medir el tiempo", "M02"),
    ("RF-10", "HU-07", "CA-07.2 / CA-07.4", "Editar un cliente, revisar historial y cambiar RFC por uno existente", "M02"),
    ("RF-11", "HU-06", "CA-06.3", "Intentar guardar un cliente con RFC ya existente", "M02"),
    ("RF-12", "HU-07", "CA-07.3", "Dar de baja un cliente con activos y cotizaciones y verificar que sigan consultables", "M02"),
    ("RF-13", "HU-08", "CA-08.1 / CA-08.3", "Registrar equipo con la cuenta del técnico y otro sin cliente", "M03"),
    ("RF-14", "HU-08", "CA-08.2 / CA-08.4", "Capturar capacidad 0 kg, 1 parada y 2 paradas", "M03"),
    ("RF-15", "HU-09", "CA-09.1 / CA-09.2 / CA-09.3 / CA-09.4", "Abrir el historial con rol técnico y de ventas; equipo nuevo y dado de baja", "M03"),
    ("RF-16", "HU-10", "CA-10.1", "Dar de alta un equipo nuevo con especificaciones y costo base", "M04"),
    ("RF-17", "HU-10", "CA-10.1 / CA-10.2", "Dar de alta un servicio y una refacción; probar un precio en cero", "M04"),
    ("RF-18", "HU-10", "CA-10.3", "Programar tarifa con vigencia futura y verificar que la cotización use la vigente", "M04"),
    ("RF-19", "HU-11", "CA-11.1 / CA-11.2 / CA-11.3", "Cargar CSV válido, sin columna precio y vacío", "M04"),
    ("RF-20", "HU-12", "CA-12.1 / CA-12.2", "Levantar solicitud con la cuenta del técnico y otra con campos faltantes", "M05"),
    ("RF-21", "HU-14", "CA-14.1 / CA-14.4", "Listar 6 pendientes y después 200 solicitudes; medir el tiempo", "M05"),
    ("RF-22", "HU-13", "CA-13.1 / CA-13.2 / CA-13.3 / CA-13.4", "Consultar mis solicitudes y verificar que no aparezcan importes ni solicitudes ajenas", "M05"),
    ("RF-23", "HU-12", "CA-12.3", "Duplicar una solicitud pendiente del mismo equipo y pieza", "M05"),
    ("RF-24", "HU-14", "CA-14.2", "Atender una solicitud como cotizada y rechazar otra con motivo", "M05"),
    ("RF-25", "HU-14", "CA-14.3", "Convertir una solicitud en cotización y verificar el cambio de estado", "M06"),
    ("RF-26", "HU-15", "CA-15.1 / CA-15.2", "Cotizar cada tipo y probar un activo de otro cliente", "M06"),
    ("RF-27", "HU-15", "CA-15.3", "Probar una combinación capacidad/paradas que no existe en el catálogo", "M07"),
    ("RF-28", "HU-16", "CA-16.1 / CA-16.2", "Verificar el desglose con 3 partidas y un descuento mayor al subtotal", "M07"),
    ("RF-29", "HU-17", "CA-17.1 / CA-17.3", "Cotizar una póliza con distinta periodicidad y comprobar el costo anual", "M07"),
    ("RF-30", "HU-18", "CA-18.1 / CA-18.3", "Cotizar un correctivo con refacciones y con fracción de hora", "M07"),
    ("RF-31", "HU-19", "CA-19.1", "Cambiar cantidades y verificar el recálculo inmediato de totales", "M06"),
    ("RF-32", "HU-19", "CA-19.3", "Guardar un borrador incompleto, salir y recuperarlo", "M06"),
    ("RF-33", "HU-19", "CA-19.2", "Recorrer los estados borrador, enviada, aceptada, rechazada y vencida", "M06"),
    ("RF-34", "HU-20", "CA-20.1 / CA-20.2 / CA-20.3", "Duplicar una cotización con precios distintos; abrir una copia eliminada", "M06"),
    ("RF-35", "HU-21", "CA-21.1 / CA-21.3", "Generar el PDF completo y simular una falla del generador", "M08"),
    ("RF-36", "HU-21", "CA-21.2", "Emitir propuestas consecutivas y verificar la numeración por año", "M08"),
    ("RF-37", "HU-22", "CA-22.1 / CA-22.2 / CA-22.3", "Enviar por correo con y sin correo registrado, y enviar dos veces", "M08"),
    ("RF-38", "HU-22", "CA-22.4", "Registrar aceptación y rechazo y revisar el cambio de estado", "M08"),
    ("RF-39", "HU-23", "CA-23.1 / CA-23.2", "Consultar el tablero con movimientos y con un periodo sin registros", "M09"),
    ("RF-40", "HU-23", "CA-23.3 / CA-23.4", "Exportar el reporte filtrado y validar un periodo con fechas invertidas", "M09"),
    ("RF-41", "HU-24", "CA-24.1", "Provocar un acceso denegado y un cambio de tarifa, y revisar la bitácora", "M09"),
    ("RF-42", "HU-24", "CA-24.2", "Simular una falla de conexión y revisar el mensaje mostrado", "M09"),
    ("RF-43", "HU-14", "CA-14.5", "Intentar convertir una solicitud ya cotizada por otro usuario", "M09"),
]

N_HU = len(HUS)
N_CA = sum(len(h[8]) for h in HUS)

LETRA_NOMBRE = {
    "A": "Acceso, roles y permisos",
    "B": "Clientes",
    "C": "Activos (equipos de elevación)",
    "D": "Catálogo y tarifas",
    "E": "Solicitudes de cotización (técnico)",
    "E/F": "Solicitudes de cotización y cotizador",
    "F": "Cotizador (administrador de ventas)",
    "G": "Documento y seguimiento",
    "H": "Reportes y administración",
}


def validar():
    import content_a
    ids = [h[0] for h in HUS]
    assert ids == ["HU-%02d" % i for i in range(1, len(ids) + 1)], "HU inconsistentes"
    cubiertos = set()
    for hu, mod, rol, q, p, rf, pri, dias, cas in HUS:
        assert pri in ("Alta", "Media", "Baja"), hu
        numero = hu.split("-")[1]
        assert [c[0] for c in cas] == sorted(c[0] for c in cas), hu
        for ca, tipo, d, c, e in cas:
            assert ca.startswith("CA-%s." % numero), (hu, ca)
            assert tipo in ("Normal", "Límite", "Error"), ca
        assert {c[1] for c in cas} == {"Normal", "Límite", "Error"}, "%s sin caso N/L/E" % hu
        for r in rf:
            n = int(r.split("-")[1])
            assert 1 <= n <= content_a.N_RF, "%s referencia %s" % (hu, r)
            cubiertos.add(n)
    faltan = sorted(set(range(1, content_a.N_RF + 1)) - cubiertos)
    assert not faltan, "RF sin historia: %s" % faltan
    assert len(TRAZA) == content_a.N_RF, "la matriz debe tener una fila por RF"
    mapa = {h[0]: h for h in HUS}
    for rf, hu, ca, pr, mod in TRAZA:
        assert hu in mapa, hu
        assert rf in mapa[hu][5], "%s no está en %s" % (rf, hu)
        ids_ca = {c[0] for c in mapa[hu][8]}
        for parte in ca.split(" / "):
            assert parte.strip() in ids_ca, "%s no existe en %s" % (parte, hu)
        assert mod in MODULOS, mod
    return ids


def build(b):
    # ══════════════════════════════════════════════════════ 7
    # ══════════════════════════════════════════════════════ 7
    b.h1("Historias de usuario", 7)
    b.p("Se redactaron %d historias de usuario con el formato “Como [tipo de usuario], quiero "
        "[funcionalidad], para [beneficio]”. Cada una indica los requerimientos funcionales que "
        "cubre, su prioridad y su estimación en días de trabajo. En esta entrega se revisaron "
        "todas: se completaron los casos límite y de error que faltaban, se separaron las "
        "historias del técnico y del administrador de ventas, y se asignó prioridad Baja a las "
        "mejoras que pueden pasar a la fase 2." % N_HU)
    filas = []
    for hu, mod, rol, quiero, para, rf, pri, dias, cas in HUS:
        filas.append((hu, "%s\nComo %s, quiero %s, para %s." % (LETRA_NOMBRE[mod], rol, quiero, para),
                      ", ".join(rf), pri, "%d día%s" % (dias, "" if dias == 1 else "s")))
    b.table(["ID", "Historia de usuario", "Requerimientos", "Prioridad", "Estimación"],
            filas, [1.3, 9.2, 2.7, 1.8, 2.1], font_size=8.5, header_size=8,
            align_center_cols=(0, 3, 4))
    from collections import Counter
    cuenta = Counter(h[6] for h in HUS)
    b.nota("Criterios de prioridad. Alta: indispensable para que el ciclo solicitud → cotización → "
           "propuesta funcione (%d historias). Media: necesaria para la operación diaria; se programa "
           "después de las altas (%d historias). Baja: mejora que puede pasar a la fase 2 sin afectar "
           "el ciclo (%d historias: HU-02, HU-09 y HU-20). Los requerimientos RF-03, RF-15 y RF-34 "
           "tienen la misma prioridad Baja." % (cuenta["Alta"], cuenta["Media"], cuenta["Baja"]))

    # ══════════════════════════════════════════════════════ 8
    b.h1("Criterios de aceptación", 8)
    b.p("Cada historia tiene entre 3 y 5 criterios en formato “Dado [condición], cuando [acción], "
        "entonces [resultado]”. Todas cumplen la regla de la práctica: al menos un caso normal, un "
        "caso límite y un caso de error. En total son %d criterios. La tabla siguiente resume el "
        "tipo de cada criterio por historia; el detalle aparece después de la tabla." % N_CA)
    resumen = []
    for hu, mod, rol, quiero, para, rf, pri, dias, cas in HUS:
        c = Counter(x[1] for x in cas)
        resumen.append((hu, str(c["Normal"]), str(c["Límite"]), str(c["Error"]), str(len(cas))))
    resumen.append(("Total", str(sum(int(r[1]) for r in resumen)), str(sum(int(r[2]) for r in resumen)),
                    str(sum(int(r[3]) for r in resumen)), str(N_CA)))
    b.table(["Historia", "Normal", "Límite", "Error", "Total"], resumen,
            [4.2, 3.0, 3.0, 3.0, 4.0], font_size=8.5, align_center_cols=(0, 1, 2, 3, 4))
    for hu, mod, rol, quiero, para, rf, pri, dias, cas in HUS:
        b.h2("Criterios de aceptación de %s (%s)" % (hu, LETRA_NOMBRE[mod]))
        b.table(["ID", "Tipo de caso", "Criterio de aceptación (Dado / Cuando / Entonces)"],
                [(ca, tipo, "Dado que %s,\ncuando %s,\nentonces %s." % (d, c, e))
                 for ca, tipo, d, c, e in cas],
                [1.8, 2.4, 13.0], font_size=8.5, align_center_cols=(0, 1))

    # ══════════════════════════════════════════════════════ 9
    b.h1("Matriz de trazabilidad", 9)
    b.p("La matriz tiene una fila por requerimiento funcional (%d en total) y las columnas que pide la "
        "práctica: requerimiento, historia de usuario, criterio de aceptación, prueba sugerida y "
        "módulo probable. El módulo probable corresponde a los nueve módulos de diseño del Anexo A. "
        "La verificación automática comprueba que cada historia contenga los requerimientos que dice "
        "cubrir, que cada criterio exista dentro de su historia y que cada módulo esté definido." % N_RF)
    b.table(["Requerimiento", "Historia", "Criterio de aceptación", "Prueba sugerida", "Módulo probable"],
            TRAZA, [2.8, 1.5, 3.1, 5.8, 4.0], font_size=8, header_size=8.5, align_center_cols=(0, 1))
    b.nota("Los %d requerimientos funcionales están trazados. Los requerimientos no funcionales se "
           "verifican con las pruebas de carga, la revisión de código, las mediciones en el ambiente de "
           "pruebas y las pruebas de permisos descritas en el apartado 6." % N_RF)

    # ══════════════════════════════════════════════════════ 10
    b.h1("Riesgos o ambigüedades", 10)
    b.h2("10.1 Riesgos identificados")
    b.table(["ID", "Riesgo", "Tipo", "Impacto", "Probabilidad", "Mitigación acordada"],
            [
                ("R-01", "El técnico no usa la aplicación y sigue avisando las piezas por llamada "
                 "o mensaje, con lo que el problema original continúa.", "Adopción", "Alto",
                 "Alta", "Formulario de 6 pasos o menos y menos de 2 minutos (RNF-04), "
                 "capacitación del personal de campo y consulta de solicitudes como evidencia "
                 "del trabajo realizado."),
                ("R-02", "El equipo tiene 2 integrantes y ambos cursan otras materias; el "
                 "proyecto podría no terminarse en 8 semanas.", "Organizacional", "Alto", "Alta",
                 "Alcance congelado al final de la semana 2 y prioridad Alta en los módulos A, B, "
                 "C, E y F, que son el ciclo mínimo solicitud → cotización."),
                ("R-03", "El catálogo y las tarifas pueden estar desactualizados o cada vendedor "
                 "maneja su propio precio.", "Negocio", "Alto", "Media",
                 "Carga inicial validada por el administrador de ventas y uso obligatorio del "
                 "catálogo vigente (RF-18, RF-27)."),
                ("R-04", "Las reglas de cálculo que usa el área técnica no coinciden con las del "
                 "área comercial.", "Requisitos", "Alto", "Alta",
                 "Validar el algoritmo con el área técnica en la semana 1 (ambigüedad A-01) y "
                 "documentarlo en el catálogo."),
                ("R-05", "El cliente no tiene disponibilidad para validar porque es una empresa "
                 "en operación.", "Requisitos", "Medio", "Alta",
                 "Agenda fija de validación semanal y avance por entregables revisables sin "
                 "reunión."),
                ("R-06", "La plantilla corporativa del PDF tiene formatos y requisitos exactos "
                 "difíciles de reproducir.", "Técnico", "Medio", "Media",
                 "Aprobar la plantilla antes de programar la generación y validarla con una "
                 "propuesta real ya emitida por la empresa."),
                ("R-07", "Fuga de información sensible: precios, márgenes y datos fiscales que "
                 "un técnico no debe ver.", "Técnico / legal", "Alto", "Media",
                 "Autorización validada en el servidor para cada petición (RNF-11), vista del "
                 "técnico sin precios (RNF-12), bitácora de accesos denegados (RF-41) y pruebas "
                 "de permisos por pantalla (RNF-26)."),
                ("R-08", "Duda sobre si una cotización ya enviada conserva sus precios cuando el "
                 "catálogo cambia.", "Requisitos", "Medio", "Media",
                 "Se registra como ambigüedad A-03; mientras se define, la cotización emitida "
                 "conserva sus precios y al duplicarla se avisa del cambio (CA-20.2)."),
                ("R-09", "Falta de experiencia del equipo para poner el sistema en el servidor de "
                 "producción.", "Técnico", "Medio", "Media",
                 "Ambiente de pruebas separado, instructivo de instalación documentado (RNF-25, "
                 "RNF-28) y despliegue acompañado por el responsable de TI."),
                ("R-10", "Los datos iniciales de clientes y tarifas vienen en archivos con "
                 "formatos distintos entre vendedores.", "Técnico", "Medio", "Alta",
                 "Plantilla CSV única validada (RF-19) y carga asistida revisada antes de "
                 "publicar el sistema."),
                ("R-11", "Crecimiento del alcance en pleno desarrollo (que se pida inventario, "
                 "facturación u órdenes de trabajo con fotos).", "Gestión", "Alto", "Alta",
                 "Documento de alcance y fuera del alcance aceptado por el cliente; lo nuevo se "
                 "registra como solicitud para la fase 2."),
                ("R-12", "Dos usuarios trabajan la misma solicitud y se generan dos cotizaciones "
                 "duplicadas.", "Técnico", "Medio", "Media",
                 "Cambio de estado bloqueado al atender (RF-24) y aviso “Esta solicitud ya fue "
                 "cotizada por otro usuario” (CA-24.3)."),
            ],
            [1.3, 4.5, 2.5, 1.6, 2.2, 4.7], font_size=8.5, header_size=8,
            align_center_cols=(0, 2, 3, 4))

    b.h2("10.2 Ambigüedades y puntos por validar con el cliente")
    b.table(["ID", "¿Qué falta aclarar?", "Con quién se valida", "Cuándo"],
            [
                ("A-01", "Regla exacta para calcular el costo de una modernización (¿porcentaje "
                 "del equipo nuevo o lista de conceptos?).", "Área técnica / administrador de "
                 "ventas", "Semana 1"),
                ("A-02", "¿El IVA se aplica siempre al 16 % o hay operaciones exentas o con tasa "
                 "distinta?", "Administración", "Semana 1"),
                ("A-03", "¿Una cotización enviada conserva sus precios aunque el catálogo cambie "
                 "después?", "Gerencia comercial", "Semana 2"),
                ("A-04", "¿Existe un descuento máximo autorizado y quién lo aprueba?",
                 "Gerencia comercial", "Semana 2"),
                ("A-05", "¿Cuántos niveles de mantenimiento preventivo hay y cómo se decide la "
                 "periodicidad de las visitas?", "Área técnica / administración", "Semana 2"),
                ("A-06", "¿Qué versión de PHP y MySQL y qué proveedor de hosting tiene la "
                 "empresa?", "TI del cliente", "Semana 1"),
                ("A-07", "¿Qué datos fiscales son obligatorios en la propuesta (RFC, régimen "
                 "fiscal, dirección fiscal)?", "Administración", "Semana 1"),
                ("A-08", "¿La numeración de folios continúa la serie actual de la empresa o "
                 "inicia una nueva?", "Administración", "Semana 2"),
                ("A-09", "¿Se puede cotizar un equipo que no es propiedad del cliente (equipo de "
                 "un tercero)?", "Administrador de ventas", "Semana 3"),
                ("A-10", "¿Se permite cotizar un servicio suelto sin registrar el activo?",
                 "Administrador de ventas", "Semana 2"),
                ("A-11", "¿El técnico debe ver el monto final de lo que solicitó o solo el "
                 "estado de su solicitud?", "Gerencia comercial", "Semana 1"),
                ("A-12", "¿El técnico puede dar de alta clientes nuevos y equipos, o esa captura "
                 "queda solo para el administrador de ventas?", "Gerencia comercial", "Semana 1"),
                ("A-13", "¿Puede el técnico editar o cancelar una solicitud después de que ya "
                 "fue cotizada?", "Administrador de ventas", "Semana 2"),
            ],
            [1.4, 8.2, 4.0, 2.2], font_size=8.5, align_center_cols=(0, 3))

    b.h2("10.3 Revisión del equipo con las preguntas guía")
    b.table(["Pregunta guía", "Respuesta del equipo"],
            [
                ("¿El problema está claramente definido?",
                 "Sí. Se separó el problema (dispersión del proceso de cotización y pérdida de "
                 "las solicitudes que levanta el técnico) de la solución (aplicación web) y se "
                 "enlistaron las consecuencias concretas en el apartado 1.4."),
                ("¿El usuario objetivo es específico?",
                 "Sí. Quedaron dos usuarios operativos con responsabilidades distintas: el "
                 "técnico, que da de alta la pieza o servicio a cotizar, y el administrador de "
                 "ventas, que cotiza. El cliente final se documenta como beneficiario indirecto "
                 "sin acceso al sistema."),
                ("¿Los requerimientos son verificables?",
                 "Los funcionales sí, porque describen una acción observable; los no funcionales "
                 "se redactaron con métricas (3 segundos, 50 usuarios, 99 %, 30 días de respaldo, "
                 "6 pasos para levantar una solicitud) en lugar de adjetivos."),
                ("¿Hay requerimientos demasiado grandes o vagos?",
                 "Al inicio sí: la IA propuso 52 requerimientos y algunos mezclaban dos "
                 "funciones (alta y edición de cliente en un mismo enunciado). En la versión 2.0 "
                 "quedaron %d enunciados unitarios después de eliminar el asistente de IA y "
                 "agregar el flujo del técnico." % N_RF),
                ("¿Los criterios de aceptación permiten probar la funcionalidad?",
                 "Sí. Cada historia tiene entre 3 y 5 criterios, con al menos un caso normal, uno límite y uno de error, y datos concretos de prueba (montos, "
                 "periodicidad, archivos vacíos, descuento del 100 %, acceso por URL con el rol "
                 "técnico) y la matriz de trazabilidad indica la prueba sugerida."),
                ("¿La IA propuso algo que el equipo decidió rechazar?",
                 "Sí. En la versión 1.0 se rechazaron la facturación electrónica, la app móvil "
                 "nativa, la integración con WhatsApp Business, la predicción de cierre de "
                 "ventas y el inicio de sesión con Google o Facebook; y en la versión 2.0 se "
                 "eliminó por completo el asistente de IA que la propia IA había propuesto, "
                 "además de su sugerencia de que el técnico viera el monto cotizado (apartado "
                 "11.3)."),
                ("¿Qué falta aclarar antes de diseñar el sistema?",
                 "Principalmente las reglas de cálculo de modernización, el tratamiento del IVA, "
                 "los datos fiscales obligatorios y hasta dónde puede capturar el técnico "
                 "(clientes y equipos). Son las ambigüedades A-01, A-02, A-07 y A-12 del "
                 "apartado 10.2."),
            ],
            [5.4, 11.0], font_size=9)

    # ══════════════════════════════════════════════════════ 11
    b.h1("Bitácora de uso de la IA", 11)
    b.p("La práctica pide documentar el uso de la IA como evidencia del trabajo del equipo. Aquí "
        "se registra qué herramienta se usó, qué prompt se aplicó en cada fase, qué entregó la "
        "IA, qué se aceptó, qué se modificó y quién fue el responsable, incluyendo los dos "
        "ajustes que dieron origen a la versión 2.0 del documento. También se incluyen las "
        "propuestas que el equipo rechazó y los errores o imprecisiones detectados al revisar las "
        "respuestas.")

    b.h2("11.1 Herramienta utilizada y forma de trabajo")
    b.bullets([
        ("Herramienta: ", "agente de IA en terminal (Hermes Agent), con el modelo de lenguaje configurado en la sesión (en la entrega 3.0 se usó anthropic/claude-haiku-5.5), "
         "utilizado como asistente de análisis de requerimientos equivalente a Codex: no generó "
         "código, solo texto de análisis."),
        ("Forma de trabajo: ", "se ejecutaron 16 prompts (el prompt base de la práctica, los "
         "prompts 5.1 a 5.7, y prompts propios de revisión, ajuste de usuarios, eliminación del "
         "asistente de IA, trazabilidad y formato). Todos los resultados se revisaron antes de "
         "incorporarlos al documento; ninguna respuesta se pegó sin corrección."),
        ("Regla del equipo: ", "todo dato numérico o normativo que la IA mencionara debía "
         "comprobarse con la propuesta inicial o con información del cliente; lo que no se pudo "
         "comprobar se eliminó del documento."),
        ("Reparto: ", "Diego Emilio Alférez Vallejo ejecutó los prompts y registró las "
         "respuestas; Orlando Villalobos Gutiérrez revisó, corrigió y validó los requerimientos, "
         "historias y criterios."),
    ])

    b.h2("11.2 Bitácora por fase")
    b.table(["Fase", "Prompt usado", "Resultado de la IA", "Qué aceptamos", "Qué modificamos",
             "Responsable"],
            [
                ("Análisis del problema", "Prompt base (5.1) con la idea inicial de la propuesta "
                 "inicial.",
                 "Lista de problema principal, necesidad, usuarios afectados y consecuencias, "
                 "además de estadísticas del sector.",
                 "La estructura de cinco puntos y la separación entre problema y necesidad.",
                 "Se eliminaron las estadísticas porque no tenían fuente verificable y se agregó "
                 "la pérdida de las solicitudes que el técnico avisa por llamada o mensaje, que "
                 "la IA no había considerado.",
                 "Diego (operador) / Orlando (revisión)"),
                ("Alcance y exclusiones", "Prompt 5.2 pidiendo tabla de alcance para 8 semanas y "
                 "equipos de 3 integrantes.",
                 "Tabla con 14 funcionalidades dentro del alcance y 9 fuera, con justificación; "
                 "incluyó notificaciones push y chat interno.",
                 "Las agrupaciones por módulo y la justificación de cada exclusión.",
                 "Se ajustó al equipo real de 2 integrantes, se quitaron las notificaciones y el "
                 "chat, y en la versión 2.0 se agregó al fuera del alcance el asistente de IA.",
                 "Diego"),
                ("Req. funcionales", "Prompt 5.3 solicitando requerimientos numerados RF-01, "
                 "RF-02, RF-03, verificables y con manejo de errores.",
                 "Generó 52 requerimientos funcionales, con validaciones repetidas y algunos "
                 "fuera del alcance (facturación, inventario).",
                 "Los requerimientos unitarios y verificables y la numeración por módulo.",
                 "Se consolidaron a 39 en la versión 1.0 y a %d en la versión 2.0 después de "
                 "quitar los cuatro del asistente de IA y agregar los de solicitudes del "
                 "técnico y permisos por rol." % N_RF,
                 "Ambos integrantes"),
                ("Req. no funcionales", "Prompt 5.4 con las ocho categorías exigidas por la "
                 "práctica.",
                 "Listado de requerimientos redactados con adjetivos (“rápido”, “seguro”, “fácil "
                 "de usar”).",
                 "Las ocho categorías solicitadas por la práctica.",
                 "Se sustituyeron los adjetivos por valores medibles y se agregaron los "
                 "requerimientos de restricción de precios para el técnico y de pruebas de "
                 "permisos entre roles (RNF-12, RNF-26).",
                 "Diego"),
                ("Historias de usuario", "Prompt 5.5 pidiendo historias con el formato Como / "
                 "quiero / para y su relación con los requerimientos.",
                 "12 historias de usuario, dos de ellas sin beneficio claro (por ejemplo “Como "
                 "usuario quiero cargar un archivo”).",
                 "El formato solicitado y la relación con los requerimientos funcionales.",
                 "En la versión 1.0 se agregaron las historias faltantes (22). En la versión 2.0 "
                 "se quitaron 3 del asistente de IA y se agregaron 5 del nuevo flujo, quedando "
                 "%d historias." % N_HU,
                 "Orlando"),
                ("Criterios de aceptación", "Prompt 5.6 con el formato Dado / Cuando / Entonces y "
                 "casos de éxito, datos incorrectos, errores y casos límite.",
                 "Criterios generales y repetitivos, casi todos del caso exitoso.",
                 "La plantilla Dado / Cuando / Entonces.",
                 "Se agregaron casos de error y límite en cada historia (archivo vacío, descuento "
                 "del 100 %%, activo de otro cliente, acceso por URL con el rol técnico, solicitud "
                 "ya cotizada por otro usuario) hasta completar %d criterios." % N_CA,
                 "Orlando"),
                ("Ambigüedades", "Prompt 5.7 pidiendo detectar ambigüedad, falta de "
                 "verificabilidad, duplicados, requerimientos grandes, fuera de alcance y "
                 "riesgos técnicos.",
                 "Detectó 6 requerimientos ambiguos o no verificables y 3 riesgos técnicos.",
                 "Las observaciones sobre requerimientos demasiado grandes y no verificables, y "
                 "la lista de riesgos.",
                 "Los puntos que dependen del cliente quedaron registrados como ambigüedades "
                 "(apartado 10.2) para validarlos en la semana 1 y 2.",
                 "Ambos integrantes"),
                ("Trazabilidad", "Prompt propio: relacionar cada RF con su historia, su criterio "
                 "de aceptación y una prueba sugerida.",
                 "Matriz completa, con 5 relaciones equivocadas entre requerimiento y criterio.",
                 "La estructura de la matriz requerida por la práctica.",
                 "Se corrigieron las 5 relaciones equivocadas, por ejemplo el criterio de "
                 "rendimiento que la IA ligó al requerimiento de auditoría.",
                 "Orlando"),
                ("Formato y redacción", "Prompt propio de revisión de redacción, consistencia y "
                 "nombres propios.",
                 "Documento ordenado por secciones, con mejoras de estilo, y nombres de empresa "
                 "inventados (“Ascensores del Bajío”).",
                 "El orden de las secciones y las mejoras de redacción que no cambiaban el "
                 "contenido técnico.",
                 "Se corrigieron los nombres de la empresa cliente (ELEVESE) y del proyecto, y se "
                 "eliminaron viñetas repetidas.",
                 "Diego / Orlando (validación final)"),
                ("Ajuste 1: usuarios y vista del técnico", "Prompt propio: unificar los cuatro "
                 "perfiles en uno solo y agregar el perfil del técnico que da de alta las piezas "
                 "a cotizar, con vistas diferentes por rol.",
                 "Propuesta de conservar los cuatro perfiles con permisos por módulo, y agregar "
                 "el técnico como “consulta de catálogo”.",
                 "La idea de separar vistas y permisos por rol y la de ligar cada solicitud al "
                 "cliente y al activo.",
                 "Se unificaron los cuatro perfiles en el rol administrador de ventas, se creó "
                 "el módulo de solicitudes de cotización del técnico y se agregó la restricción "
                 "explícita de que el técnico no vea precios (RF-05, RF-06, RNF-12).",
                 "Ambos integrantes"),
                ("Ajuste 2: eliminación del asistente de IA", "Prompt propio: eliminar por "
                 "completo el módulo del asistente de IA y ajustar todos los requerimientos en "
                 "consecuencia.",
                 "La IA propuso conservarlo “solo para sugerir servicios sin mostrar precios”; "
                 "también propuso dejar que el técnico viera el monto de lo que solicitó.",
                 "La advertencia de la IA de que ningún importe debe calcularse fuera del "
                 "catálogo (se conservó esa regla para el administrador de ventas).",
                 "Se eliminó el módulo completo: 4 requerimientos funcionales, 1 no funcional, 3 "
                 "historias de usuario y 9 criterios de aceptación; se rehízo la numeración de "
                 "todo el documento y se rechazó que el técnico vea importes.",
                 "Ambos integrantes"),
                ("Renumeración y verificación", "Prompt propio: renumerar requerimientos, "
                 "historias y criterios, y revisar que no queden huecos ni referencias rotas.",
                 "Renumeración y matriz nueva, pero dejó dos referencias cruzadas equivocadas "
                 "entre requerimientos e historias.",
                 "La estructura de la matriz y la idea de validar contra un listado de "
                 "requerimientos.",
                 "El equipo ejecutó una revisión automática del documento que comprueba que cada "
                 "requerimiento tenga al menos una historia de usuario y que los identificadores "
                 "sean consecutivos; se corrigieron las dos referencias equivocadas.",
                 "Orlando"),
            ],
            [2.3, 2.6, 3.2, 2.5, 3.3, 2.5], font_size=8)

    b.h2("11.3 Propuestas de la IA que el equipo rechazó")
    b.table(["Propuesta de la IA", "Motivo del rechazo"],
            [
                ("Asistente de IA que sugiere servicios, refacciones y precios (versión 1.0).",
                 "Se eliminó por completo en la versión 2.0: los precios provienen siempre del "
                 "catálogo que administra el administrador de ventas, que es la única persona "
                 "que cotiza; el asistente no atendía una necesidad real y agregaba dependencia "
                 "externa, costo y riesgo de inventar importes."),
                ("Conservar el asistente “solo para sugerir servicios sin precios”.",
                 "El problema era la dispersión de la información y la falta de registro de las "
                 "solicitudes del técnico; recomendar conceptos no resolvía eso y distraía el "
                 "tiempo de desarrollo disponible."),
                ("Que el técnico vea el monto cotizado de lo que solicitó, “para darle "
                 "transparencia”.",
                 "Contradice el requisito de seguridad de separar vistas: el técnico solo "
                 "consulta el estado de su solicitud (RF-06, RNF-12)."),
                ("Módulo de facturación electrónica con timbrado de CFDI.",
                 "Fuera del alcance: requiere un proveedor autorizado, certificados y "
                 "cumplimiento fiscal que no forman parte de la práctica."),
                ("Aplicación móvil nativa para los técnicos.",
                 "Fuera del alcance y del tiempo disponible; la vista del técnico se resuelve "
                 "desde el navegador del teléfono."),
                ("Envío de propuestas por WhatsApp Business API.",
                 "No hay acceso ni presupuesto para la API; el envío por correo ya cubre la "
                 "necesidad del cliente."),
                ("Uso de IA para predecir qué cliente aceptará la propuesta.",
                 "No existen datos históricos suficientes y no aporta al objetivo inmediato de "
                 "cotizar."),
                ("Renombrar a la empresa cliente como “Ascensores del Bajío, S.A. de C.V.”.",
                 "La IA inventó el nombre: el cliente real es ELEVESE. Se corrigió en todo el "
                 "documento."),
                ("Firma de aceptación con e.firma del SAT.",
                 "Requisito legal fuera del alcance; la propuesta se formaliza con firma "
                 "autógrafa."),
                ("Inicio de sesión con cuenta de Google o Facebook.",
                 "La empresa no lo pidió y agrega dependencia de terceros y temas de privacidad "
                 "que no se quieren atender en esta versión."),
            ],
            [7.0, 9.4], font_size=8.5)

    b.h2("11.4 Errores o imprecisiones de la IA detectadas por el equipo")
    b.table(["Lo que dijo la IA", "Cómo se verificó", "Corrección aplicada"],
            [
                ("Que el sector de elevadores cotiza en promedio en 48 horas.",
                 "No se encontró fuente en la propuesta inicial ni en la información de la "
                 "empresa.",
                 "Se eliminó el dato del planteamiento del problema; quedó redactado con las "
                 "consecuencias que sí son observables."),
                ("Que existía una norma mexicana de seguridad para elevadores, con una clave que "
                 "no se pudo abrir en ninguna fuente oficial.",
                 "Se buscó la norma en fuentes oficiales y no se localizó el documento.",
                 "Se eliminó la referencia normativa del documento y quedó como punto por "
                 "confirmar con la empresa."),
                ("Mantener el módulo del asistente de IA aunque se pidió eliminarlo, con el "
                 "argumento de que “en el futuro serviría”.",
                 "Se revisó contra el alcance definido y contra el tiempo disponible de 8 "
                 "semanas.",
                 "Se eliminó el módulo completo, sus requerimientos, historias y criterios, y se "
                 "registró la propuesta como rechazada (apartado 11.3)."),
                ("Que el técnico pudiera ver el importe de la cotización de su solicitud.",
                 "Revisión contra el requisito de vistas separadas y de seguridad.",
                 "Se reescribió como acceso denegado (RF-06) y como requisito no funcional de "
                 "seguridad (RNF-12); el técnico solo ve el estado."),
                ("“El sistema debe ser fácil de usar” como requerimiento.",
                 "Revisión del validador: no se puede probar ni medir.",
                 "Se reescribió como RNF-02 y RNF-03 con métricas y obligación de mensajes de "
                 "confirmación y de error en español."),
                ("Tres requerimientos distintos para validar campos obligatorios del cliente.",
                 "Comparación de los enunciados: describían la misma función.",
                 "Se consolidaron en un solo requerimiento, que ahora es único y verificable "
                 "(RF-08)."),
                ("Supuso la tecnología del proyecto (React, Node y PostgreSQL) sin que se le "
                 "indicara.",
                 "El equipo no contaba con esa definición ni con la confirmación del cliente.",
                 "Se marcó como supuesto por confirmar en el apartado 3.2 y como RNF-22."),
                ("Historia “Como usuario quiero cargar un archivo”, sin rol ni beneficio.",
                 "Revisión contra la lista de errores comunes de la práctica.",
                 "Se reescribió con rol y beneficio concretos; en la versión 2.0 las historias "
                 "del administrador de ventas y del técnico indican siempre su rol."),
                ("Dejó dos referencias cruzadas equivocadas al renumerar la versión 2.0.",
                 "Revisión automática del documento: cada requerimiento debe tener al menos una "
                 "historia y los identificadores deben ser consecutivos.",
                 "Se corrigieron las referencias y se agregó esa verificación al proceso de "
                 "revisión del equipo."),
            ],
            [6.2, 5.0, 5.2], font_size=8.5)

    b.h2("11.5 Resumen del uso de la IA")
    b.bullets([
        "Prompts ejecutados: 16 (1 prompt base de la práctica, 7 prompts específicos por fase y "
        "8 prompts propios de revisión, ajuste de usuarios, eliminación del asistente, "
        "renumeración y formato).",
        "Requerimientos generados por la IA en el primer intento: 52 funcionales y 19 no "
        "funcionales; versión final 2.0: %d funcionales y %d no funcionales." % (N_RF, N_RNF),
        "Historias de usuario: 12 generadas por la IA; versión 1.0 del equipo: 22; versión 2.0: "
        "%d historias con %d criterios de aceptación." % (N_HU, N_CA),
        "Propuestas rechazadas por el equipo: 10 (apartado 11.3), incluyendo el asistente de IA "
        "en su totalidad.",
        "Errores o imprecisiones detectadas y corregidas: 9 (apartado 11.4), incluyendo un "
        "nombre de empresa inventado, una referencia normativa no verificable y dos referencias "
        "cruzadas equivocadas después de la renumeración.",
        "Decisión final: la IA se usó como apoyo para estructurar, redactar y renumerar; el "
        "equipo decidió siempre el contenido definitivo y ningún requerimiento entró al "
        "documento sin revisión de los dos integrantes.",
    ])

    b.h2("11.6 Bitácora de la práctica (prompts 1 a 4)")
    b.table(["Fase", "Prompt usado", "Resultado de la IA", "Qué aceptamos", "Qué modificamos", "Responsable"],
            [
                ("Historias", "Prompt 1: historias en formato Como / quiero / para, sin funcionalidades fuera del alcance y señalando requerimientos ambiguos.",
                 "Propuso historias y señaló requerimientos ambiguos, entre ellos la vista por rol y los perfiles múltiples.",
                 "El formato Como / quiero / para y la señal de ambigüedad sobre la vista por rol.",
                 "Se separaron las historias del técnico (HU-08, HU-12, HU-13), se quitaron las del asistente de IA y se asignaron prioridades Alta, Media y Baja.",
                 "Diego (operador) / Orlando (analista)"),
                ("Criterios", "Prompt 2: criterios Dado / Cuando / Entonces con caso normal, límite y de error; sin código ni funcionalidades nuevas.",
                 "Criterios normales y pocos casos límite o de error; tres criterios sin cifras.",
                 "El formato Dado / Cuando / Entonces y la separación por tipo de caso.",
                 "Se completaron los casos límite y de error en 9 historias y se cambiaron los criterios sin cifras (CA-14.4, CA-22.3 y CA-24.3) por datos concretos.",
                 "Orlando (validador)"),
                ("Trazabilidad", "Prompt 3: matriz con requerimiento, historia, criterio, prueba sugerida y módulo probable; revisar huecos.",
                 "Matriz con módulos probables; RF-43 apuntaba a un criterio de bitácora.",
                 "Las columnas pedidas y la revisión de huecos.",
                 "Se corrigió RF-43 y se verificó con un script que cada requerimiento tenga historia y criterio existentes.",
                 "Diego"),
                ("Revisión", "Prompt 4: revisión crítica de historias y criterios (ambiguas, grandes, no verificables, fuera de alcance, faltantes).",
                 "Observaciones sobre historias agrupadas y criterios sin cifras.",
                 "Las observaciones sobre criterios sin cifras y sobre casos faltantes.",
                 "Las historias agrupadas (HU-07, HU-10, HU-14 y HU-19) se conservaron porque comparten pantalla; ver apartado 12.1.",
                 "Ambos integrantes"),
            ],
            [2.1, 3.2, 3.2, 2.8, 4.1, 1.8], font_size=7.8, header_size=8)

    # ══════════════════════════════════════════════════════ 12
    b.h1("Revisión del equipo y errores comunes a evitar", 12)
    b.p("Se revisó el documento con la lista de errores comunes de la práctica y con lo que la IA "
        "propuso durante el proceso. La siguiente tabla muestra el error, cómo se presentó en "
        "este proyecto y la corrección aplicada.")
    b.table(["Error común", "Cómo se presentó en este proyecto", "Corrección aplicada"],
            [
                ("Funcionalidad sin necesidad real", "La IA propuso un asistente de "
                 "inteligencia artificial que sugería servicios, refacciones y precios.",
                 "Se eliminó por completo en la versión 2.0: los precios salen del catálogo y el "
                 "administrador de ventas es quien cotiza."),
                ("Confundir los perfiles de usuario", "Se habían definido cuatro perfiles "
                 "(asesor comercial, ingeniero de ventas, administrativo y gerencia) cuando en "
                 "la empresa es una sola persona quien cotiza.",
                 "Se unificaron en el rol administrador de ventas y se agregó el perfil técnico, "
                 "que es el que realmente faltaba, con su propia vista."),
                ("Requerimiento vago", "La IA propuso “el sistema debe ser fácil de usar” y “el "
                 "sistema debe ser rápido”.",
                 "Se reescribieron como RNF-02, RNF-03 y RNF-05 a RNF-08, con mensajes "
                 "obligatorios y tiempos medibles."),
                ("Alcance demasiado grande", "La IA propuso un “sistema integral de gestión de la "
                 "empresa” con facturación, inventario y órdenes de trabajo.",
                 "El alcance se limitó al ciclo solicitud → cotización → propuesta (8 módulos y "
                 "%d requerimientos) y todo lo demás se declaró fuera del alcance." % N_RF),
                ("Confundir solución con problema", "La propuesta inicial parecía pedir “una app "
                 "web con login”.",
                 "El problema se redactó como dispersión de la información y pérdida de las "
                 "solicitudes del técnico, y la aplicación quedó como la solución propuesta."),
                ("No considerar errores", "La IA solo describió el caso con archivos CSV "
                 "correctos y cotizaciones completas.",
                 "Se agregaron requerimientos y criterios para archivos vacíos, columnas "
                 "faltantes, descuentos fuera de rango, accesos denegados y solicitudes ya "
                 "cotizadas por otro usuario."),
                ("Historias sin beneficio", "“Como usuario quiero cargar un archivo”.",
                 "Todas las historias indican rol y beneficio; por ejemplo HU-12 explica que el "
                 "técnico registra la pieza para que no se pierda en una llamada."),
                ("Criterios de aceptación que no se pueden probar", "El criterio inicial era “el "
                 "sistema funciona correctamente”.",
                 "Cada criterio se reescribió con datos concretos de prueba (montos, "
                 "periodicidad, cantidades, rol con el que se ejecuta) y con el resultado "
                 "esperado del sistema."),
                ("Permisos mal definidos", "No se había previsto qué ve exactamente cada tipo de "
                 "usuario.",
                 "Se agregaron RF-05, RF-06, RNF-12, RNF-26 y los criterios CA-03.1 a CA-03.3 "
                 "para probar la separación de vistas entre el técnico y el administrador de "
                 "ventas."),
            ],
            [3.4, 6.4, 6.6], font_size=8.5)

    b.h2("12.1 Revisión crítica de historias y criterios (prompt 4)")
    b.table(["Punto de revisión", "Observación", "Acción tomada"],
            [
                ("Historias ambiguas", "HU-03 y HU-05 describían la vista y la sesión de forma genérica (“vista adecuada”, “sesión segura”).",
                 "Se especificaron la pantalla de prueba de 360 px y los tiempos de inactividad de 19 y 20 minutos."),
                ("Historias demasiado grandes", "HU-07, HU-10, HU-14 y HU-19 agrupan tres o más requerimientos.",
                 "Se conservan agrupadas porque comparten la misma pantalla o flujo; cada requerimiento tiene su criterio y su módulo probable en la matriz."),
                ("Criterios no verificables", "CA-14.4, CA-22.3 y CA-24.3 no tenían cifras o resultado medible.",
                 "Se cambiaron por datos concretos: 200 solicitudes y 3 segundos, dos envíos del mismo documento, 100,000 registros y 2 segundos."),
                ("Funcionalidades fuera del alcance", "Se revisaron las historias contra la sección 4 (facturación, inventario, firma electrónica, app móvil, portal del cliente).",
                 "Ninguna historia incluye esas funcionalidades."),
                ("Casos límite o de error faltantes", "Nueve historias no tenían el caso límite o de error completo.",
                 "Se agregaron los criterios CA-06.4, CA-07.4, CA-08.4, CA-09.4, CA-13.4, CA-14.4, CA-14.5, CA-22.4 y CA-23.4, y se reemplazaron los que no cumplían el tipo."),
                ("Prioridades", "Solo existían prioridades Alta y Media.",
                 "Se asignó Baja a HU-02, HU-09 y HU-20 y a sus requerimientos RF-03, RF-15 y RF-34."),
                ("Trazabilidad", "La matriz no tenía módulo probable y RF-43 apuntaba a un criterio de bitácora.",
                 "Se agregó la columna Módulo probable y se corrigió RF-43 con el criterio CA-14.5."),
            ],
            [3.6, 6.3, 7.3], font_size=8.5)

    b.h2("12.2 Preguntas de revisión de la práctica")
    b.table(["Pregunta", "Respuesta del equipo"],
            [
                ("¿Cuál es la historia de usuario más importante del proyecto?",
                 "HU-14: la bandeja de solicitudes del administrador de ventas y su conversión en cotización. Es el punto donde se conecta el problema original (solicitudes que se pierden por llamada o mensaje) con la solución."),
                ("¿Qué criterio de aceptación valida esa historia?",
                 "CA-14.1 valida que las solicitudes pendientes aparezcan en orden de llegada, y CA-14.3 que la conversión precargue cliente y activo y cambie el estado a Cotizada."),
                ("¿Qué caso de error están considerando?",
                 "CA-14.5: otro usuario ya convirtió la misma solicitud en cotización. El sistema lo informa y no genera una segunda cotización."),
                ("¿Qué requerimiento podría ser ambiguo?",
                 "RF-27, porque la regla de cálculo de las modernizaciones no está definida. Está registrada como ambigüedad A-01 y se valida con el cliente en la semana 1."),
                ("¿Qué funcionalidad propuso la IA que decidieron rechazar?",
                 "El asistente de inteligencia artificial que sugería servicios y precios, eliminado en la versión 2.0. También se rechazó que el técnico viera el monto de su solicitud (sección 11.3)."),
                ("¿Qué prueba usarían para validar el criterio principal?",
                 "Convertir una solicitud pendiente en cotización con un usuario y comprobar el cambio de estado y la precarga de datos, según la fila RF-25 de la matriz; además, abrir la misma solicitud desde otro usuario para probar CA-14.5."),
                ("¿Qué módulo creen que implementará esta funcionalidad?",
                 "M05 Solicitudes para la bandeja y los estados, y M06 Cotizador para la conversión. Ver Anexo A."),
            ],
            [5.6, 11.6], font_size=8.5)

    # ══════════════════════════════════════════════════════ 13
    b.h1("Tarea para la siguiente semana", 13)
    b.p("Los entregables solicitados para la siguiente sesión quedan cubiertos en este mismo "
        "documento, que a partir de ahora es la versión base para las correcciones.")
    b.table(["Entregable solicitado", "Estado en este documento"],
            [
                ("Versión corregida del documento de requerimientos.",
                 "Cubierto: este documento es la versión 2.0, con los dos ajustes aplicados "
                 "(unificación de usuarios y eliminación del asistente de IA) y registrados en el "
                 "control de cambios y en la bitácora."),
                ("Mínimo 5 historias de usuario que cubran los requerimientos funcionales.",
                 "Cubierto y superado: %d historias de usuario (apartado 7) que cubren los %d "
                 "requerimientos funcionales." % (N_HU, N_RF)),
                ("Criterios de aceptación por historia.",
                 "Cubierto: %d criterios en formato Dado / Cuando / Entonces (apartado 8), "
                 "incluyendo casos de error, límite y de permisos por rol." % N_CA),
                ("Matriz simple de trazabilidad: requerimiento, historia, criterio y prueba "
                 "sugerida.",
                 "Cubierto: matriz de %d filas (apartado 9), verificada de forma automática." % N_RF),
                ("Pendientes para la próxima sesión.",
                 "Validar con el cliente las ambigüedades A-01, A-02, A-06, A-07, A-11 y A-12 "
                 "(apartado 10.2) y ajustar el documento a la versión 2.1."),
            ],
            [6.2, 10.2], font_size=9)

    b.h2("13.1 Cronograma propuesto para las 8 semanas")
    b.table(["Semana", "Actividades", "Entregable de la semana"],
            [
                ("1", "Validación del problema, el alcance, los permisos por rol y las "
                 "ambigüedades con el cliente; modelo de datos inicial.",
                 "Documento de requerimientos v2.1 y modelo de datos"),
                ("2 – 3", "Diseño de la interfaz y de la arquitectura; desarrollo de los módulos "
                 "A, B y C (acceso y roles, clientes y activos) y del módulo E (solicitudes del "
                 "técnico).", "Alta de solicitudes por el técnico funcionando en ambiente de "
                 "pruebas"),
                ("4 – 5", "Desarrollo de los módulos D y F (catálogo, tarifas, cotizador y motor "
                 "de cálculo, incluida la conversión de solicitudes).",
                 "Cotización completa generada a partir de una solicitud del técnico"),
                ("6", "Desarrollo del módulo G (PDF, folio, envío y seguimiento de la "
                 "propuesta).", "Propuesta económica en PDF generada desde el sistema"),
                ("7", "Desarrollo del módulo H (tablero, reportes, auditoría y manejo de "
                 "errores); pruebas por requerimiento, incluidas las de permisos por rol.",
                 "Matriz de pruebas ejecutada con evidencia"),
                ("8", "Correcciones, manual de usuario, documentación técnica, despliegue y "
                 "presentación final.", "Sistema en operación y documentación entregada"),
            ],
            [1.6, 8.8, 6.8], font_size=8.5, align_center_cols=(0,))

    # ══════════════════════════════════════════════════════ 14
    b.h1("Conclusión", 14)
    b.p("El documento define con claridad qué debe resolverse: hoy ELEVESE cotiza con "
        "procedimientos manuales y archivos desvinculados, y además las piezas que el técnico "
        "detecta en sitio se avisan por llamada o mensaje, sin quedar registradas. La solución "
        "propuesta es una aplicación web centralizada con dos vistas: el técnico levanta la "
        "solicitud de la pieza o del servicio, y el administrador de ventas la recibe y elabora "
        "la cotización completa hasta la propuesta económica en PDF.")
    b.p("El alcance quedó delimitado a 8 módulos, %d requerimientos funcionales y %d "
        "requerimientos no funcionales verificables, con %d historias de usuario, %d criterios de "
        "aceptación y la matriz de trazabilidad correspondiente. En la versión 2.0 se unificaron "
        "los perfiles de usuario en el rol administrador de ventas, se agregó el perfil técnico y se "
        "eliminó el asistente de inteligencia artificial. En la versión 3.0 cada historia tiene "
        "casos normales, límite y de error, se agregó la prioridad Baja, la matriz incluye el módulo "
        "probable y el Anexo A propone los nueve módulos del sistema."
        % (N_RF, N_RNF, N_HU, N_CA))
    b.p("Para el equipo, el aprendizaje principal de esta práctica fue usar la IA como apoyo y no "
        "como autora: de las respuestas que generó se conservó la estructura y la redacción, pero "
        "se rechazó el asistente de IA que ella misma había propuesto, se corrigieron datos "
        "inventados, requerimientos duplicados, criterios que no se podían probar y permisos mal "
        "definidos. Antes de programar todavía hay que confirmar con el cliente las ambigüedades "
        "del apartado 10.2, sobre todo la regla de cálculo de las modernizaciones, el tratamiento "
        "del IVA y hasta dónde puede capturar el técnico.")

    # ══════════════════════════════════════════════════════ Anexo A
    b.h1("Anexo A. Propuesta inicial de módulos del sistema")
    b.p("El sistema se divide en nueve módulos de diseño. Cada módulo tiene una responsabilidad "
        "única y agrupa los requerimientos de la matriz de trazabilidad. Los módulos M01, M04, M07, "
        "M08 y M09 contienen además utilerías que pueden reutilizarse en otros módulos.")
    b.table(["Módulo", "Responsabilidad", "Requerimientos", "Reutilizable como utilería"],
            [
                ("M01 Acceso y seguridad", "Inicio de sesión, roles, vistas por rol, bloqueos y cierre por inactividad.",
                 "RF-01 a RF-07", "Sí: control de roles que usan todos los módulos."),
                ("M02 Clientes", "Alta, búsqueda, edición, baja lógica y detección de duplicados.",
                 "RF-08 a RF-12", "Sí: validador de formularios (correo, RFC, campos obligatorios)."),
                ("M03 Activos", "Ficha técnica del equipo, validación de rangos e historial.",
                 "RF-13 a RF-15", "No."),
                ("M04 Catálogo y tarifas", "Catálogo de equipos, servicios y refacciones, vigencias y carga CSV.",
                 "RF-16 a RF-19", "Sí: importador CSV con reporte de filas rechazadas."),
                ("M05 Solicitudes", "Alta de solicitud por el técnico, bandeja, estados y consulta del técnico.",
                 "RF-20 a RF-24", "No."),
                ("M06 Cotizador", "Creación de cotizaciones, partidas, edición, borradores, duplicado y estados.",
                 "RF-25, RF-26, RF-31 a RF-34", "No."),
                ("M07 Motor de cálculo", "Precios del catálogo, pólizas, correctivos, subtotal, descuento, IVA y total.",
                 "RF-27 a RF-30", "Sí: lo usan M06, M08 y los reportes de M09."),
                ("M08 Propuesta PDF y seguimiento", "Generación del PDF con folio, envío por correo y registro de aceptación.",
                 "RF-35 a RF-38", "Sí: generador de folios y de PDF."),
                ("M09 Reportes, auditoría y errores", "Tablero, exportación, bitácora y manejo centralizado de errores.",
                 "RF-39 a RF-43", "Sí: bitácora y manejador de errores para todos los módulos."),
            ],
            [4.0, 6.2, 3.2, 3.8], font_size=8.5)

    b.h2("Respuestas a las preguntas de la propuesta de módulos")
    b.table(["Pregunta", "Respuesta"],
            [
                ("¿Qué partes principales tendrá el sistema?",
                 "Nueve módulos, agrupados en tres capas: datos base (M01 a M04), flujo comercial (M05 a M08) y control y reportes (M09)."),
                ("¿Qué responsabilidad tendrá cada módulo?",
                 "La columna Responsabilidad de la tabla anterior. Cada módulo solo modifica sus propios datos y consume los demás por su interfaz."),
                ("¿Qué módulo implementará cada requerimiento?",
                 "La columna Módulo probable de la matriz de trazabilidad (sección 9), que asigna cada uno de los 43 requerimientos."),
                ("¿Qué módulo será más difícil de probar?",
                 "M07 Motor de cálculo. Combina capacidad, número de paradas, periodicidad, descuento, IVA y vigencias de tarifa, y un error de redondeo cambia el total de la propuesta. Se prueba con una tabla de casos con valores esperados calculados a mano."),
                ("¿Qué puede reutilizarse como manejador o utilería?",
                 "El control de roles (M01), el importador CSV (M04), el motor de cálculo (M07), el generador de folios y PDF (M08), y la bitácora y el manejador de errores (M09)."),
            ],
            [5.0, 12.2], font_size=8.5)
