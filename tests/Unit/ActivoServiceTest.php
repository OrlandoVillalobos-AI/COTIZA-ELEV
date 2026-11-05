<?php

declare(strict_types=1);

namespace Tests\Unit;

require_once __DIR__ . '/../../src/Services/ActivoService.php';

use CotizaElev\Services\ActivoService;
use InvalidArgumentException;
use RuntimeException;

/**
 * Suite de pruebas unitarias para ActivoService (M03 - Incremento 2)
 * Trazabilidad: HU-08
 */
class ActivoServiceTest
{
    private int $ejecutadas = 0;
    private int $pasadas = 0;

    public function ejecutarTodas(): array
    {
        $this->ejecutadas = 0;
        $this->pasadas = 0;

        $this->testRegistroActivoNormal_CA08_1();
        $this->testRegistroActivoFueraDeRango_CA08_2();
        $this->testRegistroActivoSinCliente_CA08_3();
        $this->testRegistroActivoLimiteDosParadas_CA08_4();

        return [
            'ejecutadas' => $this->ejecutadas,
            'pasadas' => $this->pasadas,
        ];
    }

    private function testRegistroActivoNormal_CA08_1(): void
    {
        $this->ejecutadas++;
        $service = new ActivoService();

        $activo = $service->registrarActivo([
            'cliente_id' => 1,
            'tipo' => 'pasajeros',
            'marca' => 'Otis',
            'modelo' => 'Gen2 Comfort',
            'serie' => 'OT-2022-8891',
            'capacidad_kg' => 800,
            'paradas' => 8,
            'velocidad' => 1.6,
            'anio' => 2022,
            'ubicacion' => 'Torre Norte - Cubo Principal',
        ]);

        if (
            $activo['id'] === 1
            && $activo['cliente_id'] === 1
            && $activo['paradas'] === 8
            && $activo['capacidad_kg'] === 800.0
        ) {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-08.1');
        }
    }

    private function testRegistroActivoFueraDeRango_CA08_2(): void
    {
        $this->ejecutadas++;
        $service = new ActivoService();

        $rechazaCapacidadNegativa = false;
        try {
            $service->registrarActivo([
                'cliente_id' => 1,
                'tipo' => 'carga',
                'marca' => 'Schindler',
                'modelo' => '2600',
                'serie' => 'SC-001',
                'capacidad_kg' => -500,
                'paradas' => 5,
                'velocidad' => 1.0,
                'anio' => 2020,
                'ubicacion' => 'Bodega',
            ]);
        } catch (InvalidArgumentException $e) {
            $rechazaCapacidadNegativa = str_contains($e->getMessage(), 'fuera del rango esperado');
        }

        $rechazaCeroParadas = false;
        try {
            $service->registrarActivo([
                'cliente_id' => 1,
                'tipo' => 'carga',
                'marca' => 'Schindler',
                'modelo' => '2600',
                'serie' => 'SC-002',
                'capacidad_kg' => 1500,
                'paradas' => 0,
                'velocidad' => 1.0,
                'anio' => 2020,
                'ubicacion' => 'Bodega',
            ]);
        } catch (InvalidArgumentException $e) {
            $rechazaCeroParadas = str_contains($e->getMessage(), 'fuera del rango esperado');
        }

        if ($rechazaCapacidadNegativa && $rechazaCeroParadas) {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-08.2');
        }
    }

    private function testRegistroActivoSinCliente_CA08_3(): void
    {
        $this->ejecutadas++;
        $service = new ActivoService();

        try {
            $service->registrarActivo([
                'tipo' => 'pasajeros',
                'marca' => 'Kone',
                'modelo' => 'MonoSpace',
                'serie' => 'KN-999',
                'capacidad_kg' => 630,
                'paradas' => 4,
                'velocidad' => 1.0,
                'anio' => 2023,
                'ubicacion' => 'Lobby',
            ]);
            throw new RuntimeException('Debió fallar CA-08.3');
        } catch (InvalidArgumentException $e) {
            if ($e->getMessage() === 'Selecciona el cliente al que pertenece el equipo') {
                $this->pasadas++;
                return;
            }
            throw $e;
        }
    }

    private function testRegistroActivoLimiteDosParadas_CA08_4(): void
    {
        $this->ejecutadas++;
        $service = new ActivoService();

        $activo = $service->registrarActivo([
            'cliente_id' => 2,
            'tipo' => 'montaautos',
            'marca' => 'Hidral',
            'modelo' => 'QHV-3000',
            'serie' => 'HD-2P-01',
            'capacidad_kg' => 3000,
            'paradas' => 2,
            'velocidad' => 0.4,
            'anio' => 2024,
            'ubicacion' => 'Sótano a Planta Baja',
        ]);

        if ($activo['paradas'] === 2 && $activo['sin_advertencia_rango'] === true) {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-08.4');
        }
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $res = (new ActivoServiceTest())->ejecutarTodas();
    echo "ActivoServiceTest (inc2): {$res['pasadas']}/{$res['ejecutadas']} pasadas\n";
    exit($res['pasadas'] === $res['ejecutadas'] ? 0 : 1);
}
