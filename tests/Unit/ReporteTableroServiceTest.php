<?php

declare(strict_types=1);

namespace Tests\Unit;

use CotizaElev\Services\ReporteTableroService;
use InvalidArgumentException;

require_once __DIR__ . '/../../src/Services/ReporteTableroService.php';

final class ReporteTableroServiceTest
{
    public function ejecutarTodas(): array { return $this->run(); }

    public function run(): array
    {
        $ejecutadas = 0;
        $pasadas = 0;

        $service = new ReporteTableroService();
        $cotizaciones = [
            [
                'folio' => 'COT-2026-0001',
                'fecha' => '2026-05-02',
                'cliente_id' => 10,
                'cliente' => 'Torre Corporativa Bajío',
                'tecnico' => 'carlos.mendez',
                'monto' => 14500.00,
                'estado' => 'Aceptada',
            ],
            [
                'folio' => 'COT-2026-0002',
                'fecha' => '2026-05-10',
                'cliente_id' => 11,
                'cliente' => 'Plaza Mayor Locales',
                'tecnico' => 'luis.torres',
                'monto' => 8200.50,
                'estado' => 'Rechazada',
            ],
            [
                'folio' => 'COT-2026-0003',
                'fecha' => '2026-05-18',
                'cliente_id' => 10,
                'cliente' => 'Torre Corporativa Bajío',
                'tecnico' => 'carlos.mendez',
                'monto' => 6300.00,
                'estado' => 'Emitida',
            ],
        ];
        $solicitudes = [
            ['id' => 1, 'fecha' => '2026-05-03', 'tecnico' => 'carlos.mendez', 'estado' => 'Pendiente'],
            ['id' => 2, 'fecha' => '2026-05-05', 'tecnico' => 'carlos.mendez', 'estado' => 'Pendiente'],
            ['id' => 3, 'fecha' => '2026-05-08', 'tecnico' => 'luis.torres', 'estado' => 'Cotizada'],
        ];

        // CA-23.1: Normal - Tablero con métricas del periodo
        $ejecutadas++;
        $tablero = $service->obtenerTablero($cotizaciones, $solicitudes, '2026-05-01', '2026-05-31');
        assert($tablero['emitidas'] === 3);
        assert($tablero['aceptadas'] === 1);
        assert($tablero['rechazadas'] === 1);
        assert($tablero['monto_total_cotizado'] === 29000.50);
        assert($tablero['solicitudes_pendientes_por_tecnico']['carlos.mendez'] === 2);
        $pasadas++;

        // CA-23.2: Límite - Periodo sin movimientos devuelve todos los indicadores en 0 y mensaje claro
        $ejecutadas++;
        $vacio = $service->obtenerTablero($cotizaciones, $solicitudes, '2026-01-01', '2026-01-31');
        assert($vacio['emitidas'] === 0);
        assert($vacio['aceptadas'] === 0);
        assert($vacio['rechazadas'] === 0);
        assert($vacio['monto_total_cotizado'] === 0.0);
        assert($vacio['mensaje'] === 'No hay cotizaciones en el periodo seleccionado');
        $pasadas++;

        // CA-23.3: Normal - Exportación CSV filtrada por cliente y técnico
        $ejecutadas++;
        $csv = $service->exportarReporteCsv($cotizaciones, '2026-05-01', '2026-05-31', 10, 'carlos.mendez');
        $lineas = explode("\n", $csv);
        assert(count($lineas) === 3);
        assert($lineas[0] === 'folio,fecha,cliente,tecnico,monto,estado');
        assert(str_contains($lineas[1], 'COT-2026-0001'));
        assert(str_contains($lineas[2], 'COT-2026-0003'));
        $pasadas++;

        // CA-23.4: Error - Fecha final anterior a la inicial
        $ejecutadas++;
        $capturada23_4 = false;
        try {
            $service->obtenerTablero($cotizaciones, $solicitudes, '2026-05-31', '2026-05-01');
        } catch (InvalidArgumentException $e) {
            $capturada23_4 = ($e->getMessage() === 'La fecha final no puede ser anterior a la inicial');
        }
        assert($capturada23_4 === true);
        $pasadas++;

        return ['ejecutadas' => $ejecutadas, 'pasadas' => $pasadas];
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $res = (new ReporteTableroServiceTest())->run();
    echo json_encode($res) . PHP_EOL;
}
