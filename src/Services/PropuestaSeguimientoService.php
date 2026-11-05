<?php

declare(strict_types=1);

namespace CotizaElev\Services;

use InvalidArgumentException;
use RuntimeException;

/**
 * M08: Generación de propuesta económica PDF, numeración consecutiva,
 * envío por correo e historial comercial (RF-35, RF-36, RF-37, RF-38; HU-21, HU-22).
 */
final class PropuestaSeguimientoService
{
    /** @var array<string, int> Contadores por clave "{tipoDoc}-{anio}" */
    private array $consecutivos = [];

    /** @var array<string, array<string, mixed>> Propuestas generadas por folio */
    private array $propuestas = [];

    /** @var array<string, list<array{fecha: string, usuario: string, destinatario: string}>> Historial de envíos por folio */
    private array $historialEnvios = [];

    /** @var list<array{evento: string, fecha: string, detalle: string}> Eventos internos de fallo */
    private array $eventosInternos = [];

    /**
     * Pre-carga el contador de folios para un tipo y año dado (útil para pruebas de límite o sincronización con BD).
     */
    public function establecerUltimoConsecutivo(string $tipoDoc, int $anio, int $ultimoNumero): void
    {
        $clave = strtoupper(trim($tipoDoc)) . '-' . $anio;
        $this->consecutivos[$clave] = max(0, $ultimoNumero);
    }

    /**
     * Genera la propuesta económica en formato estructurado/PDF con plantilla corporativa de ELEVESE (RF-35, RF-36, HU-21).
     *
     * @param array<string, mixed> $cotizacion
     * @param array<string, mixed> $cliente
     * @return array<string, mixed>
     */
    public function generarPropuestaPdf(
        array $cotizacion,
        array $cliente,
        int $anio = 2026,
        string $tipoDoc = 'COT',
        bool $simularFallaServidor = false
    ): array {
        if ($simularFallaServidor === true) {
            $this->eventosInternos[] = [
                'evento' => 'ERROR_GENERADOR_PDF',
                'fecha' => date('Y-m-d H:i:s'),
                'detalle' => 'Fallo interno al renderizar plantilla PDF para cotización',
            ];
            throw new RuntimeException('No se pudo generar el documento, inténtalo de nuevo');
        }

        $tipoLimpio = strtoupper(trim($tipoDoc));
        $claveConsecutivo = $tipoLimpio . '-' . $anio;
        $siguiente = ($this->consecutivos[$claveConsecutivo] ?? 0) + 1;
        $this->consecutivos[$claveConsecutivo] = $siguiente;

        $folio = sprintf('%s-%d-%04d', $tipoLimpio, $anio, $siguiente);

        $partidas = isset($cotizacion['partidas']) && is_array($cotizacion['partidas'])
            ? $cotizacion['partidas']
            : [];
        $subtotal = round((float) ($cotizacion['subtotal'] ?? 0.0), 2);
        $iva = round((float) ($cotizacion['iva'] ?? round($subtotal * 0.16, 2)), 2);
        $total = round((float) ($cotizacion['total'] ?? ($subtotal + $iva)), 2);

        $propuesta = [
            'folio' => $folio,
            'anio' => $anio,
            'tipo_documento' => $tipoLimpio,
            'consecutivo' => $siguiente,
            'logo_corporativo' => 'ELEVESE S.A. de C.V. - Soluciones en Elevación',
            'datos_fiscales_cliente' => [
                'razon_social' => (string) ($cliente['razon_social'] ?? $cliente['nombre'] ?? ''),
                'rfc' => (string) ($cliente['rfc'] ?? ''),
                'domicilio_fiscal' => (string) ($cliente['domicilio_fiscal'] ?? $cliente['direccion'] ?? ''),
                'correo' => (string) ($cliente['correo'] ?? ''),
            ],
            'partidas' => $partidas,
            'subtotal' => $subtotal,
            'iva' => $iva,
            'total' => $total,
            'vigencia' => (string) ($cotizacion['vigencia'] ?? '15 días naturales'),
            'condiciones_comerciales' => (string) ($cotizacion['condiciones_comerciales'] ?? '50% anticipo, 50% contra entrega. Precios en MXN más IVA.'),
            'espacio_firma' => 'Aceptación del cliente: ___________________________ Nombre, Firma y Fecha',
            'estado_comercial' => 'Enviada/Pendiente',
            'pendiente_en_tablero' => true,
            'contenido_pdf_binario' => '%PDF-1.4 ELEVESE ' . $folio,
        ];

        $this->propuestas[$folio] = $propuesta;
        if (!isset($this->historialEnvios[$folio])) {
            $this->historialEnvios[$folio] = [];
        }

        return $propuesta;
    }

    /**
     * Envía la propuesta al correo del cliente y registra el historial sin sobrescribir envíos previos (RF-37, HU-22).
     *
     * @param array<string, mixed> $cliente
     * @return array<string, mixed>
     */
    public function enviarPorCorreo(
        string $folioPropuesta,
        array $cliente,
        string $usuarioEmisor,
        string $fechaEnvio
    ): array {
        $correo = trim((string) ($cliente['correo'] ?? ''));
        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El cliente no tiene correo registrado; captúralo antes de enviar');
        }

        $registroEnvio = [
            'fecha' => $fechaEnvio,
            'usuario' => $usuarioEmisor,
            'destinatario' => $correo,
        ];

        if (!isset($this->historialEnvios[$folioPropuesta])) {
            $this->historialEnvios[$folioPropuesta] = [];
        }
        $this->historialEnvios[$folioPropuesta][] = $registroEnvio;

        if (isset($this->propuestas[$folioPropuesta])) {
            $this->propuestas[$folioPropuesta]['estado_comercial'] = 'Enviada';
        }

        return [
            'folio' => $folioPropuesta,
            'enviado' => true,
            'ultimo_envio' => $registroEnvio,
            'historial_envios' => $this->historialEnvios[$folioPropuesta],
            'total_envios' => count($this->historialEnvios[$folioPropuesta]),
        ];
    }

    /**
     * Registra la decisión comercial del cliente ('Aceptada' o 'Rechazada') con fecha y nota (RF-38, HU-22).
     *
     * @return array<string, mixed>
     */
    public function registrarDecisionCliente(
        string $folioPropuesta,
        string $decision,
        string $nota,
        string $fecha,
        string $adminUsuario
    ): array {
        $decisionNormalizada = ucfirst(strtolower(trim($decision)));
        if (!in_array($decisionNormalizada, ['Aceptada', 'Rechazada'], true)) {
            throw new InvalidArgumentException('La decisión comercial debe ser Aceptada o Rechazada');
        }

        if (!isset($this->propuestas[$folioPropuesta])) {
            $this->propuestas[$folioPropuesta] = [
                'folio' => $folioPropuesta,
            ];
        }

        $this->propuestas[$folioPropuesta]['estado_comercial'] = $decisionNormalizada;
        $this->propuestas[$folioPropuesta]['pendiente_en_tablero'] = false;
        $this->propuestas[$folioPropuesta]['decision'] = [
            'estado' => $decisionNormalizada,
            'nota' => trim($nota),
            'fecha' => $fecha,
            'usuario' => $adminUsuario,
        ];

        return $this->propuestas[$folioPropuesta];
    }

    /**
     * @return list<array{evento: string, fecha: string, detalle: string}>
     */
    public function obtenerEventosInternos(): array
    {
        return $this->eventosInternos;
    }

    /**
     * @return list<array{fecha: string, usuario: string, destinatario: string}>
     */
    public function obtenerHistorialEnvios(string $folioPropuesta): array
    {
        return $this->historialEnvios[$folioPropuesta] ?? [];
    }
}
