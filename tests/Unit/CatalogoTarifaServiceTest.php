<?php

declare(strict_types=1);

namespace Tests\Unit;

require_once __DIR__ . '/../../src/Services/CatalogoTarifaService.php';
if (file_exists(__DIR__ . '/../../src/Services/CalculoMotorService.php')) {
    require_once __DIR__ . '/../../src/Services/CalculoMotorService.php';
} elseif (file_exists('/home/orlax1s/Documents/COTIZA-ELEV/src/Services/CalculoMotorService.php')) {
    require_once '/home/orlax1s/Documents/COTIZA-ELEV/src/Services/CalculoMotorService.php';
}

use CotizaElev\Services\CalculoMotorService;
use CotizaElev\Services\CatalogoTarifaService;
use InvalidArgumentException;
use RuntimeException;

/**
 * Suite de pruebas unitarias para CatalogoTarifaService (M04 - Incremento 2)
 * Trazabilidad: HU-10, HU-11 e integración con M07 (CalculoMotorService)
 */
class CatalogoTarifaServiceTest
{
    private int $ejecutadas = 0;
    private int $pasadas = 0;

    public function ejecutarTodas(): array
    {
        $this->ejecutadas = 0;
        $this->pasadas = 0;

        $this->testAltaServicioEIntegracionCalculoMotor_CA10_1();
        $this->testAltaConceptoPrecioCeroONegativo_CA10_2();
        $this->testVigenciaTarifaFuturaVsActual_CA10_3();
        $this->testImportacionCsvMixta_CA11_1();
        $this->testImportacionCsvSinColumnaPrecio_CA11_2();
        $this->testImportacionCsvVacioOSoloEncabezados_CA11_3();

        return [
            'ejecutadas' => $this->ejecutadas,
            'pasadas' => $this->pasadas,
        ];
    }

    private function testAltaServicioEIntegracionCalculoMotor_CA10_1(): void
    {
        $this->ejecutadas++;
        $catalogo = new CatalogoTarifaService();
        $motor = new CalculoMotorService();

        $catalogo->registrarConcepto([
            'clave' => 'SRV-MP-8P',
            'descripcion' => 'Mantenimiento preventivo mensual 8 paradas',
            'unidad' => 'servicio',
            'precio_unitario' => 4500.00,
            'vigencia_inicio' => '2026-01-01',
            'vigencia_fin' => '2026-12-31',
        ]);

        $vigente = $catalogo->obtenerPrecioVigente('SRV-MP-8P', '2026-05-15');
        $totales = $motor->calcularTotales([
            [
                'descripcion' => $vigente['descripcion'],
                'cantidad' => 2,
                'precio_unitario' => $vigente['precio_unitario'],
            ],
        ]);

        $vistaTecnico = $catalogo->listarParaRol('tecnico');
        $sinPrecioParaTecnico = !array_key_exists('precio_unitario', $vistaTecnico[0]);

        if (
            $vigente['precio_unitario'] === 4500.00
            && $totales['subtotal'] === 9000.00
            && $totales['total'] === 10440.00
            && $sinPrecioParaTecnico
        ) {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-10.1');
        }
    }

    private function testAltaConceptoPrecioCeroONegativo_CA10_2(): void
    {
        $this->ejecutadas++;
        $catalogo = new CatalogoTarifaService();

        $rechazaCero = false;
        try {
            $catalogo->registrarConcepto([
                'clave' => 'REF-01',
                'descripcion' => 'Sensor óptico',
                'unidad' => 'pza',
                'precio_unitario' => 0,
                'vigencia_inicio' => '2026-01-01',
                'vigencia_fin' => '2026-12-31',
            ]);
        } catch (InvalidArgumentException $e) {
            $rechazaCero = ($e->getMessage() === 'El precio debe ser mayor que cero');
        }

        $rechazaNegativo = false;
        try {
            $catalogo->registrarConcepto([
                'clave' => 'REF-02',
                'descripcion' => 'Zapata guía',
                'unidad' => 'pza',
                'precio_unitario' => -150,
                'vigencia_inicio' => '2026-01-01',
                'vigencia_fin' => '2026-12-31',
            ]);
        } catch (InvalidArgumentException $e) {
            $rechazaNegativo = ($e->getMessage() === 'El precio debe ser mayor que cero');
        }

        if ($rechazaCero && $rechazaNegativo) {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-10.2');
        }
    }

    private function testVigenciaTarifaFuturaVsActual_CA10_3(): void
    {
        $this->ejecutadas++;
        $catalogo = new CatalogoTarifaService();

        // Tarifa vigente en octubre 2026
        $catalogo->registrarConcepto([
            'clave' => 'REF-CABLE',
            'descripcion' => 'Cable de tracción 12mm',
            'unidad' => 'metro',
            'precio_unitario' => 280.00,
            'vigencia_inicio' => '2026-01-01',
            'vigencia_fin' => '2026-10-31',
        ]);

        // Tarifa futura a partir del mes siguiente (noviembre 2026)
        $catalogo->registrarConcepto([
            'clave' => 'REF-CABLE',
            'descripcion' => 'Cable de tracción 12mm',
            'unidad' => 'metro',
            'precio_unitario' => 320.00,
            'vigencia_inicio' => '2026-11-01',
            'vigencia_fin' => '2026-12-31',
        ]);

        $precioHoy = $catalogo->obtenerPrecioVigente('REF-CABLE', '2026-10-15');
        $precioMesSiguiente = $catalogo->obtenerPrecioVigente('REF-CABLE', '2026-11-05');

        if ($precioHoy['precio_unitario'] === 280.00 && $precioMesSiguiente['precio_unitario'] === 320.00) {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-10.3');
        }
    }

    private function testImportacionCsvMixta_CA11_1(): void
    {
        $this->ejecutadas++;
        $catalogo = new CatalogoTarifaService();

        $filas = ["clave,precio,descripcion,unidad"];
        for ($i = 1; $i <= 48; $i++) {
            $filas[] = sprintf("REF-%03d,%.2f,Refaccion %d,pza", $i, $i * 100.0, $i);
        }
        // 2 filas con precio inválido (texto)
        $filas[] = "REF-049,PRECIO_TEXTO,Refaccion Erronea 1,pza";
        $filas[] = "REF-050,-99.50,Refaccion Erronea 2,pza";

        $resultado = $catalogo->importarCsv(implode("\n", $filas));

        if (
            $resultado['actualizados'] === 48
            && $resultado['rechazados'] === 2
            && $resultado['mensaje'] === '48 registros actualizados y 2 rechazados'
        ) {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-11.1');
        }
    }

    private function testImportacionCsvSinColumnaPrecio_CA11_2(): void
    {
        $this->ejecutadas++;
        $catalogo = new CatalogoTarifaService();
        $csv = "clave,descripcion,unidad\nREF-001,Botonera de piso,pza";

        try {
            $catalogo->importarCsv($csv);
            throw new RuntimeException('Debió fallar CA-11.2');
        } catch (InvalidArgumentException $e) {
            if ($e->getMessage() === 'Falta la columna obligatoria: precio') {
                $this->pasadas++;
                return;
            }
            throw $e;
        }
    }

    private function testImportacionCsvVacioOSoloEncabezados_CA11_3(): void
    {
        $this->ejecutadas++;
        $catalogo = new CatalogoTarifaService();

        try {
            $catalogo->importarCsv("clave,precio,descripcion\n");
            throw new RuntimeException('Debió fallar CA-11.3');
        } catch (InvalidArgumentException $e) {
            if ($e->getMessage() === 'El archivo no contiene registros para procesar') {
                $this->pasadas++;
                return;
            }
            throw $e;
        }
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $res = (new CatalogoTarifaServiceTest())->ejecutarTodas();
    echo "CatalogoTarifaServiceTest (inc2): {$res['pasadas']}/{$res['ejecutadas']} pasadas\n";
    exit($res['pasadas'] === $res['ejecutadas'] ? 0 : 1);
}
