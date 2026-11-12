<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;
use RuntimeException;

class CotizacionModel
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function crear(array $datos, array $partidas): int
    {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();

        try {
            $this->db->query(
                'INSERT INTO cotizaciones (
                    folio_cotizacion, id_cliente, id_activo, id_usuario_creador, id_solicitud,
                    tipo_cotizacion, estado, fecha_emision, fecha_vencimiento,
                    subtotal, porcentaje_descuento, monto_descuento, iva, total, moneda
                ) VALUES (
                    :folio, :cliente, :activo, :usuario, :solicitud,
                    :tipo, :estado, :emision, :vencimiento,
                    :subtotal, :pct_desc, :mto_desc, :iva, :total, :moneda
                )',
                [
                    ':folio' => strtoupper(trim($datos['folio_cotizacion'])),
                    ':cliente' => (int)$datos['id_cliente'],
                    ':activo' => (int)$datos['id_activo'],
                    ':usuario' => (int)$datos['id_usuario_creador'],
                    ':solicitud' => !empty($datos['id_solicitud']) ? (int)$datos['id_solicitud'] : null,
                    ':tipo' => $datos['tipo_cotizacion'],
                    ':estado' => $datos['estado'] ?? 'borrador',
                    ':emision' => $datos['fecha_emision'] ?? date('Y-m-d'),
                    ':vencimiento' => $datos['fecha_vencimiento'] ?? date('Y-m-d', strtotime('+15 days')),
                    ':subtotal' => (float)($datos['subtotal'] ?? 0.0),
                    ':pct_desc' => (float)($datos['porcentaje_descuento'] ?? 0.0),
                    ':mto_desc' => (float)($datos['monto_descuento'] ?? 0.0),
                    ':iva' => (float)($datos['iva'] ?? 0.0),
                    ':total' => (float)($datos['total'] ?? 0.0),
                    ':moneda' => $datos['moneda'] ?? 'MXN',
                ]
            );

            $idCotizacion = (int)$pdo->lastInsertId();

            $itemNum = 1;
            foreach ($partidas as $p) {
                $this->db->query(
                    'INSERT INTO partidas_cotizacion (
                        id_cotizacion, id_concepto, numero_item, descripcion_concepto, cantidad, precio_unitario, total_partida
                    ) VALUES (
                        :cot, :concepto, :item, :desc, :cant, :precio, :total
                    )',
                    [
                        ':cot' => $idCotizacion,
                        ':concepto' => !empty($p['id_concepto']) ? (int)$p['id_concepto'] : null,
                        ':item' => $itemNum++,
                        ':desc' => trim($p['descripcion_concepto'] ?? $p['concepto'] ?? ''),
                        ':cant' => (float)($p['cantidad'] ?? 1.0),
                        ':precio' => (float)($p['precio_unitario'] ?? 0.0),
                        ':total' => (float)($p['total_partida'] ?? ($p['cantidad'] * $p['precio_unitario'])),
                    ]
                );
            }

            $pdo->commit();
            return $idCotizacion;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw new RuntimeException('Error transaccional al guardar cotización: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->query(
            'SELECT c.*, cl.razon_social AS cliente_razon_social, a.numero_serie AS activo_serie, u.nombre_completo AS creador_nombre
             FROM cotizaciones c
             JOIN clientes cl ON c.id_cliente = cl.id_cliente
             JOIN equipos_activos a ON c.id_activo = a.id_activo
             JOIN usuarios u ON c.id_usuario_creador = u.id_usuario
             WHERE c.id_cotizacion = :id LIMIT 1',
            [':id' => $id]
        );
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function buscarPorFolio(string $folio): ?array
    {
        $stmt = $this->db->query(
            'SELECT * FROM cotizaciones WHERE folio_cotizacion = :folio LIMIT 1',
            [':folio' => strtoupper(trim($folio))]
        );
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function listarPartidas(int $idCotizacion): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM partidas_cotizacion WHERE id_cotizacion = :id ORDER BY numero_item ASC',
            [':id' => $idCotizacion]
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cambiarEstado(int $id, string $nuevoEstado): bool
    {
        $stmt = $this->db->query(
            'UPDATE cotizaciones SET estado = :estado WHERE id_cotizacion = :id',
            [':estado' => $nuevoEstado, ':id' => $id]
        );
        return $stmt->rowCount() > 0;
    }

    public function listarPaginado(int $limite = 50, int $offset = 0, array $filtros = []): array
    {
        $sql = 'SELECT c.*, cl.razon_social AS cliente_razon_social, a.numero_serie AS activo_serie
                FROM cotizaciones c
                JOIN clientes cl ON c.id_cliente = cl.id_cliente
                JOIN equipos_activos a ON c.id_activo = a.id_activo';

        $conds = [];
        $params = [];

        if (!empty($filtros['estado'])) {
            $conds[] = 'c.estado = :estado';
            $params[':estado'] = $filtros['estado'];
        }
        if (!empty($filtros['id_cliente'])) {
            $conds[] = 'c.id_cliente = :cli';
            $params[':cli'] = (int)$filtros['id_cliente'];
        }

        if (!empty($conds)) {
            $sql .= ' WHERE ' . implode(' AND ', $conds);
        }

        $sql .= ' ORDER BY c.id_cotizacion DESC LIMIT :limite OFFSET :offset';
        $params[':limite'] = $limite;
        $params[':offset'] = $offset;

        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
