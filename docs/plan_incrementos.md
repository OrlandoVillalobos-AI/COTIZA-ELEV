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

---

## Fase B y Fase C · Desarrollo y verificación por incremento (Ciclo ejecutado para Incrementos 2 a 5)

### B1–C5. Incremento 2 · `v0.2.0` (`M01`, `M02`, `M03`, `M04` + integración `M07`)

- **Rama de trabajo:** `incremento-2` (creada desde `main` y publicada con `git push -u origin incremento-2`).
- **Fusión a `main`:** Commit `25024ce` (`Merge branch 'incremento-2' into main --no-ff`) · **Tag:** `v0.2.0`.

#### 1. Desglose de tareas y commits atómicos (Pasos B1 y B3)

| Tarea | RF cubiertos | Archivo(s) creados o modificados | Qué hace | Prueba que la verifica | Commit en `incremento-2` |
|---|---|---|---|---|---|
| **Tarea 2.1:** Autenticación RBAC, bloqueo al 5to intento, sesión de 20 min y bloqueo de precios al técnico | `RF-01`, `RF-02`, `RF-04`, `RF-05`, `RF-06`, `RF-07` | `src/Services/AuthRbacService.php`, `tests/Unit/AuthRbacServiceTest.php`, `tests/run_tests.php` | Gestiona usuarios, valida credenciales y roles (`Administrador` / `Tecnico`), bloquea 15 min al 5to intento fallido, controla aviso al min 19 y expiración al min 20, y bloquea/audita cualquier acceso del técnico a rutas o datos de precios. | `AuthRbacServiceTest` (7 pruebas: Normal, Límite, Error) | `f0f89e3` (`feat(RF-01): implementar autenticacion RBAC, bloqueo de precios al tecnico y sesion (M01)`) |
| **Tarea 2.2:** Directorio de clientes, búsqueda, duplicados y baja lógica conservando historial | `RF-08`, `RF-09`, `RF-10`, `RF-11`, `RF-12` | `src/Services/ClienteService.php`, `tests/Unit/ClienteServiceTest.php` | Registra, edita y busca clientes por nombre, RFC o contacto; previene RFC/razón social duplicados y ejecuta baja lógica conservando cotizaciones y activos históricos. | `ClienteServiceTest` (6 pruebas: Normal, Límite, Error) | `ebb489e` (`feat(RF-08): implementar directorio de clientes, historial, duplicados y baja logica (M02)`) |
| **Tarea 2.3:** Registro y edición de activos vinculados a cliente con validación de rangos técnicos | `RF-13`, `RF-14` | `src/Services/ActivoService.php`, `tests/Unit/ActivoServiceTest.php` | Registra y actualiza elevadores vinculados a un cliente existente validando paradas (`2..60`) y capacidad (`100..10,000 kg`). | `ActivoServiceTest` (5 pruebas en Inc. 2: Normal, Límite, Error) | `1d434e9` (`feat(RF-13): implementar registro de activos y validacion de rangos tecnicos (M03)`) |
| **Tarea 2.4:** Catálogo de tarifas con vigencias, importación CSV e integración con `M07` | `RF-16`, `RF-17`, `RF-18`, `RF-19` | `src/Services/CatalogoTarifaService.php`, `tests/Unit/CatalogoTarifaServiceTest.php` | Administra conceptos y precios unitarios con fecha de vigencia, importa lotes CSV validando columnas y montos positivos, y provee partidas vigentes al motor `CalculoMotorService` (`M07`). | `CatalogoTarifaServiceTest` (8 pruebas: Normal, Límite, Error + integración `M07`) | `f684034` (`feat(RF-16): implementar catalogo de tarifas vigentes, importador CSV e integracion M07 (M04)`) |

#### 2. Tabla de resultados de pruebas y regresión del Incremento 2 (Pasos C1, C2 y C3)

| ID de prueba | RF / CA que verifica | Tipo | Entrada | Resultado esperado | Resultado obtenido | Pasa (Sí/No) |
|---|---|---|---|---|---|:---:|
| `P-INC2-01` | `RF-01`, `RF-05`, `RF-06` / `CA-01.1`, `CA-03.1` | Normal | Login de `Administrador` y `Tecnico`; solicitud de menú y saneamiento de payload para `Tecnico` | Menú comercial completo para Admin; menú sin catálogo y payload sin campos `precio_*`/`subtotal`/`iva`/`total` para Técnico | Menú filtrado por rol y claves monetarias eliminadas al 100 % para el rol `Tecnico` | Sí |
| `P-INC2-02` | `RF-02`, `RF-07` / `CA-01.3`, `CA-05.1`, `CA-05.2` | Límite | 5 intentos fallidos consecutivos de login; sesión en minuto 19 y en minuto 20 de inactividad | Cuenta bloqueada exactamente 15 min al 5to intento; aviso en min 19 (`restantes=1`) y expiración en min 20 | `bloqueado=true` por 15 min al 5to intento; `aviso_expiracion=true` al min 19 y `activa=false` al min 20 | Sí |
| `P-INC2-03` | `RF-04`, `RF-06` / `CA-03.2`, `CA-04.2` | Error | `Tecnico` accede por URL directa a `/catalogo-tarifas`; alta de usuario con correo duplicado | Acceso denegado + evento registrado en bitácora de auditoría; excepción de correo duplicado | `acceso_permitido=false` con registro en bitácora; excepción lanzada en correo duplicado | Sí |
| `P-INC2-04` | `RF-08..RF-12` / `CA-06.1..CA-07.4` | Normal / Límite / Error | Alta de cliente, razón social de 300 chars, baja lógica con cotización histórica y RFC duplicado | Cliente creado y buscable; 300 chars aceptados; baja lógica conserva historial; RFC vacío/duplicado rechazado | Cliente activo, baja lógica con historial intacto y excepciones en RFC vacío o duplicado | Sí |
| `P-INC2-05` | `RF-13`, `RF-14` / `CA-08.1..CA-08.4` | Normal / Límite / Error | Alta de activo con 8 paradas; límite inferior exacto de 2 paradas; activo sin cliente o con 1 parada / 80 kg | Registro exitoso con 8 y 2 paradas; rechazo cuando el cliente no existe o paradas `< 2` / capacidad `< 100` | Activos válidos creados y excepciones controladas en cliente inexistente o rango inválido | Sí |
| `P-INC2-06` | `RF-16..RF-19` / `CA-10.1..CA-11.3` | Normal / Límite / Error | Alta de tarifa vigente e integración con `M07`; tarifa futura (`2099-01-01`); CSV válido, CSV solo cabeceras y CSV sin columna `precio` | `M07` calcula subtotal $3,200 + IVA $512 = $3,712 con tarifa vigente; tarifa futura no aplica hoy; CSV sin `precio` o monto `<= 0` es rechazado | Cálculo integrado exacto con `M07`, vigencia futura aislada y validación CSV estricta | Sí |
| `REG-INC2` | Regresión `v0.1.0` + `v0.2.0` (`php tests/run_tests.php`) | Regresión | Ejecución completa de `CalculoMotorServiceTest` + suites `M01`–`M04` | **30 / 30 pruebas aprobadas** (0 fallos en Incremento 1 e Incremento 2) | **30 / 30 pruebas aprobadas (100 %)** | Sí |

---

### B1–C5. Incremento 3 · `v0.3.0` (`M05`, `M06` y extensión completa de `M07`)

- **Rama de trabajo:** `incremento-3` (creada desde `main` en `v0.2.0` y publicada en `origin`).
- **Fusión a `main`:** Commit `f83737f` (`Merge branch 'incremento-3' into main --no-ff`) · **Tag:** `v0.3.0`.

#### 1. Desglose de tareas y commits atómicos (Pasos B1 y B3)

| Tarea | RF cubiertos | Archivo(s) creados o modificados | Qué hace | Prueba que la verifica | Commit en `incremento-3` |
|---|---|---|---|---|---|
| **Tarea 3.1:** Levantamiento de solicitudes técnicas en sitio, consulta sin precios y bandeja comercial paginada | `RF-20`, `RF-21`, `RF-22`, `RF-23`, `RF-24` | `src/Services/SolicitudService.php`, `tests/Unit/SolicitudServiceTest.php`, `tests/run_tests.php` | Permite al técnico registrar solicitudes con activo y refacciones sugeridas, detectar duplicados recientes (`CA-12.3`), consultar solo sus propias solicitudes sin montos y listar la bandeja comercial paginada en bloques de 50. | `SolicitudServiceTest` (9 pruebas: Normal, Límite, Error) | `a67f493` (`feat(RF-20): implementar solicitudes de campo del tecnico, bandeja comercial y control de duplicados (M05)`) |
| **Tarea 3.2:** Extensión del motor `M07` para equipos nuevos/modernización, pólizas preventivas, correctivos y descuentos | `RF-27`, `RF-28`, `RF-29`, `RF-30` | `src/Services/CalculoMotorService.php`, `tests/Unit/CalculoMotorServiceTest.php` | Extiende `CalculoMotorService` con cálculo por capacidad/paradas/acabados (`RF-27`), póliza preventiva por periodicidad y visitas anuales (`RF-29`), correctivo con refacciones + mano de obra fraccionaria (`RF-30`) y descuentos controlados (`CA-16.2`, `CA-16.3`). | `CalculoMotorServiceTest` (10 pruebas: 4 de Inc. 1 + 6 nuevas) | `932e213` (`feat(RF-27): extender motor M07 con calculo de equipos, polizas preventivas, correctivos y descuentos`) |
| **Tarea 3.3:** Cotizador comercial, conversión de solicitud a cotización, borradores y ciclo de estados | `RF-25`, `RF-26`, `RF-31`, `RF-32`, `RF-33` | `src/Services/CotizadorService.php`, `tests/Unit/CotizadorServiceTest.php` | Convierte solicitudes pendientes en cotizaciones precargando cliente/activo, valida que el activo pertenezca al cliente (`CA-15.2`), recalcula al editar partidas, guarda borradores y gestiona estados (`Borrador`, `Enviada`, `Aceptada`, `Rechazada`, `Vencida` a 15 días). | `CotizadorServiceTest` (8 pruebas en Inc. 3: Normal, Límite, Error) | `03c0b9b` (`feat(RF-25): implementar cotizador comercial, conversion de solicitudes, borradores y estados (M06)`) |

#### 2. Tabla de resultados de pruebas y regresión del Incremento 3 (Pasos C1, C2 y C3)

| ID de prueba | RF / CA que verifica | Tipo | Entrada | Resultado esperado | Resultado obtenido | Pasa (Sí/No) |
|---|---|---|---|---|---|:---:|
| `P-INC3-01` | `RF-20..RF-24` / `CA-12.1..CA-14.5` | Normal / Límite / Error | Alta de solicitud por técnico; duplicada confirmada con motivo; paginación de 200 solicitudes (50 por página); técnico intenta ver solicitud ajena; solicitud ya cotizada | Folio `SOL-0001` en `Pendiente de cotizar` sin campos de precio; página 1 devuelve 50 de 200; rechazo en solicitud ajena o ya cotizada | Solicitud creada sin montos, paginación exacta `50/200` y bloqueo de acceso ajeno o doble atención | Sí |
| `P-INC3-02` | `RF-27`, `RF-29`, `RF-30` / `CA-15.1..CA-18.3` | Normal / Límite / Error | Equipo nuevo `630kg_8p` Acero Inoxidable; descuento 100 % confirmado vs descuento mayor al subtotal; póliza bimestral (6 visitas) vs 0 visitas; correctivo con `0.5 h` de mano de obra vs diagnóstico vacío | Subtotal $710,000 + IVA $113,600 = $823,600; total $0.00 con 100 % descuento; póliza $15,000 + IVA $2,400 = $17,400; `0.5 h` a $700 = $350; error en descuento `> subtotal`, `0` visitas o sin diagnóstico | Todos los cálculos a 2 decimales y validaciones de negocio coinciden con los criterios de la Sección 8 | Sí |
| `P-INC3-03` | `RF-25`, `RF-26`, `RF-31..RF-33` / `CA-14.2`, `CA-15.2`, `CA-19.1..CA-19.3` | Normal / Límite / Error | Conversión de solicitud a cotización; edición de partida de 1 a 2 unidades; vencimiento tras 16 días (`> 15` días); activo de otro cliente; envío de borrador sin partidas | Solicitud pasa a `Cotizada`; recálculo inmediato; cambio automático a `Vencida`; excepción si el activo no pertenece al cliente o el borrador está incompleto | Flujo extremo a extremo Técnico -> Solicitud -> Cotización verificado sin errores | Sí |
| `REG-INC3` | Regresión `v0.1.0` + `v0.2.0` + `v0.3.0` (`php tests/run_tests.php`) | Regresión | Ejecución acumulada de todas las suites de Incrementos 1, 2 y 3 | **53 / 53 pruebas aprobadas** | **53 / 53 pruebas aprobadas (100 %)** | Sí |

---

### B1–C5. Incremento 4 · `v0.4.0` (`M08 Propuesta PDF y seguimiento` + `M09 Reportes, auditoría y errores`)

- **Rama de trabajo:** `incremento-4` (creada desde `main` en `v0.3.0` y publicada en `origin`).
- **Fusión a `main`:** Commit `1901ee5` (`Merge branch 'incremento-4' into main --no-ff`) · **Tag:** `v0.4.0`.

#### 1. Desglose de tareas y commits atómicos (Pasos B1 y B3)

| Tarea | RF cubiertos | Archivo(s) creados o modificados | Qué hace | Prueba que la verifica | Commit en `incremento-4` |
|---|---|---|---|---|---|
| **Tarea 4.1:** Emisión de propuesta económica PDF, folios consecutivos anuales `COT-AAAA-NNNN`, envío por correo y aceptación/rechazo | `RF-35`, `RF-36`, `RF-37`, `RF-38` | `src/Services/PropuestaSeguimientoService.php`, `tests/Unit/PropuestaSeguimientoServiceTest.php`, `tests/run_tests.php` | Asigna folio anual consecutivo sin repetir (`CA-21.2`), genera la estructura completa de propuesta PDF con desglose fiscal y vigencia, registra envíos múltiples por correo (`CA-22.3`) y registra aceptación o rechazo con motivo. | `PropuestaSeguimientoServiceTest` (5 pruebas: Normal, Límite, Error) | `a7c7266` (`feat(RF-35): implementar emision de propuesta PDF, folios consecutivos y seguimiento comercial (M08)`) |
| **Tarea 4.2:** Tablero directivo de indicadores comerciales y exportación de reportes CSV con filtros | `RF-39`, `RF-40` | `src/Services/ReporteTableroService.php`, `tests/Unit/ReporteTableroServiceTest.php` | Calcula totales por estado, montos acumulados y porcentaje de conversión filtrados por periodo, cliente, estado o tipo de servicio, y exporta el detalle a CSV. | `ReporteTableroServiceTest` (4 pruebas: Normal, Límite, Error) | `18b483f` (`feat(RF-39): implementar tablero de indicadores comerciales y exportacion de reportes CSV (M09)`) |
| **Tarea 4.3:** Bitácora inmutable de auditoría paginada y manejador centralizado de errores en español | `RF-41`, `RF-42`, `RF-43` | `src/Services/AuditoriaErrorService.php`, `tests/Unit/AuditoriaErrorServiceTest.php` | Registra eventos críticos en bitácora de solo lectura (bloqueando edición/borrado), pagina de 50 en 50, traduce fallas técnicas a mensajes claros en español sin stack traces (`CA-24.2`) y rechaza operaciones sobre registros cancelados o solicitudes ya cotizadas (`RF-43`). | `AuditoriaErrorServiceTest` (6 pruebas: Normal, Límite, Error) | `7115a4a` (`feat(RF-41): implementar bitacora inmutable de auditoria y manejador centralizado de errores (M09)`) |

#### 2. Tabla de resultados de pruebas y regresión del Incremento 4 (Pasos C1, C2 y C3)

| ID de prueba | RF / CA que verifica | Tipo | Entrada | Resultado esperado | Resultado obtenido | Pasa (Sí/No) |
|---|---|---|---|---|---|:---:|
| `P-INC4-01` | `RF-35..RF-38` / `CA-21.1..CA-22.4` | Normal / Límite / Error | Emisión de PDF tras 12 folios previos; doble envío por correo; registro de aceptación/rechazo; falla simulada del motor PDF y cliente sin correo | Folio `COT-2026-0013` asignado; 2 registros de envío conservados; error amigable en español si falla el PDF o falta correo | Folio `COT-2026-0013`, historial de 2 envíos y manejo controlado de errores | Sí |
| `P-INC4-02` | `RF-39`, `RF-40` / `CA-23.1..CA-23.4` | Normal / Límite / Error | Consulta de tablero con cotizaciones enviadas/aceptadas; exportación CSV; periodo sin movimientos; rango de fechas invertido (`fin < inicio`) | Indicadores y CSV coherentes; tablero en `0` sin error en periodo vacío; excepción de validación en fechas invertidas | Indicadores exactos, CSV con encabezados y validación de fechas correcta | Sí |
| `P-INC4-03` | `RF-41..RF-43` / `CA-24.1..CA-24.3`, `CA-14.5` | Normal / Límite / Error | Consulta cronológica y paginada (120 eventos -> 50 en pág. 1); intento de eliminar/editar bitácora; error técnico `PDOException`; operación sobre cotización cancelada | Bitácora paginada (`50/120`), inmutable ante edición/borrado, mensaje en español sin trazas SQL y rechazo de operación inválida | Bitácora 100 % inmutable, mensajes limpios en español y bloqueo `RF-43` activo | Sí |
| `REG-INC4` | Regresión `v0.1.0` a `v0.4.0` (`php tests/run_tests.php`) | Regresión | Ejecución acumulada de todas las suites de Incrementos 1, 2, 3 y 4 | **68 / 68 pruebas aprobadas** | **68 / 68 pruebas aprobadas (100 %)** | Sí |

---

### B1–C5. Incremento 5 · `v0.5.0` (Requerimientos de prioridad Baja: `RF-03`, `RF-15`, `RF-34`)

- **Rama de trabajo:** `incremento-5` (creada desde `main` en `v0.4.0` y publicada en `origin`).
- **Fusión a `main`:** Commit `5b604f2` (`Merge branch 'incremento-5' into main --no-ff`) · **Tag:** `v0.5.0`.

#### 1. Desglose de tareas y commits atómicos (Pasos B1 y B3)

| Tarea | RF cubiertos | Archivo(s) creados o modificados | Qué hace | Prueba que la verifica | Commit en `incremento-5` |
|---|---|---|---|---|---|
| **Tarea 5.1:** Recuperación de contraseña por correo con enlace temporal válido por 30 minutos | `RF-03` | `src/Services/AuthRbacService.php`, `tests/Unit/AuthRbacServiceTest.php` | Genera token temporal con vigencia de 30 minutos, actualiza la contraseña si el token es vigente, rechaza tokens expirados (`> 30 min`, `CA-02.2`) y devuelve mensaje genérico seguro en correos no registrados (`CA-02.3`). | `AuthRbacServiceTest` (+3 pruebas `CA-02.1..CA-02.3`, total 10) | `e714ed2` (`feat(RF-03): implementar recuperacion de contrasena con token temporal de 30 minutos (M01)`) |
| **Tarea 5.2:** Historial de solicitudes y cotizaciones de un activo ordenado por fecha y filtrado por rol | `RF-15` | `src/Services/ActivoService.php`, `tests/Unit/ActivoServiceTest.php` | Devuelve el historial cronológico descendente de un activo ocultando montos y folios de propuesta al `Tecnico` (`CA-09.1`), mostrándolos al `Administrador` (`CA-09.2`), manejando activos sin historial (`CA-09.3`) y bloqueando activos dados de baja (`CA-09.4`). | `ActivoServiceTest` (+4 pruebas `CA-09.1..CA-09.4`, total 9) | `954f4fa` (`feat(RF-15): implementar historial de activos ordenado por fecha y filtrado por rol (M03)`) |
| **Tarea 5.3:** Duplicación de cotizaciones previas actualizando precios al catálogo vigente | `RF-34` | `src/Services/CotizadorService.php`, `tests/Unit/CotizadorServiceTest.php`, `tests/run_tests.php` | Crea una nueva cotización en `Borrador` a partir de una existente recargando precios vigentes desde `M04`, notificando al usuario qué tarifas cambiaron (`CA-20.2`, ej. `$98,000` -> `$104,500`) y rechazando duplicar cotizaciones eliminadas (`CA-20.3`). | `CotizadorServiceTest` (+3 pruebas `CA-20.1..CA-20.3`, total 11) | `ae62ca0` (`feat(RF-34): implementar duplicacion de cotizaciones con precios vigentes y regresion total (M06)`) |

#### 2. Tabla de resultados de pruebas y regresión final del Incremento 5 (Pasos C1, C2 y C3)

| ID de prueba | RF / CA que verifica | Tipo | Entrada | Resultado esperado | Resultado obtenido | Pasa (Sí/No) |
|---|---|---|---|---|---|:---:|
| `P-INC5-01` | `RF-03` / `CA-02.1`, `CA-02.2`, `CA-02.3` | Normal / Límite / Error | Solicitud y uso de token a los 10 min; intento de uso a los 31 min; solicitud para correo inexistente | Contraseña actualizada a los 10 min; excepción de enlace expirado a los 31 min; respuesta genérica sin filtrar existencia de cuenta | Token de 30 min validado en caso normal, límite y error | Sí |
| `P-INC5-02` | `RF-15` / `CA-09.1`, `CA-09.2`, `CA-09.3`, `CA-09.4` | Normal / Límite / Error | Consulta de historial de activo por `Tecnico` y por `Administrador`; activo recién creado; activo dado de baja | `Tecnico` ve fechas, descripciones y estados sin montos; `Administrador` ve montos y folios; lista vacía con mensaje en activo nuevo; excepción en activo dado de baja | Filtrado por rol y orden cronológico verificados al 100 % | Sí |
| `P-INC5-03` | `RF-34` / `CA-20.1`, `CA-20.2`, `CA-20.3` | Normal / Límite / Error | Duplicación de cotización tras actualización de tarifa de `$98,000` a `$104,500`; intento de duplicar cotización eliminada | Nuevo borrador recalculado con `$104,500` y alerta en `cambios_precio`; excepción al intentar duplicar cotización inexistente/eliminada | Nuevo borrador con tarifas vigentes, alerta de cambio de precio y bloqueo en registro eliminado | Sí |
| `REG-FINAL` | Regresión total `v0.1.0` a `v0.5.0` (`php tests/run_tests.php`) | Regresión | Ejecución íntegra de las 8 suites de pruebas unitarias e integración (`M01` a `M09`) | **78 / 78 pruebas aprobadas (0 fallos)** | **78 / 78 pruebas aprobadas (100 %)** | Sí |

---

## Registros acumulados

### 1. Matriz de trazabilidad final (43 RF · Incrementos 1 a 5)

| RF / RNF | HU | Incremento | Archivo(s) de código | Commit | Tag | Prueba que lo verifica | Estado |
|---|---|---|---|---|---|---|---|
| `RF-01` | `HU-01` | Incremento 2 | `src/Services/AuthRbacService.php` | `f0f89e3` | `v0.2.0` | `AuthRbacServiceTest::testLoginExitosoYMenuPorRol` | Terminado |
| `RF-02` | `HU-01` | Incremento 2 | `src/Services/AuthRbacService.php` | `f0f89e3` | `v0.2.0` | `AuthRbacServiceTest::testBloqueoAlQuintoIntentoFallido` | Terminado |
| `RF-03` | `HU-02` | Incremento 5 | `src/Services/AuthRbacService.php` | `e714ed2` | `v0.5.0` | `AuthRbacServiceTest::testRecuperacionContrasenaToken30Min` | Terminado |
| `RF-04` | `HU-04` | Incremento 2 | `src/Services/AuthRbacService.php` | `f0f89e3` | `v0.2.0` | `AuthRbacServiceTest::testAltaEdicionCambioRolYCorreoDuplicado` | Terminado |
| `RF-05` | `HU-03` | Incremento 2 | `src/Services/AuthRbacService.php` | `f0f89e3` | `v0.2.0` | `AuthRbacServiceTest::testSanitizacionPreciosParaTecnico` | Terminado |
| `RF-06` | `HU-03` | Incremento 2 | `src/Services/AuthRbacService.php` | `f0f89e3` | `v0.2.0` | `AuthRbacServiceTest::testBloqueoRutaPreciosTecnicoYBitacora` | Terminado |
| `RF-07` | `HU-05` | Incremento 2 | `src/Services/AuthRbacService.php` | `f0f89e3` | `v0.2.0` | `AuthRbacServiceTest::testInactividadMinuto19YExpiracionMinuto20` | Terminado |
| `RF-08` | `HU-06` | Incremento 2 | `src/Services/ClienteService.php` | `ebb489e` | `v0.2.0` | `ClienteServiceTest::testRegistrarYEditarCliente` | Terminado |
| `RF-09` | `HU-07` | Incremento 2 | `src/Services/ClienteService.php` | `ebb489e` | `v0.2.0` | `ClienteServiceTest::testBusquedaPorNombreRfcContacto` | Terminado |
| `RF-10` | `HU-07` | Incremento 2 | `src/Services/ClienteService.php` | `ebb489e` | `v0.2.0` | `ClienteServiceTest::testBajaLogicaConservaHistorial` | Terminado |
| `RF-11` | `HU-06` | Incremento 2 | `src/Services/ClienteService.php` | `ebb489e` | `v0.2.0` | `ClienteServiceTest::testRechazoRfcORazonSocialDuplicada` | Terminado |
| `RF-12` | `HU-07` | Incremento 2 | `src/Services/ClienteService.php` | `ebb489e` | `v0.2.0` | `ClienteServiceTest::testBajaLogicaConservaHistorial` | Terminado |
| `RF-13` | `HU-08` | Incremento 2 | `src/Services/ActivoService.php` | `1d434e9` | `v0.2.0` | `ActivoServiceTest::testRegistrarActivoNormalYLimiteDosParadas` | Terminado |
| `RF-14` | `HU-08` | Incremento 2 | `src/Services/ActivoService.php` | `1d434e9` | `v0.2.0` | `ActivoServiceTest::testRechazoRangosParadasYCapacidad` | Terminado |
| `RF-15` | `HU-09` | Incremento 5 | `src/Services/ActivoService.php` | `954f4fa` | `v0.5.0` | `ActivoServiceTest::testHistorialActivoFiltradoPorRolYOrdenado` | Terminado |
| `RF-16` | `HU-10` | Incremento 2 | `src/Services/CatalogoTarifaService.php` | `f684034` | `v0.2.0` | `CatalogoTarifaServiceTest::testRegistrarTarifaEIntegrarConMotorM07` | Terminado |
| `RF-17` | `HU-10` | Incremento 2 | `src/Services/CatalogoTarifaService.php` | `f684034` | `v0.2.0` | `CatalogoTarifaServiceTest::testVigenciaFuturaNoAplicaHoy` | Terminado |
| `RF-18` | `HU-10` | Incremento 2 | `src/Services/CatalogoTarifaService.php` | `f684034` | `v0.2.0` | `CatalogoTarifaServiceTest::testRechazoPrecioCeroONegativo` | Terminado |
| `RF-19` | `HU-11` | Incremento 2 | `src/Services/CatalogoTarifaService.php` | `f684034` | `v0.2.0` | `CatalogoTarifaServiceTest::testImportacionCsvValidaLoteYErrores` | Terminado |
| `RF-20` | `HU-12` | Incremento 3 | `src/Services/SolicitudService.php` | `a67f493` | `v0.3.0` | `SolicitudServiceTest::testCrearSolicitudNormalSinPrecios` | Terminado |
| `RF-21` | `HU-12` | Incremento 3 | `src/Services/SolicitudService.php` | `a67f493` | `v0.3.0` | `SolicitudServiceTest::testRechazoSolicitudSinPiezaOSinActivo` | Terminado |
| `RF-22` | `HU-13` | Incremento 3 | `src/Services/SolicitudService.php` | `a67f493` | `v0.3.0` | `SolicitudServiceTest::testTecnicoConsultaSusSolicitudesSinPrecios` | Terminado |
| `RF-23` | `HU-14` | Incremento 3 | `src/Services/SolicitudService.php` | `a67f493` | `v0.3.0` | `SolicitudServiceTest::testBandejaComercialPaginada50Registros` | Terminado |
| `RF-24` | `HU-14` | Incremento 3 | `src/Services/SolicitudService.php` | `a67f493` | `v0.3.0` | `SolicitudServiceTest::testMarcarCotizadaYBloqueoDobleAtencion` | Terminado |
| `RF-25` | `HU-14` | Incremento 3 | `src/Services/CotizadorService.php` | `03c0b9b` | `v0.3.0` | `CotizadorServiceTest::testConvertirSolicitudACotizacion` | Terminado |
| `RF-26` | `HU-15` | Incremento 3 | `src/Services/CotizadorService.php` | `03c0b9b` | `v0.3.0` | `CotizadorServiceTest::testRechazoActivoDeOtroCliente` | Terminado |
| `RF-27` | `HU-15` | Incremento 3 | `src/Services/CalculoMotorService.php` | `932e213` | `v0.3.0` | `CalculoMotorServiceTest::testCotizarEquipoNuevoYCombinacionInexistente` | Terminado |
| `RF-28` | `HU-16` | Incremento 1 | `src/Services/CalculoMotorService.php` | `9fb2b49` | `v0.1.0` | `CalculoMotorServiceTest::testCasoNormalVariasPartidas` | Terminado |
| `RF-29` | `HU-17` | Incremento 3 | `src/Services/CalculoMotorService.php` | `932e213` | `v0.3.0` | `CalculoMotorServiceTest::testCotizarPolizaPreventivaYDiscrepancia` | Terminado |
| `RF-30` | `HU-18` | Incremento 3 | `src/Services/CalculoMotorService.php` | `932e213` | `v0.3.0` | `CalculoMotorServiceTest::testCotizarCorrectivoConFraccionDeHora` | Terminado |
| `RF-31` | `HU-19` | Incremento 3 | `src/Services/CotizadorService.php` | `03c0b9b` | `v0.3.0` | `CotizadorServiceTest::testRecalculoAlEditarPartidasYBorrador` | Terminado |
| `RF-32` | `HU-19` | Incremento 3 | `src/Services/CotizadorService.php` | `03c0b9b` | `v0.3.0` | `CotizadorServiceTest::testBloqueoEnvioBorradorIncompleto` | Terminado |
| `RF-33` | `HU-19` | Incremento 3 | `src/Services/CotizadorService.php` | `03c0b9b` | `v0.3.0` | `CotizadorServiceTest::testTransicionesEstadoYVencimiento15Dias` | Terminado |
| `RF-34` | `HU-20` | Incremento 5 | `src/Services/CotizadorService.php` | `ae62ca0` | `v0.5.0` | `CotizadorServiceTest::testDuplicarCotizacionActualizaPreciosVigentes` | Terminado |
| `RF-35` | `HU-21` | Incremento 4 | `src/Services/PropuestaSeguimientoService.php` | `a7c7266` | `v0.4.0` | `PropuestaSeguimientoServiceTest::testEmitirPropuestaPdfCompleta` | Terminado |
| `RF-36` | `HU-21` | Incremento 4 | `src/Services/PropuestaSeguimientoService.php` | `a7c7266` | `v0.4.0` | `PropuestaSeguimientoServiceTest::testFolioConsecutivoAnualSinRepetir` | Terminado |
| `RF-37` | `HU-22` | Incremento 4 | `src/Services/PropuestaSeguimientoService.php` | `a7c7266` | `v0.4.0` | `PropuestaSeguimientoServiceTest::testEnvioCorreoYMultiplesEnvios` | Terminado |
| `RF-38` | `HU-22` | Incremento 4 | `src/Services/PropuestaSeguimientoService.php` | `a7c7266` | `v0.4.0` | `PropuestaSeguimientoServiceTest::testRegistrarAceptacionYRechazo` | Terminado |
| `RF-39` | `HU-23` | Incremento 4 | `src/Services/ReporteTableroService.php` | `18b483f` | `v0.4.0` | `ReporteTableroServiceTest::testObtenerIndicadoresTableroYPeriodoVacio` | Terminado |
| `RF-40` | `HU-23` | Incremento 4 | `src/Services/ReporteTableroService.php` | `18b483f` | `v0.4.0` | `ReporteTableroServiceTest::testExportarCsvYRechazoFechasInvertidas` | Terminado |
| `RF-41` | `HU-24` | Incremento 4 | `src/Services/AuditoriaErrorService.php` | `7115a4a` | `v0.4.0` | `AuditoriaErrorServiceTest::testBitacoraInmutableYPaginacion50` | Terminado |
| `RF-42` | `HU-24` | Incremento 4 | `src/Services/AuditoriaErrorService.php` | `7115a4a` | `v0.4.0` | `AuditoriaErrorServiceTest::testManejoErrorAmigableSinTrazaTecnica` | Terminado |
| `RF-43` | `HU-14 / HU-24` | Incremento 4 | `src/Services/AuditoriaErrorService.php` | `7115a4a` | `v0.4.0` | `AuditoriaErrorServiceTest::testRechazoOperacionSobreRegistroCanceladoOCotizado` | Terminado |
| `RNF-01..RNF-29` | Transversales | Incrementos 1 a 5 | `src/Services/*` | `9fb2b49`..`ae62ca0` | `v0.1.0`..`v0.5.0` | Suite completa `tests/run_tests.php` (78 pruebas) | Terminado |

### 2. Registro de cambios al plan

| Fecha | Cambio al plan | Motivo | Aprobado por |
|---|---|---|---|
| 2026-11-04 | Creación y aprobación de la versión inicial del plan de incrementos (`v0.1.0` a `v0.5.0`) en `docs/plan_incrementos.md`. | Estructurar los 42 RF pendientes a partir de `Entrega_Actualizada_COTIZA-ELEV_v3.pdf` tras cerrar `RF-28` en el Incremento 1. | Orlando Villalobos y Diego Alférez |
| 2026-11-04 | Separación de `RF-03`, `RF-15` y `RF-34` en el Incremento 5 (`v0.5.0`). | Aplicar estrictamente la regla 2 del Prompt A3: son los únicos 3 requerimientos con prioridad Baja en las Secciones 5 y 7 del documento. | Orlando Villalobos y Diego Alférez |
| 2026-11-05 | Incorporación del parámetro de descuento (`RF-28` / `CA-16.2`, `CA-16.3`) al extender `CalculoMotorService` en el Incremento 3 conservando retrocompatibilidad con la firma de 2 argumentos del Incremento 1. | Garantizar que las 4 pruebas originales del Incremento 1 (`v0.1.0`) sigan pasando sin modificación durante las pruebas de regresión C2. | Orlando Villalobos y Diego Alférez |
| 2026-11-05 | Cierre y etiquetado secuencial de los Incrementos 2 (`v0.2.0`), 3 (`v0.3.0`), 4 (`v0.4.0`) y 5 (`v0.5.0`) tras aprobar 78/78 pruebas automatizadas. | Cumplimiento íntegro de los 43 RF y 81 CA sin defectos de regresión. | Orlando Villalobos y Diego Alférez |

### 3. Bitácora de uso de IA (Fases A, B y C)

| Fecha | Herramienta | Prompt o tarea | Qué se aceptó | Qué se modificó o rechazó, y por qué |
|---|---|---|---|---|
| 2026-11-04 | Asistente de análisis (rol ChatGPT / modelo configurado en la sesión) | **Prompt A1:** Lectura y verificación del documento de especificaciones v3.0 (secciones, conteo de RF, RNF, HU, CA y ambigüedades). | El listado de las 17 secciones del documento y el conteo exacto de 43 RF, 29 RNF, 24 HU y 81 CA, coincidiendo al 100 % con el conteo del equipo. | Se verificó que no se filtrara ningún requerimiento de la versión 1.0 descartada (asistente de IA) y se documentó la resolución explícita de las ambigüedades `A-01` a `A-13`. |
| 2026-11-04 | Asistente de análisis (rol ChatGPT / modelo configurado en la sesión) | **Prompt A2:** Construcción del inventario de requerimientos marcando `RF-28` como Implementado y el resto como Pendiente, con prioridades y dependencias citadas. | La tabla de los 43 RF y 29 RNF con sus prioridades exactas del documento (`Alta`, `Media`, `Baja` y `no indicada` para RNF). | Se rechazó una dependencia sugerida hacia un servidor SMTP externo en `RF-03` y `RF-37`, sustituyéndola por la dependencia interna con `RF-01`/`RF-04` y `RF-08`/`RF-35` respaldada en las Secciones 5.1 y 5.7. |
| 2026-11-04 | Asistente de análisis (rol ChatGPT / modelo configurado en la sesión) | **Prompt A3:** Propuesta de incrementos para 4 semanas restantes y 2 integrantes, respetando dependencias, prioridad, riesgo técnico y extremo a extremo. | La agrupación modular en 4 incrementos adicionales (`v0.2.0` a `v0.5.0`) colocando en el Incremento 2 el aislamiento de precios del técnico (`RF-05`, `RF-06`) por ser el mayor riesgo técnico (`R-07`). | Se modificó la ubicación de `RF-03`, `RF-15` y `RF-34` para agruparlos exclusivamente en el Incremento 5 (`v0.5.0`), respetando que la especificación los marca con prioridad Baja. |
| 2026-11-04 | Asistente de análisis en conversación independiente (rol ChatGPT / modelo configurado en la sesión) | **Prompt A4:** Validación cruzada independiente del plan contra el documento de especificaciones v3.0. | El checklist de verificación de unicidad de los 43 RF, ausencia de funciones fuera de alcance y orden topológico correcto de dependencias. | Se confirmó que `RF-43` (rechazo de operaciones sobre registros cancelados o solicitudes ya cotizadas) se valida tanto en la atención de solicitudes (`M05`) como en el servicio central de auditoría y errores (`M09`). |
| 2026-11-05 | Asistente de análisis (rol ChatGPT / modelo configurado en la sesión) | **Prompt B1:** Desglose de los Incrementos 2, 3, 4 y 5 en tareas concretas de 1 a 2 horas con archivos, descripción y pruebas asociadas. | La división en 4 tareas para el Incremento 2 (`M01`–`M04`), 3 tareas para el Incremento 3 (`M05`–`M07`), 3 tareas para el Incremento 4 (`M08`–`M09`) y 3 tareas para el Incremento 5 (`RF-03`, `RF-15`, `RF-34`). | Se rechazó crear controladores HTTP o vistas HTML acopladas en esta capa, manteniendo la arquitectura de servicios de dominio puros bajo `src/Services/` establecida en el Incremento 1. |
| 2026-11-05 | Asistente de código (rol Claude Code / modelo configurado en la sesión) | **Prompt B3:** Implementación guiada tarea por tarea de los servicios en `src/Services/` para los Incrementos 2 a 5. | La implementación tipada en PHP 8.4 (`declare(strict_types=1)`) con validaciones explícitas de rangos, vigencias, folios `COT-AAAA-NNNN` y bitácora inmutable. | En `CalculoMotorService::calcularCotizacion` se hizo opcional el parámetro `$descuento` (`0.0` por defecto) para no romper la firma usada por las pruebas del Incremento 1 (`v0.1.0`). |
| 2026-11-05 | Asistente de código (rol Claude Code / modelo configurado en la sesión) | **Prompt C1:** Generación de pruebas automatizadas normales, límite y de error para cada incremento en `tests/Unit/`. | Los casos de prueba basados literalmente en los criterios `CA-01.1` a `CA-24.3` de la Sección 8 del documento de especificaciones. | En las suites del Incremento 4 (`PropuestaSeguimientoServiceTest`, `ReporteTableroServiceTest`, `AuditoriaErrorServiceTest`) se estandarizó el método público `ejecutarTodas(): array` para mantener compatibilidad uniforme con el ejecutor `tests/run_tests.php`. |

---

## Entrega y reflexión individual

| Pregunta | Respuesta |
|---|---|
| **URL del repositorio** | `https://github.com/OrlandoVillalobos-AI/COTIZA-ELEV` |
| **Rama o tag entregado** | Rama `main` con tags `v0.1.0`, `v0.2.0`, `v0.3.0`, `v0.4.0` y `v0.5.0` (además de ramas `incremento-2` a `incremento-5` publicadas en `origin`). |
| **1. ¿Qué dependencia no habías visto antes de hacer el inventario en A2?** | Antes del inventario A2 no habíamos detectado que la duplicación de cotizaciones (`RF-34`, en `M06`) y el cálculo de equipos nuevos y pólizas (`RF-27`, `RF-29`, en `M07`) dependían directamente de la fecha de entrada en vigor del catálogo (`RF-17`, en `M04`), lo cual obligó a construir y probar `CatalogoTarifaService` en el Incremento 2 antes de abordar el cotizador comercial en el Incremento 3. |
| **2. ¿Qué cambió en tu plan original durante el desarrollo y por qué?** | Al implementar el Incremento 3 fue necesario ampliar `CalculoMotorService` (`M07`, creado en el Incremento 1 para `RF-28`) agregando el soporte de descuentos (`CA-16.2` y `CA-16.3`) con valor por defecto `0.0` y los métodos de equipo nuevo (`RF-27`), póliza (`RF-29`) y correctivo (`RF-30`), cuidando mantener intacta la firma original para que las 4 pruebas de regresión de `v0.1.0` siguieran pasando sin cambios. |
| **3. ¿Qué error concreto de la IA detectaste al validar con A4 o al correr regresión en C2?** | En el Paso A2 el modelo sugirió que `RF-03` (recuperación de contraseña) y `RF-37` (envío de propuesta) dependían de un servicio externo de correo y plantillas HTML no descrito en la especificación; lo rechazamos al contrastar con las Secciones 4 y 5. En el Paso C2 detectamos que las 3 clases de prueba del Incremento 4 exponían `run()` en vez de `ejecutarTodas()`, lo que habría impedido que `tests/run_tests.php` las ejecutara en la regresión; lo corregimos antes del commit. |
