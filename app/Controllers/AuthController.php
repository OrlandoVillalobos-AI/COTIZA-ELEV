<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\UsuarioModel;
use App\Models\AuditoriaModel;

class AuthController
{
    private UsuarioModel $usuarios;
    private AuditoriaModel $auditoria;

    public function __construct(?UsuarioModel $usuarios = null, ?AuditoriaModel $auditoria = null)
    {
        $this->usuarios = $usuarios ?? new UsuarioModel();
        $this->auditoria = $auditoria ?? new AuditoriaModel();
    }

    public function login(Request $request): Response
    {
        $correo = trim((string)$request->get('correo_electronico', $request->get('correo', '')));
        $password = (string)$request->get('password', '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $request->getHeader('user-agent', 'Unknown');

        if (empty($correo) || empty($password)) {
            return Response::json(['error' => 'Correo y contraseña requeridos.', 'codigo' => 'VAL_ERROR'], 422);
        }

        $usuario = $this->usuarios->buscarPorCorreo($correo);
        if (!$usuario) {
            return Response::json(['error' => 'Credenciales inválidas.', 'codigo' => 'AUTH_FAILED'], 401);
        }

        // Verificar bloqueo temporal (5 intentos fallidos, 15 min) (RF-02)
        if (!empty($usuario['bloqueado_hasta'])) {
            $bloqueadoHasta = strtotime($usuario['bloqueado_hasta']);
            if (time() < $bloqueadoHasta) {
                $minRestantes = (int)ceil(($bloqueadoHasta - time()) / 60);
                return Response::json([
                    'error' => "Cuenta temporalmente bloqueada. Intente de nuevo en {$minRestantes} minuto(s).",
                    'codigo' => 'ACCOUNT_LOCKED',
                    'minutos_restantes' => $minRestantes,
                ], 423);
            }
        }

        if (!password_verify($password, $usuario['password_hash'])) {
            $this->usuarios->incrementarIntentosFallidos((int)$usuario['id_usuario'], 15);
            $this->auditoria->registrar(
                (int)$usuario['id_usuario'],
                'AUTENTICACION',
                'LOGIN',
                $ip,
                $ua,
                "Intento fallido de inicio de sesión para el correo {$correo}"
            );

            return Response::json(['error' => 'Credenciales inválidas.', 'codigo' => 'AUTH_FAILED'], 401);
        }

        // Éxito: restablecer intentos y fijar sesión
        $this->usuarios->restablecerIntentos((int)$usuario['id_usuario']);
        $sesionData = [
            'id_usuario' => (int)$usuario['id_usuario'],
            'nombre_completo' => $usuario['nombre_completo'],
            'correo_electronico' => $usuario['correo_electronico'],
            'rol' => $usuario['rol'],
        ];
        Session::setUser($sesionData);

        $this->auditoria->registrar(
            (int)$usuario['id_usuario'],
            'AUTENTICACION',
            'LOGIN',
            $ip,
            $ua,
            "Inicio de sesión exitoso rol {$usuario['rol']}"
        );

        return Response::json([
            'mensaje' => 'Inicio de sesión exitoso.',
            'usuario' => $sesionData,
            'redireccion' => $usuario['rol'] === 'tecnico' ? '/mis-solicitudes' : '/dashboard',
        ], 200);
    }

    public function logout(Request $request): Response
    {
        $userId = Session::getUserId();
        if ($userId) {
            $this->auditoria->registrar(
                $userId,
                'AUTENTICACION',
                'LOGIN',
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $request->getHeader('user-agent', 'Unknown'),
                'Cierre de sesión voluntario'
            );
        }
        Session::destroy();
        return Response::json(['mensaje' => 'Sesión cerrada correctamente.'], 200);
    }

    public function recuperarPassword(Request $request): Response
    {
        $correo = trim((string)$request->get('correo', ''));
        if (empty($correo)) {
            return Response::json(['error' => 'Correo requerido.', 'codigo' => 'VAL_ERROR'], 422);
        }

        $usuario = $this->usuarios->buscarPorCorreo($correo);
        if (!$usuario) {
            // No revelar existencia de cuenta por seguridad
            return Response::json(['mensaje' => 'Si el correo existe, se ha enviado un token de recuperación de 30 minutos.'], 200);
        }

        $token = bin2hex(random_bytes(32));
        $this->usuarios->guardarResetToken((int)$usuario['id_usuario'], $token, 30);

        return Response::json([
            'mensaje' => 'Token de recuperación generado con vigencia estricta de 30 minutos.',
            'token' => $token, // En desarrollo se devuelve en JSON
        ], 200);
    }

    public function resetPassword(Request $request): Response
    {
        $token = (string)$request->get('token', '');
        $password = (string)$request->get('password', '');

        if (empty($token) || strlen($password) < 8) {
            return Response::json(['error' => 'Token válido y contraseña mínima de 8 caracteres requeridos.', 'codigo' => 'VAL_ERROR'], 422);
        }

        $usuario = $this->usuarios->buscarPorTokenValido($token);
        if (!$usuario) {
            return Response::json(['error' => 'El token es inválido o ha expirado (vigencia 30 min).', 'codigo' => 'TOKEN_EXPIRED'], 400);
        }

        $nuevoHash = password_hash($password, PASSWORD_BCRYPT);
        $this->usuarios->actualizarPassword((int)$usuario['id_usuario'], $nuevoHash);

        return Response::json(['mensaje' => 'Contraseña actualizada con éxito. Ya puede iniciar sesión.'], 200);
    }

    public function sessionStatus(Request $request): Response
    {
        $activo = Session::checkInactivity();
        if (!$activo || !Session::has('usuario')) {
            return Response::json(['activa' => false, 'segundos_restantes' => 0], 200);
        }

        return Response::json([
            'activa' => true,
            'segundos_restantes' => Session::getRemainingSeconds(),
            'usuario' => Session::getUser(),
        ], 200);
    }
}
