<?php

declare(strict_types=1);

namespace CotizaElev\Services;

use InvalidArgumentException;

/**
 * M07: Motor de Cálculo Comercial (CalculoMotorService)
 * 
 * Componente de dominio puro encargado de la lógica matemática de cotizaciones:
 * cálculo de importes por partida, subtotal acumulado, cálculo de IVA (16%) y total
 * con precisión a 2 decimales usando redondeo financiero estándar.
 * 
 * Atiende a: RF-28 / HU-16 / Criterios CA-16.1, CA-16.2 y CA-16.3.
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
     * 
     * @param array $partidas Lista de partidas. Cada partida debe tener 'cantidad' y 'precio_unitario'.
     * @param float $tasaIva Tasa impositiva de IVA (por defecto 0.16)
     * @return array Desglose financiero con subtotal, monto_iva, total y detalle de partidas.
     * @throws InvalidArgumentException Si faltan campos requeridos o hay valores inválidos.
     */
    public function calcularTotales(array $partidas, float $tasaIva = self::TASA_IVA_DEFAULT): array
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
        $montoIva = round($subtotal * $tasaIva, 2, PHP_ROUND_HALF_UP);
        $total = round($subtotal + $montoIva, 2, PHP_ROUND_HALF_UP);

        return [
            'numero_partidas' => count($desglosePartidas),
            'subtotal' => $subtotal,
            'tasa_iva' => $tasaIva,
            'monto_iva' => $montoIva,
            'total' => $total,
            'partidas' => $desglosePartidas
        ];
    }
}
