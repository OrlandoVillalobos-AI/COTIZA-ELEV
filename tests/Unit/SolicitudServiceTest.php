<?php

declare(strict_types=1);

namespace Tests\Unit;

require_once __DIR__ . '/../../src/Services/SolicitudService.php';

use CotizaElev\Services\SolicitudService;
use InvalidArgumentException;
use RuntimeException;

/**
 * Suite de pruebas unitarias para SolicitudService (M05)
 * Trazabilidad: RF-20, RF-21, RF-22, RF-23, RF-24, RF-43 / HU-12, HU-13, HU-14
 */
class SolicitudServiceTest
{
    private int $ejecutadas = 0;
    private int $pasadas = 0;

    /**
     * @return array{ejecutadas: int, pasadas: int}
     */
    public function ejecutarTodas(): array
    {
        $this->testAltaSolicitudNormal_CA12_1();
        $this->testAltaSolicitudCamposFaltantes_CA12_2();
        $this->testDuplicadoPendienteAvisoYConfirmacion_CA12_3();
        $this->testConsultaTecnicoSinImportesYCambioEstado_CA13_1_CA13_2();
        $this->testTecnicoIntentaVerSolicitudAjenaYMotivoRechazo_CA13_3();
        $this->testTecnicoSinSolicitudes_CA13_4();
        $this->testBandejaAdminPaginacionAtencionYBloqueo_CA14_1_14_2_14_4_14_5();

        return [
            'ejecutadas' => $this->ejecutadas,
            'pasadas' => $this->pasadas,
        ];
    }

    /**
     * CA-12.1: Caso Normal - Alta de solicitud con estado 'Pendiente de cotizar' y folio SOL-0001
     */
    public function testAltaSolicitudNormal_CA12_1(): void
    {
        $this->ejecutadas++;
        $service = new SolicitudService();

        $sol = $service->crearSolicitud([
            'cliente_id' => 10,
            'cliente' => 'Torre Corporativa Reforma',
            'activo_id' => 25,
            'activo' => 'Elevador Otis Gen2 #1',
            'pieza_o_servicio' => 'Tarjeta de control de puertas',
            'cantidad' => 1,
            'urgencia' => 'Alta',
            'tecnico_correo' => 'tecnico1@elevese.mx',
        ]);

        if (
            $sol['folio'] === 'SOL-0001' &&
            $sol['estado'] === 'Pendiente de cotizar' &&
            !empty($sol['fecha_creacion'])
        ) {
            $this->pasadas++;
        }
    }

    /**
     * CA-12.2: Caso Error - Campos obligatorios faltantes
     */
    public function testAltaSolicitudCamposFaltantes_CA12_2(): void
    {
        $this->ejecutadas++;
        $service = new SolicitudService();

        try {
            $service->crearSolicitud([
                'cliente_id' => 10,
                'activo_id' => null,
                'pieza_o_servicio' => '',
                'cantidad' => 1,
                'urgencia' => 'Media',
                'tecnico_correo' => 'tecnico1@elevese.mx',
            ]);
        } catch (InvalidArgumentException $e) {
            if ($e->getMessage() === 'Faltan campos obligatorios: cliente, activo o descripción de la pieza') {
                $this->pasadas++;
            }
        }
    }

    /**
     * CA-12.3: Caso Límite - Duplicado pendiente lanza aviso y permite confirmar con motivo de repetición
     */
    public function testDuplicadoPendienteAvisoYConfirmacion_CA12_3(): void
    {
        $this->ejecutadas++;
        $service = new SolicitudService();

        $datos = [
            'cliente_id' => 10,
            'activo_id' => 25,
            'pieza_o_servicio' => 'Contactor principal 24V',
            'cantidad' => 2,
            'urgencia' => 'Alta',
            'tecnico_correo' => 'tecnico1@elevese.mx',
        ];

        $service->crearSolicitud($datos);

        $bloqueadoSinConfirmar = false;
        try {
            $service->crearSolicitud($datos, false);
        } catch (RuntimeException $e) {
            $bloqueadoSinConfirmar = ($e->getMessage() === 'Ya existe una solicitud pendiente de esa pieza para este equipo');
        }

        $solConfirmada = $service->crearSolicitud($datos, true, 'Se dañó una segunda unidad durante pruebas');

        if (
            $bloqueadoSinConfirmar &&
            $solConfirmada['folio'] === 'SOL-0002' &&
            $solConfirmada['motivo_repeticion'] === 'Se dañó una segunda unidad durante pruebas'
        ) {
            $this->pasadas++;
        }
    }

    /**
     * CA-13.1 y CA-13.2: Caso Normal - Consulta de técnico sin precios/importes y actualización a Cotizada
     */
    public function testConsultaTecnicoSinImportesYCambioEstado_CA13_1_CA13_2(): void
    {
        $this->ejecutadas++;
        $service = new SolicitudService();

        $creada = $service->crearSolicitud([
            'cliente_id' => 12,
            'cliente' => 'Hospital Ángeles',
            'activo_id' => 30,
            'activo' => 'Montacamas #2',
            'pieza_o_servicio' => 'Encoder rot incremental',
            'cantidad' => 1,
            'urgencia' => 'Urgente',
            'tecnico_correo' => 'carlos.campo@elevese.mx',
            'precio_estimado' => 14500.00,
            'importe_cotizado' => 16820.00,
            'folio_propuesta' => 'COT-2026-0099',
        ]);

        $service->atenderSolicitud($creada['id'], 'Cotizada', 'admin@elevese.mx');

        $consulta = $service->consultarSolicitudesTecnico('carlos.campo@elevese.mx');
        $item = $consulta['solicitudes'][0] ?? [];

        $sinCamposComerciales = !array_key_exists('precio_estimado', $item)
            && !array_key_exists('importe_cotizado', $item)
            && !array_key_exists('precio', $item)
            && !array_key_exists('importe', $item)
            && !array_key_exists('folio_propuesta', $item);

        if (
            count($consulta['solicitudes']) === 1 &&
            $item['estado'] === 'Cotizada' &&
            $sinCamposComerciales
        ) {
            $this->pasadas++;
        }
    }

    /**
     * CA-13.3: Caso Error - Técnico intenta consultar solicitud ajena y visualización de motivo de rechazo
     */
    public function testTecnicoIntentaVerSolicitudAjenaYMotivoRechazo_CA13_3(): void
    {
        $this->ejecutadas++;
        $service = new SolicitudService();

        $sol = $service->crearSolicitud([
            'cliente_id' => 15,
            'activo_id' => 40,
            'pieza_o_servicio' => 'Zapata guía de contrapeso',
            'cantidad' => 4,
            'urgencia' => 'Media',
            'tecnico_correo' => 'tecnicoA@elevese.mx',
        ]);

        $service->atenderSolicitud($sol['id'], 'Rechazada', 'admin@elevese.mx', 'Cubierto por garantía vigente');

        $accesoDenegado = false;
        try {
            $service->obtenerDetalleSolicitudParaTecnico($sol['id'], 'tecnicoB@elevese.mx');
        } catch (RuntimeException $e) {
            $accesoDenegado = ($e->getMessage() === 'No tienes permisos para ver esta solicitud');
        }

        $detallePropio = $service->obtenerDetalleSolicitudParaTecnico($sol['id'], 'tecnicoA@elevese.mx');

        if (
            $accesoDenegado &&
            $detallePropio['estado'] === 'Rechazada' &&
            $detallePropio['motivo_rechazo'] === 'Cubierto por garantía vigente'
        ) {
            $this->pasadas++;
        }
    }

    /**
     * CA-13.4: Caso Límite - Técnico sin solicitudes registradas
     */
    public function testTecnicoSinSolicitudes_CA13_4(): void
    {
        $this->ejecutadas++;
        $service = new SolicitudService();

        $res = $service->consultarSolicitudesTecnico('nuevo.tecnico@elevese.mx');

        if (
            $res['mensaje'] === 'No tienes solicitudes registradas' &&
            $res['solicitudes'] === []
        ) {
            $this->pasadas++;
        }
    }

    /**
     * CA-14.1, CA-14.2, CA-14.4, CA-14.5: Bandeja en orden cronológico, paginación de 200 en bloques de 50,
     * registro de auditoría en atención y bloqueo cuando ya fue cotizada por otro usuario.
     */
    public function testBandejaAdminPaginacionAtencionYBloqueo_CA14_1_14_2_14_4_14_5(): void
    {
        $this->ejecutadas++;
        $service = new SolicitudService();

        for ($i = 1; $i <= 200; $i++) {
            $service->crearSolicitud([
                'cliente_id' => 1,
                'activo_id' => $i,
                'pieza_o_servicio' => "Pieza #{$i}",
                'cantidad' => 1,
                'urgencia' => 'Media',
                'tecnico_correo' => 'tecnico@elevese.mx',
                'fecha_creacion' => sprintf('2026-10-%02d %02d:00:00', (int)ceil($i / 10), $i % 24),
            ]);
        }

        $pagina1 = $service->listarBandejaAdmin(1, 50);
        $pagina4 = $service->listarBandejaAdmin(4, 50);

        $atendida = $service->atenderSolicitud(1, 'Cotizada', 'admin1@elevese.mx');

        $bloqueoDobleCotizacion = false;
        try {
            $service->atenderSolicitud(1, 'Cotizada', 'admin2@elevese.mx');
        } catch (RuntimeException $e) {
            $bloqueoDobleCotizacion = ($e->getMessage() === 'Esta solicitud ya fue cotizada por otro usuario');
        }

        if (
            $pagina1['total_pendientes'] === 200 &&
            $pagina1['total_paginas'] === 4 &&
            count($pagina1['solicitudes']) === 50 &&
            count($pagina4['solicitudes']) === 50 &&
            $pagina1['solicitudes'][0]['id'] === 1 &&
            $atendida['atendido_por'] === 'admin1@elevese.mx' &&
            !empty($atendida['fecha_atencion']) &&
            $bloqueoDobleCotizacion
        ) {
            $this->pasadas++;
        }
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $res = (new SolicitudServiceTest())->ejecutarTodas();
    echo "SolicitudServiceTest: {$res['pasadas']}/{$res['ejecutadas']} pasadas.\n";
    exit($res['pasadas'] === $res['ejecutadas'] ? 0 : 1);
}
