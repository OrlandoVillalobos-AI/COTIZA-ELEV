<?php

declare(strict_types=1);

/**
 * SISTEMA WEB COTIZA-ELEV — FRONT CONTROLLER (INCREMENTO 7 / v0.7.0)
 * Único punto de entrada HTTP público de la aplicación.
 */

require_once __DIR__ . '/../app/autoload.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Middlewares\AuthMiddleware;
use App\Controllers\AuthController;
use App\Controllers\ClienteController;
use App\Controllers\ActivoController;
use App\Controllers\CatalogoController;
use App\Controllers\SolicitudController;
use App\Controllers\CotizadorController;
use App\Controllers\ReporteController;

// Manejador centralizado de errores sin trazas en producción (RNF-03)
set_exception_handler(function (Throwable $e) {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'error' => 'Ha ocurrido un error interno en el servidor.',
        'codigo' => 'INTERNAL_SERVER_ERROR',
        'mensaje' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
});

// Inicialización de sesión segura
Session::start();

$router = new Router();
$authMiddleware = new AuthMiddleware();

// 1. RUTAS DE AUTENTICACIÓN Y SESIÓN (PÚBLICAS / SEMI-PÚBLICAS)
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->post('/recuperar-password', [AuthController::class, 'recuperarPassword']);
$router->post('/reset-password', [AuthController::class, 'resetPassword']);
$router->get('/api/session/status', [AuthController::class, 'sessionStatus']);

// 2. RUTAS PROTEGIDAS CON AUTHMIDDLEWARE (RBAC + INACTIVIDAD 20 MIN)
// Directorio de Clientes
$router->get('/clientes', [ClienteController::class, 'index'], [$authMiddleware]);
$router->post('/clientes', [ClienteController::class, 'crear'], [$authMiddleware]);
$router->post('/clientes/{id}/editar', [ClienteController::class, 'editar'], [$authMiddleware]);
$router->post('/clientes/{id}/baja', [ClienteController::class, 'baja'], [$authMiddleware]);

// Activos / Equipos de Elevación
$router->post('/activos', [ActivoController::class, 'crear'], [$authMiddleware]);
$router->get('/activos/{id}/historial', [ActivoController::class, 'historial'], [$authMiddleware]);

// Catálogo de Tarifas (Protegido estrictamente contra rol Técnico)
$router->get('/catalogo', [CatalogoController::class, 'index'], [$authMiddleware]);
$router->post('/catalogo/importar-csv', [CatalogoController::class, 'importarCsv'], [$authMiddleware]);

// Solicitudes Técnicas Móviles y Bandeja
$router->post('/solicitudes', [SolicitudController::class, 'crear'], [$authMiddleware]);
$router->get('/mis-solicitudes', [SolicitudController::class, 'misSolicitudes'], [$authMiddleware]);
$router->get('/bandeja-solicitudes', [SolicitudController::class, 'bandeja'], [$authMiddleware]);
$router->post('/solicitudes/{id}/rechazar', [SolicitudController::class, 'rechazar'], [$authMiddleware]);

// Cotizador Comercial (Protegido contra rol Técnico)
$router->post('/solicitudes/{id}/convertir', [CotizadorController::class, 'convertirSolicitud'], [$authMiddleware]);
$router->post('/cotizaciones/calcular', [CotizadorController::class, 'calcular'], [$authMiddleware]);
$router->post('/cotizaciones/guardar', [CotizadorController::class, 'guardar'], [$authMiddleware]);
$router->post('/cotizaciones/{id}/duplicar', [CotizadorController::class, 'duplicar'], [$authMiddleware]);
$router->post('/cotizaciones/{id}/cambiar-estado', [CotizadorController::class, 'cambiarEstado'], [$authMiddleware]);

// Reportes y Dashboard Comercial (Protegido contra rol Técnico)
$router->get('/dashboard', [ReporteController::class, 'dashboard'], [$authMiddleware]);
$router->get('/reportes/exportar-csv', [ReporteController::class, 'exportarCsv'], [$authMiddleware]);
$router->get('/auditoria', [ReporteController::class, 'auditoria'], [$authMiddleware]);

// Despacho de la petición HTTP
$request = Request::createFromGlobals();
$response = $router->dispatch($request);
$response->send();
