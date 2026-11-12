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
use App\Controllers\CatalogoController;
use App\Controllers\CotizadorController;
use App\Controllers\SolicitudController;
use App\Models\AuditoriaModel;

class SecurityRbacHttpTest
{
    private array $results = [];

    public function run(): array
    {
        $pdo = TestDatabaseHelper::createTestPdo();
        Session::start();

        $router = new Router();
        $authMiddleware = new AuthMiddleware();

        $router->get('/catalogo', [CatalogoController::class, 'index'], [$authMiddleware]);
        $router->post('/cotizaciones/guardar', [CotizadorController::class, 'guardar'], [$authMiddleware]);
        $router->get('/mis-solicitudes', [SolicitudController::class, 'misSolicitudes'], [$authMiddleware]);

        // 1. Acceso sin sesión activa a ruta protegida -> 401
        Session::destroy();
        $req1 = Request::create('GET', '/catalogo', [], ['Content-Type' => 'application/json']);
        $resp1 = $router->dispatch($req1);
        $this->assert($resp1->getStatusCode() === 401, 'Seguridad RBAC: Acceso no autenticado retorna HTTP 401');

        // 2. Rol Técnico intentando acceder a tarifas de catálogo -> 403
        Session::setUser([
            'id_usuario' => 2,
            'nombre_completo' => 'Carlos Tecnico',
            'correo_electronico' => 'tecnico@elevese.mx',
            'rol' => 'tecnico',
        ]);
        $req2 = Request::create('GET', '/catalogo', [], ['Content-Type' => 'application/json']);
        $resp2 = $router->dispatch($req2);
        $this->assert($resp2->getStatusCode() === 403, 'Seguridad RBAC: Rol Técnico bloqueado con HTTP 403 en catálogo de tarifas (RF-05, RNF-12)');

        // 3. Verificación de registro de SECURITY_ALERT en bitácora
        $auditoriaModel = new AuditoriaModel();
        $logs = $auditoriaModel->listarPaginado(5);
        $alertaDetectada = false;
        foreach ($logs as $l) {
            if ($l['accion'] === 'SECURITY_ALERT' && str_contains($l['detalle_evento'], '/catalogo')) {
                $alertaDetectada = true;
                break;
            }
        }
        $this->assert($alertaDetectada, 'Seguridad RBAC: Intento de violación perimetral registrado en bitácora con SECURITY_ALERT (RF-41, RNF-11)');

        // 4. Rol Técnico intentando guardar cotización comercial -> 403
        $req3 = Request::create('POST', '/cotizaciones/guardar', ['id_cliente' => 1], ['Content-Type' => 'application/json']);
        $resp3 = $router->dispatch($req3);
        $this->assert($resp3->getStatusCode() === 403, 'Seguridad RBAC: Rol Técnico bloqueado con HTTP 403 en guardado de cotizaciones');

        // 5. Rol Técnico accediendo a sus solicitudes permitidas -> 200
        $req4 = Request::create('GET', '/mis-solicitudes', [], ['Content-Type' => 'application/json']);
        $resp4 = $router->dispatch($req4);
        $this->assert($resp4->getStatusCode() === 200, 'Seguridad RBAC: Rol Técnico autorizado correctamente en sus solicitudes');

        // 6. Rol Administrador de Ventas accediendo a catálogo -> 200
        Session::setUser([
            'id_usuario' => 1,
            'nombre_completo' => 'Orlando Administrador',
            'correo_electronico' => 'admin@elevese.mx',
            'rol' => 'admin_ventas',
        ]);
        $req5 = Request::create('GET', '/catalogo', [], ['Content-Type' => 'application/json']);
        $resp5 = $router->dispatch($req5);
        $this->assert($resp5->getStatusCode() === 200, 'Seguridad RBAC: Rol Admin Ventas autorizado con HTTP 200 en catálogo');

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
