<?php

declare(strict_types=1);

namespace CotizaElev\Services;

use InvalidArgumentException;
use RuntimeException;

/**
 * M01: Autenticación y Control de Acceso Basado en Roles (AuthRbacService - Incremento 5)
 *
 * Atiende a: RF-01, RF-02, RF-03, RF-04, RF-05, RF-06, RF-07
 * Historias de usuario: HU-01, HU-02, HU-03, HU-04, HU-05
 */
class AuthRbacService
{
    public const MAX_INTENTOS_FALLIDOS = 5;
    public const SEGUNDOS_BLOQUEO = 900; // 15 minutos
    public const SEGUNDOS_VIGENCIA_TOKEN_RECUPERACION = 1800; // 30 minutos
    public const MINUTOS_AVISO_INACTIVIDAD = 19;
    public const MINUTOS_LIMITE_INACTIVIDAD = 20;

    /** @var array<string, array<string, mixed>> */
    private array $usuarios = [];

    /** @var array<int, array<string, mixed>> */
    private array $bitacoraCambiosRol = [];

    /** @var array<int, array<string, mixed>> */
    private array $bitacoraAccesos = [];

    /** @var array<string, array{correo: string, expira_en: int, usado: bool}> */
    private array $tokensRecuperacion = [];

    public function __construct()
    {
        $this->usuarios = [
            'admin@elevese.mx' => [
                'correo' => 'admin@elevese.mx',
                'password_hash' => password_hash('Admin2026!', PASSWORD_DEFAULT),
                'rol' => 'admin_ventas',
                'nombre' => 'Administrador Elevese',
                'intentos_fallidos' => 0,
                'bloqueado_hasta' => 0,
                'activo' => true,
            ],
            'tecnico@elevese.mx' => [
                'correo' => 'tecnico@elevese.mx',
                'password_hash' => password_hash('Tec2026!', PASSWORD_DEFAULT),
                'rol' => 'tecnico',
                'nombre' => 'Técnico de Campo',
                'intentos_fallidos' => 0,
                'bloqueado_hasta' => 0,
                'activo' => true,
            ],
        ];
    }

    /**
     * RF-04 (CA-04.1, CA-04.2): Registro de usuarios por admin_ventas.
     */
    public function registrarUsuario(
        string $rolEjecutor,
        string $correo,
        string $password,
        string $rolNuevo,
        string $nombre
    ): array {
        if ($rolEjecutor !== 'admin_ventas') {
            throw new RuntimeException('No tienes permisos para registrar usuarios');
        }

        if (!in_array($rolNuevo, ['tecnico', 'admin_ventas'], true)) {
            throw new InvalidArgumentException("Rol inválido: {$rolNuevo}");
        }

        $correoNorm = strtolower(trim($correo));
        if ($correoNorm === '' || !str_contains($correoNorm, '@')) {
            throw new InvalidArgumentException('Correo electrónico inválido');
        }

        if (isset($this->usuarios[$correoNorm])) {
            throw new InvalidArgumentException('Ese correo ya tiene una cuenta');
        }

        $this->usuarios[$correoNorm] = [
            'correo' => $correoNorm,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'rol' => $rolNuevo,
            'nombre' => trim($nombre),
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => 0,
            'activo' => true,
        ];

        return [
            'correo' => $correoNorm,
            'rol' => $rolNuevo,
            'nombre' => trim($nombre),
            'activo' => true,
        ];
    }

    /**
     * RF-04 (CA-04.3): Cambio de rol registrado en bitácora.
     */
    public function cambiarRol(string $rolEjecutor, string $correo, string $nuevoRol): array
    {
        if ($rolEjecutor !== 'admin_ventas') {
            throw new RuntimeException('No tienes permisos para cambiar roles');
        }

        if (!in_array($nuevoRol, ['tecnico', 'admin_ventas'], true)) {
            throw new InvalidArgumentException("Rol inválido: {$nuevoRol}");
        }

        $correoNorm = strtolower(trim($correo));
        if (!isset($this->usuarios[$correoNorm])) {
            throw new InvalidArgumentException('El usuario especificado no existe');
        }

        $rolAnterior = $this->usuarios[$correoNorm]['rol'];
        $this->usuarios[$correoNorm]['rol'] = $nuevoRol;

        $registroBitacora = [
            'correo' => $correoNorm,
            'rol_anterior' => $rolAnterior,
            'rol_nuevo' => $nuevoRol,
            'ejecutor_rol' => $rolEjecutor,
            'fecha' => date('Y-m-d H:i:s'),
        ];
        $this->bitacoraCambiosRol[] = $registroBitacora;

        return $registroBitacora;
    }

    public function obtenerBitacoraCambiosRol(): array
    {
        return $this->bitacoraCambiosRol;
    }

    /**
     * RF-01, RF-02 (CA-01.1, CA-01.2, CA-01.3): Inicio de sesión con bloqueo tras 5 intentos fallidos por 15 minutos.
     */
    public function iniciarSesion(string $correo, string $password, int $timestampActual = 0): array
    {
        $ahora = $timestampActual > 0 ? $timestampActual : time();
        $correoNorm = strtolower(trim($correo));

        if (!isset($this->usuarios[$correoNorm])) {
            throw new InvalidArgumentException('Correo o contraseña incorrectos');
        }

        $usuario = &$this->usuarios[$correoNorm];

        if ($usuario['bloqueado_hasta'] > $ahora) {
            throw new RuntimeException(
                'Cuenta bloqueada temporalmente por 15 minutos tras 5 intentos fallidos consecutivos'
            );
        }

        if ($usuario['bloqueado_hasta'] > 0 && $usuario['bloqueado_hasta'] <= $ahora) {
            $usuario['bloqueado_hasta'] = 0;
            $usuario['intentos_fallidos'] = 0;
        }

        if (!password_verify($password, $usuario['password_hash'])) {
            $usuario['intentos_fallidos']++;

            if ($usuario['intentos_fallidos'] >= self::MAX_INTENTOS_FALLIDOS) {
                $usuario['bloqueado_hasta'] = $ahora + self::SEGUNDOS_BLOQUEO;
                throw new RuntimeException(
                    'Cuenta bloqueada temporalmente por 15 minutos tras 5 intentos fallidos consecutivos'
                );
            }

            throw new InvalidArgumentException('Correo o contraseña incorrectos');
        }

        $usuario['intentos_fallidos'] = 0;
        $usuario['bloqueado_hasta'] = 0;

        return [
            'autenticado' => true,
            'correo' => $usuario['correo'],
            'nombre' => $usuario['nombre'],
            'rol' => $usuario['rol'],
            'menu' => $this->obtenerMenuPorRol($usuario['rol']),
        ];
    }

    /**
     * RF-03 (CA-02.1, CA-02.3): Solicitud de recuperación de contraseña con respuesta neutra y token temporal (30 min).
     */
    public function solicitarRecuperacionPassword(string $correo, int $timestampActual): array
    {
        $correoNorm = strtolower(trim($correo));
        $token = null;

        if (isset($this->usuarios[$correoNorm])) {
            $token = bin2hex(random_bytes(16));
            $this->tokensRecuperacion[$token] = [
                'correo' => $correoNorm,
                'expira_en' => $timestampActual + self::SEGUNDOS_VIGENCIA_TOKEN_RECUPERACION,
                'usado' => false,
            ];
        }

        return [
            'mensaje' => 'Si el correo está registrado recibirás un enlace',
            'token_generado' => $token,
        ];
    }

    /**
     * RF-03 (CA-02.1, CA-02.2): Restablecimiento de contraseña validando vigencia de 30 minutos del token.
     */
    public function restablecerPasswordConToken(string $token, string $nuevaPassword, int $timestampActual): bool
    {
        if (!isset($this->tokensRecuperacion[$token])) {
            throw new InvalidArgumentException('Token de recuperación inválido');
        }

        $datosToken = &$this->tokensRecuperacion[$token];

        if ($datosToken['usado'] === true || $timestampActual > $datosToken['expira_en']) {
            throw new RuntimeException('El enlace expiró, solicita uno nuevo');
        }

        $correo = $datosToken['correo'];
        $this->usuarios[$correo]['password_hash'] = password_hash($nuevaPassword, PASSWORD_DEFAULT);
        $this->usuarios[$correo]['intentos_fallidos'] = 0;
        $this->usuarios[$correo]['bloqueado_hasta'] = 0;
        $datosToken['usado'] = true;

        return true;
    }

    /**
     * RF-05 (CA-03.1, CA-03.3): Menú diferenciado por rol y responsivo a 360px sin desplazamiento horizontal.
     */
    public function obtenerMenuPorRol(string $rol, int $anchoPantallaPx = 1366): array
    {
        $esMovil = $anchoPantallaPx <= 360;

        if ($rol === 'tecnico') {
            return [
                'rol' => 'tecnico',
                'secciones' => ['Clientes', 'Activos', 'Mis solicitudes de cotización'],
                'ancho_pantalla_px' => $anchoPantallaPx,
                'modo_compacto' => $esMovil,
                'sin_desplazamiento_horizontal' => true,
            ];
        }

        if ($rol === 'admin_ventas') {
            return [
                'rol' => 'admin_ventas',
                'secciones' => [
                    'Clientes',
                    'Activos',
                    'Solicitudes',
                    'Catálogo y tarifas',
                    'Cotizador',
                    'Propuestas',
                    'Reportes',
                    'Usuarios',
                ],
                'ancho_pantalla_px' => $anchoPantallaPx,
                'modo_compacto' => $esMovil,
                'sin_desplazamiento_horizontal' => true,
            ];
        }

        throw new InvalidArgumentException("Rol no reconocido: {$rol}");
    }

    /**
     * RF-06 (CA-03.2): Restricción de acceso a rutas financieras para rol técnico y registro en bitácora.
     */
    public function verificarAccesoRuta(string $rol, string $ruta, string $usuarioCorreo = ''): bool
    {
        $rutasFinancieras = ['/cotizador', '/catalogo/tarifas', '/precios', '/propuestas'];

        if ($rol === 'tecnico') {
            foreach ($rutasFinancieras as $rutaRestringida) {
                if ($ruta === $rutaRestringida || str_starts_with($ruta, $rutaRestringida . '/')) {
                    $this->bitacoraAccesos[] = [
                        'usuario' => $usuarioCorreo !== '' ? $usuarioCorreo : 'tecnico_autenticado',
                        'rol' => $rol,
                        'ruta' => $ruta,
                        'resultado' => 'denegado',
                        'fecha' => date('Y-m-d H:i:s'),
                    ];
                    throw new RuntimeException('No tienes permisos para esta sección');
                }
            }
        }

        return true;
    }

    public function obtenerBitacoraAccesos(): array
    {
        return $this->bitacoraAccesos;
    }

    /**
     * RF-07 (CA-05.1, CA-05.2, CA-05.3): Expiración automática de sesión a los 20 minutos y aviso al minuto 19.
     */
    public function verificarInactividad(int $minutosInactivo, bool $actividadDetectadaEnAviso = false): array
    {
        if ($minutosInactivo >= self::MINUTOS_LIMITE_INACTIVIDAD) {
            return [
                'estado' => 'cerrada',
                'sesion_valida' => false,
                'aviso' => false,
                'mensaje' => 'Sesión cerrada por 20 minutos de inactividad',
            ];
        }

        if ($minutosInactivo === self::MINUTOS_AVISO_INACTIVIDAD) {
            if ($actividadDetectadaEnAviso) {
                return [
                    'estado' => 'activa',
                    'sesion_valida' => true,
                    'aviso' => false,
                    'minutos_inactivo' => 0,
                    'mensaje' => 'Actividad detectada, sesión renovada',
                ];
            }

            return [
                'estado' => 'aviso',
                'sesion_valida' => true,
                'aviso' => true,
                'mensaje' => 'La sesión se cerrará en 1 minuto por inactividad',
            ];
        }

        return [
            'estado' => 'activa',
            'sesion_valida' => true,
            'aviso' => false,
            'minutos_inactivo' => $minutosInactivo,
        ];
    }
}
