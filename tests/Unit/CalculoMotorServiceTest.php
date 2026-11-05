<?php

declare(strict_types=1);

namespace Tests\Unit;

use CotizaElev\Services\CalculoMotorService;
use InvalidArgumentException;

/**
 * Suite de pruebas unitarias para CalculoMotorService (M07)
 * Trazabilidad: HU-16 / Criterios CA-16.1, CA-16.2 y CA-16.3
 */
class CalculoMotorServiceTest
{
    private CalculoMotorService $motor;

    public function setUp(): void
    {
        $this->motor = new CalculoMotorService();
    }

    /**
     * CA-16.1: Caso Normal
     */
    public function testCalculoTotalesNormal(): void
    {
        $partidas = [
            ['descripcion' => 'Sensor de límite', 'cantidad' => 2, 'precio_unitario' => 1000.00],
            ['descripcion' => 'Mano de obra', 'cantidad' => 1, 'precio_unitario' => 500.00]
        ];

        $resultado = $this->motor->calcularTotales($partidas, 0.16);

        assert($resultado['subtotal'] === 2500.00, "Subtotal debe ser 2500.00");
        assert($resultado['monto_iva'] === 400.00, "IVA debe ser 400.00");
        assert($resultado['total'] === 2900.00, "Total debe ser 2900.00");
    }

    /**
     * CA-16.2: Caso Límite / Redondeo de centavos
     */
    public function testCalculoLimiteRedondeo(): void
    {
        $partidas = [
            ['descripcion' => 'Refacción X', 'cantidad' => 3, 'precio_unitario' => 333.33]
        ];

        $resultado = $this->motor->calcularTotales($partidas, 0.16);

        assert($resultado['subtotal'] === 999.99, "Subtotal debe ser 999.99");
        assert($resultado['monto_iva'] === 160.00, "IVA debe ser 160.00 con redondeo half-up");
        assert($resultado['total'] === 1159.99, "Total debe ser 1159.99");
    }

    /**
     * CA-16.3: Caso Error
     */
    public function testLanzamientoExcepcionEnPrecioNegativo(): void
    {
        $partidas = [
            ['descripcion' => 'Invalido', 'cantidad' => 1, 'precio_unitario' => -500.00]
        ];

        try {
            $this->motor->calcularTotales($partidas);
            assert(false, "Debió lanzar InvalidArgumentException");
        } catch (InvalidArgumentException $e) {
            assert(str_contains($e->getMessage(), 'negativo'));
        }
    }
}
