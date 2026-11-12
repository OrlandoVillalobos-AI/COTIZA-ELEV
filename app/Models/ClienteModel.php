<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ClienteModel
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function listar(int $limite = 50, int $offset = 0, ?string $busqueda = null): array
    {
        if ($busqueda !== null && trim($busqueda) !== '') {
            $termino = '%' . trim($busqueda) . '%';
            $stmt = $this->db->query(
                'SELECT * FROM clientes 
                 WHERE estatus = "activo" 
                   AND (razon_social LIKE :t1 OR rfc LIKE :t2 OR clave_cliente LIKE :t3)
                 ORDER BY id_cliente DESC LIMIT :limite OFFSET :offset',
                [
                    ':t1' => $termino,
                    ':t2' => $termino,
                    ':t3' => $termino,
                    ':limite' => $limite,
                    ':offset' => $offset,
                ]
            );
        } else {
            $stmt = $this->db->query(
                'SELECT * FROM clientes WHERE estatus = "activo" ORDER BY id_cliente DESC LIMIT :limite OFFSET :offset',
                [':limite' => $limite, ':offset' => $offset]
            );
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->query(
            'SELECT * FROM clientes WHERE id_cliente = :id LIMIT 1',
            [':id' => $id]
        );
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function buscarPorRfc(string $rfc): ?array
    {
        $stmt = $this->db->query(
            'SELECT * FROM clientes WHERE rfc = :rfc LIMIT 1',
            [':rfc' => strtoupper(trim($rfc))]
        );
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function buscarPorClave(string $clave): ?array
    {
        $stmt = $this->db->query(
            'SELECT * FROM clientes WHERE clave_cliente = :clave LIMIT 1',
            [':clave' => strtoupper(trim($clave))]
        );
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function crear(array $datos): int
    {
        $this->db->query(
            'INSERT INTO clientes (clave_cliente, razon_social, rfc, nombre_contacto, correo, telefono, direccion_fiscal, estatus)
             VALUES (:clave, :razon, :rfc, :contacto, :correo, :telefono, :direccion, "activo")',
            [
                ':clave' => strtoupper(trim($datos['clave_cliente'])),
                ':razon' => trim($datos['razon_social']),
                ':rfc' => strtoupper(trim($datos['rfc'])),
                ':contacto' => trim($datos['nombre_contacto']),
                ':correo' => strtolower(trim($datos['correo'])),
                ':telefono' => trim($datos['telefono']),
                ':direccion' => trim($datos['direccion_fiscal']),
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): bool
    {
        $stmt = $this->db->query(
            'UPDATE clientes SET 
                razon_social = :razon,
                nombre_contacto = :contacto,
                correo = :correo,
                telefono = :telefono,
                direccion_fiscal = :direccion
             WHERE id_cliente = :id',
            [
                ':razon' => trim($datos['razon_social']),
                ':contacto' => trim($datos['nombre_contacto']),
                ':correo' => strtolower(trim($datos['correo'])),
                ':telefono' => trim($datos['telefono']),
                ':direccion' => trim($datos['direccion_fiscal']),
                ':id' => $id,
            ]
        );
        return $stmt->rowCount() > 0;
    }

    public function bajaLogica(int $id): bool
    {
        $stmt = $this->db->query(
            'UPDATE clientes SET estatus = "inactivo" WHERE id_cliente = :id',
            [':id' => $id]
        );
        return $stmt->rowCount() > 0;
    }
}
