<?php

declare(strict_types=1);

namespace CotizaElev\Services;

use InvalidArgumentException;

/**
 * M03: Registro de Activos e Instalaciones (ActivoService)
 *
 * Atiende a: RF-13, RF-14
 * Historias de usuario: HU-08
 */
class ActivoService
{
    public const TIPOS_PERMITIDOS = ['pasajeros', 'carga', 'montaautos'];
    public const MIN_PARADAS = 2;
    public const MAX_PARADAS = 40;

    /** @var array<int, array<string, mixed>> */
    private array $activos = [];

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

        return $nuevoActivo;
    }

    public function obtenerActivo(int $id): array
    {
        if (!isset($this->activos[$id])) {
            throw new InvalidArgumentException("El activo con ID {$id} no existe");
        }

        return $this->activos[$id];
    }
}
