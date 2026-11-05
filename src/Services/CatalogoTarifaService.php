<?php

declare(strict_types=1);

namespace CotizaElev\Services;

use InvalidArgumentException;
use RuntimeException;

/**
 * M04: Catálogo Comercial y Tarifas (CatalogoTarifaService)
 *
 * Atiende a: RF-16, RF-17, RF-18, RF-19
 * Historias de usuario: HU-10, HU-11
 */
class CatalogoTarifaService
{
    /** @var array<int, array<string, mixed>> */
    private array $tarifas = [];

    private int $siguienteId = 1;

    /**
     * RF-16: Alta de equipo nuevo en catálogo comercial.
     */
    public function registrarEquipoNuevo(array $datos): array
    {
        $datos['categoria'] = 'equipo_nuevo';
        return $this->registrarConcepto($datos);
    }

    /**
     * RF-17, RF-18 (CA-10.1, CA-10.2, CA-10.3): Registro de concepto con precio unitario > 0 y vigencia.
     */
    public function registrarConcepto(array $datos): array
    {
        $campos = ['clave', 'descripcion', 'unidad', 'precio_unitario', 'vigencia_inicio', 'vigencia_fin'];
        foreach ($campos as $campo) {
            if (!isset($datos[$campo]) || trim((string)$datos[$campo]) === '') {
                throw new InvalidArgumentException("El campo '{$campo}' es obligatorio en el catálogo");
            }
        }

        if (!is_numeric($datos['precio_unitario']) || (float)$datos['precio_unitario'] <= 0) {
            throw new InvalidArgumentException('El precio debe ser mayor que cero');
        }

        $inicio = trim((string)$datos['vigencia_inicio']);
        $fin = trim((string)$datos['vigencia_fin']);
        if ($fin < $inicio) {
            throw new InvalidArgumentException('La fecha de fin de vigencia no puede ser menor a la de inicio');
        }

        $id = $this->siguienteId++;
        $registro = [
            'id' => $id,
            'clave' => strtoupper(trim((string)$datos['clave'])),
            'descripcion' => trim((string)$datos['descripcion']),
            'unidad' => trim((string)$datos['unidad']),
            'categoria' => (string)($datos['categoria'] ?? 'servicio_refaccion'),
            'precio_unitario' => round((float)$datos['precio_unitario'], 2, PHP_ROUND_HALF_UP),
            'vigencia_inicio' => $inicio,
            'vigencia_fin' => $fin,
            'activo' => true,
        ];

        $this->tarifas[] = $registro;

        return $registro;
    }

    /**
     * RF-18 (CA-10.3): Obtiene la tarifa vigente según la fecha de emisión para integración con CalculoMotorService (M07).
     */
    public function obtenerPrecioVigente(string $clave, string $fechaEmision): array
    {
        $claveNorm = strtoupper(trim($clave));
        $fecha = trim($fechaEmision);
        $candidatos = [];

        foreach ($this->tarifas as $tarifa) {
            if (
                $tarifa['activo'] === true
                && $tarifa['clave'] === $claveNorm
                && $tarifa['vigencia_inicio'] <= $fecha
                && $tarifa['vigencia_fin'] >= $fecha
            ) {
                $candidatos[] = $tarifa;
            }
        }

        if (empty($candidatos)) {
            throw new RuntimeException("No existe tarifa vigente para la clave {$claveNorm} en la fecha {$fecha}");
        }

        // Si hay más de una que cubra el rango, toma la de vigencia_inicio más reciente
        usort(
            $candidatos,
            static fn(array $a, array $b): int => strcmp($b['vigencia_inicio'], $a['vigencia_inicio'])
        );

        return $candidatos[0];
    }

    /**
     * RF-05 / RF-17 (CA-10.1): Lista conceptos ocultando precios para el rol técnico.
     */
    public function listarParaRol(string $rol): array
    {
        if ($rol === 'tecnico') {
            $vistaTecnica = [];
            foreach ($this->tarifas as $tarifa) {
                if ($tarifa['activo'] !== true) {
                    continue;
                }
                $vistaTecnica[] = [
                    'clave' => $tarifa['clave'],
                    'descripcion' => $tarifa['descripcion'],
                    'unidad' => $tarifa['unidad'],
                    'categoria' => $tarifa['categoria'],
                ];
            }
            return $vistaTecnica;
        }

        if ($rol === 'admin_ventas') {
            return array_values(array_filter(
                $this->tarifas,
                static fn(array $t): bool => $t['activo'] === true
            ));
        }

        throw new InvalidArgumentException("Rol no válido: {$rol}");
    }

    /**
     * RF-19 (CA-11.1, CA-11.2, CA-11.3): Importación masiva de precios vía CSV con reporte de actualizados y rechazados.
     */
    public function importarCsv(string $contenidoCsv): array
    {
        $lineas = array_values(array_filter(
            array_map('trim', explode("\n", trim($contenidoCsv))),
            static fn(string $l): bool => $l !== ''
        ));

        if (empty($lineas)) {
            throw new InvalidArgumentException('El archivo no contiene registros para procesar');
        }

        $encabezados = array_map(
            static fn(string $col): string => strtolower(trim($col)),
            str_getcsv($lineas[0])
        );

        if (!in_array('clave', $encabezados, true)) {
            throw new InvalidArgumentException('Falta la columna obligatoria: clave');
        }
        if (!in_array('precio', $encabezados, true)) {
            throw new InvalidArgumentException('Falta la columna obligatoria: precio');
        }

        if (count($lineas) === 1) {
            throw new InvalidArgumentException('El archivo no contiene registros para procesar');
        }

        $idxClave = (int)array_search('clave', $encabezados, true);
        $idxPrecio = (int)array_search('precio', $encabezados, true);
        $idxDescripcion = array_search('descripcion', $encabezados, true);
        $idxUnidad = array_search('unidad', $encabezados, true);

        $actualizados = 0;
        $rechazados = 0;
        $errores = [];

        for ($i = 1, $total = count($lineas); $i < $total; $i++) {
            $numFila = $i + 1;
            $columnas = str_getcsv($lineas[$i]);
            $clave = strtoupper(trim((string)($columnas[$idxClave] ?? '')));
            $precioRaw = trim((string)($columnas[$idxPrecio] ?? ''));

            if ($clave === '') {
                $rechazados++;
                $errores[] = "Fila {$numFila}: clave vacía";
                continue;
            }

            if ($precioRaw === '' || !is_numeric($precioRaw) || (float)$precioRaw <= 0) {
                $rechazados++;
                $errores[] = "Fila {$numFila} ({$clave}): precio inválido '{$precioRaw}'";
                continue;
            }

            $nuevoPrecio = round((float)$precioRaw, 2, PHP_ROUND_HALF_UP);
            $encontrado = false;

            foreach ($this->tarifas as &$tarifa) {
                if ($tarifa['clave'] === $clave && $tarifa['activo'] === true) {
                    $tarifa['precio_unitario'] = $nuevoPrecio;
                    $encontrado = true;
                }
            }
            unset($tarifa);

            if (!$encontrado) {
                $this->registrarConcepto([
                    'clave' => $clave,
                    'descripcion' => $idxDescripcion !== false && isset($columnas[$idxDescripcion])
                        ? (string)$columnas[$idxDescripcion]
                        : "Concepto importado {$clave}",
                    'unidad' => $idxUnidad !== false && isset($columnas[$idxUnidad])
                        ? (string)$columnas[$idxUnidad]
                        : 'pza',
                    'precio_unitario' => $nuevoPrecio,
                    'vigencia_inicio' => '2026-01-01',
                    'vigencia_fin' => '2026-12-31',
                ]);
            }

            $actualizados++;
        }

        return [
            'actualizados' => $actualizados,
            'rechazados' => $rechazados,
            'errores' => $errores,
            'mensaje' => "{$actualizados} registros actualizados y {$rechazados} rechazados",
        ];
    }
}
