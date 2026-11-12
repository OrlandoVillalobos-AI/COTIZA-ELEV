<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class SolicitudModel
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function existeDuplicadaEnActivo(int $idActivo): bool
    {
        $stmt = $this->db->query(
            'SELECT COUNT(*) FROM solicitudes WHERE id_activo = :act AND estado = "pendiente"',
            [':act' => $idActivo]
        );
        return ((int)$stmt->fetchColumn()) > 0;
    }

    public function crear(array $datos): int
    {
        $this->db->query(
            'INSERT INTO solicitudes (folio_solicitud, id_tecnico, id_cliente, id_activo, tipo_servicio, diagnostico_falla, piezas_requeridas, urgencia, estado)
             VALUES (:folio, :tecnico, :cliente, :activo, :tipo, :diag, :piezas, :urgencia, "pendiente")',
            [
                ':folio' => strtoupper(trim($datos['folio_solicitud'])),
                ':tecnico' => (int)$datos['id_tecnico'],
                ':cliente' => (int)$datos['id_cliente'],
                ':activo' => (int)$datos['id_activo'],
                ':tipo' => $datos['tipo_servicio'],
                ':diag' => trim($datos['diagnostico_falla']),
                ':piezas' => trim($datos['piezas_requeridas'] ?? ''),
                ':urgencia' => $datos['urgencia'] ?? 'media',
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->query(
            'SELECT s.*, c.razon_social AS cliente_razon_social, a.numero_serie AS activo_serie, u.nombre_completo AS tecnico_nombre
             FROM solicitudes s
             JOIN clientes c ON s.id_cliente = c.id_cliente
             JOIN equipos_activos a ON s.id_activo = a.id_activo
             JOIN usuarios u ON s.id_tecnico = u.id_usuario
             WHERE s.id_solicitud = :id LIMIT 1',
            [':id' => $id]
        );
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function buscarPorFolio(string $folio): ?array
    {
        $stmt = $this->db->query(
            'SELECT * FROM solicitudes WHERE folio_solicitud = :folio LIMIT 1',
            [':folio' => strtoupper(trim($folio))]
        );
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function listarPendientes(int $limite = 50, int $offset = 0): array
    {
        $stmt = $this->db->query(
            'SELECT s.*, c.razon_social AS cliente_razon_social, a.numero_serie AS activo_serie, u.nombre_completo AS tecnico_nombre
             FROM solicitudes s
             JOIN clientes c ON s.id_cliente = c.id_cliente
             JOIN equipos_activos a ON s.id_activo = a.id_activo
             JOIN usuarios u ON s.id_tecnico = u.id_usuario
             WHERE s.estado = "pendiente"
             ORDER BY s.id_solicitud DESC LIMIT :limite OFFSET :offset',
            [':limite' => $limite, ':offset' => $offset]
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPorTecnico(int $idTecnico): array
    {
        $stmt = $this->db->query(
            'SELECT s.id_solicitud, s.folio_solicitud, s.tipo_servicio, s.urgencia, s.estado, s.created_at,
                    c.razon_social AS cliente_razon_social, a.numero_serie AS activo_serie
             FROM solicitudes s
             JOIN clientes c ON s.id_cliente = c.id_cliente
             JOIN equipos_activos a ON s.id_activo = a.id_activo
             WHERE s.id_tecnico = :tec
             ORDER BY s.id_solicitud DESC',
            [':tec' => $idTecnico]
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cambiarEstado(int $id, string $nuevoEstado, ?string $motivo = null): bool
    {
        $stmt = $this->db->query(
            'UPDATE solicitudes SET estado = :estado, motivo_rechazo = :motivo WHERE id_solicitud = :id',
            [':estado' => $nuevoEstado, ':motivo' => $motivo, ':id' => $id]
        );
        return $stmt->rowCount() > 0;
    }
}
