<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use PDO;

class TestDatabaseHelper
{
    public static function createTestPdo(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON;');

        // Esquema compatible SQLite de las 9 tablas en 3FN
        $pdo->exec('
            CREATE TABLE usuarios (
                id_usuario INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_completo TEXT NOT NULL,
                correo_electronico TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                rol TEXT NOT NULL DEFAULT "tecnico",
                intentos_fallidos INTEGER NOT NULL DEFAULT 0,
                bloqueado_hasta TEXT NULL,
                reset_token TEXT NULL,
                reset_token_expira TEXT NULL,
                estatus TEXT NOT NULL DEFAULT "activo",
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE clientes (
                id_cliente INTEGER PRIMARY KEY AUTOINCREMENT,
                clave_cliente TEXT NOT NULL UNIQUE,
                razon_social TEXT NOT NULL,
                rfc TEXT NOT NULL UNIQUE,
                nombre_contacto TEXT NOT NULL,
                correo TEXT NOT NULL,
                telefono TEXT NOT NULL,
                direccion_fiscal TEXT NOT NULL,
                estatus TEXT NOT NULL DEFAULT "activo",
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE equipos_activos (
                id_activo INTEGER PRIMARY KEY AUTOINCREMENT,
                id_cliente INTEGER NOT NULL,
                numero_serie TEXT NOT NULL UNIQUE,
                tipo_equipo TEXT NOT NULL DEFAULT "pasajeros",
                marca TEXT NOT NULL,
                modelo TEXT NOT NULL,
                capacidad_kg INTEGER NOT NULL,
                numero_paradas INTEGER NOT NULL,
                ubicacion_instalacion TEXT NOT NULL,
                estatus TEXT NOT NULL DEFAULT "activo",
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_cliente) REFERENCES clientes (id_cliente) ON UPDATE CASCADE ON DELETE RESTRICT
            );

            CREATE TABLE catalogo_conceptos (
                id_concepto INTEGER PRIMARY KEY AUTOINCREMENT,
                codigo_sku TEXT NOT NULL UNIQUE,
                tipo_concepto TEXT NOT NULL,
                descripcion TEXT NOT NULL,
                unidad_medida TEXT NOT NULL DEFAULT "pieza",
                precio_base REAL NOT NULL,
                vigente_desde TEXT NOT NULL,
                vigente_hasta TEXT NULL,
                estatus TEXT NOT NULL DEFAULT "activo",
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE solicitudes (
                id_solicitud INTEGER PRIMARY KEY AUTOINCREMENT,
                folio_solicitud TEXT NOT NULL UNIQUE,
                id_tecnico INTEGER NOT NULL,
                id_cliente INTEGER NOT NULL,
                id_activo INTEGER NOT NULL,
                tipo_servicio TEXT NOT NULL,
                diagnostico_falla TEXT NOT NULL,
                piezas_requeridas TEXT NOT NULL,
                urgencia TEXT NOT NULL DEFAULT "media",
                estado TEXT NOT NULL DEFAULT "pendiente",
                motivo_rechazo TEXT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_tecnico) REFERENCES usuarios (id_usuario) ON UPDATE CASCADE ON DELETE RESTRICT,
                FOREIGN KEY (id_cliente) REFERENCES clientes (id_cliente) ON UPDATE CASCADE ON DELETE RESTRICT,
                FOREIGN KEY (id_activo) REFERENCES equipos_activos (id_activo) ON UPDATE CASCADE ON DELETE RESTRICT
            );

            CREATE TABLE cotizaciones (
                id_cotizacion INTEGER PRIMARY KEY AUTOINCREMENT,
                folio_cotizacion TEXT NOT NULL UNIQUE,
                id_cliente INTEGER NOT NULL,
                id_activo INTEGER NOT NULL,
                id_usuario_creador INTEGER NOT NULL,
                id_solicitud INTEGER NULL,
                tipo_cotizacion TEXT NOT NULL,
                estado TEXT NOT NULL DEFAULT "borrador",
                fecha_emision TEXT NOT NULL,
                fecha_vencimiento TEXT NOT NULL,
                subtotal REAL NOT NULL DEFAULT 0.00,
                porcentaje_descuento REAL NOT NULL DEFAULT 0.00,
                monto_descuento REAL NOT NULL DEFAULT 0.00,
                iva REAL NOT NULL DEFAULT 0.00,
                total REAL NOT NULL DEFAULT 0.00,
                moneda TEXT NOT NULL DEFAULT "MXN",
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_cliente) REFERENCES clientes (id_cliente) ON UPDATE CASCADE ON DELETE RESTRICT,
                FOREIGN KEY (id_activo) REFERENCES equipos_activos (id_activo) ON UPDATE CASCADE ON DELETE RESTRICT,
                FOREIGN KEY (id_usuario_creador) REFERENCES usuarios (id_usuario) ON UPDATE CASCADE ON DELETE RESTRICT,
                FOREIGN KEY (id_solicitud) REFERENCES solicitudes (id_solicitud) ON UPDATE CASCADE ON DELETE RESTRICT
            );

            CREATE TABLE partidas_cotizacion (
                id_partida INTEGER PRIMARY KEY AUTOINCREMENT,
                id_cotizacion INTEGER NOT NULL,
                id_concepto INTEGER NULL,
                numero_item INTEGER NOT NULL,
                descripcion_concepto TEXT NOT NULL,
                cantidad REAL NOT NULL DEFAULT 1.00,
                precio_unitario REAL NOT NULL,
                total_partida REAL NOT NULL,
                FOREIGN KEY (id_cotizacion) REFERENCES cotizaciones (id_cotizacion) ON UPDATE CASCADE ON DELETE RESTRICT,
                FOREIGN KEY (id_concepto) REFERENCES catalogo_conceptos (id_concepto) ON UPDATE CASCADE ON DELETE RESTRICT
            );

            CREATE TABLE seguimiento_propuestas (
                id_seguimiento INTEGER PRIMARY KEY AUTOINCREMENT,
                id_cotizacion INTEGER NOT NULL,
                folio_propuesta TEXT NOT NULL,
                version_pdf INTEGER NOT NULL DEFAULT 1,
                ruta_archivo_pdf TEXT NOT NULL,
                fecha_envio_cliente TEXT NULL,
                medio_envio TEXT NOT NULL DEFAULT "correo",
                destinatario_correo TEXT NOT NULL,
                resultado TEXT NOT NULL DEFAULT "en_espera",
                motivo_rechazo TEXT NULL,
                fecha_cierre TEXT NULL,
                registrado_por INTEGER NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_cotizacion) REFERENCES cotizaciones (id_cotizacion) ON UPDATE CASCADE ON DELETE RESTRICT,
                FOREIGN KEY (registrado_por) REFERENCES usuarios (id_usuario) ON UPDATE CASCADE ON DELETE RESTRICT
            );

            CREATE TABLE bitacora_auditoria (
                id_log INTEGER PRIMARY KEY AUTOINCREMENT,
                id_usuario INTEGER NULL,
                modulo_afectado TEXT NOT NULL,
                accion TEXT NOT NULL,
                ip_origen TEXT NOT NULL DEFAULT "127.0.0.1",
                user_agent TEXT NOT NULL DEFAULT "CLI/Browser",
                detalle_evento TEXT NOT NULL,
                payload_previo TEXT NULL,
                payload_nuevo TEXT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario) ON UPDATE CASCADE ON DELETE RESTRICT
            );
        ');

        // Semillas
        $adminPass = password_hash('Admin2026!*', PASSWORD_BCRYPT);
        $tecPass = password_hash('Tecnico2026!*', PASSWORD_BCRYPT);

        $pdo->exec("
            INSERT INTO usuarios (id_usuario, nombre_completo, correo_electronico, password_hash, rol, estatus) VALUES
            (1, 'Orlando Administrador', 'admin@elevese.mx', '{$adminPass}', 'admin_ventas', 'activo'),
            (2, 'Carlos Tecnico Campo', 'tecnico@elevese.mx', '{$tecPass}', 'tecnico', 'activo');

            INSERT INTO clientes (id_cliente, clave_cliente, razon_social, rfc, nombre_contacto, correo, telefono, direccion_fiscal, estatus) VALUES
            (1, 'CLI-001', 'Corporativo Torre Bajio S.A.', 'CTB180410AB1', 'Ing. Roberto Alcocer', 'ralcocer@torrebajio.mx', '4771234567', 'Blvd. Aeropuerto 1000', 'activo');

            INSERT INTO equipos_activos (id_activo, id_cliente, numero_serie, tipo_equipo, marca, modelo, capacidad_kg, numero_paradas, ubicacion_instalacion, estatus) VALUES
            (1, 1, 'ELV-TB-01', 'pasajeros', 'Schindler', '3300 AP', 1000, 12, 'Torre A Principal', 'activo');

            INSERT INTO catalogo_conceptos (id_concepto, codigo_sku, tipo_concepto, descripcion, unidad_medida, precio_base, vigente_desde, estatus) VALUES
            (1, 'MO-HORA', 'mano_obra', 'Mano de obra especializada tecnico elevadores', 'hora', 700.00, '2026-01-01', 'activo'),
            (2, 'REF-SNS-OPT', 'refaccion', 'Sensor optico de nivelacion cabina', 'pieza', 3200.00, '2026-01-01', 'activo');
        ");

        Database::setConnection($pdo);
        return $pdo;
    }
}
