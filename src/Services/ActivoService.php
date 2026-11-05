<?php

declare(strict_types=1);

namespace CotizaElev\Services;

use InvalidArgumentException;
use RuntimeException;

/**
 * M03: Registro de Activos e Instalaciones e Historial (ActivoService - Incremento 5)
 *
 * Atiende a: RF-13, RF-14, RF-15
 * Historias de usuario: HU-08, HU-09
 */
class ActivoService
{
    public const TIPOS_PERMITIDOS = ['pasajeros', 'carga', 'montaautos'];
    public const MIN_PARADAS = 2;
    public const MAX_PARADAS = 40;

    /** @var array<int, array<string, mixed>> */
    private array $activos = [];

    /** @var array<int, array<int, array<string, mixed>>> */
    private array $historialPorActivo = [];

    private int $siguienteId = 1;

    /**
     * RF-13, RF-14 (CA-08.1, CA-08.2, CA-08.3, CA-08.4): Registro de activo vinculado a un cliente
     * y validación de rangos técnicos (capacidad_kg > 0 y paradas entre 2 y 40).
     */
    public function registrarActivo(array $datos, bool $confirmarFueraDeRango = false): array
    {
        if (!isset($datos['cliente_id']) || (int)$datos['cliente_id'] <= 0) {
            throw new InvalidArgumentException('Selecciona el cliente al que pertenece el equipo');
        }

        $camposRequeridos = ['tipo', 'marca', 'modelo', 'serie', 'capacidad_kg', 'paradas', 'velocidad', 'anio', 'ubicacion'];
        foreach ($camposRequeridos as $campo) {
            if (!isset($datos[$campo]) || trim((string)$datos[$campo]) === '') {
                throw new InvalidArgumentException("El campo '{$campo}' es obligatorio para el activo");
            }
        }

        $tipo = strtolower(trim((string)$datos['tipo']));
        if (!in_array($tipo, self::TIPOS_PERMITIDOS, true)) {
            throw new InvalidArgumentException("Tipo de elevador no válido: {$tipo}");
        }

        $capacidadKg = (float)$datos['capacidad_kg'];
        $paradas = (int)$datos['paradas'];
        $velocidad = (float)$datos['velocidad'];
        $anio = (int)$datos['anio'];

        $fueraDeRango = ($capacidadKg <= 0) || ($paradas < self::MIN_PARADAS) || ($paradas > self::MAX_PARADAS);

        if ($fueraDeRango && !$confirmarFueraDeRango) {
            throw new InvalidArgumentException(
                'El valor está fuera del rango esperado (2 a 40 paradas y capacidad > 0 kg)'
            );
        }

        $id = $this->siguienteId++;
        $nuevoActivo = [
            'id' => $id,
            'cliente_id' => (int)$datos['cliente_id'],
            'tipo' => $tipo,
            'marca' => trim((string)$datos['marca']),
            'modelo' => trim((string)$datos['modelo']),
            'serie' => trim((string)$datos['serie']),
            'capacidad_kg' => $capacidadKg,
            'paradas' => $paradas,
            'velocidad' => $velocidad,
            'anio' => $anio,
            'ubicacion' => trim((string)$datos['ubicacion']),
            'rango_confirmado' => $fueraDeRango && $confirmarFueraDeRango,
            'sin_advertencia_rango' => !$fueraDeRango,
            'activo' => true,
        ];

        $this->activos[$id] = $nuevoActivo;
        $this->historialPorActivo[$id] = [];

        return $nuevoActivo;
    }

    public function obtenerActivo(int $id): array
    {
        if (!isset($this->activos[$id])) {
            throw new InvalidArgumentException("El activo con ID {$id} no existe");
        }

        return $this->activos[$id];
    }

    /**
     * RF-15: Registra un movimiento comercial o de servicio en el historial del activo.
     */
    public function registrarMovimientoHistorial(int $activoId, array $movimiento): void
    {
        if (!isset($this->activos[$activoId]) || $this->activos[$activoId]['activo'] !== true) {
            throw new RuntimeException('El activo no existe o fue dado de baja');
        }

        $this->historialPorActivo[$activoId][] = [
            'tipo_servicio' => (string)($movimiento['tipo_servicio'] ?? 'Servicio general'),
            'fecha' => (string)($movimiento['fecha'] ?? date('Y-m-d')),
            'estado' => (string)($movimiento['estado'] ?? 'Completado'),
            'estado_comercial' => (string)($movimiento['estado_comercial'] ?? ($movimiento['estado'] ?? 'Aceptada')),
            'folio_propuesta' => (string)($movimiento['folio_propuesta'] ?? ''),
            'monto' => isset($movimiento['monto']) ? round((float)$movimiento['monto'], 2, PHP_ROUND_HALF_UP) : 0.0,
        ];
    }

    /**
     * RF-15 (CA-09.4): Da de baja lógica un activo.
     */
    public function darDeBajaActivo(int $activoId): void
    {
        if (!isset($this->activos[$activoId])) {
            throw new RuntimeException('El activo no existe o fue dado de baja');
        }

        $this->activos[$activoId]['activo'] = false;
    }

    /**
     * RF-15 (CA-09.1, CA-09.2, CA-09.3, CA-09.4): Consulta historial ordenado de más reciente a más antiguo,
     * ocultando importes y folios comerciales al rol técnico y mostrándolos a admin_ventas.
     */
    public function consultarHistorialActivo(int $activoId, string $rol): array
    {
        if (!isset($this->activos[$activoId]) || $this->activos[$activoId]['activo'] !== true) {
            throw new RuntimeException('El activo no existe o fue dado de baja');
        }

        $movimientos = $this->historialPorActivo[$activoId] ?? [];

        if (empty($movimientos)) {
            return [
                'activo_id' => $activoId,
                'mensaje' => 'Este activo todavía no tiene servicios registrados',
                'movimientos' => [],
            ];
        }

        // Ordenar de más reciente a más antiguo
        usort(
            $movimientos,
            static fn(array $a, array $b): int => strcmp($b['fecha'], $a['fecha'])
        );

        if ($rol === 'tecnico') {
            $movimientosFiltrados = [];
            foreach ($movimientos as $mov) {
                $movimientosFiltrados[] = [
                    'tipo_servicio' => $mov['tipo_servicio'],
                    'fecha' => $mov['fecha'],
                    'estado' => $mov['estado'],
                ];
            }

            return [
                'activo_id' => $activoId,
                'rol' => $rol,
                'movimientos' => $movimientosFiltrados,
            ];
        }

        if ($rol === 'admin_ventas') {
            return [
                'activo_id' => $activoId,
                'rol' => $rol,
                'movimientos' => $movimientos,
            ];
        }

        throw new InvalidArgumentException("Rol no reconocido: {$rol}");
    }
}
