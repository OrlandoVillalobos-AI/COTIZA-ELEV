<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Services/CalculoMotorService.php';

use CotizaElev\Services\CalculoMotorService;

class CalculoMotorServiceTest
{
    private CalculoMotorService $motor;
    private int $pruebasEjecutadas = 0;
    private int $pruebasPasadas = 0;

    public function __construct()
    {
        $this->motor = new CalculoMotorService();
    }

    public function ejecutarTodas(): void
    {
        echo "========================================================\n";
        echo " EJECUCIÓN DE PRUEBAS UNITARIAS: CalculoMotorService (M07)\n";
        echo " Entorno: PHP " . PHP_VERSION . " en Windows\n";
        echo "========================================================\n\n";

        $this->testCasoNormal_CA16_1();
        $this->testCasoLimiteCentavos_CA16_2();
        $this->testCasoErrorPrecioNegativo_CA16_3();
        $this->testCasoErrorCantidadCero_CA16_3();

        echo "\n--------------------------------------------------------\n";
        echo "RESULTADO FINAL: {$this->pruebasPasadas} de {$this->pruebasEjecutadas} pruebas pasaron exitosamente.\n";
        echo ($this->pruebasPasadas === $this->pruebasEjecutadas) ? "ESTADO: APROBADO [100% SUCCESS]\n" : "ESTADO: FALLIDO\n";
        echo "========================================================\n";
    }

    /**
     * CA-16.1: Caso Normal
     * 2 partidas ($1,000 c/u) + 1 mano de obra ($500)
     * Subtotal esperado: $2,500.00
     * IVA 16% esperado: $400.00
     * Total esperado: $2,900.00
     */
    public function testCasoNormal_CA16_1(): void
    {
        $this->pruebasEjecutadas++;
        echo "[PRUEBA 1 - NORMAL / CA-16.1] Cálculo estándar de partidas comerciales:\n";

        $partidas = [
            ['descripcion' => 'Sensor de límite electromecánico', 'cantidad' => 2, 'precio_unitario' => 1000.00],
            ['descripcion' => 'Servicio de mano de obra técnica', 'cantidad' => 1, 'precio_unitario' => 500.00]
        ];

        $resultado = $this->motor->calcularTotales($partidas, 0.16);

        $esperadoSubtotal = 2500.00;
        $esperadoIva = 400.00;
        $esperadoTotal = 2900.00;

        echo "  - Entradas: 2 partidas x $1000.00 + 1 mano de obra x $500.00\n";
        echo "  - Subtotal obtenido: \${$resultado['subtotal']} (Esperado: \${$esperadoSubtotal})\n";
        echo "  - IVA 16% obtenido:  \${$resultado['monto_iva']} (Esperado: \${$esperadoIva})\n";
        echo "  - Total obtenido:    \${$resultado['total']} (Esperado: \${$esperadoTotal})\n";

        if ($resultado['subtotal'] === $esperadoSubtotal &&
            $resultado['monto_iva'] === $esperadoIva &&
            $resultado['total'] === $esperadoTotal) {
            echo "  -> ESTADO: PASA [OK]\n\n";
            $this->pruebasPasadas++;
        } else {
            echo "  -> ESTADO: FALLA [ERROR EN ASERCIÓN]\n\n";
        }
    }

    /**
     * CA-16.2: Caso Límite
     * 3 refacciones a $333.33 c/u ($999.99 total)
     * IVA 16% esperado: $160.00 exacto ($999.99 * 0.16 = 159.9984 -> redondeo a 160.00)
     * Total esperado: $1,159.99
     */
    public function testCasoLimiteCentavos_CA16_2(): void
    {
        $this->pruebasEjecutadas++;
        echo "[PRUEBA 2 - LÍMITE / CA-16.2] Precisión de centavos y redondeo financiero:\n";

        $partidas = [
            ['descripcion' => 'Juego de patines de freno', 'cantidad' => 3, 'precio_unitario' => 333.33]
        ];

        $resultado = $this->motor->calcularTotales($partidas, 0.16);

        $esperadoSubtotal = 999.99;
        $esperadoIva = 160.00;
        $esperadoTotal = 1159.99;

        echo "  - Entradas: 3 refacciones x $333.33\n";
        echo "  - Subtotal obtenido: \${$resultado['subtotal']} (Esperado: \${$esperadoSubtotal})\n";
        echo "  - IVA 16% obtenido:  \${$resultado['monto_iva']} (Esperado: \${$esperadoIva})\n";
        echo "  - Total obtenido:    \${$resultado['total']} (Esperado: \${$esperadoTotal})\n";

        if ($resultado['subtotal'] === $esperadoSubtotal &&
            $resultado['monto_iva'] === $esperadoIva &&
            $resultado['total'] === $esperadoTotal) {
            echo "  -> ESTADO: PASA [OK]\n\n";
            $this->pruebasPasadas++;
        } else {
            echo "  -> ESTADO: FALLA [ERROR EN REDONDEO]\n\n";
        }
    }

    /**
     * CA-16.3: Caso Error 1
     * Precio unitario negativo (-500.00)
     * Debe lanzar InvalidArgumentException
     */
    public function testCasoErrorPrecioNegativo_CA16_3(): void
    {
        $this->pruebasEjecutadas++;
        echo "[PRUEBA 3 - ERROR / CA-16.3] Rechazo de precio unitario negativo:\n";

        $partidas = [
            ['descripcion' => 'Refacción con precio erróneo', 'cantidad' => 1, 'precio_unitario' => -500.00]
        ];

        try {
            $this->motor->calcularTotales($partidas, 0.16);
            echo "  -> ESTADO: FALLA [No lanzó excepción]\n\n";
        } catch (\InvalidArgumentException $e) {
            echo "  - Excepción capturada correctamente: " . $e->getMessage() . "\n";
            echo "  -> ESTADO: PASA [OK]\n\n";
            $this->pruebasPasadas++;
        }
    }

    /**
     * CA-16.3: Caso Error 2
     * Cantidad en cero o negativa (0)
     * Debe lanzar InvalidArgumentException
     */
    public function testCasoErrorCantidadCero_CA16_3(): void
    {
        $this->pruebasEjecutadas++;
        echo "[PRUEBA 4 - ERROR / CA-16.3] Rechazo de cantidad en cero:\n";

        try {
            $this->motor->calcularImportePartida(0, 250.00);
            echo "  -> ESTADO: FALLA [No lanzó excepción]\n\n";
        } catch (\InvalidArgumentException $e) {
            echo "  - Excepción capturada correctamente: " . $e->getMessage() . "\n";
            echo "  -> ESTADO: PASA [OK]\n\n";
            $this->pruebasPasadas++;
        }
    }
}

// Ejecución directa
$testSuite = new CalculoMotorServiceTest();
$testSuite->ejecutarTodas();
