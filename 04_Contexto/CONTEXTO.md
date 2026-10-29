# Proyecto COTIZA-ELEV — contexto y estado

Última actualización: 8 de octubre de 2026.

## 1. Datos del proyecto

| Dato | Valor |
|---|---|
| Cliente | ELEVESE (instalación, modernización y mantenimiento de equipos de elevación vertical) |
| Proyecto | COTIZA-ELEV — aplicación web de cotización |
| Materia | Desarrollo de Aplicaciones y Servicios Virtuales |
| Docente | Ing. Alejandro Montes |
| Equipo | Orlando Villalobos Gutiérrez y Diego Emilio Alférez Vallejo (2 integrantes, sin razón social) |
| Periodo | 8 semanas |
| Versión vigente | 3.0 (entrega actualizada, 37 páginas) |

**Regla pendiente:** no vincular nada con ELEVESE TECH (otra empresa del mismo nombre) hasta que el usuario lo indique.

## 2. Qué hay en cada carpeta

- `01_Entregables/` — el documento en Word y PDF: la versión vigente 3.0 y las dos versiones anteriores (2.0 y la 2.0 revisada por Orlando).
- `02_Fuentes_y_practica/` — propuesta inicial del cliente, guía de la práctica y la plantilla de evidencia `Prompts_Requerimientos_IA.pdf`.
- `03_Generador/` — scripts que generan el documento desde código. Regenerar en cualquier momento con el comando del apartado 6.
- `04_Contexto/` — este documento.

## 3. Contenido del entregable vigente (v3.0)

| Elemento | Cantidad |
|---|---|
| Requerimientos funcionales (RF-01 a RF-43) | 43 |
| Requerimientos no funcionales (RNF-01 a RNF-29) | 29 |
| Historias de usuario (HU-01 a HU-24) | 24 (16 Alta, 5 Media, 3 Baja) |
| Criterios de aceptación (Normal / Límite / Error) | 81 (31 / 24 / 26) |
| Filas de la matriz de trazabilidad | 43 (una por requerimiento, con módulo probable) |
| Módulos del sistema propuestos (Anexo A) | 9 |
| Riesgos / ambigüedades | 12 / 13 |
| Prompts documentados en la bitácora de IA | 20 (16 generales + 4 de la práctica) |
| Páginas | 37 |

Secciones: presentación · 1 problema · 2 usuarios · 3 alcance · 4 fuera del alcance · 5 requerimientos funcionales · 6 no funcionales · 7 historias · 8 criterios · 9 trazabilidad · 10 riesgos y ambigüedades · 11 bitácora de IA · 12 revisión crítica · 13 tarea siguiente y cronograma · 14 conclusión · Anexo A módulos.

## 4. Decisiones tomadas

1. **Dos usuarios operativos**, no cuatro: el **administrador de ventas** (unifica asesor comercial, ingeniero de ventas, administrativo y gerencia) y el **técnico**, que da de alta la pieza o servicio a cotizar.
2. **El técnico no ve precios, tarifas ni montos.** Solo ve el estado de su solicitud. Queda protegido por RF-05, RF-06, RNF-12 y RNF-26, con prueba de acceso por URL directa.
3. **Se eliminó el asistente de IA** que la propia IA había propuesto (módulo, requerimientos, historias y criterios). Queda registrado como exclusión, como propuesta rechazada y como error detectado.
4. **Prioridad Baja** para lo que puede pasar a la fase 2: HU-02 (recuperar contraseña), HU-09 (historial del equipo) y HU-20 (duplicar cotización).
5. **Fuera del alcance:** facturación electrónica/CFDI, app móvil nativa, portal de clientes, inventario, firma electrónica.
6. Documento en Arial, paleta navy, tamaño carta, índice con números de página reales.

## 5. Pendientes

1. **Validar con el cliente** las ambigüedades que dependen de él: A-01 regla de cálculo de modernizaciones, A-02 tratamiento del IVA, A-06 versión de PHP/MySQL y hosting, A-07 datos fiscales obligatorios, A-11 si el técnico ve el monto, A-12 hasta dónde puede capturar el técnico.
2. **Llenar `Prompts_Requerimientos_IA.pdf`** (carpeta 02): es la plantilla de evidencia que pidió el usuario. El contenido ya existe dentro del documento v3.0; falta vaciarlo en ese formato si se entrega aparte.
3. Si el docente exige una historia por requerimiento sin agrupar, hay que partir HU-07, HU-10, HU-14 y HU-19 (hoy agrupan tres o más requerimientos porque comparten pantalla).
4. Confirmar que la bitácora del apartado 11.6 corresponde al trabajo real del equipo.

## 6. Cómo regenerar el documento

```bash
cd ~/Documents/COTIZA-ELEV/03_Generador

# 1) construir el .docx (valida numeración, criterios y trazabilidad)
python3 build_doc.py

# 2) convertir a PDF con el índice en blanco
soffice --headless --convert-to pdf --outdir /tmp/paso1 \
  ../01_Entregables/Entrega_Actualizada_COTIZA-ELEV_v3.docx

# 3) leer los números de página reales del PDF
python3 pagemap.py ../01_Entregables/Entrega_Actualizada_COTIZA-ELEV_v3.docx \
  /tmp/paso1/Entrega_Actualizada_COTIZA-ELEV_v3.pdf

# 4) reconstruir con el índice ya rellenado
python3 build_doc.py paginas.json

# 5) convertir el PDF final y comprobar que el índice coincide
soffice --headless --convert-to pdf --outdir /tmp/paso2 \
  ../01_Entregables/Entrega_Actualizada_COTIZA-ELEV_v3.docx
python3 verificar_indice.py ../01_Entregables/Entrega_Actualizada_COTIZA-ELEV_v3.docx \
  /tmp/paso2/Entrega_Actualizada_COTIZA-ELEV_v3.pdf
```

El paso 4 (segunda pasada del índice) es necesario porque LibreOffice deja vacío el campo de índice de Word al exportar: por eso se calculan las páginas y se reconstruye.

## 7. Contenido del código

| Archivo | Qué contiene |
|---|---|
| `build_doc.py` | Estilos, portada, índice, tablas, figuras, conversión a PDF (Entrega v3.0) |
| `build_practica_arquitectura.py` | Generador de la Práctica: Diseño modular y arquitectura inicial (DOCX + PDF) |
| `build_practica_persistencia.py` | Generador de la Práctica: Persistencia y diseño mínimo de base de datos (DOCX + PDF) |
| `content_a.py` | Presentación, problema, usuarios, alcance, RF-01 a RF-43, RNF-01 a RNF-29 |
| `content_b.py` | Historias, criterios, matriz de trazabilidad, riesgos, bitácora de IA, revisión, cronograma, conclusión, Anexo A |
| `pagemap.py` | Extrae etiqueta → página del PDF para el índice |
| `verificar_indice.py` | Comprueba que el índice apunte a la página correcta |
| `paginas.json` | Mapa de páginas de la última ejecución |

El generador valida antes de escribir: identificadores consecutivos sin huecos, cada historia con al menos un caso Normal, Límite y Error, cada criterio perteneciente a su historia, cada requerimiento cubierto por una historia y una fila de la matriz, y cada módulo definido.

## 8. Entregable de Arquitectura y Diseño Modular

- Archivos generados: `01_Entregables/Practica_Diseno_Modular_y_Arquitectura_COTIZA-ELEV.docx` y `.pdf`.
- Cubre las secciones exigidas por la rúbrica del Ing. Alejandro Montes: portada con roles asignados, objetivo, módulos (M01-M09), matriz de trazabilidad de los 43 RF, diagrama visual por capas, árbol de archivos, justificación de diseño (7 preguntas guía), bitácora de IA (4 fases) y checklist de entrega.

## 9. Entregable de Persistencia y Diseño Mínimo de Base de Datos

- Archivos generados: `01_Entregables/Practica_Persistencia_Datos_COTIZA-ELEV.docx` y `.pdf` (14 páginas).
- Generado por: `python3 03_Generador/build_practica_persistencia.py`.
- Cubre: Decisión de persistencia (Ruta B - MySQL 8 / InnoDB), 9 entidades relacionales, diccionario de datos formal (28 campos), operaciones CRUD asignadas a módulos M01-M09, diagrama ERD visual de alta resolución con matplotlib, ejecución de los 3 prompts oficiales de la guía, bitácora de IA de 6 fases y las 8 preguntas de cierre contestadas.
- Sincronizado en Obsidian Second Brain: `~/Documents/Hermes-Brain/04-Proyectos/COTIZA-ELEV.md` y `~/Documents/EleveseTech-Brain/04-Proyectos/COTIZA-ELEV.md`.


