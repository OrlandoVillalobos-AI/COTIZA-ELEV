<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class AuditoriaModel
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function registrar(
        ?int $idUsuario,
        string $modulo,
        string $accion,
        string $ip,
        string $userAgent,
        string $detalle,
        ?array $payloadPrevio = null,
        ?array $payloadNuevo = null
    ): int {
        $this->db->query(
            'INSERT INTO bitacora_auditoria (
                id_usuario, modulo_afectado, accion, ip_origen, user_agent, detalle_evento, payload_previo, payload_nuevo
            ) VALUES (
                :usuario, :modulo, :accion, :ip, :ua, :detalle, :previo, :nuevo
            )',
            [
                ':usuario' => $idUsuario,
                ':modulo' => $modulo,
                ':accion' => $accion,
                ':ip' => $ip ?: '127.0.0.1',
                ':ua' => substr($userAgent ?: 'CLI/Unknown', 0, 255),
                ':detalle' => $detalle,
                ':previo' => $payloadPrevio !== null ? json_encode($payloadPrevio, JSON_UNESCAPED_UNICODE) : null,
                ':nuevo' => $payloadNuevo !== null ? json_encode($payloadNuevo, JSON_UNESCAPED_UNICODE) : null,
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function listarPaginado(int $limite = 50, int $offset = 0): array
    {
        $stmt = $this->db->query(
            'SELECT b.*, u.nombre_completo AS usuario_nombre, u.correo_electronico AS usuario_correo
             FROM bitacora_auditoria b
             LEFT JOIN usuarios u ON b.id_usuario = u.id_usuario
             ORDER BY b.id_log DESC LIMIT :limite OFFSET :offset',
            [':limite' => $limite, ':offset' => $offset]
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
