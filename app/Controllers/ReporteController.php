<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\CotizacionModel;
use App\Models\SolicitudModel;
use App\Models\AuditoriaModel;

class ReporteController
{
    private CotizacionModel $cotizaciones;
    private SolicitudModel $solicitudes;
    private AuditoriaModel $auditoria;

    public function __construct(
        ?CotizacionModel $cotizaciones = null,
        ?SolicitudModel $solicitudes = null,
        ?AuditoriaModel $auditoria = null
    ) {
        $this->cotizaciones = $cotizaciones ?? new CotizacionModel();
        $this->solicitudes = $solicitudes ?? new SolicitudModel();
        $this->auditoria = $auditoria ?? new AuditoriaModel();
    }

    public function dashboard(Request $request): Response
    {
        $todas = $this->cotizaciones->listarPaginado(100, 0);
        $pendientes = $this->solicitudes->listarPendientes(100, 0);

        $totalMonto = 0.0;
        $conteoPorEstado = [
            'borrador' => 0,
            'enviada' => 0,
            'aceptada' => 0,
            'rechazada' => 0,
            'vencida' => 0,
        ];

        foreach ($todas as $c) {
            $totalMonto += (float)$c['total'];
            $st = $c['estado'];
            if (isset($conteoPorEstado[$st])) {
                $conteoPorEstado[$st]++;
            }
        }

        $totalCotizaciones = count($todas);
        $tasaConversion = $totalCotizaciones > 0 
            ? round(($conteoPorEstado['aceptada'] / $totalCotizaciones) * 100, 2)
            : 0.0;

        return Response::json([
            'kpis' => [
                'total_cotizaciones' => $totalCotizaciones,
                'monto_acumulado' => round($totalMonto, 2),
                'tasa_conversion_pct' => $tasaConversion,
                'solicitudes_pendientes_atencion' => count($pendientes),
                'distribucion_estados' => $conteoPorEstado,
            ],
            'fecha_corte' => date('Y-m-d H:i:s'),
        ], 200);
    }

    public function exportarCsv(Request $request): Response
    {
        $cotizaciones = $this->cotizaciones->listarPaginado(500, 0);

        $csv = "Folio,Cliente,Activo,Tipo,Estado,Emision,Total\n";
        foreach ($cotizaciones as $c) {
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",%.2f\n",
                $c['folio_cotizacion'],
                str_replace('"', '""', $c['cliente_razon_social']),
                $c['activo_serie'],
                $c['tipo_cotizacion'],
                $c['estado'],
                $c['fecha_emision'],
                (float)$c['total']
            );
        }

        $resp = new Response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="reporte_cotizaciones_' . date('Ymd_His') . '.csv"',
        ]);

        return $resp;
    }

    public function auditoria(Request $request): Response
    {
        $limite = max(1, min(100, (int)$request->get('limite', 50)));
        $offset = max(0, (int)$request->get('offset', 0));

        $logs = $this->auditoria->listarPaginado($limite, $offset);

        return Response::json([
            'bitacora' => $logs,
            'total_retornados' => count($logs),
            'limite' => $limite,
            'offset' => $offset,
        ], 200);
    }
}
