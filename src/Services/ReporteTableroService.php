<?php

declare(strict_types=1);

namespace CotizaElev\Services;

use InvalidArgumentException;

/**
 * M09: Tablero de indicadores comerciales y exportación de reportes CSV (RF-39, RF-40; HU-23).
 */
final class ReporteTableroService
{
    /**
     * Calcula los indicadores operativos y comerciales del tablero dentro de un rango de fechas (RF-39, HU-23).
     *
     * @param list<array<string, mixed>> $cotizaciones
     * @param list<array<string, mixed>> $solicitudes
     * @return array<string, mixed>
     */
    public function obtenerTablero(
        array $cotizaciones,
        array $solicitudes,
        string $fechaInicio,
        string $fechaFin
    ): array {
        $this->validarRangoFechas($fechaInicio, $fechaFin);

        $cotizacionesPeriodo = array_values(array_filter(
            $cotizaciones,
            static fn(array $cot): bool =>
                isset($cot['fecha']) &&
                (string) $cot['fecha'] >= $fechaInicio &&
                (string) $cot['fecha'] <= $fechaFin
        ));

        $solicitudesPeriodo = array_values(array_filter(
            $solicitudes,
            static fn(array $sol): bool =>
                isset($sol['fecha']) &&
                (string) $sol['fecha'] >= $fechaInicio &&
                (string) $sol['fecha'] <= $fechaFin
        ));

        if ($cotizacionesPeriodo === [] && $solicitudesPeriodo === []) {
            return [
                'emitidas' => 0,
                'aceptadas' => 0,
                'rechazadas' => 0,
                'monto_total_cotizado' => 0.0,
                'solicitudes_pendientes_por_tecnico' => [],
                'mensaje' => 'No hay cotizaciones en el periodo seleccionado',
            ];
        }

        $emitidas = count($cotizacionesPeriodo);
        $aceptadas = 0;
        $rechazadas = 0;
        $montoTotal = 0.0;

        foreach ($cotizacionesPeriodo as $cot) {
            $estado = strtolower(trim((string) ($cot['estado'] ?? 'emitida')));
            if ($estado === 'aceptada') {
                $aceptadas++;
            } elseif ($estado === 'rechazada') {
                $rechazadas++;
            }
            $montoTotal += (float) ($cot['monto'] ?? 0.0);
        }

        $pendientesPorTecnico = [];
        foreach ($solicitudesPeriodo as $sol) {
            $estadoSol = strtolower(trim((string) ($sol['estado'] ?? 'pendiente')));
            if ($estadoSol === 'pendiente') {
                $tecnico = (string) ($sol['tecnico'] ?? 'Sin asignar');
                $pendientesPorTecnico[$tecnico] = ($pendientesPorTecnico[$tecnico] ?? 0) + 1;
            }
        }

        return [
            'emitidas' => $emitidas,
            'aceptadas' => $aceptadas,
            'rechazadas' => $rechazadas,
            'monto_total_cotizado' => round($montoTotal, 2),
            'solicitudes_pendientes_por_tecnico' => $pendientesPorTecnico,
            'mensaje' => 'Indicadores calculados correctamente',
        ];
    }

    /**
     * Exporta el listado de cotizaciones filtrado por fechas, cliente y técnico en formato CSV (RF-40, CA-23.3).
     *
     * @param list<array<string, mixed>> $cotizaciones
     */
    public function exportarReporteCsv(
        array $cotizaciones,
        string $fechaInicio,
        string $fechaFin,
        ?int $clienteId = null,
        ?string $tecnico = null
    ): string {
        $this->validarRangoFechas($fechaInicio, $fechaFin);

        $lineas = ['folio,fecha,cliente,tecnico,monto,estado'];

        foreach ($cotizaciones as $cot) {
            $fecha = (string) ($cot['fecha'] ?? '');
            if ($fecha < $fechaInicio || $fecha > $fechaFin) {
                continue;
            }
            if ($clienteId !== null && (int) ($cot['cliente_id'] ?? 0) !== $clienteId) {
                continue;
            }
            if ($tecnico !== null && trim($tecnico) !== '' && (string) ($cot['tecnico'] ?? '') !== $tecnico) {
                continue;
            }

            $lineas[] = sprintf(
                '%s,%s,%s,%s,%.2f,%s',
                (string) ($cot['folio'] ?? ''),
                $fecha,
                str_replace(',', ' ', (string) ($cot['cliente'] ?? '')),
                str_replace(',', ' ', (string) ($cot['tecnico'] ?? '')),
                (float) ($cot['monto'] ?? 0.0),
                (string) ($cot['estado'] ?? 'Emitida')
            );
        }

        return implode("\n", $lineas);
    }

    private function validarRangoFechas(string $fechaInicio, string $fechaFin): void
    {
        if (trim($fechaInicio) === '' || trim($fechaFin) === '' || $fechaFin < $fechaInicio) {
            throw new InvalidArgumentException('La fecha final no puede ser anterior a la inicial');
        }
    }
}
