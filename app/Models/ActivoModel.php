<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ActivoModel
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->query(
            'SELECT a.*, c.razon_social AS cliente_razon_social 
             FROM equipos_activos a
             JOIN clientes c ON a.id_cliente = c.id_cliente
             WHERE a.id_activo = :id LIMIT 1',
            [':id' => $id]
        );
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function buscarPorSerie(string $serie): ?array
    {
        $stmt = $this->db->query(
            'SELECT * FROM equipos_activos WHERE numero_serie = :serie LIMIT 1',
            [':serie' => trim($serie)]
        );
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function listarPorCliente(int $idCliente): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM equipos_activos WHERE id_cliente = :cli AND estatus = "activo" ORDER BY id_activo ASC',
            [':cli' => $idCliente]
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear(array $datos): int
    {
        $this->db->query(
            'INSERT INTO equipos_activos (id_cliente, numero_serie, tipo_equipo, marca, modelo, capacidad_kg, numero_paradas, ubicacion_instalacion, estatus)
             VALUES (:cliente, :serie, :tipo, :marca, :modelo, :capacidad, :paradas, :ubicacion, "activo")',
            [
                ':cliente' => (int)$datos['id_cliente'],
                ':serie' => trim($datos['numero_serie']),
                ':tipo' => $datos['tipo_equipo'] ?? 'pasajeros',
                ':marca' => trim($datos['marca']),
                ':modelo' => trim($datos['modelo']),
                ':capacidad' => (int)$datos['capacidad_kg'],
                ':paradas' => (int)$datos['numero_paradas'],
                ':ubicacion' => trim($datos['ubicacion_instalacion']),
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function obtenerHistorial(int $idActivo): array
    {
        // Historial de solicitudes y cotizaciones asociadas al activo
        $stmt = $this->db->query(
            'SELECT c.id_cotizacion, c.folio_cotizacion, c.fecha_emision, c.tipo_cotizacion, c.estado, c.total,
                    s.folio_solicitud, s.diagnostico_falla
             FROM cotizaciones c
             LEFT JOIN solicitudes s ON c.id_solicitud = s.id_solicitud
             WHERE c.id_activo = :id
             ORDER BY c.fecha_emision DESC',
            [':id' => $idActivo]
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
