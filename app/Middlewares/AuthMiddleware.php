<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\AuditoriaModel;

class AuthMiddleware
{
    private AuditoriaModel $auditoria;

    public function __construct(?AuditoriaModel $auditoria = null)
    {
        $this->auditoria = $auditoria ?? new AuditoriaModel();
    }

    public function handle(Request $request, callable $next, array $allowedRoles = []): Response
    {
        // 1. Validar inactividad de 20 minutos (RF-07 / RNF-10)
        if (!Session::checkInactivity()) {
            if ($request->isJson() || str_starts_with($request->getPath(), '/api/')) {
                return Response::json([
                    'error' => 'Sesión expirada por inactividad (20 minutos).',
                    'codigo' => 'SESSION_EXPIRED',
                ], 401);
            }
            return Response::redirect('/login?motivo=expirada');
        }

        $usuario = Session::getUser();
        if ($usuario === null) {
            if ($request->isJson() || str_starts_with($request->getPath(), '/api/')) {
                return Response::json([
                    'error' => 'Autenticación requerida para acceder al recurso.',
                    'codigo' => 'AUTH_REQUIRED',
                ], 401);
            }
            return Response::redirect('/login');
        }

        $rolActual = $usuario['rol'] ?? 'tecnico';
        $path = $request->getPath();

        // 2. Blindaje RBAC Perimetral para rol Técnico (RF-05, RF-06, RNF-11, RNF-12, RNF-26)
        $rutasRestringidasTecnico = [
            '/catalogo',
            '/catalogo/importar-csv',
            '/cotizaciones/crear',
            '/cotizaciones/calcular',
            '/cotizaciones/guardar',
            '/dashboard',
            '/reportes',
            '/reportes/exportar-csv',
            '/auditoria',
        ];

        $esRutaRestringida = false;
        foreach ($rutasRestringidasTecnico as $r) {
            if ($path === $r || str_starts_with($path, $r . '/')) {
                $esRutaRestringida = true;
                break;
            }
        }

        if ($rolActual === 'tecnico' && $esRutaRestringida) {
            // Registrar alerta inmutable de seguridad
            try {
                $this->auditoria->registrar(
                    (int)($usuario['id_usuario'] ?? null),
                    'PERIMETRO_SEGURIDAD',
                    'SECURITY_ALERT',
                    $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    $request->getHeader('user-agent', 'Unknown'),
                    "Intento de acceso denegado por rol técnico a la ruta restringida: {$path}",
                    null,
                    ['ruta' => $path, 'metodo' => $request->getMethod(), 'rol' => $rolActual]
                );
            } catch (\Throwable) {
                // Falla silenciosa de auditoría para no romper la respuesta HTTP
            }

            return Response::json([
                'error' => 'Acceso denegado: El perfil Técnico tiene restringido el acceso a tarifas, cálculos comerciales y reportes.',
                'codigo' => 'FORBIDDEN_TECH_ROLE',
            ], 403);
        }

        // 3. Verificación de roles permitidos explícitos si fueron especificados
        if (!empty($allowedRoles) && !in_array($rolActual, $allowedRoles, true)) {
            return Response::json([
                'error' => 'Acceso no autorizado para su rol de usuario.',
                'codigo' => 'FORBIDDEN_ROLE',
            ], 403);
        }

        return $next($request);
    }
}
