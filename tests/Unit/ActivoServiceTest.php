<?php

declare(strict_types=1);

namespace Tests\Unit;

require_once __DIR__ . '/../../src/Services/ActivoService.php';

use CotizaElev\Services\ActivoService;
use InvalidArgumentException;
use RuntimeException;

/**
 * Suite de pruebas unitarias para ActivoService (M03 - Incremento 5 con RF-15 / HU-09)
 * Trazabilidad: HU-08, HU-09
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
        $this->testHistorialVistaTecnicoSinImportes_CA09_1();
        $this->testHistorialVistaAdminConImportesYFolio_CA09_2();
        $this->testHistorialActivoNuevoSinMovimientos_CA09_3();
        $this->testHistorialActivoInexistenteODadoDeBaja_CA09_4();

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

    private function testHistorialVistaTecnicoSinImportes_CA09_1(): void
    {
        $this->ejecutadas++;
        $service = new ActivoService();

        $activo = $service->registrarActivo([
            'cliente_id' => 1,
            'tipo' => 'pasajeros',
            'marca' => 'Otis',
            'modelo' => 'Gen2',
            'serie' => 'OT-HIST-01',
            'capacidad_kg' => 800,
            'paradas' => 6,
            'velocidad' => 1.6,
            'anio' => 2021,
            'ubicacion' => 'Torre A',
        ]);

        $service->registrarMovimientoHistorial($activo['id'], [
            'tipo_servicio' => 'Mantenimiento preventivo',
            'fecha' => '2026-01-10',
            'estado' => 'Completado',
            'estado_comercial' => 'Aceptada',
            'folio_propuesta' => 'COT-2026-0001',
            'monto' => 4500.00,
        ]);
        $service->registrarMovimientoHistorial($activo['id'], [
            'tipo_servicio' => 'Cambio de tarjetas de control',
            'fecha' => '2026-03-15',
            'estado' => 'En proceso',
            'estado_comercial' => 'Enviada',
            'folio_propuesta' => 'COT-2026-0014',
            'monto' => 18500.00,
        ]);
        $service->registrarMovimientoHistorial($activo['id'], [
            'tipo_servicio' => 'Ajuste de puertas de cabina',
            'fecha' => '2026-02-20',
            'estado' => 'Completado',
            'estado_comercial' => 'Aceptada',
            'folio_propuesta' => 'COT-2026-0008',
            'monto' => 3200.00,
        ]);

        $historialTec = $service->consultarHistorialActivo($activo['id'], 'tecnico');
        $movs = $historialTec['movimientos'];

        $ordenDescendente = ($movs[0]['fecha'] === '2026-03-15')
            && ($movs[1]['fecha'] === '2026-02-20')
            && ($movs[2]['fecha'] === '2026-01-10');

        $sinMontoNiFolio = !array_key_exists('monto', $movs[0])
            && !array_key_exists('folio_propuesta', $movs[0]);

        if (count($movs) === 3 && $ordenDescendente && $sinMontoNiFolio) {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-09.1');
        }
    }

    private function testHistorialVistaAdminConImportesYFolio_CA09_2(): void
    {
        $this->ejecutadas++;
        $service = new ActivoService();

        $activo = $service->registrarActivo([
            'cliente_id' => 1,
            'tipo' => 'pasajeros',
            'marca' => 'Otis',
            'modelo' => 'Gen2',
            'serie' => 'OT-HIST-02',
            'capacidad_kg' => 800,
            'paradas' => 6,
            'velocidad' => 1.6,
            'anio' => 2021,
            'ubicacion' => 'Torre B',
        ]);

        $service->registrarMovimientoHistorial($activo['id'], [
            'tipo_servicio' => 'Modernización de variador',
            'fecha' => '2026-04-02',
            'estado' => 'Completado',
            'estado_comercial' => 'Aceptada',
            'folio_propuesta' => 'COT-2026-0025',
            'monto' => 42000.00,
        ]);

        $historialAdmin = $service->consultarHistorialActivo($activo['id'], 'admin_ventas');
        $primero = $historialAdmin['movimientos'][0];

        if (
            $primero['monto'] === 42000.00
            && $primero['folio_propuesta'] === 'COT-2026-0025'
            && $primero['estado_comercial'] === 'Aceptada'
        ) {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-09.2');
        }
    }

    private function testHistorialActivoNuevoSinMovimientos_CA09_3(): void
    {
        $this->ejecutadas++;
        $service = new ActivoService();

        $activo = $service->registrarActivo([
            'cliente_id' => 1,
            'tipo' => 'pasajeros',
            'marca' => 'Kone',
            'modelo' => 'NMonoSpace',
            'serie' => 'KN-NUEVO-01',
            'capacidad_kg' => 630,
            'paradas' => 5,
            'velocidad' => 1.0,
            'anio' => 2026,
            'ubicacion' => 'Edificio Sur',
        ]);

        $historial = $service->consultarHistorialActivo($activo['id'], 'tecnico');

        if (
            $historial['mensaje'] === 'Este activo todavía no tiene servicios registrados'
            && $historial['movimientos'] === []
        ) {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-09.3');
        }
    }

    private function testHistorialActivoInexistenteODadoDeBaja_CA09_4(): void
    {
        $this->ejecutadas++;
        $service = new ActivoService();

        $activo = $service->registrarActivo([
            'cliente_id' => 1,
            'tipo' => 'carga',
            'marca' => 'Schindler',
            'modelo' => '2600',
            'serie' => 'SC-BAJA-01',
            'capacidad_kg' => 2000,
            'paradas' => 4,
            'velocidad' => 0.8,
            'anio' => 2019,
            'ubicacion' => 'Almacén',
        ]);

        $service->darDeBajaActivo($activo['id']);

        $fallaDadoDeBaja = false;
        try {
            $service->consultarHistorialActivo($activo['id'], 'admin_ventas');
        } catch (RuntimeException $e) {
            $fallaDadoDeBaja = ($e->getMessage() === 'El activo no existe o fue dado de baja');
        }

        $fallaInexistente = false;
        try {
            $service->consultarHistorialActivo(999, 'admin_ventas');
        } catch (RuntimeException $e) {
            $fallaInexistente = ($e->getMessage() === 'El activo no existe o fue dado de baja');
        }

        if ($fallaDadoDeBaja && $fallaInexistente) {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-09.4');
        }
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $res = (new ActivoServiceTest())->ejecutarTodas();
    echo "ActivoServiceTest (inc5): {$res['pasadas']}/{$res['ejecutadas']} pasadas\n";
    exit($res['pasadas'] === $res['ejecutadas'] ? 0 : 1);
}
