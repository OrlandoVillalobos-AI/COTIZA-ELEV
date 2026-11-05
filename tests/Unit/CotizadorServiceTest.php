<?php

declare(strict_types=1);

namespace Tests\Unit;

require_once __DIR__ . '/../../src/Services/CalculoMotorService.php';
require_once __DIR__ . '/../../src/Services/SolicitudService.php';
require_once __DIR__ . '/../../src/Services/CotizadorService.php';

use CotizaElev\Services\CalculoMotorService;
use CotizaElev\Services\CotizadorService;
use CotizaElev\Services\SolicitudService;
use RuntimeException;

/**
 * Suite de pruebas unitarias para CotizadorService (M06 - Incremento 3)
 * Trazabilidad: RF-25, RF-26, RF-31, RF-32, RF-33 / HU-14, HU-15, HU-19
 */
class CotizadorServiceTest
{
    private int $ejecutadas = 0;
    private int $pasadas = 0;

    /**
     * @return array{ejecutadas: int, pasadas: int}
     */
    public function ejecutarTodas(): array
    {
        $this->testConvertirSolicitudEnCotizacion_CA14_3();
        $this->testErrorActivoNoPerteneceAlCliente_CA15_2();
        $this->testRecalculoInmediatoAlModificarPartidas_CA19_1();
        $this->testCicloEstadosYVencimientoAutomatico_CA19_2();
        $this->testBorradorIncompletoBloqueaEnvioYPermiteRecuperar_CA19_3();

        return [
            'ejecutadas' => $this->ejecutadas,
            'pasadas' => $this->pasadas,
        ];
    }

    /**
     * CA-14.3: Conversión de solicitud en cotización precarga cliente y activo y pasa solicitud a 'Cotizada'
     */
    public function testConvertirSolicitudEnCotizacion_CA14_3(): void
    {
        $this->ejecutadas++;
        $solicitudService = new SolicitudService();
        $motor = new CalculoMotorService();
        $cotizador = new CotizadorService($motor, $solicitudService);

        $sol = $solicitudService->crearSolicitud([
            'cliente_id' => 20,
            'activo_id' => 55,
            'pieza_o_servicio' => 'Bobina de freno 110V',
            'cantidad' => 2,
            'urgencia' => 'Alta',
            'tecnico_correo' => 'tecnico@elevese.mx',
        ]);

        $cot = $cotizador->convertirSolicitudEnCotizacion(
            $sol['id'],
            $sol,
            'admin@elevese.mx',
            [['descripcion' => 'Bobina de freno 110V', 'cantidad' => 2, 'precio_unitario' => 3200.00]]
        );

        $detalleSol = $solicitudService->obtenerDetalleSolicitudParaTecnico($sol['id'], 'tecnico@elevese.mx');

        if (
            $cot['cliente_id'] === 20 &&
            $cot['activo_id'] === 55 &&
            $cot['estado'] === 'borrador' &&
            $detalleSol['estado'] === 'Cotizada'
        ) {
            $this->pasadas++;
        }
    }

    /**
     * CA-15.2: Error cuando el activo seleccionado no pertenece al cliente
     */
    public function testErrorActivoNoPerteneceAlCliente_CA15_2(): void
    {
        $this->ejecutadas++;
        $cotizador = new CotizadorService(new CalculoMotorService());

        try {
            $cotizador->crearCotizacion([
                'cliente_id' => 10,
                'activo_id' => 99,
                'activo_cliente_id' => 25,
                'tipo' => 'equipo_nuevo',
                'partidas' => [
                    ['descripcion' => 'Elevador 8 paradas', 'cantidad' => 1, 'precio_unitario' => 750000.00],
                ],
            ]);
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'El activo seleccionado no pertenece al cliente') {
                $this->pasadas++;
            }
        }
    }

    /**
     * CA-19.1: Al cambiar cantidad de 2 a 5 piezas a $1,200.00, el importe sube de $2,400.00 a $6,000.00 y recalcula IVA/total
     */
    public function testRecalculoInmediatoAlModificarPartidas_CA19_1(): void
    {
        $this->ejecutadas++;
        $cotizador = new CotizadorService(new CalculoMotorService());

        $cot = $cotizador->crearCotizacion([
            'cliente_id' => 10,
            'activo_id' => 12,
            'tipo' => 'correctivo',
            'partidas' => [
                ['descripcion' => 'Rodamiento guía superior', 'cantidad' => 2, 'precio_unitario' => 1200.00],
            ],
        ]);

        $antesSubtotal = $cot['subtotal'];
        $actualizada = $cotizador->modificarPartidas($cot['id'], [
            ['descripcion' => 'Rodamiento guía superior', 'cantidad' => 5, 'precio_unitario' => 1200.00],
        ]);

        if (
            $antesSubtotal === 2400.00 &&
            $actualizada['subtotal'] === 6000.00 &&
            $actualizada['monto_iva'] === 960.00 &&
            $actualizada['total'] === 6960.00
        ) {
            $this->pasadas++;
        }
    }

    /**
     * CA-19.2: Paso de borrador a enviada y transición automática a vencida tras superar los 15 días de vigencia
     */
    public function testCicloEstadosYVencimientoAutomatico_CA19_2(): void
    {
        $this->ejecutadas++;
        $cotizador = new CotizadorService(new CalculoMotorService());

        $cot = $cotizador->crearCotizacion([
            'cliente_id' => 10,
            'activo_id' => 12,
            'tipo' => 'preventivo',
            'dias_vigencia' => 15,
            'partidas' => [
                ['descripcion' => 'Póliza anual mantenimiento', 'cantidad' => 12, 'precio_unitario' => 2500.00],
            ],
        ]);

        $enviada = $cotizador->cambiarEstadoCotizacion($cot['id'], 'enviada', 5);
        $vencida = $cotizador->cambiarEstadoCotizacion($cot['id'], 'enviada', 16);

        if (
            $enviada['estado'] === 'enviada' &&
            $vencida['estado'] === 'vencida' &&
            $vencida['total'] === 34800.00
        ) {
            $this->pasadas++;
        }
    }

    /**
     * CA-19.3: Borrador guardado con datos incompletos bloquea su envío pero permite recuperarlo y completarlo después
     */
    public function testBorradorIncompletoBloqueaEnvioYPermiteRecuperar_CA19_3(): void
    {
        $this->ejecutadas++;
        $cotizador = new CotizadorService(new CalculoMotorService());

        $borrador = $cotizador->crearCotizacion([
            'cliente_id' => 10,
            'activo_id' => 12,
            'tipo' => 'modernizacion',
            'datos_cliente_completos' => false,
            'partidas' => [
                ['descripcion' => 'Tablero de control VVVF', 'cantidad' => 1, 'precio_unitario' => 95000.00],
            ],
        ]);

        $envioBloqueado = false;
        try {
            $cotizador->cambiarEstadoCotizacion($borrador['id'], 'enviada');
        } catch (RuntimeException) {
            $envioBloqueado = true;
        }

        $recuperado = $cotizador->obtenerCotizacion($borrador['id']);
        $cotizador->completarBorrador($borrador['id'], ['contacto_cliente' => 'compras@cliente.mx']);
        $enviadaOk = $cotizador->cambiarEstadoCotizacion($borrador['id'], 'enviada');

        if (
            $borrador['estado'] === 'borrador' &&
            $borrador['incompleta'] === true &&
            $envioBloqueado &&
            $recuperado['id'] === $borrador['id'] &&
            $enviadaOk['estado'] === 'enviada'
        ) {
            $this->pasadas++;
        }
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $res = (new CotizadorServiceTest())->ejecutarTodas();
    echo "CotizadorServiceTest (Inc3): {$res['pasadas']}/{$res['ejecutadas']} pasadas.\n";
    exit($res['pasadas'] === $res['ejecutadas'] ? 0 : 1);
}
