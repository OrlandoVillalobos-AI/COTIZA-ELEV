<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class UsuarioModel
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function buscarPorCorreo(string $correo): ?array
    {
        $stmt = $this->db->query(
            'SELECT * FROM usuarios WHERE correo_electronico = :correo AND estatus = "activo" LIMIT 1',
            [':correo' => strtolower(trim($correo))]
        );
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->query(
            'SELECT * FROM usuarios WHERE id_usuario = :id LIMIT 1',
            [':id' => $id]
        );
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function incrementarIntentosFallidos(int $id, int $bloquearMinutos = 15): void
    {
        $usuario = $this->buscarPorId($id);
        if (!$usuario) {
            return;
        }

        $nuevosIntentos = ((int)$usuario['intentos_fallidos']) + 1;
        $bloqueadoHasta = null;

        if ($nuevosIntentos >= 5) {
            $bloqueadoHasta = date('Y-m-d H:i:s', time() + ($bloquearMinutos * 60));
        }

        $this->db->query(
            'UPDATE usuarios SET intentos_fallidos = :intentos, bloqueado_hasta = :bloqueado WHERE id_usuario = :id',
            [
                ':intentos' => $nuevosIntentos,
                ':bloqueado' => $bloqueadoHasta,
                ':id' => $id,
            ]
        );
    }

    public function restablecerIntentos(int $id): void
    {
        $this->db->query(
            'UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id_usuario = :id',
            [':id' => $id]
        );
    }

    public function guardarResetToken(int $id, string $token, int $vigenciaMinutos = 30): void
    {
        $expira = date('Y-m-d H:i:s', time() + ($vigenciaMinutos * 60));
        $this->db->query(
            'UPDATE usuarios SET reset_token = :token, reset_token_expira = :expira WHERE id_usuario = :id',
            [
                ':token' => $token,
                ':expira' => $expira,
                ':id' => $id,
            ]
        );
    }

    public function buscarPorTokenValido(string $token): ?array
    {
        $ahora = date('Y-m-d H:i:s');
        $stmt = $this->db->query(
            'SELECT * FROM usuarios WHERE reset_token = :token AND reset_token_expira >= :ahora AND estatus = "activo" LIMIT 1',
            [':token' => $token, ':ahora' => $ahora]
        );
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function actualizarPassword(int $id, string $nuevoHash): void
    {
        $this->db->query(
            'UPDATE usuarios SET password_hash = :hash, reset_token = NULL, reset_token_expira = NULL, intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id_usuario = :id',
            [':hash' => $nuevoHash, ':id' => $id]
        );
    }

    public function crear(array $datos): int
    {
        $this->db->query(
            'INSERT INTO usuarios (nombre_completo, correo_electronico, password_hash, rol, estatus)
             VALUES (:nombre, :correo, :hash, :rol, :estatus)',
            [
                ':nombre' => $datos['nombre_completo'],
                ':correo' => strtolower(trim($datos['correo_electronico'])),
                ':hash' => $datos['password_hash'],
                ':rol' => $datos['rol'] ?? 'tecnico',
                ':estatus' => $datos['estatus'] ?? 'activo',
            ]
        );
        return (int)$this->db->lastInsertId();
    }
}
