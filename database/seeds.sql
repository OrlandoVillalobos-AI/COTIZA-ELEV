-- ========================================================================
-- SISTEMA COTIZA-ELEV — SEMILLAS INICIALES (INCREMENTO 6 / v0.6.0)
-- ========================================================================

-- 1. USUARIOS BASE (Credenciales de prueba: Admin2026!* / Tecnico2026!*)
INSERT INTO `usuarios` (`id_usuario`, `nombre_completo`, `correo_electronico`, `password_hash`, `rol`, `intentos_fallidos`, `estatus`) VALUES
(1, 'Orlando Administrador', 'admin@elevese.mx', '$2y$12$DF2CNqiUhCR9C9tF1kosO.fEXYun6sQbKJyf/cx6IO6gu5jysiPOm', 'admin_ventas', 0, 'activo'),
(2, 'Carlos Técnico Campo', 'tecnico@elevese.mx', '$2y$12$zQbqRYy6rGE7BqDYExfBXOGNsP.FnFC4MA5AO.uP0.W/F9.lwk.pS', 'tecnico', 0, 'activo');

-- 2. CLIENTES DE PRUEBA
INSERT INTO `clientes` (`id_cliente`, `clave_cliente`, `razon_social`, `rfc`, `nombre_contacto`, `correo`, `telefono`, `direccion_fiscal`, `estatus`) VALUES
(1, 'CLI-001', 'Corporativo Torre Bajío S.A. de C.V.', 'CTB180410AB1', 'Ing. Roberto Alcocer', 'ralcocer@torrebajio.mx', '4771234567', 'Blvd. Aeropuerto 1000, León, GTO', 'activo'),
(2, 'CLI-002', 'Centro Comercial Galerías Metrópoli S.A.', 'CCG200115XX9', 'Lic. Mariana Zavala', 'mzavala@metropoli.mx', '4777654321', 'Av. Cerro Gordo 250, León, GTO', 'activo');

-- 3. EQUIPOS / ACTIVOS DE ELEVACIÓN
INSERT INTO `equipos_activos` (`id_activo`, `id_cliente`, `numero_serie`, `tipo_equipo`, `marca`, `modelo`, `capacidad_kg`, `numero_paradas`, `ubicacion_instalacion`, `estatus`) VALUES
(1, 1, 'ELV-TB-01', 'pasajeros', 'Schindler', '3300 AP', 1000, 12, 'Torre A - Elevador Principal Norte', 'activo'),
(2, 1, 'ELV-TB-02', 'carga', 'Otis', 'Gen2 Comfort', 2000, 8, 'Torre B - Elevador Montacargas Servicio', 'activo'),
(3, 2, 'ELV-GM-01', 'pasajeros', 'Kone', 'MonoSpace 500', 800, 4, 'Plaza Central - Acceso Cines', 'activo');

-- 4. CATÁLOGO BASE DE CONCEPTOS Y TARIFAS VIGENTES
INSERT INTO `catalogo_conceptos` (`id_concepto`, `codigo_sku`, `tipo_concepto`, `descripcion`, `unidad_medida`, `precio_base`, `vigente_desde`, `vigente_hasta`, `estatus`) VALUES
(1, 'MO-HORA', 'mano_obra', 'Mano de obra especializada técnico elevadores (por hora)', 'hora', 700.00, '2026-01-01', NULL, 'activo'),
(2, 'REF-SNS-OPT', 'refaccion', 'Sensor óptico de nivelación de cabina infrarrojo', 'pieza', 3200.00, '2026-01-01', NULL, 'activo'),
(3, 'REF-PAT-FRN', 'refaccion', 'Juego de patines de freno electromagnético de tracción', 'juego', 8500.00, '2026-01-01', NULL, 'activo'),
(4, 'REF-CBL-TRC', 'refaccion', 'Cable de tracción de acero 1/2 pulgada alma de fibra (por metro)', 'metro', 185.00, '2026-01-01', NULL, 'activo'),
(5, 'POL-MNT-EST', 'poliza', 'Póliza de mantenimiento preventivo mensual estándar (hasta 10 niveles)', 'servicio', 4500.00, '2026-01-01', NULL, 'activo'),
(6, 'MOD-CTR-VVVF', 'modernizacion', 'Modernización de maniobra de control con variador VVVF regenerativo', 'paquete', 145000.00, '2026-01-01', NULL, 'activo');
