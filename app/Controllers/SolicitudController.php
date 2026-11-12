<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\SolicitudModel;
use App\Models\AuditoriaModel;

class SolicitudController
{
    private SolicitudModel $solicitudes;
    private AuditoriaModel $auditoria;

    public function __construct(?SolicitudModel $solicitudes = null, ?AuditoriaModel $auditoria = null)
    {
        $this->solicitudes = $solicitudes ?? new SolicitudModel();
        $this->auditoria = $auditoria ?? new AuditoriaModel();
    }

    public function crear(Request $request): Response
    {
        $idCliente = (int)$request->get('id_cliente', 0);
        $idActivo = (int)$request->get('id_activo', 0);
        $tipo = (string)$request->get('tipo_servicio', 'correctivo');
        $diag = trim((string)$request->get('diagnostico_falla', ''));
        $piezas = trim((string)$request->get('piezas_requeridas', ''));
        $urgencia = (string)$request->get('urgencia', 'media');

        if ($idCliente <= 0 || $idActivo <= 0 || empty($diag)) {
            return Response::json(['error' => 'Cliente, activo y diagnóstico técnico son requeridos.', 'codigo' => 'VAL_ERROR'], 422);
        }

        // Control de duplicados en el mismo activo (RF-23)
        if ($this->solicitudes->existeDuplicadaEnActivo($idActivo)) {
            return Response::json([
                'error' => 'Ya existe una solicitud pendiente de cotizar para este elevador.',
                'codigo' => 'SOLICITUD_DUPLICADA',
            ], 409);
        }

        $idTecnico = Session::getUserId() ?? 2;
        $folio = 'SOL-' . date('Y') . '-' . str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);

        $id = $this->solicitudes->crear([
            'folio_solicitud' => $folio,
            'id_tecnico' => $idTecnico,
            'id_cliente' => $idCliente,
            'id_activo' => $idActivo,
            'tipo_servicio' => $tipo,
            'diagnostico_falla' => $diag,
            'piezas_requeridas' => $piezas,
            'urgencia' => $urgencia,
        ]);

        $this->auditoria->registrar(
            $idTecnico,
            'SOLICITUDES_TECNICAS',
            'INSERT',
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $request->getHeader('user-agent', 'Unknown'),
            "Solicitud técnica registrada: {$folio} ID {$id}"
        );

        return Response::json([
            'mensaje' => 'Solicitud técnica enviada exitosamente a la bandeja comercial.',
            'folio_solicitud' => $folio,
            'id_solicitud' => $id,
        ], 201);
    }

    public function misSolicitudes(Request $request): Response
    {
        $idTecnico = Session::getUserId() ?? 2;
        $solicitudes = $this->solicitudes->listarPorTecnico($idTecnico);

        return Response::json([
            'solicitudes' => $solicitudes,
            'total' => count($solicitudes),
        ], 200);
    }

    public function bandeja(Request $request): Response
    {
        $limite = max(1, min(100, (int)$request->get('limite', 50))); // Paginada de 50 en 50 (RF-21)
        $offset = max(0, (int)$request->get('offset', 0));

        $pendientes = $this->solicitudes->listarPendientes($limite, $offset);

        return Response::json([
            'solicitudes_pendientes' => $pendientes,
            'total_retornados' => count($pendientes),
            'limite' => $limite,
            'offset' => $offset,
        ], 200);
    }

    public function rechazar(Request $request, array $params): Response
    {
        $id = (int)($params['id'] ?? 0);
        $motivo = trim((string)$request->get('motivo_rechazo', ''));

        if (empty($motivo)) {
            return Response::json(['error' => 'Debe justificar el motivo del rechazo.', 'codigo' => 'VAL_ERROR'], 422);
        }

        $solicitud = $this->solicitudes->buscarPorId($id);
        if (!$solicitud) {
            return Response::json(['error' => 'Solicitud no encontrada.', 'codigo' => 'NOT_FOUND'], 404);
        }

        $this->solicitudes->cambiarEstado($id, 'rechazada', $motivo);

        $this->auditoria->registrar(
            Session::getUserId(),
            'SOLICITUDES_TECNICAS',
            'STATUS_CHANGE',
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $request->getHeader('user-agent', 'Unknown'),
            "Solicitud {$solicitud['folio_solicitud']} rechazada: {$motivo}"
        );

        return Response::json(['mensaje' => 'Solicitud técnica rechazada y archivada.'], 200);
    }
}
