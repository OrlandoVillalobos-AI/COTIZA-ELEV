<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\CotizacionModel;
use App\Models\SolicitudModel;
use App\Models\CatalogoModel;
use App\Models\AuditoriaModel;
use CotizaElev\Services\CalculoMotorService;

class CotizadorController
{
    private CotizacionModel $cotizaciones;
    private SolicitudModel $solicitudes;
    private CatalogoModel $catalogo;
    private AuditoriaModel $auditoria;
    private CalculoMotorService $motor;

    public function __construct(
        ?CotizacionModel $cotizaciones = null,
        ?SolicitudModel $solicitudes = null,
        ?CatalogoModel $catalogo = null,
        ?AuditoriaModel $auditoria = null,
        ?CalculoMotorService $motor = null
    ) {
        $this->cotizaciones = $cotizaciones ?? new CotizacionModel();
        $this->solicitudes = $solicitudes ?? new SolicitudModel();
        $this->catalogo = $catalogo ?? new CatalogoModel();
        $this->auditoria = $auditoria ?? new AuditoriaModel();
        $this->motor = $motor ?? new CalculoMotorService();
    }

    public function convertirSolicitud(Request $request, array $params): Response
    {
        $idSol = (int)($params['id'] ?? 0);
        $sol = $this->solicitudes->buscarPorId($idSol);

        if (!$sol) {
            return Response::json(['error' => 'Solicitud técnica no encontrada.', 'codigo' => 'NOT_FOUND'], 404);
        }

        // Marcar solicitud como cotizada
        $this->solicitudes->cambiarEstado($idSol, 'cotizada');

        return Response::json([
            'mensaje' => 'Solicitud lista para cotización.',
            'datos_precargados' => [
                'id_solicitud' => $sol['id_solicitud'],
                'folio_solicitud' => $sol['folio_solicitud'],
                'id_cliente' => $sol['id_cliente'],
                'cliente' => $sol['cliente_razon_social'],
                'id_activo' => $sol['id_activo'],
                'activo_serie' => $sol['activo_serie'],
                'tipo_cotizacion' => $sol['tipo_servicio'],
                'diagnostico' => $sol['diagnostico_falla'],
                'piezas_sugeridas' => $sol['piezas_requeridas'],
            ],
        ], 200);
    }

    public function calcular(Request $request): Response
    {
        $partidas = (array)$request->get('partidas', []);
        $porcentajeDescuento = (float)$request->get('porcentaje_descuento', 0.0);

        if (empty($partidas)) {
            return Response::json(['error' => 'Debe incluir al menos una partida.', 'codigo' => 'VAL_ERROR'], 422);
        }

        try {
            $totales = $this->ejecutarCalculoMotor($partidas, $porcentajeDescuento);
            return Response::json([
                'calculo' => $totales,
            ], 200);
        } catch (\Throwable $e) {
            return Response::json(['error' => $e->getMessage(), 'codigo' => 'CALCULO_ERROR'], 422);
        }
    }

    public function guardar(Request $request): Response
    {
        $idCliente = (int)$request->get('id_cliente', 0);
        $idActivo = (int)$request->get('id_activo', 0);
        $tipo = (string)$request->get('tipo_cotizacion', 'correctivo');
        $partidas = (array)$request->get('partidas', []);
        $pctDesc = (float)$request->get('porcentaje_descuento', 0.0);

        if ($idCliente <= 0 || $idActivo <= 0 || empty($partidas)) {
            return Response::json(['error' => 'Cliente, activo y partidas son obligatorios.', 'codigo' => 'VAL_ERROR'], 422);
        }

        // Ejecutar cálculo matemático oficial con CalculoMotorService (M07)
        $totales = $this->ejecutarCalculoMotor($partidas, $pctDesc);

        $idUsuario = Session::getUserId() ?? 1;
        $folio = 'COT-' . date('Y') . '-' . str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);

        $partidasParaGuardar = [];
        foreach ($partidas as $idx => $p) {
            $partidasParaGuardar[] = [
                'id_concepto' => !empty($p['id_concepto']) ? (int)$p['id_concepto'] : null,
                'descripcion_concepto' => $p['descripcion_concepto'] ?? $p['descripcion'] ?? "Partida #" . ($idx + 1),
                'cantidad' => (float)$p['cantidad'],
                'precio_unitario' => (float)$p['precio_unitario'],
                'total_partida' => round((float)$p['cantidad'] * (float)$p['precio_unitario'], 2),
            ];
        }

        $id = $this->cotizaciones->crear([
            'folio_cotizacion' => $folio,
            'id_cliente' => $idCliente,
            'id_activo' => $idActivo,
            'id_usuario_creador' => $idUsuario,
            'id_solicitud' => $request->get('id_solicitud', null),
            'tipo_cotizacion' => $tipo,
            'estado' => 'borrador',
            'fecha_emision' => date('Y-m-d'),
            'fecha_vencimiento' => date('Y-m-d', strtotime('+15 days')),
            'subtotal' => $totales['subtotal'],
            'porcentaje_descuento' => $totales['porcentaje_descuento'],
            'monto_descuento' => $totales['monto_descuento'],
            'iva' => $totales['iva'],
            'total' => $totales['total'],
            'moneda' => 'MXN',
        ], $partidasParaGuardar);

        $this->auditoria->registrar(
            $idUsuario,
            'COTIZACIONES',
            'INSERT',
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $request->getHeader('user-agent', 'Unknown'),
            "Cotización borrador creada: {$folio} Total \${$totales['total']}"
        );

        return Response::json([
            'mensaje' => 'Cotización guardada como borrador con vigencia a 15 días.',
            'id_cotizacion' => $id,
            'folio_cotizacion' => $folio,
            'totales' => $totales,
        ], 201);
    }

    public function duplicar(Request $request, array $params): Response
    {
        $id = (int)($params['id'] ?? 0);
        $original = $this->cotizaciones->buscarPorId($id);

        if (!$original) {
            return Response::json(['error' => 'Cotización original no encontrada.', 'codigo' => 'NOT_FOUND'], 404);
        }

        $partidasOriginales = $this->cotizaciones->listarPartidas($id);
        $nuevasPartidas = [];
        $variaciones = [];

        foreach ($partidasOriginales as $p) {
            $precioAplicado = (float)$p['precio_unitario'];
            if (!empty($p['id_concepto'])) {
                $conceptoActual = $this->catalogo->buscarPorId((int)$p['id_concepto']);
                if ($conceptoActual && (float)$conceptoActual['precio_base'] !== $precioAplicado) {
                    $variaciones[] = [
                        'concepto' => $p['descripcion_concepto'],
                        'precio_anterior' => $precioAplicado,
                        'precio_actual' => (float)$conceptoActual['precio_base'],
                    ];
                    $precioAplicado = (float)$conceptoActual['precio_base'];
                }
            }

            $nuevasPartidas[] = [
                'id_concepto' => $p['id_concepto'],
                'descripcion_concepto' => $p['descripcion_concepto'],
                'cantidad' => (float)$p['cantidad'],
                'precio_unitario' => $precioAplicado,
            ];
        }

        // Recalcular con tarifas vigentes
        $totales = $this->ejecutarCalculoMotor($nuevasPartidas, (float)$original['porcentaje_descuento']);
        $folio = 'COT-' . date('Y') . '-' . str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $idUsuario = Session::getUserId() ?? 1;

        $nuevoId = $this->cotizaciones->crear([
            'folio_cotizacion' => $folio,
            'id_cliente' => $original['id_cliente'],
            'id_activo' => $original['id_activo'],
            'id_usuario_creador' => $idUsuario,
            'id_solicitud' => null,
            'tipo_cotizacion' => $original['tipo_cotizacion'],
            'estado' => 'borrador',
            'subtotal' => $totales['subtotal'],
            'porcentaje_descuento' => $totales['porcentaje_descuento'],
            'monto_descuento' => $totales['monto_descuento'],
            'iva' => $totales['iva'],
            'total' => $totales['total'],
        ], $nuevasPartidas);

        $this->auditoria->registrar(
            $idUsuario,
            'COTIZACIONES',
            'INSERT',
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $request->getHeader('user-agent', 'Unknown'),
            "Duplicación de cotización {$original['folio_cotizacion']} a nueva {$folio}"
        );

        return Response::json([
            'mensaje' => 'Cotización duplicada con actualización de tarifas vigentes.',
            'id_cotizacion' => $nuevoId,
            'folio_cotizacion' => $folio,
            'variaciones_tarifas' => $variaciones,
            'totales' => $totales,
        ], 201);
    }

    public function cambiarEstado(Request $request, array $params): Response
    {
        $id = (int)($params['id'] ?? 0);
        $nuevoEstado = (string)$request->get('estado', '');

        $estadosValidos = ['borrador', 'enviada', 'aceptada', 'rechazada', 'vencida'];
        if (!in_array($nuevoEstado, $estadosValidos, true)) {
            return Response::json(['error' => 'Estado inválido.', 'codigo' => 'VAL_ERROR'], 422);
        }

        $cot = $this->cotizaciones->buscarPorId($id);
        if (!$cot) {
            return Response::json(['error' => 'Cotización no encontrada.', 'codigo' => 'NOT_FOUND'], 404);
        }

        $this->cotizaciones->cambiarEstado($id, $nuevoEstado);

        $this->auditoria->registrar(
            Session::getUserId(),
            'COTIZACIONES',
            'STATUS_CHANGE',
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $request->getHeader('user-agent', 'Unknown'),
            "Cotización {$cot['folio_cotizacion']} transicionó de {$cot['estado']} a {$nuevoEstado}"
        );

        return Response::json(['mensaje' => "Estado de cotización actualizado a {$nuevoEstado}."], 200);
    }

    private function ejecutarCalculoMotor(array $partidas, float $pctDescuento): array
    {
        $subtotalBruto = 0.0;
        foreach ($partidas as $p) {
            $cant = (float)($p['cantidad'] ?? 0);
            $pu = (float)($p['precio_unitario'] ?? 0);
            $subtotalBruto += ($cant * $pu);
        }

        $montoDescuento = round($subtotalBruto * ($pctDescuento / 100), 2);
        $calculo = $this->motor->calcularTotales($partidas, CalculoMotorService::TASA_IVA_DEFAULT, $montoDescuento);

        return [
            'numero_partidas' => $calculo['numero_partidas'],
            'subtotal' => $calculo['subtotal'],
            'porcentaje_descuento' => $pctDescuento,
            'monto_descuento' => $calculo['descuento'],
            'base_gravable' => $calculo['base_gravable'],
            'tasa_iva' => $calculo['tasa_iva'],
            'iva' => $calculo['monto_iva'],
            'monto_iva' => $calculo['monto_iva'],
            'total' => $calculo['total'],
            'partidas' => $calculo['partidas'],
        ];
    }
}
