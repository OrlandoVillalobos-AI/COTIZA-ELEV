<?php

declare(strict_types=1);

namespace Tests\Unit;

use CotizaElev\Services\AuditoriaErrorService;
use DomainException;
use RuntimeException;

require_once __DIR__ . '/../../src/Services/AuditoriaErrorService.php';

final class AuditoriaErrorServiceTest
{
    public function ejecutarTodas(): array { return $this->run(); }

    public function run(): array
    {
        $ejecutadas = 0;
        $pasadas = 0;

        $service = new AuditoriaErrorService();

        // CA-24.1: Normal - Registro cronológico e inmutable de acceso denegado y cambio de tarifa
        $ejecutadas++;
        $service->registrarEvento(
            'tecnico01@elevese.mx',
            'ACCESO_DENEGADO',
            'M06-CatalogoTarifas',
            '2026-05-10 08:15:00',
            ['ruta' => '/tarifas/editar', 'motivo' => 'Rol Técnico sin privilegios']
        );
        $service->registrarEvento(
            'admin@elevese.mx',
            'EDICION_TARIFA',
            'M06-CatalogoTarifas',
            '2026-05-10 08:20:00',
            ['codigo' => 'REF-001', 'precio_anterior' => 1200.00, 'precio_nuevo' => 1350.00]
        );

        $consulta = $service->consultarBitacora(null, '2026-05-10', '2026-05-10');
        assert($consulta['total_registros'] === 2);
        assert($consulta['registros'][0]['accion'] === 'ACCESO_DENEGADO');
        assert($consulta['registros'][1]['accion'] === 'EDICION_TARIFA');
        assert(!method_exists($service, 'eliminarEvento') && !method_exists($service, 'editarEvento'));
        $pasadas++;

        // CA-24.2: Error - Pérdida de conexión devuelve mensaje amigable sin traza técnica y registra en bitácora
        $ejecutadas++;
        $respuestaError = $service->manejarExcepcion(
            new RuntimeException('SQLSTATE[HY000] [2002] Connection timed out at /var/www/db.php:42'),
            'admin@elevese.mx',
            'guardar'
        );
        assert($respuestaError['exito'] === false);
        assert($respuestaError['mensaje_usuario'] === 'No se pudo guardar por un problema de conexión, inténtalo de nuevo en unos segundos');
        assert(!str_contains($respuestaError['mensaje_usuario'], 'SQLSTATE'));
        assert($service->consultarBitacora()['total_registros'] === 3);
        $pasadas++;

        // CA-24.3: Límite - Paginación en bloques de 50 registros sobre volumen alto (120 eventos)
        $ejecutadas++;
        for ($i = 1; $i <= 120; $i++) {
            $service->registrarEvento(
                'auditor@elevese.mx',
                'CONSULTA_REPORTE_' . $i,
                'M09-Reportes',
                sprintf('2026-05-11 10:%02d:%02d', intdiv($i, 60), $i % 60)
            );
        }
        $pagina1 = $service->consultarBitacora('auditor@elevese.mx', '2026-05-11', '2026-05-11', 1, 50);
        $pagina3 = $service->consultarBitacora('auditor@elevese.mx', '2026-05-11', '2026-05-11', 3, 50);
        assert($pagina1['total_registros'] === 120);
        assert($pagina1['total_paginas'] === 3);
        assert(count($pagina1['registros']) === 50);
        assert(count($pagina3['registros']) === 20);
        $pasadas++;

        // RF-43 / CA-14.5: Rechazo de operación sobre registro cancelado, activo ajeno o solicitud ya cotizada por otro usuario
        $ejecutadas++;
        $errorCancelado = false;
        try {
            $service->validarOperacionSobreRegistro(['id' => 9, 'estado' => 'cancelado'], 'cotizacion');
        } catch (DomainException $e) {
            $errorCancelado = ($e->getMessage() === 'El registro solicitado no existe o ya fue cancelado');
        }
        assert($errorCancelado === true);

        $errorActivoAjeno = false;
        try {
            $service->validarOperacionSobreRegistro(['id' => 4, 'cliente_id' => 25, 'estado' => 'activo'], 'activo', 10);
        } catch (DomainException $e) {
            $errorActivoAjeno = ($e->getMessage() === 'El activo seleccionado no pertenece al cliente');
        }
        assert($errorActivoAjeno === true);

        $errorYaCotizada = false;
        try {
            $service->validarOperacionSobreRegistro(
                ['id' => 77, 'estado' => 'cotizada', 'cotizada_por' => 'admin1@elevese.mx'],
                'solicitud',
                null,
                'admin2@elevese.mx'
            );
        } catch (DomainException $e) {
            $errorYaCotizada = ($e->getMessage() === 'Esta solicitud ya fue cotizada por otro usuario');
        }
        assert($errorYaCotizada === true);
        $pasadas++;

        return ['ejecutadas' => $ejecutadas, 'pasadas' => $pasadas];
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $res = (new AuditoriaErrorServiceTest())->run();
    echo json_encode($res) . PHP_EOL;
}
