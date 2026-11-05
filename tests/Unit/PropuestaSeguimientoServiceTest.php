<?php

declare(strict_types=1);

namespace Tests\Unit;

use CotizaElev\Services\PropuestaSeguimientoService;
use InvalidArgumentException;
use RuntimeException;

require_once __DIR__ . '/../../src/Services/PropuestaSeguimientoService.php';

final class PropuestaSeguimientoServiceTest
{
    public function ejecutarTodas(): array { return $this->run(); }

    public function run(): array
    {
        $ejecutadas = 0;
        $pasadas = 0;

        // CA-21.1: Normal - Generación de propuesta PDF con todos los elementos corporativos
        $ejecutadas++;
        $service = new PropuestaSeguimientoService();
        $cotizacion = [
            'partidas' => [
                ['concepto' => 'Mantenimiento preventivo elevador 8 paradas', 'cantidad' => 1, 'importe' => 5500.00],
                ['concepto' => 'Ajuste de puertas de cabina', 'cantidad' => 1, 'importe' => 1500.00],
            ],
            'subtotal' => 7000.00,
            'iva' => 1120.00,
            'total' => 8120.00,
            'vigencia' => '30 días naturales',
            'condiciones_comerciales' => 'Pago 50% anticipo y 50% al finalizar.',
        ];
        $cliente = [
            'razon_social' => 'Hospital Ángeles del Bajío S.A. de C.V.',
            'rfc' => 'HAB990101AAA',
            'domicilio_fiscal' => 'Blvd. Aeropuerto 101, León, Gto.',
            'correo' => 'compras@hospitalbajio.mx',
        ];
        $pdf = $service->generarPropuestaPdf($cotizacion, $cliente, 2026, 'COT');
        assert($pdf['folio'] === 'COT-2026-0001');
        assert(!empty($pdf['logo_corporativo']));
        assert($pdf['datos_fiscales_cliente']['rfc'] === 'HAB990101AAA');
        assert(count($pdf['partidas']) === 2);
        assert($pdf['subtotal'] === 7000.00);
        assert($pdf['iva'] === 1120.00);
        assert($pdf['total'] === 8120.00);
        assert($pdf['vigencia'] === '30 días naturales');
        assert(!empty($pdf['condiciones_comerciales']));
        assert(!empty($pdf['espacio_firma']));
        $pasadas++;

        // CA-21.2: Límite - Folio consecutivo #13 en el año sin repetir números
        $ejecutadas++;
        $service->establecerUltimoConsecutivo('COT', 2026, 12);
        $pdf13 = $service->generarPropuestaPdf($cotizacion, $cliente, 2026, 'COT');
        assert($pdf13['folio'] === 'COT-2026-0013');
        assert($pdf13['consecutivo'] === 13);
        $pasadas++;

        // CA-21.3: Error - Falla del generador PDF devuelve mensaje amigable en español y registra evento interno
        $ejecutadas++;
        $capturada21_3 = false;
        try {
            $service->generarPropuestaPdf($cotizacion, $cliente, 2026, 'COT', true);
        } catch (RuntimeException $e) {
            $capturada21_3 = ($e->getMessage() === 'No se pudo generar el documento, inténtalo de nuevo');
        }
        assert($capturada21_3 === true);
        assert(count($service->obtenerEventosInternos()) === 1);
        $pasadas++;

        // CA-22.1: Normal - Envío por correo a cliente con correo válido
        $ejecutadas++;
        $resultadoEnvio1 = $service->enviarPorCorreo('COT-2026-0013', $cliente, 'admin@elevese.mx', '2026-05-10 09:30:00');
        assert($resultadoEnvio1['enviado'] === true);
        assert($resultadoEnvio1['total_envios'] === 1);
        assert($resultadoEnvio1['ultimo_envio']['destinatario'] === 'compras@hospitalbajio.mx');
        $pasadas++;

        // CA-22.2: Error - Cliente sin correo registrado
        $ejecutadas++;
        $capturada22_2 = false;
        try {
            $service->enviarPorCorreo('COT-2026-0013', ['razon_social' => 'Cliente Sin Correo', 'correo' => ''], 'admin@elevese.mx', '2026-05-10 10:00:00');
        } catch (InvalidArgumentException $e) {
            $capturada22_2 = ($e->getMessage() === 'El cliente no tiene correo registrado; captúralo antes de enviar');
        }
        assert($capturada22_2 === true);
        $pasadas++;

        // CA-22.3: Límite - Segundo envío conserva ambos registros sin sobrescribir el primero
        $ejecutadas++;
        $resultadoEnvio2 = $service->enviarPorCorreo('COT-2026-0013', $cliente, 'ventas@elevese.mx', '2026-05-12 14:15:00');
        assert($resultadoEnvio2['total_envios'] === 2);
        assert($resultadoEnvio2['historial_envios'][0]['fecha'] === '2026-05-10 09:30:00');
        assert($resultadoEnvio2['historial_envios'][1]['fecha'] === '2026-05-12 14:15:00');
        $pasadas++;

        // CA-22.4: Normal/Límite - Registro de aceptación y rechazo actualiza estado y deja de contar como pendiente
        $ejecutadas++;
        $aceptada = $service->registrarDecisionCliente('COT-2026-0001', 'Aceptada', 'Orden de compra OC-889 recibida', '2026-05-15', 'admin@elevese.mx');
        assert($aceptada['estado_comercial'] === 'Aceptada');
        assert($aceptada['pendiente_en_tablero'] === false);
        assert($aceptada['decision']['nota'] === 'Orden de compra OC-889 recibida');

        $rechazada = $service->registrarDecisionCliente('COT-2026-0013', 'Rechazada', 'Cliente pospuso presupuesto para próximo trimestre', '2026-05-16', 'admin@elevese.mx');
        assert($rechazada['estado_comercial'] === 'Rechazada');
        assert($rechazada['pendiente_en_tablero'] === false);
        $pasadas++;

        return ['ejecutadas' => $ejecutadas, 'pasadas' => $pasadas];
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $res = (new PropuestaSeguimientoServiceTest())->run();
    echo json_encode($res) . PHP_EOL;
}
