<?php

declare(strict_types=1);

namespace Tests\Unit;

require_once __DIR__ . '/../../src/Services/CalculoMotorService.php';

use CotizaElev\Services\CalculoMotorService;
use InvalidArgumentException;
use RuntimeException;

/**
 * Suite de pruebas unitarias para CalculoMotorService (M07 extendido)
 * Trazabilidad: RF-27, RF-28, RF-29, RF-30 / HU-15, HU-16, HU-17, HU-18
 */
class CalculoMotorServiceTest
{
    private CalculoMotorService $motor;
    private int $ejecutadas = 0;
    private int $pasadas = 0;

    public function __construct()
    {
        $this->motor = new CalculoMotorService();
    }

    public function setUp(): void
    {
        $this->motor = new CalculoMotorService();
    }

    /**
     * @return array{ejecutadas: int, pasadas: int}
     */
    public function ejecutarTodas(): array
    {
        $this->setUp();

        // Pruebas originales de Incremento 1
        $this->testCalculoTotalesNormal();
        $this->testCalculoLimiteRedondeo();
        $this->testLanzamientoExcepcionEnPrecioNegativo();

        // RF-27 / HU-15: Equipo nuevo y modernización
        $this->testEquipoNuevoCatalogoPrecioBloqueado_CA15_1();
        $this->testEquipoCombinacionInexistente_CA15_3();

        // RF-28 / HU-16: Descuentos e IVA
        $this->testCalculoConDescuentoNormal_CA16_1();
        $this->testDescuentoMayorQueSubtotal_CA16_2();
        $this->testDescuentoCienPorCientoConConfirmacion_CA16_3();

        // RF-29 / HU-17: Pólizas preventivas
        $this->testPolizaPreventivaMensualNormal_CA17_1();
        $this->testPolizaPreventivaCeroVisitasError_CA17_2();
        $this->testPolizaPreventivaLimitePeriodicidad_CA17_3();

        // RF-30 / HU-18: Mantenimiento correctivo
        $this->testMantenimientoCorrectivoNormal_CA18_1();
        $this->testMantenimientoCorrectivoSinDiagnostico_CA18_2();
        $this->testMantenimientoCorrectivoFraccionMediaHora_CA18_3();

        return [
            'ejecutadas' => $this->ejecutadas,
            'pasadas' => $this->pasadas,
        ];
    }

    public function testCalculoTotalesNormal(): void
    {
        $this->ejecutadas++;
        $partidas = [
            ['descripcion' => 'Sensor de límite', 'cantidad' => 2, 'precio_unitario' => 1000.00],
            ['descripcion' => 'Mano de obra', 'cantidad' => 1, 'precio_unitario' => 500.00]
        ];

        $resultado = $this->motor->calcularTotales($partidas, 0.16);

        assert($resultado['subtotal'] === 2500.00, "Subtotal debe ser 2500.00");
        assert($resultado['monto_iva'] === 400.00, "IVA debe ser 400.00");
        assert($resultado['total'] === 2900.00, "Total debe ser 2900.00");
        $this->pasadas++;
    }

    public function testCalculoLimiteRedondeo(): void
    {
        $this->ejecutadas++;
        $partidas = [
            ['descripcion' => 'Refacción X', 'cantidad' => 3, 'precio_unitario' => 333.33]
        ];

        $resultado = $this->motor->calcularTotales($partidas, 0.16);

        assert($resultado['subtotal'] === 999.99, "Subtotal debe ser 999.99");
        assert($resultado['monto_iva'] === 160.00, "IVA debe ser 160.00 con redondeo half-up");
        assert($resultado['total'] === 1159.99, "Total debe ser 1159.99");
        $this->pasadas++;
    }

    public function testLanzamientoExcepcionEnPrecioNegativo(): void
    {
        $this->ejecutadas++;
        $partidas = [
            ['descripcion' => 'Invalido', 'cantidad' => 1, 'precio_unitario' => -500.00]
        ];

        try {
            $this->motor->calcularTotales($partidas);
            assert(false, "Debió lanzar InvalidArgumentException");
        } catch (InvalidArgumentException $e) {
            assert(str_contains($e->getMessage(), 'negativo'));
            $this->pasadas++;
        }
    }

    /**
     * CA-15.1: Cálculo de equipo nuevo por capacidad/paradas con precio bloqueado
     */
    public function testEquipoNuevoCatalogoPrecioBloqueado_CA15_1(): void
    {
        $this->ejecutadas++;
        $catalogo = [
            'equipo_nuevo:630:8' => ['precio' => 850000.00, 'descripcion' => 'Elevador Pasajeros 630kg 8 paradas'],
        ];

        $partida = $this->motor->calcularPartidaEquipoOModernizacion('equipo_nuevo', 630, 8, $catalogo);

        if ($partida['importe'] === 850000.00 && $partida['precio_bloqueado'] === true) {
            $this->pasadas++;
        }
    }

    /**
     * CA-15.3: Combinación inexistente en catálogo lanza excepción clara
     */
    public function testEquipoCombinacionInexistente_CA15_3(): void
    {
        $this->ejecutadas++;
        $catalogo = [
            'equipo_nuevo:630:8' => 850000.00,
        ];

        try {
            $this->motor->calcularPartidaEquipoOModernizacion('equipo_nuevo', 1000, 15, $catalogo);
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'No hay precio para esa combinación; selecciona otro modelo o solicita el alta de la tarifa') {
                $this->pasadas++;
            }
        }
    }

    /**
     * CA-16.1: Subtotal $50,000, descuento $5,000 -> base $45,000, IVA $7,200, total $52,200
     */
    public function testCalculoConDescuentoNormal_CA16_1(): void
    {
        $this->ejecutadas++;
        $partidas = [
            ['descripcion' => 'Modernización de cuadro de maniobra', 'cantidad' => 1, 'precio_unitario' => 50000.00],
        ];

        $res = $this->motor->calcularTotalesConDescuento($partidas, 5000.00, 0.16);

        if (
            $res['subtotal'] === 50000.00 &&
            $res['base_gravable'] === 45000.00 &&
            $res['monto_iva'] === 7200.00 &&
            $res['total'] === 52200.00
        ) {
            $this->pasadas++;
        }
    }

    /**
     * CA-16.2: Descuento mayor que el subtotal es rechazado
     */
    public function testDescuentoMayorQueSubtotal_CA16_2(): void
    {
        $this->ejecutadas++;
        $partidas = [
            ['descripcion' => 'Servicio técnico', 'cantidad' => 1, 'precio_unitario' => 10000.00],
        ];

        try {
            $this->motor->calcularTotalesConDescuento($partidas, 12000.00, 0.16);
        } catch (InvalidArgumentException $e) {
            if ($e->getMessage() === 'El descuento no puede ser mayor que el subtotal') {
                $this->pasadas++;
            }
        }
    }

    /**
     * CA-16.3: Descuento del 100% requiere confirmación y deja total $0.00 e IVA $0.00
     */
    public function testDescuentoCienPorCientoConConfirmacion_CA16_3(): void
    {
        $this->ejecutadas++;
        $partidas = [
            ['descripcion' => 'Servicio en garantía comercial', 'cantidad' => 1, 'precio_unitario' => 8000.00],
        ];

        $exigioConfirmacion = false;
        try {
            $this->motor->calcularTotalesConDescuento($partidas, 8000.00, 0.16, false);
        } catch (RuntimeException) {
            $exigioConfirmacion = true;
        }

        $res = $this->motor->calcularTotalesConDescuento($partidas, 8000.00, 0.16, true);

        if ($exigioConfirmacion && $res['monto_iva'] === 0.00 && $res['total'] === 0.00) {
            $this->pasadas++;
        }
    }

    /**
     * CA-17.1: Póliza mensual con 12 visitas a $2,500 -> costo anual $30,000.00
     */
    public function testPolizaPreventivaMensualNormal_CA17_1(): void
    {
        $this->ejecutadas++;
        $res = $this->motor->calcularPolizaPreventiva('mensual', 12, 2500.00);

        if ($res['costo_anual'] === 30000.00 && $res['coincide_periodicidad'] === true) {
            $this->pasadas++;
        }
    }

    /**
     * CA-17.2: Póliza con 0 visitas anuales lanza error
     */
    public function testPolizaPreventivaCeroVisitasError_CA17_2(): void
    {
        $this->ejecutadas++;
        try {
            $this->motor->calcularPolizaPreventiva('mensual', 0, 2500.00);
        } catch (InvalidArgumentException $e) {
            if ($e->getMessage() === 'El número de visitas debe ser al menos 1') {
                $this->pasadas++;
            }
        }
    }

    /**
     * CA-17.3: Póliza límite de 1 visita anual y aviso cuando difiere de la periodicidad
     */
    public function testPolizaPreventivaLimitePeriodicidad_CA17_3(): void
    {
        $this->ejecutadas++;
        $anual = $this->motor->calcularPolizaPreventiva('anual', 1, 4500.00);
        $desfasada = $this->motor->calcularPolizaPreventiva('mensual', 6, 2500.00);

        if (
            $anual['costo_anual'] === 4500.00 &&
            $anual['coincide_periodicidad'] === true &&
            $desfasada['coincide_periodicidad'] === false &&
            !empty($desfasada['aviso_periodicidad'])
        ) {
            $this->pasadas++;
        }
    }

    /**
     * CA-18.1: Mantenimiento correctivo normal con refacciones y mano de obra
     */
    public function testMantenimientoCorrectivoNormal_CA18_1(): void
    {
        $this->ejecutadas++;
        $res = $this->motor->calcularMantenimientoCorrectivo(
            'Reemplazo de tarjeta inversora de puerta y ajuste mecánico',
            [['descripcion' => 'Tarjeta VF puerta', 'cantidad' => 1, 'precio_unitario' => 6000.00]],
            3.0,
            800.00
        );

        if ($res['subtotal'] === 8400.00 && $res['importe_mano_obra'] === 2400.00) {
            $this->pasadas++;
        }
    }

    /**
     * CA-18.2: Mantenimiento correctivo sin diagnóstico o sin partidas lanza error
     */
    public function testMantenimientoCorrectivoSinDiagnostico_CA18_2(): void
    {
        $this->ejecutadas++;
        try {
            $this->motor->calcularMantenimientoCorrectivo('', [], 0.0, 800.00);
        } catch (InvalidArgumentException $e) {
            if ($e->getMessage() === 'El mantenimiento correctivo requiere un diagnóstico y al menos una partida') {
                $this->pasadas++;
            }
        }
    }

    /**
     * CA-18.3: Mantenimiento correctivo con fracción de 0.5 horas de mano de obra
     */
    public function testMantenimientoCorrectivoFraccionMediaHora_CA18_3(): void
    {
        $this->ejecutadas++;
        $res = $this->motor->calcularMantenimientoCorrectivo(
            'Rearme y calibración de sensor de cortina óptica',
            [],
            0.5,
            900.00
        );

        if ($res['importe_mano_obra'] === 450.00 && $res['subtotal'] === 450.00) {
            $this->pasadas++;
        }
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $res = (new CalculoMotorServiceTest())->ejecutarTodas();
    echo "CalculoMotorServiceTest: {$res['pasadas']}/{$res['ejecutadas']} pasadas.\n";
    exit($res['pasadas'] === $res['ejecutadas'] ? 0 : 1);
}
