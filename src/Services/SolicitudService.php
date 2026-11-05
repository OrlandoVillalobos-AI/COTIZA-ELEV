<?php

declare(strict_types=1);

namespace CotizaElev\Services;

use InvalidArgumentException;
use RuntimeException;

/**
 * M05: Gestión de Solicitudes de Campo (SolicitudService)
 *
 * Atiende a: RF-20, RF-21, RF-22, RF-23, RF-24, RF-43 / HU-12, HU-13, HU-14.
 */
class SolicitudService
{
    public const ESTADO_PENDIENTE = 'Pendiente de cotizar';
    public const ESTADO_COTIZADA = 'Cotizada';
    public const ESTADO_RECHAZADA = 'Rechazada';

    /** @var array<int, array<string, mixed>> */
    private array $solicitudes = [];
    private int $siguienteId = 1;

    /**
     * Registra una nueva solicitud de cotización desde campo (RF-20, RF-21, RF-23; HU-12).
     *
     * @param array<string, mixed> $datos
     * @param bool $confirmarDuplicado
     * @param string $motivoRepeticion
     * @return array<string, mixed>
     */
    public function crearSolicitud(array $datos, bool $confirmarDuplicado = false, string $motivoRepeticion = ''): array
    {
        $clienteId = $datos['cliente_id'] ?? null;
        $activoId = $datos['activo_id'] ?? null;
        $piezaOServicio = isset($datos['pieza_o_servicio']) ? trim((string)$datos['pieza_o_servicio']) : '';
        $cantidad = $datos['cantidad'] ?? null;
        $urgencia = isset($datos['urgencia']) ? trim((string)$datos['urgencia']) : '';
        $tecnicoCorreo = isset($datos['tecnico_correo']) ? trim((string)$datos['tecnico_correo']) : '';

        if (
            empty($clienteId) ||
            empty($activoId) ||
            $piezaOServicio === '' ||
            !is_numeric($cantidad) || (float)$cantidad <= 0 ||
            $urgencia === '' ||
            $tecnicoCorreo === ''
        ) {
            throw new InvalidArgumentException("Faltan campos obligatorios: cliente, activo o descripción de la pieza");
        }

        // RF-23, CA-12.3: Verificar duplicado en estado 'Pendiente de cotizar' para el mismo activo y pieza
        foreach ($this->solicitudes as $sol) {
            if (
                $sol['estado'] === self::ESTADO_PENDIENTE &&
                (int)$sol['activo_id'] === (int)$activoId &&
                mb_strtolower($sol['pieza_o_servicio']) === mb_strtolower($piezaOServicio)
            ) {
                if (!$confirmarDuplicado || trim($motivoRepeticion) === '') {
                    throw new RuntimeException("Ya existe una solicitud pendiente de esa pieza para este equipo");
                }
                break;
            }
        }

        $id = $this->siguienteId++;
        $folio = sprintf('SOL-%04d', $id);
        $fechaCreacion = isset($datos['fecha_creacion']) ? (string)$datos['fecha_creacion'] : date('Y-m-d H:i:s');

        $nueva = [
            'id' => $id,
            'folio' => $folio,
            'cliente_id' => (int)$clienteId,
            'cliente' => (string)($datos['cliente'] ?? "Cliente #{$clienteId}"),
            'activo_id' => (int)$activoId,
            'activo' => (string)($datos['activo'] ?? "Equipo #{$activoId}"),
            'equipo' => (string)($datos['equipo'] ?? ($datos['activo'] ?? "Equipo #{$activoId}")),
            'pieza_o_servicio' => $piezaOServicio,
            'pieza' => $piezaOServicio,
            'cantidad' => (float)$cantidad,
            'urgencia' => $urgencia,
            'tecnico_correo' => $tecnicoCorreo,
            'tecnico' => $tecnicoCorreo,
            'creado_por' => $tecnicoCorreo,
            'estado' => self::ESTADO_PENDIENTE,
            'fecha_creacion' => $fechaCreacion,
            'fecha' => $fechaCreacion,
            'motivo_repeticion' => $confirmarDuplicado ? trim($motivoRepeticion) : null,
            'motivo_rechazo' => null,
            'atendido_por' => null,
            'fecha_atencion' => null,
            // Campos internos administrativos (nunca visibles para el técnico en RF-22)
            'precio_estimado' => $datos['precio_estimado'] ?? null,
            'importe_cotizado' => $datos['importe_cotizado'] ?? null,
            'folio_propuesta' => $datos['folio_propuesta'] ?? null,
        ];

        $this->solicitudes[$id] = $nueva;
        return $nueva;
    }

    /**
     * Lista la bandeja del administrador ordenada de la más antigua a la más reciente (RF-21, CA-14.1, CA-14.4).
     *
     * @param int $pagina
     * @param int $porPagina
     * @return array<string, mixed>
     */
    public function listarBandejaAdmin(int $pagina = 1, int $porPagina = 50): array
    {
        if ($pagina < 1) {
            $pagina = 1;
        }
        if ($porPagina < 1) {
            $porPagina = 50;
        }

        $pendientes = array_filter(
            $this->solicitudes,
            static fn(array $s): bool => $s['estado'] === self::ESTADO_PENDIENTE
        );

        usort($pendientes, static function (array $a, array $b): int {
            $cmp = strcmp((string)$a['fecha_creacion'], (string)$b['fecha_creacion']);
            return $cmp !== 0 ? $cmp : ($a['id'] <=> $b['id']);
        });

        $totalPendientes = count($pendientes);
        $totalPaginas = (int)max(1, ceil($totalPendientes / $porPagina));
        $offset = ($pagina - 1) * $porPagina;
        $bloque = array_slice($pendientes, $offset, $porPagina);

        $items = array_map(static function (array $s): array {
            return [
                'id' => $s['id'],
                'folio' => $s['folio'],
                'cliente_id' => $s['cliente_id'],
                'cliente' => $s['cliente'],
                'activo_id' => $s['activo_id'],
                'activo' => $s['activo'],
                'pieza_o_servicio' => $s['pieza_o_servicio'],
                'pieza' => $s['pieza'],
                'cantidad' => $s['cantidad'],
                'urgencia' => $s['urgencia'],
                'tecnico_correo' => $s['tecnico_correo'],
                'tecnico' => $s['tecnico'],
                'fecha_creacion' => $s['fecha_creacion'],
                'fecha' => $s['fecha'],
                'estado' => $s['estado'],
            ];
        }, $bloque);

        return [
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
            'total_pendientes' => $totalPendientes,
            'total_paginas' => $totalPaginas,
            'solicitudes' => array_values($items),
        ];
    }

    /**
     * Consulta las solicitudes propias de un técnico sin exponer importes ni precios (RF-22, HU-13).
     *
     * @param string $tecnicoCorreo
     * @return array<string, mixed>
     */
    public function consultarSolicitudesTecnico(string $tecnicoCorreo): array
    {
        $propias = array_filter(
            $this->solicitudes,
            static fn(array $s): bool => mb_strtolower($s['tecnico_correo']) === mb_strtolower(trim($tecnicoCorreo))
        );

        if (empty($propias)) {
            return [
                'mensaje' => 'No tienes solicitudes registradas',
                'solicitudes' => [],
            ];
        }

        $sanitizadas = [];
        foreach ($propias as $sol) {
            $sanitizadas[] = $this->sanitizarVistaTecnico($sol);
        }

        return [
            'mensaje' => 'Solicitudes recuperadas correctamente',
            'solicitudes' => $sanitizadas,
        ];
    }

    /**
     * Obtiene el detalle de una solicitud específica validando propiedad del técnico (CA-13.3).
     *
     * @param int $solicitudId
     * @param string $tecnicoCorreo
     * @return array<string, mixed>
     */
    public function obtenerDetalleSolicitudParaTecnico(int $solicitudId, string $tecnicoCorreo): array
    {
        if (!isset($this->solicitudes[$solicitudId])) {
            throw new RuntimeException("La solicitud indicada no existe");
        }

        $sol = $this->solicitudes[$solicitudId];
        if (mb_strtolower($sol['tecnico_correo']) !== mb_strtolower(trim($tecnicoCorreo))) {
            throw new RuntimeException("No tienes permisos para ver esta solicitud");
        }

        return $this->sanitizarVistaTecnico($sol);
    }

    /**
     * Atiende una solicitud marcándola como Cotizada o Rechazada (RF-24, RF-43; CA-14.2, CA-14.5).
     *
     * @param int $solicitudId
     * @param string $decision 'Cotizada' o 'Rechazada'
     * @param string $adminUsuario
     * @param string $motivoRechazo
     * @return array<string, mixed>
     */
    public function atenderSolicitud(int $solicitudId, string $decision, string $adminUsuario, string $motivoRechazo = ''): array
    {
        if (!isset($this->solicitudes[$solicitudId])) {
            throw new RuntimeException("La solicitud #{$solicitudId} no existe");
        }

        $sol = &$this->solicitudes[$solicitudId];

        if ($sol['estado'] === self::ESTADO_COTIZADA) {
            throw new RuntimeException("Esta solicitud ya fue cotizada por otro usuario");
        }

        if (!in_array($decision, [self::ESTADO_COTIZADA, self::ESTADO_RECHAZADA], true)) {
            throw new InvalidArgumentException("Decisión inválida: {$decision}");
        }

        if ($decision === self::ESTADO_RECHAZADA && trim($motivoRechazo) === '') {
            throw new InvalidArgumentException("Debe registrar un motivo al rechazar una solicitud");
        }

        $sol['estado'] = $decision;
        $sol['atendido_por'] = trim($adminUsuario);
        $sol['fecha_atencion'] = date('Y-m-d H:i:s');
        if ($decision === self::ESTADO_RECHAZADA) {
            $sol['motivo_rechazo'] = trim($motivoRechazo);
        }

        return $sol;
    }

    /**
     * Elimina cualquier rastro de precios, importes o folios comerciales para la vista del técnico (RF-22).
     *
     * @param array<string, mixed> $sol
     * @return array<string, mixed>
     */
    private function sanitizarVistaTecnico(array $sol): array
    {
        return [
            'id' => $sol['id'],
            'folio' => $sol['folio'],
            'cliente_id' => $sol['cliente_id'],
            'cliente' => $sol['cliente'],
            'activo_id' => $sol['activo_id'],
            'equipo' => $sol['equipo'],
            'pieza_o_servicio' => $sol['pieza_o_servicio'],
            'pieza' => $sol['pieza'],
            'cantidad' => $sol['cantidad'],
            'urgencia' => $sol['urgencia'],
            'fecha_creacion' => $sol['fecha_creacion'],
            'fecha' => $sol['fecha'],
            'estado' => $sol['estado'],
            'motivo_rechazo' => $sol['motivo_rechazo'],
        ];
    }
}
