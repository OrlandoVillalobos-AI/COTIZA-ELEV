<?php

declare(strict_types=1);

namespace Tests\Unit;

require_once __DIR__ . '/../../src/Services/AuthRbacService.php';

use CotizaElev\Services\AuthRbacService;
use InvalidArgumentException;
use RuntimeException;

/**
 * Suite de pruebas unitarias para AuthRbacService (M01 - Incremento 2)
 * Trazabilidad: HU-01, HU-03, HU-04, HU-05
 */
class AuthRbacServiceTest
{
    private int $ejecutadas = 0;
    private int $pasadas = 0;

    public function ejecutarTodas(): array
    {
        $this->ejecutadas = 0;
        $this->pasadas = 0;

        $this->testInicioSesionNormal_CA01_1();
        $this->testInicioSesionCredencialesInvalidas_CA01_2();
        $this->testBloqueoPorCincoIntentosFallidos_CA01_3();
        $this->testMenuTecnicoSinPreciosNiCotizador_CA03_1();
        $this->testBloqueoAccesoUrlPreciosTecnicoYBitacora_CA03_2();
        $this->testMenuResponsivo360pxSinScrollHorizontal_CA03_3();
        $this->testAltaUsuarioDuplicadoYCambioRol_CA04_1_2_3();
        $this->testExpiracionSesionInactividad19y20Min_CA05_1_2_3();

        return [
            'ejecutadas' => $this->ejecutadas,
            'pasadas' => $this->pasadas,
        ];
    }

    private function testInicioSesionNormal_CA01_1(): void
    {
        $this->ejecutadas++;
        $auth = new AuthRbacService();
        $res = $auth->iniciarSesion('admin@elevese.mx', 'Admin2026!', 1000);

        if ($res['autenticado'] === true && $res['rol'] === 'admin_ventas') {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-01.1');
        }
    }

    private function testInicioSesionCredencialesInvalidas_CA01_2(): void
    {
        $this->ejecutadas++;
        $auth = new AuthRbacService();

        try {
            $auth->iniciarSesion('admin@elevese.mx', 'ClaveErronea', 1000);
            throw new RuntimeException('Debió fallar CA-01.2');
        } catch (InvalidArgumentException $e) {
            if ($e->getMessage() === 'Correo o contraseña incorrectos') {
                $this->pasadas++;
                return;
            }
            throw $e;
        }
    }

    private function testBloqueoPorCincoIntentosFallidos_CA01_3(): void
    {
        $this->ejecutadas++;
        $auth = new AuthRbacService();
        $t0 = 10000;

        for ($i = 1; $i <= 4; $i++) {
            try {
                $auth->iniciarSesion('tecnico@elevese.mx', 'Mal' . $i, $t0);
            } catch (InvalidArgumentException) {
                // Esperado en intentos 1..4
            }
        }

        $bloqueadoEnQuinto = false;
        try {
            $auth->iniciarSesion('tecnico@elevese.mx', 'Mal5', $t0);
        } catch (RuntimeException $e) {
            $bloqueadoEnQuinto = str_contains($e->getMessage(), '15 minutos');
        }

        // Durante el bloqueo (ej. minuto 10 = +600s) rechaza incluso con contraseña correcta
        $rechazadoDuranteBloqueo = false;
        try {
            $auth->iniciarSesion('tecnico@elevese.mx', 'Tec2026!', $t0 + 600);
        } catch (RuntimeException $e) {
            $rechazadoDuranteBloqueo = str_contains($e->getMessage(), '15 minutos');
        }

        // Después de 15 minutos (+901s) permite acceso normal
        $resDespues = $auth->iniciarSesion('tecnico@elevese.mx', 'Tec2026!', $t0 + 901);

        if ($bloqueadoEnQuinto && $rechazadoDuranteBloqueo && $resDespues['autenticado'] === true) {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-01.3');
        }
    }

    private function testMenuTecnicoSinPreciosNiCotizador_CA03_1(): void
    {
        $this->ejecutadas++;
        $auth = new AuthRbacService();
        $menu = $auth->obtenerMenuPorRol('tecnico');

        $secciones = $menu['secciones'];
        $tieneBasicas = in_array('Clientes', $secciones, true)
            && in_array('Activos', $secciones, true)
            && in_array('Mis solicitudes de cotización', $secciones, true);
        $sinPreciosNiCotizador = !in_array('Catálogo y tarifas', $secciones, true)
            && !in_array('Cotizador', $secciones, true);

        if ($tieneBasicas && $sinPreciosNiCotizador) {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-03.1');
        }
    }

    private function testBloqueoAccesoUrlPreciosTecnicoYBitacora_CA03_2(): void
    {
        $this->ejecutadas++;
        $auth = new AuthRbacService();
        $denegado = false;

        try {
            $auth->verificarAccesoRuta('tecnico', '/catalogo/tarifas', 'tecnico@elevese.mx');
        } catch (RuntimeException $e) {
            $denegado = ($e->getMessage() === 'No tienes permisos para esta sección');
        }

        $bitacora = $auth->obtenerBitacoraAccesos();
        if ($denegado && count($bitacora) === 1 && $bitacora[0]['ruta'] === '/catalogo/tarifas') {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-03.2');
        }
    }

    private function testMenuResponsivo360pxSinScrollHorizontal_CA03_3(): void
    {
        $this->ejecutadas++;
        $auth = new AuthRbacService();
        $menu = $auth->obtenerMenuPorRol('tecnico', 360);

        if ($menu['sin_desplazamiento_horizontal'] === true && $menu['modo_compacto'] === true) {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-03.3');
        }
    }

    private function testAltaUsuarioDuplicadoYCambioRol_CA04_1_2_3(): void
    {
        $this->ejecutadas++;
        $auth = new AuthRbacService();

        // CA-04.1: Alta de usuario nuevo
        $nuevo = $auth->registrarUsuario(
            'admin_ventas',
            'nuevo.tec@elevese.mx',
            'Pass2026!',
            'tecnico',
            'Carlos Méndez'
        );

        // CA-04.2: Correo duplicado
        $duplicadoDetectado = false;
        try {
            $auth->registrarUsuario(
                'admin_ventas',
                'nuevo.tec@elevese.mx',
                'OtraPass!',
                'tecnico',
                'Otro Nombre'
            );
        } catch (InvalidArgumentException $e) {
            $duplicadoDetectado = ($e->getMessage() === 'Ese correo ya tiene una cuenta');
        }

        // CA-04.3: Cambio de rol con registro en bitácora
        $cambio = $auth->cambiarRol('admin_ventas', 'nuevo.tec@elevese.mx', 'admin_ventas');
        $bitacora = $auth->obtenerBitacoraCambiosRol();

        if (
            $nuevo['rol'] === 'tecnico'
            && $duplicadoDetectado
            && $cambio['rol_nuevo'] === 'admin_ventas'
            && count($bitacora) === 1
        ) {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-04.1/04.2/04.3');
        }
    }

    private function testExpiracionSesionInactividad19y20Min_CA05_1_2_3(): void
    {
        $this->ejecutadas++;
        $auth = new AuthRbacService();

        // Minuto 19 sin actividad muestra aviso
        $aviso19 = $auth->verificarInactividad(19, false);
        // CA-05.2: Actividad en minuto 19 renueva sesión
        $renovada19 = $auth->verificarInactividad(19, true);
        // CA-05.1 y CA-05.3: Minuto 20 cierra sesión
        $cerrada20 = $auth->verificarInactividad(20, false);

        if (
            $aviso19['estado'] === 'aviso'
            && $renovada19['estado'] === 'activa'
            && $renovada19['aviso'] === false
            && $cerrada20['estado'] === 'cerrada'
            && $cerrada20['mensaje'] === 'Sesión cerrada por 20 minutos de inactividad'
        ) {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-05.1/05.2/05.3');
        }
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $res = (new AuthRbacServiceTest())->ejecutarTodas();
    echo "AuthRbacServiceTest (inc2): {$res['pasadas']}/{$res['ejecutadas']} pasadas\n";
    exit($res['pasadas'] === $res['ejecutadas'] ? 0 : 1);
}
