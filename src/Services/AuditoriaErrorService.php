<?php

declare(strict_types=1);

namespace CotizaElev\Services;

use DomainException;
use Throwable;

/**
 * M09: Bitácora de auditoría inmutable, manejo amigable de errores en español
 * y validación de operaciones sobre registros vigentes (RF-41, RF-42, RF-43; HU-14, HU-24).
 */
final class AuditoriaErrorService
{
    /**
     * Bitácora de auditoría inmutable (solo inserción y consulta, sin edición ni borrado).
     *
     * @var list<array{id: int, usuario: string, accion: string, modulo: string, fecha_hora: string, detalles: array<string, mixed>}>
     */
    private array $registros = [];

    /**
     * Registra un evento crítico en la bitácora en orden cronológico (RF-41, CA-24.1).
     *
     * @param array<string, mixed> $detalles
     * @return array{id: int, usuario: string, accion: string, modulo: string, fecha_hora: string, detalles: array<string, mixed>}
     */
    public function registrarEvento(
        string $usuario,
        string $accion,
        string $modulo,
        string $fechaHora,
        array $detalles = []
    ): array {
        $evento = [
            'id' => count($this->registros) + 1,
            'usuario' => trim($usuario),
            'accion' => trim($accion),
            'modulo' => trim($modulo),
            'fecha_hora' => trim($fechaHora),
            'detalles' => $detalles,
        ];

        $this->registros[] = $evento;

        return $evento;
    }

    /**
     * Consulta la bitácora filtrando por usuario y rango de fechas, devolviendo bloques paginados de 50 registros (CA-24.1, CA-24.3).
     *
     * @return array{pagina: int, tamano_bloque: int, total_registros: int, total_paginas: int, registros: list<array<string, mixed>>}
     */
    public function consultarBitacora(
        ?string $usuario = null,
        ?string $fechaInicio = null,
        ?string $fechaFin = null,
        int $pagina = 1,
        int $tamanoBloque = 50
    ): array {
        $filtrados = array_values(array_filter(
            $this->registros,
            static function (array $ev) use ($usuario, $fechaInicio, $fechaFin): bool {
                if ($usuario !== null && trim($usuario) !== '' && $ev['usuario'] !== trim($usuario)) {
                    return false;
                }
                $fechaEv = substr($ev['fecha_hora'], 0, 10);
                if ($fechaInicio !== null && trim($fechaInicio) !== '' && $fechaEv < trim($fechaInicio)) {
                    return false;
                }
                if ($fechaFin !== null && trim($fechaFin) !== '' && $fechaEv > trim($fechaFin)) {
                    return false;
                }
                return true;
            }
        ));

        usort(
            $filtrados,
            static fn(array $a, array $b): int => [$a['fecha_hora'], $a['id']] <=> [$b['fecha_hora'], $b['id']]
        );

        $tamano = max(1, $tamanoBloque);
        $paginaActual = max(1, $pagina);
        $total = count($filtrados);
        $totalPaginas = (int) max(1, ceil($total / $tamano));
        $offset = ($paginaActual - 1) * $tamano;

        return [
            'pagina' => $paginaActual,
            'tamano_bloque' => $tamano,
            'total_registros' => $total,
            'total_paginas' => $totalPaginas,
            'registros' => array_slice($filtrados, $offset, $tamano),
        ];
    }

    /**
     * Captura una excepción técnica, la registra en la bitácora y devuelve un mensaje amigable en español sin trazas (RF-42, CA-24.2).
     *
     * @return array{exito: false, mensaje_usuario: string, codigo_referencia: int}
     */
    public function manejarExcepcion(
        Throwable $e,
        string $usuario = 'sistema',
        string $contexto = 'guardar'
    ): array {
        $evento = $this->registrarEvento(
            $usuario,
            'FALLO_TECNICO_' . strtoupper(trim($contexto)),
            'Sistema',
            date('Y-m-d H:i:s'),
            [
                'clase_excepcion' => $e::class,
                'mensaje_interno' => $e->getMessage(),
            ]
        );

        $mensajeAmigable = match (strtolower(trim($contexto))) {
            'guardar', 'conexion' => 'No se pudo guardar por un problema de conexión, inténtalo de nuevo en unos segundos',
            'pdf' => 'No se pudo generar el documento, inténtalo de nuevo',
            default => 'Ocurrió un inconveniente al procesar tu solicitud, inténtalo nuevamente en unos segundos',
        };

        return [
            'exito' => false,
            'mensaje_usuario' => $mensajeAmigable,
            'codigo_referencia' => $evento['id'],
        ];
    }

    /**
     * Valida que un registro exista, esté vigente, pertenezca al cliente esperado y no haya sido cotizado previamente (RF-43, CA-14.5).
     *
     * @param array<string, mixed>|null $registro
     */
    public function validarOperacionSobreRegistro(
        ?array $registro,
        string $tipoEntidad,
        ?int $clienteEsperadoId = null,
        ?string $usuarioActual = null
    ): void {
        if ($registro === null || $registro === []) {
            throw new DomainException('El registro solicitado no existe o ya fue cancelado');
        }

        $estado = strtolower(trim((string) ($registro['estado'] ?? 'activo')));
        $activo = (bool) ($registro['activo'] ?? true);

        if (!$activo || in_array($estado, ['cancelado', 'cancelada', 'baja', 'inactivo', 'eliminado'], true)) {
            throw new DomainException('El registro solicitado no existe o ya fue cancelado');
        }

        if (
            strtolower(trim($tipoEntidad)) === 'activo' &&
            $clienteEsperadoId !== null &&
            (int) ($registro['cliente_id'] ?? 0) !== $clienteEsperadoId
        ) {
            throw new DomainException('El activo seleccionado no pertenece al cliente');
        }

        if (strtolower(trim($tipoEntidad)) === 'solicitud') {
            $cotizadaPor = trim((string) ($registro['cotizada_por'] ?? ''));
            if ($estado === 'cotizada' || ($cotizadaPor !== '' && $cotizadaPor !== trim((string) $usuarioActual))) {
                throw new DomainException('Esta solicitud ya fue cotizada por otro usuario');
            }
        }
    }
}
