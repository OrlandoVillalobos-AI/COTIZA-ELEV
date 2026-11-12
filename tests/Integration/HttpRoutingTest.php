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

class HttpRoutingTest
{
    private array $results = [];

    public function run(): array
    {
        TestDatabaseHelper::createTestPdo();
        Session::start();

        $router = new Router();
        $router->get('/saludo', function (Request $req) {
            return Response::json(['mensaje' => 'Hola Mundo'], 200);
        });

        $router->get('/items/{id}', function (Request $req, array $params) {
            return Response::json(['id' => (int)$params['id']], 200);
        });

        // 1. Ruta estándar coincidente
        $req1 = Request::create('GET', '/saludo');
        $resp1 = $router->dispatch($req1);
        $this->assert($resp1->getStatusCode() === 200, 'HTTP Router: Coincidencia de ruta estática');

        // 2. Ruta con parámetro dinámico
        $req2 = Request::create('GET', '/items/42');
        $resp2 = $router->dispatch($req2);
        $data2 = json_decode($resp2->getContent(), true);
        $this->assert($resp2->getStatusCode() === 200 && ($data2['id'] ?? null) === 42, 'HTTP Router: Extracción de parámetros dinámicos {id}');

        // 3. Ruta inexistente (404)
        $req3 = Request::create('GET', '/ruta-no-existe');
        $resp3 = $router->dispatch($req3);
        $this->assert($resp3->getStatusCode() === 404, 'HTTP Router: Retorno de HTTP 404 para rutas inexistentes');

        // 4. Verificación de timeout de inactividad de sesión (20 min)
        Session::setUser(['id_usuario' => 1, 'nombre' => 'Test', 'rol' => 'admin_ventas']);
        $this->assert(Session::checkInactivity(), 'Sesión: Sesión recién creada activa');

        // Simular paso de 1201 segundos (20 minutos y 1 segundo)
        $_SESSION['last_activity'] = time() - 1205;
        $activaTrasTimeout = Session::checkInactivity(Session::MAX_INACTIVITY_SECONDS);
        $this->assert(!$activaTrasTimeout && Session::getUser() === null, 'Sesión: Destrucción automática tras 20 minutos de inactividad (RF-07 / RNF-10)');

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
