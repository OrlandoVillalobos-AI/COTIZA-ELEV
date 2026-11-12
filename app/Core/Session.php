<?php

declare(strict_types=1);

namespace App\Core;

class Session
{
    public const MAX_INACTIVITY_SECONDS = 1200; // 20 minutos estrictos (RF-07 / RNF-10)

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (php_sapi_name() === 'cli') {
                if (!isset($_SESSION)) {
                    $_SESSION = [];
                }
                return;
            }
            if (!headers_sent()) {
                ini_set('session.cookie_httponly', '1');
                ini_set('session.cookie_samesite', 'Lax');
                ini_set('session.use_only_cookies', '1');
                @session_start();
            } else {
                @session_start();
            }
        }
    }

    public static function checkInactivity(int $maxSeconds = self::MAX_INACTIVITY_SECONDS): bool
    {
        self::start();

        if (empty($_SESSION['usuario'])) {
            return true;
        }

        $now = time();
        if (isset($_SESSION['last_activity'])) {
            $inactivo = $now - (int)$_SESSION['last_activity'];
            if ($inactivo > $maxSeconds) {
                self::destroy();
                return false; // Sesión expirada por inactividad
            }
        }

        $_SESSION['last_activity'] = $now;
        return true;
    }

    public static function getRemainingSeconds(int $maxSeconds = self::MAX_INACTIVITY_SECONDS): int
    {
        self::start();
        if (empty($_SESSION['last_activity'])) {
            return $maxSeconds;
        }
        $inactivo = time() - (int)$_SESSION['last_activity'];
        return max(0, $maxSeconds - $inactivo);
    }

    public static function set(string $key, mixed $val): void
    {
        self::start();
        $_SESSION[$key] = $val;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function setUser(array $usuario): void
    {
        self::start();
        $_SESSION['usuario'] = $usuario;
        $_SESSION['last_activity'] = time();
    }

    public static function getUser(): ?array
    {
        self::start();
        return $_SESSION['usuario'] ?? null;
    }

    public static function getRole(): ?string
    {
        $u = self::getUser();
        return $u['rol'] ?? null;
    }

    public static function getUserId(): ?int
    {
        $u = self::getUser();
        return isset($u['id_usuario']) ? (int)$u['id_usuario'] : null;
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                if (!headers_sent()) {
                    setcookie(
                        session_name(),
                        '',
                        time() - 42000,
                        $params['path'],
                        $params['domain'],
                        $params['secure'],
                        $params['httponly']
                    );
                }
            }
            @session_destroy();
        }
    }
}
