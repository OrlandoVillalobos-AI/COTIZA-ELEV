<?php

declare(strict_types=1);

namespace Tests\Integration;

require_once __DIR__ . '/../../app/autoload.php';
require_once __DIR__ . '/../TestDatabaseHelper.php';

use Tests\TestDatabaseHelper;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Middlewares\AuthMiddleware;
use App\Controllers\CotizadorController;
use App\Models\SolicitudModel;
use App\Models\CotizacionModel;

class CotizadorControllerTest
{
    private array $results = [];

    public function run(): array
    {
        $pdo = TestDatabaseHelper::createTestPdo();
        Session::start();
        Session::setUser([
            'id_usuario' => 1,
            'nombre_completo' => 'Orlando Administrador',
            'correo_electronico' => 'admin@elevese.mx',
            'rol' => 'admin_ventas',
        ]);

        $router = new Router();
        $authMiddleware = new AuthMiddleware();

        $router->post('/solicitudes/{id}/convertir', [CotizadorController::class, 'convertirSolicitud'], [$authMiddleware]);
        $router->post('/cotizaciones/calcular', [CotizadorController::class, 'calcular'], [$authMiddleware]);
        $router->post('/cotizaciones/guardar', [CotizadorController::class, 'guardar'], [$authMiddleware]);
        $router->post('/cotizaciones/{id}/duplicar', [CotizadorController::class, 'duplicar'], [$authMiddleware]);
        $router->post('/cotizaciones/{id}/cambiar-estado', [CotizadorController::class, 'cambiarEstado'], [$authMiddleware]);

        // Crear una solicitud de prueba en BD
        $solModel = new SolicitudModel();
        $idSol = $solModel->crear([
            'folio_solicitud' => 'SOL-2026-9999',
            'id_tecnico' => 2,
            'id_cliente' => 1,
            'id_activo' => 1,
            'tipo_servicio' => 'correctivo',
            'diagnostico_falla' => 'Cambio de sensor de piso',
            'piezas_requeridas' => '1 sensor',
        ]);

        // 1. Convertir Solicitud a Cotización (RF-25)
        $req1 = Request::create('POST', "/solicitudes/{$idSol}/convertir");
        $resp1 = $router->dispatch($req1);
        $data1 = json_decode($resp1->getContent(), true);
        $this->assert(
            $resp1->getStatusCode() === 200 && ($data1['datos_precargados']['id_solicitud'] ?? null) === $idSol,
            'Controlador Cotizador: Conversión de solicitud técnica a cotización (RF-25)'
        );

        // 2. Calcular Totales por AJAX (RF-27..RF-30)
        $partidasPrueba = [
            ['cantidad' => 2, 'precio_unitario' => 3200.00], // 6400
            ['cantidad' => 3, 'precio_unitario' => 700.00],  // 2100 -> subtotal 8500
        ];
        $req2 = Request::create('POST', '/cotizaciones/calcular', [
            'partidas' => $partidasPrueba,
            'porcentaje_descuento' => 10.0, // desc: 850 -> base: 7650 -> IVA 16%: 1224 -> total: 8874
        ]);
        $resp2 = $router->dispatch($req2);
        $data2 = json_decode($resp2->getContent(), true);
        $totales = $data2['calculo'] ?? [];
        $this->assert(
            $resp2->getStatusCode() === 200 &&
            (float)($totales['subtotal'] ?? 0) === 8500.0 &&
            (float)($totales['total'] ?? 0) === 8874.0,
            'Controlador Cotizador: Cálculo aritmético interactivo con M07 (RF-27..RF-30)'
        );

        // 3. Guardar Cotización Borrador (RF-31, RF-32)
        $req3 = Request::create('POST', '/cotizaciones/guardar', [
            'id_cliente' => 1,
            'id_activo' => 1,
            'id_solicitud' => $idSol,
            'tipo_cotizacion' => 'correctivo',
            'partidas' => [
                ['id_concepto' => 2, 'descripcion_concepto' => 'Sensor', 'cantidad' => 2, 'precio_unitario' => 3200.00],
                ['id_concepto' => 1, 'descripcion_concepto' => 'Mano de obra', 'cantidad' => 3, 'precio_unitario' => 700.00],
            ],
            'porcentaje_descuento' => 10.0,
        ]);
        $resp3 = $router->dispatch($req3);
        $data3 = json_decode($resp3->getContent(), true);
        $idCotGuardada = $data3['id_cotizacion'] ?? 0;
        $this->assert(
            $resp3->getStatusCode() === 201 && $idCotGuardada > 0 && str_starts_with($data3['folio_cotizacion'] ?? '', 'COT-'),
            'Controlador Cotizador: Guardado de cotización en borrador con folio formal (RF-31, RF-32)'
        );

        // 4. Duplicar Cotización con tarifas vigentes (RF-34)
        $req4 = Request::create('POST', "/cotizaciones/{$idCotGuardada}/duplicar");
        $resp4 = $router->dispatch($req4);
        $data4 = json_decode($resp4->getContent(), true);
        $this->assert(
            $resp4->getStatusCode() === 201 && ($data4['id_cotizacion'] ?? 0) > $idCotGuardada,
            'Controlador Cotizador: Duplicación de cotización previa con actualización de catálogo (RF-34)'
        );

        // 5. Transición de estado a 'enviada' (RF-33)
        $req5 = Request::create('POST', "/cotizaciones/{$idCotGuardada}/cambiar-estado", ['estado' => 'enviada']);
        $resp5 = $router->dispatch($req5);
        $this->assert(
            $resp5->getStatusCode() === 200,
            'Controlador Cotizador: Transición de estado de cotización (RF-33)'
        );

        return $this->results;
    }

    private function assert(bool $condition, string $description): void
    {
        $this->results[] = [
            'desc' => $description,
            'status' => $condition,
        ];
    }
}
