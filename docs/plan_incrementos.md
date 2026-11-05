# Plan de Incrementos — Proyecto COTIZA-ELEV (ELEVESE)

- **Asignatura:** Desarrollo de Aplicaciones y Servicios Virtuales
- **Docente titular:** Ing. Alejandro Montes
- **Equipo de desarrollo:** Orlando Villalobos Gutiérrez y Diego Emilio Alférez Vallejo (2 integrantes)
- **Documento fuente único:** `docs/Entrega_Actualizada_COTIZA-ELEV_v3.pdf` (Versión 3.0, 37 páginas), `docs/Practica_Diseno_Modular_y_Arquitectura_COTIZA-ELEV.pdf` y `docs/Practica_Persistencia_Datos_COTIZA-ELEV.pdf`
- **Incremento base publicado en `main`:** Incremento 1 (`v0.1.0`) — Módulo M07 (`CalculoMotorService`, `RF-28 / HU-16`)

---

## Fase A. Planeación de los incrementos a partir de la especificación

### A1. Verificación de lectura completa del documento de especificaciones

#### 1. Títulos de todas las secciones del documento de especificaciones (`Entrega_Actualizada_COTIZA-ELEV_v3.pdf`)
1. **Presentación** (Tabla de mapeo de entregables)
2. **Datos generales del proyecto e integrantes** (Integrantes y roles, Control de cambios v1.0 a v3.0)
3. **1. Planteamiento del problema** (1.1 Problema principal, 1.2 Necesidad, 1.3 Usuario afectado, 1.4 Consecuencias, 1.5 Redacción mejorada, 1.6 Contexto de la solución)
4. **2. Usuario objetivo** (Tabla de perfiles: Técnico, Administrador de ventas, Cliente final; 2.1 Contexto de uso; 2.2 Usuarios excluidos)
5. **3. Alcance del proyecto** (Figura 1 Flujo, 3.1 Funcionalidades dentro del alcance Módulos A–H, Figura 2 Módulos, 3.2 Restricciones y supuestos)
6. **4. Fuera del alcance** (Exclusiones justificadas: asistente de IA, CFDI, app nativa, portal de clientes, ERP, inventario, firma electrónica)
7. **5. Requerimientos funcionales** (5.1 Módulo A `RF-01..RF-07`, 5.2 Módulo B `RF-08..RF-12`, 5.3 Módulo C `RF-13..RF-15`, 5.4 Módulo D `RF-16..RF-19`, 5.5 Módulo E `RF-20..RF-24`, 5.6 Módulo F `RF-25..RF-34`, 5.7 Módulo G `RF-35..RF-38`, 5.8 Módulo H `RF-39..RF-43`)
8. **6. Requerimientos no funcionales** (6.1 Usabilidad `RNF-01..04`, 6.2 Rendimiento `RNF-05..08`, 6.3 Seguridad básica `RNF-09..15`, 6.4 Mantenibilidad `RNF-16..18`, 6.5 Modularidad `RNF-19..20`, 6.6 Compatibilidad `RNF-21..23`, 6.7 Pruebas `RNF-24..26`, 6.8 Documentación y disponibilidad `RNF-27..29`)
9. **7. Historias de usuario** (`HU-01` a `HU-24`, prioridades Alta/Media/Baja y estimación en días)
10. **8. Criterios de aceptación** (Resumen y tablas detalladas de los 81 criterios `CA-01.1` a `CA-24.3` con casos Normal, Límite y Error)
11. **9. Matriz de trazabilidad** (43 filas: `RF-01` a `RF-43` vinculados a `HU`, `CA`, prueba sugerida y módulo `M01`–`M09`)
12. **10. Riesgos o ambigüedades** (10.1 Riesgos `R-01` a `R-12`, 10.2 Ambigüedades `A-01` a `A-13`, 10.3 Revisión con preguntas guía)
13. **11. Bitácora de uso de la IA** (11.1 Herramienta, 11.2 Bitácora por fase, 11.3 Propuestas rechazadas, 11.4 Errores detectados, 11.5 Resumen, 11.6 Bitácora prompts 1 a 4)
14. **12. Revisión del equipo y errores comunes a evitar** (12.1 Revisión crítica prompt 4, 12.2 Preguntas de revisión)
15. **13. Tarea para la siguiente semana** (13.1 Cronograma propuesto para las 8 semanas)
16. **14. Conclusión**
17. **Anexo A. Propuesta inicial de módulos del sistema** (`M01` a `M09` y respuestas a las 5 preguntas de diseño modular)

#### 2. Tabla de control de totales (Punto de control A1)

| Tipo | Total según análisis de IA (Prompt A1) | Total contado por el equipo en el documento | ¿Coinciden? |
|---|---:|---:|:---:|
| Requerimientos funcionales (RF) | 43 (`RF-01` a `RF-43`) | 43 (`RF-01` a `RF-43`) | Sí |
| Requerimientos no funcionales (RNF) | 29 (`RNF-01` a `RNF-29`) | 29 (`RNF-01` a `RNF-29`) | Sí |
| Historias de usuario (HU) | 24 (`HU-01` a `HU-24`) | 24 (16 Alta, 5 Media, 3 Baja) | Sí |
| Criterios de aceptación (CA) | 81 (`CA-01.1` a `CA-24.3`) | 81 (31 Normal, 24 Límite, 26 Error) | Sí |

#### 3. Registro y resolución de ambigüedades del documento (Sección 10.2)

| Requerimiento / ID | Ambigüedad detectada en el documento (Sección 10.2) | Resolución acordada por el equipo y el docente |
|---|---|---|
| `RF-27` (`A-01`) | Regla exacta para calcular el costo de una modernización o equipo nuevo (¿porcentaje o tabla paramétrica por capacidad y paradas?). | Se resuelve mediante tabla de catálogo paramétrica indexada por `(tipo, capacidad_kg, paradas)` en `M04`/`M07`; si la combinación no existe en catálogo, lanza aviso sin inventar importes (`CA-15.3`). |
| `RF-28` (`A-02`) | ¿El IVA se aplica siempre al 16 % o existen operaciones con tasa distinta? | Tasa estándar fija por defecto en `0.16` (16 %) configurable como parámetro en `CalculoMotorService::TASA_IVA_DEFAULT` para soportar ajustes fiscales sin cambiar la lógica. |
| `RF-33 / RF-34` (`A-03`) | ¿Una cotización enviada conserva sus precios aunque el catálogo cambie después? | Sí: las partidas guardan copia inmutable del precio al momento de emisión; solo al duplicar (`RF-34`) se actualizan a la tarifa vigente avisando el cambio (`CA-20.2`). |
| `RF-28` (`A-04`) | ¿Existe un descuento máximo autorizado en una cotización? | El descuento nunca puede superar el subtotal (`CA-16.2`); un descuento del 100 % requiere bandera explícita de confirmación (`CA-16.3`). |
| `RF-29` (`A-05`) | ¿Cómo se valida la periodicidad frente al número de visitas anuales en pólizas preventivas? | Se soportan periodicidades `mensual` (12), `bimestral` (6) y `trimestral` (4); si las visitas capturadas (`>= 1`) no coinciden con la periodicidad estándar, se calcula el anual y se emite aviso (`CA-17.3`). |
| `RNF-22` (`A-06`) | Versión de PHP y motor de ejecución para pruebas y despliegue. | Estándar fijado en PHP 8.x (`strict_types=1`) con arquitectura desacoplada de servicios de dominio y MySQL 8.0 / InnoDB. |
| `RF-08 / RF-35` (`A-07`) | ¿Qué datos fiscales son obligatorios para el cliente y la propuesta PDF? | Son obligatorios: `razon_social`, `rfc`, `contacto`, `correo`, `telefono` y `domicilio` (`RF-08`, `CA-06.1`, `CA-06.2`). |
| `RF-36` (`A-08`) | ¿Qué formato sigue la numeración de folios consecutivos? | Formato corporativo `COT-AAAA-NNNN` para cotizaciones/propuestas y `SOL-NNNN` para solicitudes, con secuencia anual sin repetir números (`CA-21.2`). |
| `RF-26` (`A-09 / A-10`) | ¿Se puede cotizar un equipo que no pertenece al cliente seleccionado o sin activo? | No: el sistema valida obligatoriamente que el activo pertenezca al cliente seleccionado antes de crear la cotización (`CA-15.2`, `RF-43`). |
| `RF-05 / RF-06 / RF-22` (`A-11`) | ¿El técnico ve el monto final de su solicitud o solo el estado? | Resuelto en Sección 2, `RF-06` y `RNF-12`: el técnico jamás ve precios, tarifas, montos ni folios de propuesta; solo ve el estado (`Pendiente de cotizar`, `Cotizada` o `Rechazada`). |
| `RF-13 / RF-20` (`A-12 / A-13`) | ¿Hasta dónde puede capturar el técnico en campo? | El técnico consulta clientes, registra activos nuevos cuando no existen en el sistema (`RF-13`, `CA-08.1`) y da de alta solicitudes (`RF-20`); una solicitud ya cotizada queda bloqueada contra nueva atención (`CA-14.5`). |

---

### A2. Inventario completo de requerimientos

#### 1. Requerimientos Funcionales (`RF-01` a `RF-43`)

| ID | Descripción breve (máximo 15 palabras) | Tipo | Prioridad | Depende de (con cita de sección) | Sección del documento | Estado (al inicio de Fase A) | Incremento asignado |
|---|---|---|---|---|---|---|---|
| `RF-01` | Iniciar sesión únicamente con correo y contraseña válidos registrados por administrador. | RF | Alta | Ninguna (base de acceso, Sección 5.1) | 5.1 Módulo A — Acceso, roles y permisos | Pendiente | Incremento 2 (`v0.2.0`) |
| `RF-02` | Bloquear cuenta 15 minutos tras 5 intentos fallidos consecutivos de acceso. | RF | Alta | RF-01 (requiere intento de autenticación, Sección 5.1) | 5.1 Módulo A — Acceso, roles y permisos | Pendiente | Incremento 2 (`v0.2.0`) |
| `RF-03` | Recuperar contraseña mediante enlace enviado al correo con vigencia de 30 minutos. | RF | Baja | RF-01, RF-04 (requiere cuenta y correo registrado, Sección 5.1) | 5.1 Módulo A — Acceso, roles y permisos | Pendiente | Incremento 5 (`v0.5.0`) |
| `RF-04` | Permitir al administrador dar de alta usuarios asignando rol técnico o administrador. | RF | Alta | RF-01 (requiere sesión de administrador, Sección 5.1) | 5.1 Módulo A — Acceso, roles y permisos | Pendiente | Incremento 2 (`v0.2.0`) |
| `RF-05` | Mostrar vistas y menús diferenciados según el rol del usuario autenticado. | RF | Alta | RF-01, RF-04 (requiere rol asignado en sesión, Sección 5.1) | 5.1 Módulo A — Acceso, roles y permisos | Pendiente | Incremento 2 (`v0.2.0`) |
| `RF-06` | Impedir al técnico consultar precios o cotizaciones por menú o URL directa y auditar. | RF | Alta | RF-01, RF-05 (control de permisos por rol, Sección 5.1) | 5.1 Módulo A — Acceso, roles y permisos | Pendiente | Incremento 2 (`v0.2.0`) |
| `RF-07` | Cerrar sesión tras 20 minutos de inactividad avisando 1 minuto antes. | RF | Media | RF-01 (requiere sesión activa, Sección 5.1) | 5.1 Módulo A — Acceso, roles y permisos | Pendiente | Incremento 2 (`v0.2.0`) |
| `RF-08` | Registrar clientes validando razón social, RFC, contacto, correo, teléfono y domicilio. | RF | Alta | RF-01, RF-05 (acceso autorizado al módulo B, Sección 5.2) | 5.2 Módulo B — Clientes | Pendiente | Incremento 2 (`v0.2.0`) |
| `RF-09` | Buscar y filtrar clientes por razón social, RFC o correo hasta 5,000 registros. | RF | Alta | RF-08 (requiere clientes registrados, Sección 5.2) | 5.2 Módulo B — Clientes | Pendiente | Incremento 2 (`v0.2.0`) |
| `RF-10` | Editar datos de un cliente conservando historial de usuario y fecha de modificación. | RF | Media | RF-08 (requiere cliente registrado, Sección 5.2) | 5.2 Módulo B — Clientes | Pendiente | Incremento 2 (`v0.2.0`) |
| `RF-11` | Avisar antes de guardar cuando se detecte cliente duplicado por RFC o razón social. | RF | Alta | RF-08 (validación durante el alta de cliente, Sección 5.2) | 5.2 Módulo B — Clientes | Pendiente | Incremento 2 (`v0.2.0`) |
| `RF-12` | Dar de baja lógica a un cliente conservando activos, solicitudes y cotizaciones históricas. | RF | Media | RF-08 (requiere cliente registrado, Sección 5.2) | 5.2 Módulo B — Clientes | Pendiente | Incremento 2 (`v0.2.0`) |
| `RF-13` | Registrar activo ligado obligatoriamente a un cliente con su ficha técnica completa. | RF | Alta | RF-08 (asociación obligatoria a un cliente, Sección 5.3) | 5.3 Módulo C — Activos (equipos de elevación) | Pendiente | Incremento 2 (`v0.2.0`) |
| `RF-14` | Validar rangos técnicos del activo (capacidad > 0, paradas >= 2) con aviso confirmable. | RF | Media | RF-13 (validación durante captura del activo, Sección 5.3) | 5.3 Módulo C — Activos (equipos de elevación) | Pendiente | Incremento 2 (`v0.2.0`) |
| `RF-15` | Mostrar historial del activo ordenado por fecha, sin importes al técnico y completo a ventas. | RF | Baja | RF-05, RF-13, RF-20, RF-26 (requiere activo y movimientos previos, Sección 5.3) | 5.3 Módulo C — Activos (equipos de elevación) | Pendiente | Incremento 5 (`v0.5.0`) |
| `RF-16` | Administrar catálogo de equipos nuevos con especificaciones técnicas y costo base. | RF | Alta | RF-01, RF-05 (acceso exclusivo de administrador de ventas, Sección 5.4) | 5.4 Módulo D — Catálogo y tarifas | Pendiente | Incremento 2 (`v0.2.0`) |
| `RF-17` | Administrar catálogo de servicios y refacciones con clave, descripción, unidad y precio. | RF | Alta | RF-01, RF-05 (acceso exclusivo de administrador de ventas, Sección 5.4) | 5.4 Módulo D — Catálogo y tarifas | Pendiente | Incremento 2 (`v0.2.0`) |
| `RF-18` | Manejar tarifas con fechas de vigencia y usar solo el precio vigente al cotizar. | RF | Alta | RF-16, RF-17 (requiere conceptos del catálogo, Sección 5.4) | 5.4 Módulo D — Catálogo y tarifas | Pendiente | Incremento 2 (`v0.2.0`) |
| `RF-19` | Actualizar precios masivamente por CSV validando formato y reportando actualizados y rechazados. | RF | Media | RF-17, RF-18 (actualiza tarifas del catálogo, Sección 5.4) | 5.4 Módulo D — Catálogo y tarifas | Pendiente | Incremento 2 (`v0.2.0`) |
| `RF-20` | Permitir al técnico dar de alta solicitud indicando cliente, activo, pieza, cantidad y urgencia. | RF | Alta | RF-05, RF-08, RF-13 (requiere rol técnico, cliente y activo, Sección 5.5) | 5.5 Módulo E — Solicitudes de cotización (vista del técnico) | Pendiente | Incremento 3 (`v0.3.0`) |
| `RF-21` | Asignar estado Pendiente de cotizar y listar en bandeja de ventas por antigüedad. | RF | Alta | RF-20 (requiere solicitud creada por el técnico, Sección 5.5) | 5.5 Módulo E — Solicitudes de cotización (vista del técnico) | Pendiente | Incremento 3 (`v0.3.0`) |
| `RF-22` | Permitir al técnico consultar estado de sus solicitudes sin mostrar precios ni importes. | RF | Alta | RF-06, RF-20 (aislamiento de precios para solicitudes del técnico, Sección 5.5) | 5.5 Módulo E — Solicitudes de cotización (vista del técnico) | Pendiente | Incremento 3 (`v0.3.0`) |
| `RF-23` | Avisar al técnico si existe solicitud pendiente del mismo activo y pieza, pidiendo motivo. | RF | Media | RF-20, RF-21 (verifica solicitudes pendientes del activo, Sección 5.5) | 5.5 Módulo E — Solicitudes de cotización (vista del técnico) | Pendiente | Incremento 3 (`v0.3.0`) |
| `RF-24` | Permitir al administrador marcar solicitud como cotizada o rechazarla con motivo escrito. | RF | Alta | RF-21 (opera sobre solicitudes pendientes en bandeja, Sección 5.5) | 5.5 Módulo E — Solicitudes de cotización (vista del técnico) | Pendiente | Incremento 3 (`v0.3.0`) |
| `RF-25` | Convertir solicitud pendiente en cotización precargando cliente/activo y cambiando estado a Cotizada. | RF | Alta | RF-21, RF-24 (toma solicitud pendiente y cambia su estado, Sección 5.6) | 5.6 Módulo F — Cotizador (vista del administrador de ventas) | Pendiente | Incremento 3 (`v0.3.0`) |
| `RF-26` | Crear cotización seleccionando cliente, activo y tipo (nuevo, modernización, preventivo, correctivo). | RF | Alta | RF-08, RF-13, RF-18 (requiere cliente, activo y catálogo vigente, Sección 5.6) | 5.6 Módulo F — Cotizador (vista del administrador de ventas) | Pendiente | Incremento 3 (`v0.3.0`) |
| `RF-27` | Calcular partidas de equipo nuevo y modernización con reglas del catálogo sin precio manual. | RF | Alta | RF-16, RF-18, RF-26, RF-28 (usa catálogo vigente y motor M07, Sección 5.6) | 5.6 Módulo F — Cotizador (vista del administrador de ventas) | Pendiente | Incremento 3 (`v0.3.0`) |
| `RF-28` | Calcular automáticamente subtotal por concepto, descuento, IVA (16%) y total con desglose. | RF | Alta | Ninguna (lógica matemática pura de dominio M07, Sección 5.6 y Anexo A) | 5.6 Módulo F — Cotizador (vista del administrador de ventas) | Implementado (Inc. 1) | Incremento 1 (`v0.1.0`) |
| `RF-29` | Cotizar pólizas preventivas por periodicidad y visitas anuales mostrando costo visita y anual. | RF | Alta | RF-17, RF-18, RF-26, RF-28 (usa tarifa vigente y motor M07, Sección 5.6) | 5.6 Módulo F — Cotizador (vista del administrador de ventas) | Pendiente | Incremento 3 (`v0.3.0`) |
| `RF-30` | Cotizar mantenimiento correctivo capturando diagnóstico, servicios, refacciones y horas de mano de obra. | RF | Alta | RF-17, RF-18, RF-26, RF-28 (usa refacciones del catálogo y motor M07, Sección 5.6) | 5.6 Módulo F — Cotizador (vista del administrador de ventas) | Pendiente | Incremento 3 (`v0.3.0`) |
| `RF-31` | Agregar, eliminar y modificar cantidad de partidas recalculando totales de inmediato. | RF | Alta | RF-26, RF-28 (recalcula con el motor M07, Sección 5.6) | 5.6 Módulo F — Cotizador (vista del administrador de ventas) | Pendiente | Incremento 3 (`v0.3.0`) |
| `RF-32` | Guardar cotización en estado borrador y permitir recuperarla después para continuar edición. | RF | Alta | RF-26, RF-28 (persiste cotización calculada, Sección 5.6) | 5.6 Módulo F — Cotizador (vista del administrador de ventas) | Pendiente | Incremento 3 (`v0.3.0`) |
| `RF-33` | Asignar estado comercial (borrador, enviada, aceptada, rechazada, vencida) y fecha de vigencia. | RF | Media | RF-32 (gestiona ciclo de vida de la cotización guardada, Sección 5.6) | 5.6 Módulo F — Cotizador (vista del administrador de ventas) | Pendiente | Incremento 3 (`v0.3.0`) |
| `RF-34` | Duplicar cotización previa como base copiando partidas y actualizando a precios vigentes. | RF | Baja | RF-18, RF-28, RF-32 (requiere cotización previa y tarifas vigentes, Sección 5.6) | 5.6 Módulo F — Cotizador (vista del administrador de ventas) | Pendiente | Incremento 5 (`v0.5.0`) |
| `RF-35` | Generar propuesta económica en PDF con plantilla corporativa, partidas, totales y espacio de firma. | RF | Alta | RF-28, RF-32, RF-36 (requiere cotización completa y folio, Sección 5.7) | 5.7 Módulo G — Documento y seguimiento | Pendiente | Incremento 4 (`v0.4.0`) |
| `RF-36` | Asignar folio consecutivo único por año y tipo de documento y mostrarlo en el PDF. | RF | Alta | RF-32 (numera las propuestas generadas de cotizaciones, Sección 5.7) | 5.7 Módulo G — Documento y seguimiento | Pendiente | Incremento 4 (`v0.4.0`) |
| `RF-37` | Enviar propuesta por correo al cliente registrado y guardar fecha, usuario y destinatario. | RF | Media | RF-08, RF-35 (requiere correo del cliente y PDF generado, Sección 5.7) | 5.7 Módulo G — Documento y seguimiento | Pendiente | Incremento 4 (`v0.4.0`) |
| `RF-38` | Registrar si la propuesta fue aceptada o rechazada por el cliente con fecha y nota. | RF | Media | RF-33, RF-35 (actualiza estado comercial tras emisión de propuesta, Sección 5.7) | 5.7 Módulo G — Documento y seguimiento | Pendiente | Incremento 4 (`v0.4.0`) |
| `RF-39` | Mostrar tablero con cotizaciones emitidas, aceptadas, rechazadas, monto cotizado y solicitudes pendientes. | RF | Media | RF-21, RF-33, RF-38 (consolida métricas de solicitudes y cotizaciones, Sección 5.8) | 5.8 Módulo H — Reportes, administración y errores | Pendiente | Incremento 4 (`v0.4.0`) |
| `RF-40` | Exportar reporte de cotizaciones y solicitudes filtrado por fechas, cliente y técnico en CSV/PDF. | RF | Media | RF-21, RF-33, RF-39 (exporta los registros filtrados del tablero, Sección 5.8) | 5.8 Módulo H — Reportes, administración y errores | Pendiente | Incremento 4 (`v0.4.0`) |
| `RF-41` | Registrar bitácora inmutable de auditoría con usuario, fecha y acción en operaciones críticas. | RF | Media | RF-01, RF-06, RF-18, RF-35 (audita eventos de seguridad y negocio, Sección 5.8) | 5.8 Módulo H — Reportes, administración y errores | Pendiente | Incremento 4 (`v0.4.0`) |
| `RF-42` | Centralizar manejo de errores mostrando mensaje en español sin trazas técnicas y registrando evento. | RF | Alta | RF-41 (registra el fallo técnico en bitácora y formatea salida, Sección 5.8) | 5.8 Módulo H — Reportes, administración y errores | Pendiente | Incremento 4 (`v0.4.0`) |
| `RF-43` | Rechazar con mensaje claro operaciones sobre registros inexistentes, cancelados o ya cotizados. | RF | Media | RF-12, RF-13, RF-24, RF-42 (valida integridad de estado entre módulos, Sección 5.8) | 5.8 Módulo H — Reportes, administración y errores | Pendiente | Incremento 4 (`v0.4.0`) |

#### 2. Requerimientos No Funcionales (`RNF-01` a `RNF-29`)

| ID | Descripción breve (máximo 15 palabras) | Tipo | Prioridad | Depende de (con cita de sección) | Sección del documento | Estado |
|---|---|---|---|---|---|---|
| `RNF-01` | Interfaz completamente en español accesible desde navegador web sin instalación local. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.1) | 6.1 Usabilidad | Transversal (Inc. 2–5) |
| `RNF-02` | Confirmación visual de guardado o modificación en máximo 2 segundos. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.1) | 6.1 Usabilidad | Transversal (Inc. 2–5) |
| `RNF-03` | Mensajes de error en español indicando motivo y acción sin trazas técnicas. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.1) | 6.1 Usabilidad | Transversal (Inc. 2–5) |
| `RNF-04` | Cotización preventiva en <=10 pasos (<5 min) y solicitud técnica en <=6 pasos (<2 min). | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.1) | 6.1 Usabilidad | Transversal (Inc. 2–5) |
| `RNF-05` | Tiempo de respuesta <=3 segundos en el 95% de peticiones con 50 usuarios. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.2) | 6.2 Rendimiento | Transversal (Inc. 2–5) |
| `RNF-06` | Generación de propuesta económica en PDF en 10 segundos o menos. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.2) | 6.2 Rendimiento | Transversal (Inc. 2–5) |
| `RNF-07` | Búsquedas y filtros en <=2 segundos con 5,000 clientes y 100,000 cotizaciones. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.2) | 6.2 Rendimiento | Transversal (Inc. 2–5) |
| `RNF-08` | Bandeja lista hasta 200 solicitudes en <=3 s y refleja altas en <=5 s. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.2) | 6.2 Rendimiento | Transversal (Inc. 2–5) |
| `RNF-09` | Tráfico por HTTPS (TLS 1.2+) y contraseñas con hash y sal (bcrypt/argon2). | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.3) | 6.3 Seguridad básica | Transversal (Inc. 2–5) |
| `RNF-10` | Expiración de sesión a los 20 minutos de inactividad e invalidación en servidor. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.3) | 6.3 Seguridad básica | Transversal (Inc. 2–5) |
| `RNF-11` | Validación de autorización por rol en el servidor en cada petición HTTP. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.3) | 6.3 Seguridad básica | Transversal (Inc. 2–5) |
| `RNF-12` | Vista y respuestas del técnico sin exponer precios, tarifas, márgenes ni importes. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.3) | 6.3 Seguridad básica | Transversal (Inc. 2–5) |
| `RNF-13` | Validación y sanitización de entradas en cliente y servidor contra SQLi y XSS. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.3) | 6.3 Seguridad básica | Transversal (Inc. 2–5) |
| `RNF-14` | Respaldo automático diario de base de datos con retención de 30 días. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.3) | 6.3 Seguridad básica | Transversal (Inc. 2–5) |
| `RNF-15` | Datos fiscales accesibles solo a usuarios autorizados (ocultos al rol técnico). | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.3) | 6.3 Seguridad básica | Transversal (Inc. 2–5) |
| `RNF-16` | Código en repositorio Git con historial descriptivo y convención de estilo. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.4) | 6.4 Mantenibilidad | Implementado (Inc. 1) |
| `RNF-17` | Credenciales y parámetros de conexión en archivo de configuración fuera del repositorio. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.4) | 6.4 Mantenibilidad | Transversal (Inc. 2–5) |
| `RNF-18` | Lógica de cálculo de precios centralizada en un único componente reutilizable (M07). | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.4) | 6.4 Mantenibilidad | Implementado (Inc. 1) |
| `RNF-19` | Módulos expuestos como servicios con interfaz documentada para pruebas independientes. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.5) | 6.5 Modularidad | Transversal (Inc. 2–5) |
| `RNF-20` | Tarifas y reglas de cálculo configurables desde la interfaz sin modificar código. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.5) | 6.5 Modularidad | Transversal (Inc. 2–5) |
| `RNF-21` | Compatible con Chrome, Edge y Firefox (1366x768) y vista móvil para el técnico. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.6) | 6.6 Compatibilidad | Transversal (Inc. 2–5) |
| `RNF-22` | Despliegue en servidor Linux con Apache/Nginx, PHP 8.x y MySQL 8. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.6) | 6.6 Compatibilidad | Transversal (Inc. 2–5) |
| `RNF-23` | Exportación de datos en formatos abiertos (CSV y PDF) legibles estándar. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.6) | 6.6 Compatibilidad | Transversal (Inc. 2–5) |
| `RNF-24` | Al menos un caso de prueba documentado con evidencia por requerimiento (normal/límite/error). | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.7) | 6.7 Pruebas | Transversal (Inc. 2–5) |
| `RNF-25` | Ambiente de pruebas separado con datos de ejemplo y pruebas de regresión. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.7) | 6.7 Pruebas | Transversal (Inc. 2–5) |
| `RNF-26` | Prueba explícita por pantalla de que el técnico no puede obtener precios. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.7) | 6.7 Pruebas | Transversal (Inc. 2–5) |
| `RNF-27` | Manual de usuario en PDF en español con secciones separadas por rol. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.8) | 6.8 Documentación y disponibilidad | Transversal (Inc. 2–5) |
| `RNF-28` | Documentación técnica con modelo de datos, diccionario, servicios e instructivo. | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.8) | 6.8 Documentación y disponibilidad | Transversal (Inc. 2–5) |
| `RNF-29` | Disponibilidad mínima del 99% en horario laboral (lunes a viernes 8:00 a 19:00). | RNF | no indicada | Transversal a módulos M01–M09 (Sección 6.8) | 6.8 Documentación y disponibilidad | Transversal (Inc. 2–5) |

> **Pregunta de control A2:** *¿Cada dependencia está respaldada por una sección del documento, o es una suposición del modelo?*  
> **Respuesta del equipo:** Todas las dependencias registradas provienen directamente de las reglas explícitas de las Secciones 5.1 a 5.8, la Matriz de Trazabilidad (Sección 9) y la tabla de módulos del Anexo A del documento `Entrega_Actualizada_COTIZA-ELEV_v3.pdf`. Se eliminó cualquier dependencia supuesta hacia servicios externos (como servidores SMTP de terceros o motores de IA), dado que la Sección 4 los declara fuera del alcance.

---

### A3. Restricciones del equipo y fichas aprobadas por incremento

#### 1. Restricciones reales del equipo

| Dato | Respuesta del equipo |
|---|---|
| Semanas disponibles hasta la entrega final | 4 semanas efectivas restantes (Semanas 5 a 8 del cronograma de 8 semanas de la Sección 13.1). |
| Número de incrementos que pueden cerrar | 4 incrementos adicionales (`Incremento 2 · v0.2.0` a `Incremento 5 · v0.5.0`), sumados al `Incremento 1 · v0.1.0` ya publicado en `main` (total: 5 incrementos). |
| Integrantes del equipo | 2 integrantes: Orlando Villalobos Gutiérrez y Diego Emilio Alférez Vallejo. |

#### 2. Fichas por incremento

##### Ficha de referencia: Incremento 1 · `v0.1.0` (Publicado en `main`)

| Campo | Respuesta del equipo |
|---|---|
| **Incremento y versión** | Incremento 1 · `v0.1.0` |
| **Objetivo en una frase** | Implementar y verificar el motor de cálculo comercial puro (`M07 CalculoMotorService`) para calcular importes por partida, subtotal, IVA (16 %) y total a dos decimales. |
| **RF e HU incluidos** | `RF-28` (`HU-16`) |
| **Criterios de aceptación (con sección)** | `CA-16.1` (Normal), `CA-16.2` (Límite/Error), `CA-16.3` (Error/Límite) — Sección 8 (pág. 20) y Sección 9 (pág. 25). |
| **Módulos involucrados** | `M07 Motor de cálculo` (`src/Services/CalculoMotorService.php`) — Anexo A. |
| **Depende de** | Ninguno (componente de dominio puro sin dependencias externas). |
| **Pruebas mínimas: normal, límite y error** | Normal: 2 partidas de $1,000 + 1 mano de obra de $500 = subtotal $2,500, IVA $400, total $2,900. Límite: 3 refacciones a $333.33 = subtotal $999.99, IVA redondeado $160.00, total $1,159.99. Error: rechazo de precio negativo (`-500`) y cantidad `0`. |
| **Rama de Git** | `main` |
| **Fecha de cierre** | 3 de noviembre de 2026 |
| **Decisión del equipo y justificación** | **Aceptada** · Implementado y verificado al 100 % (4/4 pruebas pasando en `tests/run_tests.php`, commit `9fb2b49` y tag `v0.1.0`). |

##### Ficha del Incremento 2 · `v0.2.0`

| Campo | Respuesta del equipo |
|---|---|
| **Incremento y versión** | Incremento 2 · `v0.2.0` |
| **Objetivo en una frase** | Implementar la autenticación y control de acceso por rol RBAC con bloqueo e inactividad (`M01`), el directorio de clientes (`M02`), el registro de activos (`M03`) y el catálogo de tarifas con importación CSV (`M04`), garantizando que el técnico no vea precios e integrando las tarifas vigentes con el motor `M07` del Incremento 1. |
| **RF e HU incluidos** | **17 RF / 8 HU:** `RF-01`, `RF-02`, `RF-04`, `RF-05`, `RF-06`, `RF-07` (`HU-01`, `HU-03`, `HU-04`, `HU-05`); `RF-08`, `RF-09`, `RF-10`, `RF-11`, `RF-12` (`HU-06`, `HU-07`); `RF-13`, `RF-14` (`HU-08`); `RF-16`, `RF-17`, `RF-18`, `RF-19` (`HU-10`, `HU-11`). |
| **Criterios de aceptación (con sección)** | Sección 8: `CA-01.1`, `CA-01.2`, `CA-01.3` (`HU-01`); `CA-03.1`, `CA-03.2`, `CA-03.3` (`HU-03`); `CA-04.1`, `CA-04.2`, `CA-04.3` (`HU-04`); `CA-05.1`, `CA-05.2`, `CA-05.3` (`HU-05`); `CA-06.1`, `CA-06.2`, `CA-06.3`, `CA-06.4` (`HU-06`); `CA-07.1`, `CA-07.2`, `CA-07.3`, `CA-07.4` (`HU-07`); `CA-08.1`, `CA-08.2`, `CA-08.3`, `CA-08.4` (`HU-08`); `CA-10.1`, `CA-10.2`, `CA-10.3` (`HU-10`); `CA-11.1`, `CA-11.2`, `CA-11.3` (`HU-11`). |
| **Módulos involucrados** | `M01 Acceso y seguridad`, `M02 Clientes`, `M03 Activos`, `M04 Catálogo y tarifas`, integrados con `M07 Motor de cálculo` (Anexo A). |
| **Depende de** | Incremento 1 (`v0.1.0` — `M07 CalculoMotorService`). |
| **Pruebas mínimas: normal, límite y error** | **Normal:** Login por rol (`CA-01.1`), menú filtrado (`CA-03.1`), alta usuario (`CA-04.1`), alta/búsqueda/edición de cliente (`CA-06.1`, `CA-07.1`, `CA-07.2`), alta de activo (`CA-08.1`), alta de tarifa vigente consumida por `M07` (`CA-10.1`) y carga CSV válida (`CA-11.1`). **Límite:** Bloqueo 15 min al 5to intento (`CA-01.3`), vista móvil 360px (`CA-03.3`), cambio de rol (`CA-04.3`), aviso inactividad min 19 y cierre min 20 (`CA-05.1`, `CA-05.2`), razón social de 300 caracteres (`CA-06.4`), baja lógica conservando historial (`CA-07.3`), activo con 2 paradas exactas (`CA-08.4`), tarifa futura no aplicada hoy (`CA-10.3`) y CSV solo con encabezados (`CA-11.3`). **Error:** Contraseña errónea (`CA-01.2`), acceso directo por URL de técnico a precios bloqueado y auditado (`CA-03.2`), correo duplicado (`CA-04.2`), regreso tras expiración (`CA-05.3`), RFC vacío/duplicado (`CA-06.2`, `CA-06.3`, `CA-07.4`), activo sin cliente o fuera de rango (`CA-08.2`, `CA-08.3`), precio `<= 0` (`CA-10.2`) y CSV sin columna `precio` (`CA-11.2`). |
| **Rama de Git** | `incremento-2` |
| **Fecha de cierre** | Semana 5 (Noviembre 2026) |
| **Decisión del equipo y justificación** | **Aceptada** · Resuelve primero las entidades maestras de las que dependen las solicitudes y cotizaciones, y mitiga desde el inicio el riesgo crítico `R-07` (filtración de precios al técnico, `RF-05`/`RF-06`/`RNF-12`). Excluye temporalmente `RF-03` y `RF-15` por tener prioridad Baja en el documento. |

##### Ficha del Incremento 3 · `v0.3.0`

| Campo | Respuesta del equipo |
|---|---|
| **Incremento y versión** | Incremento 3 · `v0.3.0` |
| **Objetivo en una frase** | Implementar el levantamiento y bandeja de solicitudes técnicas sin importes (`M05`) y el cotizador comercial completo (`M06` y extensión de `M07`) para convertir solicitudes en cotizaciones y calcular equipos nuevos, modernizaciones, pólizas preventivas y correctivos con manejo de borradores y estados. |
| **RF e HU incluidos** | **13 RF / 7 HU:** `RF-20`, `RF-21`, `RF-22`, `RF-23`, `RF-24` (`HU-12`, `HU-13`, `HU-14`); `RF-25`, `RF-26`, `RF-27`, `RF-29`, `RF-30`, `RF-31`, `RF-32`, `RF-33` (`HU-14`, `HU-15`, `HU-17`, `HU-18`, `HU-19`). |
| **Criterios de aceptación (con sección)** | Sección 8: `CA-12.1`, `CA-12.2`, `CA-12.3` (`HU-12`); `CA-13.1`, `CA-13.2`, `CA-13.3`, `CA-13.4` (`HU-13`); `CA-14.1`, `CA-14.2`, `CA-14.3`, `CA-14.4`, `CA-14.5` (`HU-14`); `CA-15.1`, `CA-15.2`, `CA-15.3` (`HU-15`); `CA-16.1`, `CA-16.2`, `CA-16.3` con descuento (`HU-16`); `CA-17.1`, `CA-17.2`, `CA-17.3` (`HU-17`); `CA-18.1`, `CA-18.2`, `CA-18.3` (`HU-18`); `CA-19.1`, `CA-19.2`, `CA-19.3` (`HU-19`). |
| **Módulos involucrados** | `M05 Solicitudes`, `M06 Cotizador` y `M07 Motor de cálculo` (integrados con `M01`, `M02`, `M03` y `M04` del Incremento 2) — Anexo A. |
| **Depende de** | Incremento 1 (`v0.1.0`) e Incremento 2 (`v0.2.0`). |
| **Pruebas mínimas: normal, límite y error** | **Normal:** Alta de solicitud por técnico (`CA-12.1`), consulta sin precios (`CA-13.1`, `CA-13.2`), bandeja ordenada y conversión a cotización precargando cliente/activo (`CA-14.1`, `CA-14.2`, `CA-14.3`), cotización de equipo nuevo con precio bloqueado (`CA-15.1`), póliza bimestral (`CA-17.1`), correctivo con refacciones y mano de obra (`CA-18.1`) y recálculo inmediato al editar partidas (`CA-19.1`). **Límite:** Solicitud duplicada confirmada con motivo (`CA-12.3`), técnico sin solicitudes (`CA-13.4`), paginación de 200 solicitudes en bloques de 50 (`CA-14.4`), combinación capacidad/paradas inexistente (`CA-15.3`), descuento del 100 % confirmado (`CA-16.3`), discrepancia periodicidad/visitas (`CA-17.3`), 0.5 horas de mano de obra (`CA-18.3`) y vencimiento a los 15 días (`CA-19.2`). **Error:** Solicitud sin pieza o activo (`CA-12.2`), técnico consultando solicitud ajena (`CA-13.3`), solicitud ya cotizada por otro usuario (`CA-14.5`), activo de otro cliente (`CA-15.2`), descuento mayor al subtotal (`CA-16.2`), póliza con 0 visitas (`CA-17.2`), correctivo sin diagnóstico (`CA-18.2`) y envío bloqueado en borrador incompleto (`CA-19.3`). |
| **Rama de Git** | `incremento-3` |
| **Fecha de cierre** | Semana 6 (Noviembre 2026) |
| **Decisión del equipo y justificación** | **Aceptada** · Completa el flujo operativo central del negocio de ELEVESE de extremo a extremo (Técnico levanta solicitud en sitio → Administrador convierte y cotiza las 4 modalidades con `M07`). |

##### Ficha del Incremento 4 · `v0.4.0`

| Campo | Respuesta del equipo |
|---|---|
| **Incremento y versión** | Incremento 4 · `v0.4.0` |
| **Objetivo en una frase** | Implementar la emisión de la propuesta económica en PDF con folio anual consecutivo, envío por correo y registro de aceptación/rechazo (`M08`), junto con el tablero directivo, exportación CSV, bitácora inmutable de auditoría y manejo centralizado de errores en español (`M09`). |
| **RF e HU incluidos** | **9 RF / 5 HU:** `RF-35`, `RF-36`, `RF-37`, `RF-38` (`HU-21`, `HU-22`); `RF-39`, `RF-40`, `RF-41`, `RF-42`, `RF-43` (`HU-14`, `HU-23`, `HU-24`). |
| **Criterios de aceptación (con sección)** | Sección 8: `CA-21.1`, `CA-21.2`, `CA-21.3` (`HU-21`); `CA-22.1`, `CA-22.2`, `CA-22.3`, `CA-22.4` (`HU-22`); `CA-23.1`, `CA-23.2`, `CA-23.3`, `CA-23.4` (`HU-23`); `CA-24.1`, `CA-24.2`, `CA-24.3` (`HU-24`) y `CA-14.5` (`RF-43`). |
| **Módulos involucrados** | `M08 Propuesta PDF y seguimiento` y `M09 Reportes, auditoría y errores` (integrados con `M01`–`M07`) — Anexo A. |
| **Depende de** | Incrementos 1 (`v0.1.0`), 2 (`v0.2.0`) y 3 (`v0.3.0`). |
| **Pruebas mínimas: normal, límite y error** | **Normal:** Generación de propuesta PDF completa (`CA-21.1`), envío por correo y registro de aceptación/rechazo (`CA-22.1`, `CA-22.4`), consulta de tablero e indicadores (`CA-23.1`), exportación CSV filtrada (`CA-23.3`) y consulta cronológica de bitácora inmutable (`CA-24.1`). **Límite:** Folio consecutivo #13 en el año sin repetir (`CA-21.2`), segundo envío por correo conservando ambos registros (`CA-22.3`), tablero en periodo sin movimientos (`CA-23.2`) y consulta paginada de bitácora en bloques de 50 registros (`CA-24.3`). **Error:** Falla del generador PDF con mensaje amigable (`CA-21.3`), envío a cliente sin correo (`CA-22.2`), filtro con fecha final anterior a la inicial (`CA-23.4`), pérdida de conexión manejada en español sin traza (`CA-24.2`) y rechazo de operación sobre registro cancelado o solicitud ya cotizada (`RF-43`, `CA-14.5`). |
| **Rama de Git** | `incremento-4` |
| **Fecha de cierre** | Semana 7 (Noviembre 2026) |
| **Decisión del equipo y justificación** | **Aceptada** · Cierra todos los requerimientos de prioridad Alta y Media del documento, entregando las salidas formales al cliente (PDF con folio) y el control administrativo y de auditoría. |

##### Ficha del Incremento 5 · `v0.5.0`

| Campo | Respuesta del equipo |
|---|---|
| **Incremento y versión** | Incremento 5 · `v0.5.0` |
| **Objetivo en una frase** | Implementar los tres requerimientos de prioridad Baja del documento: recuperación de contraseña con enlace temporal de 30 minutos (`RF-03`), historial de servicios del activo diferenciado por rol (`RF-15`) y duplicación de cotizaciones previas actualizando a precios vigentes del catálogo (`RF-34`). |
| **RF e HU incluidos** | **3 RF / 3 HU:** `RF-03` (`HU-02`), `RF-15` (`HU-09`) y `RF-34` (`HU-20`). |
| **Criterios de aceptación (con sección)** | Sección 8: `CA-02.1`, `CA-02.2`, `CA-02.3` (`HU-02`); `CA-09.1`, `CA-09.2`, `CA-09.3`, `CA-09.4` (`HU-09`); `CA-20.1`, `CA-20.2`, `CA-20.3` (`HU-20`). |
| **Módulos involucrados** | `M01 Acceso y seguridad` (`RF-03`), `M03 Activos` (`RF-15`) y `M06 Cotizador` integrado con `M04`/`M07` (`RF-34`) — Anexo A. |
| **Depende de** | Incrementos 1 (`v0.1.0`), 2 (`v0.2.0`), 3 (`v0.3.0`) y 4 (`v0.4.0`). |
| **Pruebas mínimas: normal, límite y error** | **Normal:** Recuperación de contraseña dentro de los 30 min (`CA-02.1`), consulta de historial del activo por técnico sin montos y por administrador con montos y folios (`CA-09.1`, `CA-09.2`) y duplicación de cotización aplicando tarifas vigentes (`CA-20.1`). **Límite:** Enlace de recuperación expirado tras >30 min (`CA-02.2`), historial de activo recién registrado sin servicios (`CA-09.3`) y duplicación con aviso de cambio de tarifa de $98,000 a $104,500 (`CA-20.2`). **Error:** Solicitud de recuperación con correo no registrado sin revelar existencia (`CA-02.3`), consulta de historial de activo dado de baja (`CA-09.4`) e intento de duplicar una cotización eliminada (`CA-20.3`). |
| **Rama de Git** | `incremento-5` |
| **Fecha de cierre** | Semana 8 (Noviembre 2026) |
| **Decisión del equipo y justificación** | **Aceptada** · Agrupa exactamente los 3 requerimientos clasificados con prioridad **Baja** en las Secciones 5 y 7 del documento de especificaciones, completando el 100 % de los 43 RF sin comprometer las entregas críticas previas. |

---

### A4. Validación cruzada del plan contra la especificación

| Criterio de verificación (Prompt A4) | Estado | Evidencia y sección citada del documento |
|---|:---:|---|
| Todos los requerimientos pendientes están asignados, y solo una vez. | ☑ Cumple | Los 42 RF pendientes (`RF-01..RF-27` y `RF-29..RF-43`) están repartidos sin duplicados ni omisiones: 17 en Inc. 2, 13 en Inc. 3, 9 en Inc. 4 y 3 en Inc. 5, sumando con `RF-28` (Inc. 1) los 43 RF de la Sección 5. |
| Ningún incremento incluye funcionalidades fuera del documento. | ☑ Cumple | Verificado contra la Sección 4 (Fuera del alcance, pág. 8): no se incluye asistente de IA, timbrado CFDI, app móvil nativa, portal de clientes, ERP, control de inventario ni firma electrónica avanzada. |
| El orden respeta todas las dependencias. | ☑ Cumple | Inc. 2 construye seguridad y catálogos maestros (`M01`–`M04`); Inc. 3 construye solicitudes y cotizaciones (`M05`–`M07`) sobre esos catálogos; Inc. 4 emite PDF y reportes (`M08`–`M09`) sobre cotizaciones creadas; Inc. 5 agrega histórico y duplicación (`RF-03`, `RF-15`, `RF-34`). |
| Cada incremento termina en algo ejecutable de extremo a extremo. | ☑ Cumple | Cada incremento culmina en un flujo funcional verificable por pruebas automatizadas (`php tests/run_tests.php`) que integra los módulos nuevos con el motor `M07` del Incremento 1. |
| Las observaciones de la revisión se resolvieron o se justificó por qué no. | ☑ Cumple | Las 13 ambigüedades de la Sección 10.2 (`A-01` a `A-13`) quedaron resueltas en la tabla A1.3 y el criterio de prioridad Baja (`RF-03`, `RF-15`, `RF-34`) se respetó ubicándolos en el Incremento 5. |

---

## Registros acumulados

### 1. Registro de cambios al plan

| Fecha | Cambio al plan | Motivo | Aprobado por |
|---|---|---|---|
| 2026-11-04 | Creación y aprobación de la versión inicial del plan de incrementos (`v0.1.0` a `v0.5.0`) en `docs/plan_incrementos.md`. | Estructurar los 42 RF pendientes a partir de `Entrega_Actualizada_COTIZA-ELEV_v3.pdf` tras cerrar `RF-28` en el Incremento 1. | Orlando Villalobos y Diego Alférez |
| 2026-11-04 | Separación de `RF-03`, `RF-15` y `RF-34` en el Incremento 5 (`v0.5.0`). | Aplicar estrictamente la regla 2 del Prompt A3: son los únicos 3 requerimientos con prioridad Baja en las Secciones 5 y 7 del documento. | Orlando Villalobos y Diego Alférez |

### 2. Bitácora de uso de IA (Fase A — Planificación)

| Fecha | Herramienta | Prompt o tarea | Qué se aceptó | Qué se modificó o rechazó, y por qué |
|---|---|---|---|---|
| 2026-11-04 | Asistente de análisis (rol ChatGPT / modelo configurado en la sesión) | **Prompt A1:** Lectura y verificación del documento de especificaciones v3.0 (secciones, conteo de RF, RNF, HU, CA y ambigüedades). | El listado de las 17 secciones del documento y el conteo exacto de 43 RF, 29 RNF, 24 HU y 81 CA, coincidiendo al 100 % con el conteo del equipo. | Se verificó que no se filtrara ningún requerimiento de la versión 1.0 descartada (asistente de IA) y se documentó la resolución explícita de las ambigüedades `A-01` a `A-13`. |
| 2026-11-04 | Asistente de análisis (rol ChatGPT / modelo configurado en la sesión) | **Prompt A2:** Construcción del inventario de requerimientos marcando `RF-28` como Implementado y el resto como Pendiente, con prioridades y dependencias citadas. | La tabla de los 43 RF y 29 RNF con sus prioridades exactas del documento (`Alta`, `Media`, `Baja` y `no indicada` para RNF). | Se rechazó una dependencia sugerida hacia un servidor SMTP externo en `RF-03` y `RF-37`, sustituyéndola por la dependencia interna con `RF-01`/`RF-04` y `RF-08`/`RF-35` respaldada en las Secciones 5.1 y 5.7. |
| 2026-11-04 | Asistente de análisis (rol ChatGPT / modelo configurado en la sesión) | **Prompt A3:** Propuesta de incrementos para 4 semanas restantes y 2 integrantes, respetando dependencias, prioridad, riesgo técnico y extremo a extremo. | La agrupación modular en 4 incrementos adicionales (`v0.2.0` a `v0.5.0`) colocando en el Incremento 2 el aislamiento de precios del técnico (`RF-05`, `RF-06`) por ser el mayor riesgo técnico (`R-07`). | Se modificó la ubicación de `RF-03`, `RF-15` y `RF-34` para agruparlos exclusivamente en el Incremento 5 (`v0.5.0`), respetando que la especificación los marca con prioridad Baja. |
| 2026-11-04 | Asistente de análisis en conversación independiente (rol ChatGPT / modelo configurado en la sesión) | **Prompt A4:** Validación cruzada independiente del plan contra el documento de especificaciones v3.0. | El checklist de verificación de unicidad de los 43 RF, ausencia de funciones fuera de alcance y orden topológico correcto de dependencias. | Se confirmó que `RF-43` (rechazo de operaciones sobre registros cancelados o solicitudes ya cotizadas) se valida tanto en la atención de solicitudes (`M05`) como en el servicio central de auditoría y errores (`M09`). |
