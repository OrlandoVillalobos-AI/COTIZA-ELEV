<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class CatalogoModel
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function listarVigentes(?string $tipo = null): array
    {
        $hoy = date('Y-m-d');
        if ($tipo !== null && trim($tipo) !== '') {
            $stmt = $this->db->query(
                'SELECT * FROM catalogo_conceptos 
                 WHERE estatus = "activo" 
                   AND tipo_concepto = :tipo
                   AND vigente_desde <= :hoy1 
                   AND (vigente_hasta IS NULL OR vigente_hasta >= :hoy2)
                 ORDER BY descripcion ASC',
                [':tipo' => $tipo, ':hoy1' => $hoy, ':hoy2' => $hoy]
            );
        } else {
            $stmt = $this->db->query(
                'SELECT * FROM catalogo_conceptos 
                 WHERE estatus = "activo" 
                   AND vigente_desde <= :hoy1 
                   AND (vigente_hasta IS NULL OR vigente_hasta >= :hoy2)
                 ORDER BY tipo_concepto ASC, descripcion ASC',
                [':hoy1' => $hoy, ':hoy2' => $hoy]
            );
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorSku(string $sku): ?array
    {
        $stmt = $this->db->query(
            'SELECT * FROM catalogo_conceptos WHERE codigo_sku = :sku LIMIT 1',
            [':sku' => strtoupper(trim($sku))]
        );
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->query(
            'SELECT * FROM catalogo_conceptos WHERE id_concepto = :id LIMIT 1',
            [':id' => $id]
        );
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function crear(array $datos): int
    {
        $this->db->query(
            'INSERT INTO catalogo_conceptos (codigo_sku, tipo_concepto, descripcion, unidad_medida, precio_base, vigente_desde, vigente_hasta, estatus)
             VALUES (:sku, :tipo, :desc, :unidad, :precio, :desde, :hasta, "activo")',
            [
                ':sku' => strtoupper(trim($datos['codigo_sku'])),
                ':tipo' => $datos['tipo_concepto'],
                ':desc' => trim($datos['descripcion']),
                ':unidad' => $datos['unidad_medida'] ?? 'pieza',
                ':precio' => (float)$datos['precio_base'],
                ':desde' => $datos['vigente_desde'] ?? date('Y-m-d'),
                ':hasta' => $datos['vigente_hasta'] ?? null,
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function actualizarPrecio(int $id, float $nuevoPrecio): bool
    {
        $stmt = $this->db->query(
            'UPDATE catalogo_conceptos SET precio_base = :precio WHERE id_concepto = :id',
            [':precio' => $nuevoPrecio, ':id' => $id]
        );
        return $stmt->rowCount() > 0;
    }
}
