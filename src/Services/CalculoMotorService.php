<?php

declare(strict_types=1);

namespace CotizaElev\Services;

use InvalidArgumentException;
use RuntimeException;

/**
 * M07: Motor de Cálculo Comercial (CalculoMotorService)
 * 
 * Componente de dominio puro encargado de la lógica matemática de cotizaciones:
 * cálculo de importes por partida, subtotal acumulado, descuentos, cálculo de IVA (16%),
 * total a 2 decimales, tabuladores de equipo nuevo/modernización, pólizas preventivas
 * y mantenimientos correctivos.
 * 
 * Atiende a: RF-27, RF-28, RF-29, RF-30 / HU-15, HU-16, HU-17, HU-18.
 */
class CalculoMotorService
{
    public const TASA_IVA_DEFAULT = 0.16;

    /**
     * Calcula el importe de una partida individual (cantidad * precio unitario).
     * 
     * @param float|int $cantidad Cantidad solicitada (debe ser mayor a 0)
     * @param float|int $precioUnitario Precio unitario vigente (no puede ser negativo)
     * @return float Importe redondeado a 2 decimales
     * @throws InvalidArgumentException Si la cantidad es <= 0 o precio < 0
     */
    public function calcularImportePartida(float|int $cantidad, float|int $precioUnitario): float
    {
        if ($cantidad <= 0) {
            throw new InvalidArgumentException("La cantidad debe ser mayor a 0. Recibido: {$cantidad}");
        }

        if ($precioUnitario < 0) {
            throw new InvalidArgumentException("El precio unitario no puede ser negativo. Recibido: {$precioUnitario}");
        }

        return round((float)$cantidad * (float)$precioUnitario, 2, PHP_ROUND_HALF_UP);
    }

    /**
     * Calcula el desglose completo de una cotización a partir de sus partidas.
     * Conserva 100% compatibilidad con el Incremento 1 y soporta descuento opcional.
     * 
     * @param array $partidas Lista de partidas. Cada partida debe tener 'cantidad' y 'precio_unitario'.
     * @param float $tasaIva Tasa impositiva de IVA (por defecto 0.16)
     * @param float $montoDescuento Monto de descuento opcional (por defecto 0.0)
     * @return array Desglose financiero con subtotal, monto_iva, total y detalle de partidas.
     * @throws InvalidArgumentException Si faltan campos requeridos o hay valores inválidos.
     */
    public function calcularTotales(array $partidas, float $tasaIva = self::TASA_IVA_DEFAULT, float $montoDescuento = 0.0): array
    {
        if ($tasaIva < 0) {
            throw new InvalidArgumentException("La tasa de IVA no puede ser negativa. Recibido: {$tasaIva}");
        }

        $subtotal = 0.0;
        $desglosePartidas = [];

        foreach ($partidas as $indice => $partida) {
            if (!is_array($partida)) {
                throw new InvalidArgumentException("La partida en la posición {$indice} debe ser un arreglo asociativo.");
            }

            if (!isset($partida['cantidad']) || !isset($partida['precio_unitario'])) {
                throw new InvalidArgumentException("La partida en la posición {$indice} debe incluir 'cantidad' y 'precio_unitario'.");
            }

            if (!is_numeric($partida['cantidad']) || !is_numeric($partida['precio_unitario'])) {
                throw new InvalidArgumentException("La cantidad y precio_unitario deben ser valores numéricos en la partida {$indice}.");
            }

            $cantidad = (float)$partida['cantidad'];
            $precioUnitario = (float)$partida['precio_unitario'];
            $descripcion = (string)($partida['descripcion'] ?? "Partida #" . ($indice + 1));

            $importe = $this->calcularImportePartida($cantidad, $precioUnitario);
            $subtotal += $importe;

            $desglosePartidas[] = [
                'posicion' => $indice + 1,
                'descripcion' => $descripcion,
                'cantidad' => $cantidad,
                'precio_unitario' => round($precioUnitario, 2, PHP_ROUND_HALF_UP),
                'importe' => $importe
            ];
        }

        $subtotal = round($subtotal, 2, PHP_ROUND_HALF_UP);

        if ($montoDescuento < 0) {
            throw new InvalidArgumentException("El descuento no puede ser negativo.");
        }
        if ($montoDescuento > $subtotal) {
            throw new InvalidArgumentException("El descuento no puede ser mayor que el subtotal");
        }

        $baseGravable = round($subtotal - $montoDescuento, 2, PHP_ROUND_HALF_UP);
        $montoIva = round($baseGravable * $tasaIva, 2, PHP_ROUND_HALF_UP);
        $total = round($baseGravable + $montoIva, 2, PHP_ROUND_HALF_UP);

        return [
            'numero_partidas' => count($desglosePartidas),
            'subtotal' => $subtotal,
            'descuento' => round($montoDescuento, 2, PHP_ROUND_HALF_UP),
            'base_gravable' => $baseGravable,
            'tasa_iva' => $tasaIva,
            'monto_iva' => $montoIva,
            'total' => $total,
            'partidas' => $desglosePartidas
        ];
    }

    /**
     * Calcula totales aplicando descuento comercial con validaciones de límite y confirmación del 100%
     * (RF-28 / HU-16: CA-16.1, CA-16.2, CA-16.3).
     *
     * @param array $partidas
     * @param float $montoDescuento
     * @param float $tasaIva
     * @param bool $confirmarDescuentoCien
     * @return array<string, mixed>
     */
    public function calcularTotalesConDescuento(
        array $partidas,
        float $montoDescuento = 0.0,
        float $tasaIva = self::TASA_IVA_DEFAULT,
        bool $confirmarDescuentoCien = false
    ): array {
        $base = $this->calcularTotales($partidas, $tasaIva, 0.0);
        $subtotal = $base['subtotal'];

        if ($montoDescuento < 0) {
            throw new InvalidArgumentException("El descuento no puede ser negativo");
        }

        if ($montoDescuento > $subtotal) {
            throw new InvalidArgumentException("El descuento no puede ser mayor que el subtotal");
        }

        if ($subtotal > 0 && abs($montoDescuento - $subtotal) < 0.001 && !$confirmarDescuentoCien) {
            throw new RuntimeException("Se requiere confirmación explícita para aplicar un descuento del 100%");
        }

        return $this->calcularTotales($partidas, $tasaIva, $montoDescuento);
    }

    /**
     * Calcula partida de venta de equipo nuevo o modernización desde reglas de catálogo sin precio manual
     * (RF-27 / HU-15: CA-15.1, CA-15.3).
     *
     * @param string $tipo 'equipo_nuevo' o 'modernizacion'
     * @param int $capacidadKg
     * @param int $paradas
     * @param array $catalogoEquipos
     * @return array<string, mixed>
     */
    public function calcularPartidaEquipoOModernizacion(
        string $tipo,
        int $capacidadKg,
        int $paradas,
        array $catalogoEquipos
    ): array {
        $precioEncontrado = null;
        $modeloDescripcion = null;

        $llaveCompuesta = "{$tipo}:{$capacidadKg}:{$paradas}";
        $llaveSimple = "{$capacidadKg}:{$paradas}";

        if (isset($catalogoEquipos[$llaveCompuesta])) {
            $item = $catalogoEquipos[$llaveCompuesta];
            $precioEncontrado = is_array($item) ? (float)$item['precio'] : (float)$item;
            $modeloDescripcion = is_array($item) && isset($item['descripcion'])
                ? (string)$item['descripcion']
                : ucfirst(str_replace('_', ' ', $tipo)) . " {$capacidadKg}kg {$paradas} paradas";
        } elseif (isset($catalogoEquipos[$llaveSimple])) {
            $item = $catalogoEquipos[$llaveSimple];
            $precioEncontrado = is_array($item) ? (float)$item['precio'] : (float)$item;
            $modeloDescripcion = is_array($item) && isset($item['descripcion'])
                ? (string)$item['descripcion']
                : ucfirst(str_replace('_', ' ', $tipo)) . " {$capacidadKg}kg {$paradas} paradas";
        } else {
            foreach ($catalogoEquipos as $entrada) {
                if (!is_array($entrada)) {
                    continue;
                }
                $coincideTipo = !isset($entrada['tipo']) || $entrada['tipo'] === $tipo;
                if (
                    $coincideTipo &&
                    isset($entrada['capacidad_kg'], $entrada['paradas'], $entrada['precio']) &&
                    (int)$entrada['capacidad_kg'] === $capacidadKg &&
                    (int)$entrada['paradas'] === $paradas
                ) {
                    $precioEncontrado = (float)$entrada['precio'];
                    $modeloDescripcion = (string)($entrada['descripcion'] ?? (ucfirst(str_replace('_', ' ', $tipo)) . " {$capacidadKg}kg {$paradas} paradas"));
                    break;
                }
            }
        }

        if ($precioEncontrado === null) {
            throw new RuntimeException("No hay precio para esa combinación; selecciona otro modelo o solicita el alta de la tarifa");
        }

        $importe = $this->calcularImportePartida(1, $precioEncontrado);

        return [
            'tipo' => $tipo,
            'capacidad_kg' => $capacidadKg,
            'paradas' => $paradas,
            'descripcion' => $modeloDescripcion,
            'cantidad' => 1,
            'precio_unitario' => $importe,
            'importe' => $importe,
            'precio_bloqueado' => true,
        ];
    }

    /**
     * Calcula una póliza de mantenimiento preventivo por periodicidad y visitas al año
     * (RF-29 / HU-17: CA-17.1, CA-17.2, CA-17.3).
     *
     * @param string $periodicidad
     * @param int $visitasAnuales
     * @param float $costoPorVisita
     * @return array<string, mixed>
     */
    public function calcularPolizaPreventiva(string $periodicidad, int $visitasAnuales, float $costoPorVisita): array
    {
        if ($visitasAnuales < 1) {
            throw new InvalidArgumentException("El número de visitas debe ser al menos 1");
        }

        if ($costoPorVisita < 0) {
            throw new InvalidArgumentException("El costo por visita no puede ser negativo");
        }

        $mapaEsperado = [
            'mensual' => 12,
            'bimestral' => 6,
            'trimestral' => 4,
            'cuatrimestral' => 3,
            'semestral' => 2,
            'anual' => 1,
        ];

        $clave = mb_strtolower(trim($periodicidad));
        $visitasEsperadas = $mapaEsperado[$clave] ?? null;
        $coincide = ($visitasEsperadas === null) || ($visitasEsperadas === $visitasAnuales);
        $aviso = null;

        if (!$coincide) {
            $aviso = "La periodicidad {$periodicidad} sugiere {$visitasEsperadas} visitas anuales, pero se indicaron {$visitasAnuales}";
        }

        $costoAnual = $this->calcularImportePartida($visitasAnuales, $costoPorVisita);
        $desglose = $this->calcularTotales([
            [
                'descripcion' => "Póliza preventiva ({$periodicidad}, {$visitasAnuales} visitas/año)",
                'cantidad' => $visitasAnuales,
                'precio_unitario' => $costoPorVisita,
            ]
        ]);

        return [
            'periodicidad' => $periodicidad,
            'visitas_anuales' => $visitasAnuales,
            'costo_por_visita' => round($costoPorVisita, 2, PHP_ROUND_HALF_UP),
            'costo_anual' => $costoAnual,
            'coincide_periodicidad' => $coincide,
            'aviso_periodicidad' => $aviso,
            'subtotal' => $desglose['subtotal'],
            'monto_iva' => $desglose['monto_iva'],
            'total' => $desglose['total'],
        ];
    }

    /**
     * Calcula una cotización de mantenimiento correctivo con diagnóstico, refacciones y mano de obra
     * (RF-30 / HU-18: CA-18.1, CA-18.2, CA-18.3).
     *
     * @param string $diagnostico
     * @param array $refacciones
     * @param float $horasManoObra
     * @param float $tarifaHora
     * @return array<string, mixed>
     */
    public function calcularMantenimientoCorrectivo(
        string $diagnostico,
        array $refacciones,
        float $horasManoObra,
        float $tarifaHora
    ): array {
        if (trim($diagnostico) === '' || (empty($refacciones) && $horasManoObra <= 0)) {
            throw new InvalidArgumentException("El mantenimiento correctivo requiere un diagnóstico y al menos una partida");
        }

        if ($horasManoObra < 0 || $tarifaHora < 0) {
            throw new InvalidArgumentException("Las horas de mano de obra y la tarifa no pueden ser negativas");
        }

        $partidas = [];
        foreach ($refacciones as $ref) {
            $partidas[] = [
                'descripcion' => (string)($ref['descripcion'] ?? 'Refacción correctiva'),
                'cantidad' => (float)$ref['cantidad'],
                'precio_unitario' => (float)$ref['precio_unitario'],
            ];
        }

        $importeManoObra = 0.0;
        if ($horasManoObra > 0) {
            $importeManoObra = $this->calcularImportePartida($horasManoObra, $tarifaHora);
            $partidas[] = [
                'descripcion' => "Mano de obra correctiva ({$horasManoObra} h)",
                'cantidad' => $horasManoObra,
                'precio_unitario' => $tarifaHora,
            ];
        }

        $totales = $this->calcularTotales($partidas);
        $totales['diagnostico'] = trim($diagnostico);
        $totales['horas_mano_obra'] = $horasManoObra;
        $totales['importe_mano_obra'] = $importeManoObra;

        return $totales;
    }
}
