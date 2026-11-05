<?php

declare(strict_types=1);

namespace CotizaElev\Services;

use InvalidArgumentException;
use RuntimeException;

/**
 * M06: Cotizador Comercial (CotizadorService) - Incremento 3
 *
 * Atiende a: RF-25, RF-26, RF-31, RF-32, RF-33 / HU-14, HU-15, HU-19.
 */
class CotizadorService
{
    public const TIPOS_VALIDOS = ['equipo_nuevo', 'modernizacion', 'preventivo', 'correctivo'];
    public const ESTADOS_VALIDOS = ['borrador', 'enviada', 'aceptada', 'rechazada', 'vencida'];
    public const DIAS_VIGENCIA_DEFAULT = 15;

    protected CalculoMotorService $motor;
    protected ?SolicitudService $solicitudService;

    /** @var array<int, array<string, mixed>> */
    protected array $cotizaciones = [];
    protected int $siguienteId = 1;

    public function __construct(CalculoMotorService $motor, ?SolicitudService $solicitudService = null)
    {
        $this->motor = $motor;
        $this->solicitudService = $solicitudService;
    }

    /**
     * Convierte una solicitud de campo en una cotización en borrador y actualiza el estado de la solicitud a 'Cotizada'
     * (RF-25 / CA-14.3).
     *
     * @param int $solicitudId
     * @param array<string, mixed> $solicitudDatos
     * @param string $adminUsuario
     * @param array<int, array<string, mixed>> $partidasIniciales
     * @return array<string, mixed>
     */
    public function convertirSolicitudEnCotizacion(
        int $solicitudId,
        array $solicitudDatos,
        string $adminUsuario,
        array $partidasIniciales
    ): array {
        $clienteId = (int)($solicitudDatos['cliente_id'] ?? 0);
        $activoId = (int)($solicitudDatos['activo_id'] ?? 0);

        if ($clienteId <= 0 || $activoId <= 0) {
            throw new InvalidArgumentException("La solicitud debe contener cliente_id y activo_id válidos");
        }

        if ($this->solicitudService !== null) {
            $this->solicitudService->atenderSolicitud($solicitudId, SolicitudService::ESTADO_COTIZADA, $adminUsuario);
        }

        $cotizacion = $this->crearCotizacion([
            'solicitud_origen_id' => $solicitudId,
            'cliente_id' => $clienteId,
            'activo_id' => $activoId,
            'activo_cliente_id' => $clienteId,
            'tipo' => $solicitudDatos['tipo'] ?? 'correctivo',
            'partidas' => $partidasIniciales,
            'creado_por' => $adminUsuario,
            'datos_cliente_completos' => true,
        ]);

        $cotizacion['estado_solicitud_origen'] = SolicitudService::ESTADO_COTIZADA;
        $this->cotizaciones[$cotizacion['id']] = $cotizacion;

        return $cotizacion;
    }

    /**
     * Crea una cotización en estado 'borrador', validando pertenencia del activo al cliente,
     * calculando totales con CalculoMotorService y asignando fecha de vigencia (RF-26, RF-32, RF-33; CA-15.2, CA-19.3).
     *
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    public function crearCotizacion(array $datos): array
    {
        $clienteId = (int)($datos['cliente_id'] ?? 0);
        $activoId = (int)($datos['activo_id'] ?? 0);

        if ($clienteId <= 0 || $activoId <= 0) {
            throw new InvalidArgumentException("Se requiere cliente_id y activo_id para crear la cotización");
        }

        // RF-26, CA-15.2: Validar que el activo pertenezca al cliente seleccionado
        $activoClienteId = isset($datos['activo_cliente_id']) ? (int)$datos['activo_cliente_id'] : $clienteId;
        if ($activoClienteId !== $clienteId) {
            throw new RuntimeException("El activo seleccionado no pertenece al cliente");
        }

        $tipo = (string)($datos['tipo'] ?? 'correctivo');
        if (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
            throw new InvalidArgumentException("Tipo de cotización no soportado: {$tipo}");
        }

        $partidas = $datos['partidas'] ?? [];
        $descuento = (float)($datos['descuento'] ?? 0.0);
        $totales = !empty($partidas)
            ? $this->motor->calcularTotalesConDescuento($partidas, $descuento)
            : [
                'numero_partidas' => 0,
                'subtotal' => 0.0,
                'descuento' => 0.0,
                'base_gravable' => 0.0,
                'tasa_iva' => CalculoMotorService::TASA_IVA_DEFAULT,
                'monto_iva' => 0.0,
                'total' => 0.0,
                'partidas' => [],
            ];

        // CA-19.3: Si faltan datos complementarios del cliente al guardar borrador, marcar como incompleta
        $incompleta = false;
        if (array_key_exists('datos_cliente_completos', $datos) && $datos['datos_cliente_completos'] === false) {
            $incompleta = true;
        } elseif (!empty($datos['incompleta'])) {
            $incompleta = true;
        }

        $id = $this->siguienteId++;
        $folio = sprintf('COT-2026-%04d', $id);
        $diasVigencia = (int)($datos['dias_vigencia'] ?? self::DIAS_VIGENCIA_DEFAULT);
        $fechaEmision = (string)($datos['fecha_emision'] ?? date('Y-m-d'));
        $fechaVigencia = date('Y-m-d', strtotime("{$fechaEmision} +{$diasVigencia} days"));

        $cotizacion = [
            'id' => $id,
            'folio' => $folio,
            'solicitud_origen_id' => $datos['solicitud_origen_id'] ?? null,
            'cliente_id' => $clienteId,
            'activo_id' => $activoId,
            'tipo' => $tipo,
            'estado' => 'borrador',
            'incompleta' => $incompleta,
            'fecha_emision' => $fechaEmision,
            'dias_vigencia' => $diasVigencia,
            'fecha_vigencia' => $fechaVigencia,
            'subtotal' => $totales['subtotal'],
            'descuento' => $totales['descuento'],
            'base_gravable' => $totales['base_gravable'],
            'monto_iva' => $totales['monto_iva'],
            'total' => $totales['total'],
            'partidas' => $totales['partidas'],
            'avisos' => [],
        ];

        $this->cotizaciones[$id] = $cotizacion;
        return $cotizacion;
    }

    /**
     * Completa los datos faltantes de una cotización en borrador para habilitar su envío (CA-19.3).
     *
     * @param int $cotizacionId
     * @param array<string, mixed> $datosComplementarios
     * @return array<string, mixed>
     */
    public function completarBorrador(int $cotizacionId, array $datosComplementarios = []): array
    {
        if (!isset($this->cotizaciones[$cotizacionId])) {
            throw new RuntimeException("La cotización no existe");
        }

        $this->cotizaciones[$cotizacionId]['incompleta'] = false;
        foreach ($datosComplementarios as $k => $v) {
            $this->cotizaciones[$cotizacionId][$k] = $v;
        }

        return $this->cotizaciones[$cotizacionId];
    }

    /**
     * Obtiene una cotización guardada por su ID (CA-19.3).
     *
     * @param int $cotizacionId
     * @return array<string, mixed>
     */
    public function obtenerCotizacion(int $cotizacionId): array
    {
        if (!isset($this->cotizaciones[$cotizacionId])) {
            throw new RuntimeException("La cotización ya no existe");
        }

        return $this->cotizaciones[$cotizacionId];
    }

    /**
     * Agrega, elimina o modifica cantidades de partidas y recalcula inmediatamente subtotal, IVA y total
     * (RF-31 / CA-19.1).
     *
     * @param int $cotizacionId
     * @param array<int, array<string, mixed>> $nuevasPartidas
     * @return array<string, mixed>
     */
    public function modificarPartidas(int $cotizacionId, array $nuevasPartidas): array
    {
        if (!isset($this->cotizaciones[$cotizacionId])) {
            throw new RuntimeException("La cotización #{$cotizacionId} no existe");
        }

        $cot = &$this->cotizaciones[$cotizacionId];
        $totales = $this->motor->calcularTotalesConDescuento($nuevasPartidas, (float)$cot['descuento']);

        $cot['subtotal'] = $totales['subtotal'];
        $cot['base_gravable'] = $totales['base_gravable'];
        $cot['monto_iva'] = $totales['monto_iva'];
        $cot['total'] = $totales['total'];
        $cot['partidas'] = $totales['partidas'];

        return $cot;
    }

    /**
     * Gestiona el ciclo de vida de estados de la cotización y evalúa su vencimiento automático
     * (RF-33 / CA-19.2, CA-19.3).
     *
     * @param int $cotizacionId
     * @param string $nuevoEstado
     * @param int $diasTranscurridos
     * @return array<string, mixed>
     */
    public function cambiarEstadoCotizacion(int $cotizacionId, string $nuevoEstado, int $diasTranscurridos = 0): array
    {
        if (!isset($this->cotizaciones[$cotizacionId])) {
            throw new RuntimeException("La cotización #{$cotizacionId} no existe");
        }

        if (!in_array($nuevoEstado, self::ESTADOS_VALIDOS, true)) {
            throw new InvalidArgumentException("Estado de cotización inválido: {$nuevoEstado}");
        }

        $cot = &$this->cotizaciones[$cotizacionId];

        if ($nuevoEstado === 'enviada' && !empty($cot['incompleta'])) {
            throw new RuntimeException("No se puede enviar una cotización incompleta; complete los datos faltantes del borrador");
        }

        $cot['estado'] = $nuevoEstado;

        if ($cot['estado'] === 'enviada' && $diasTranscurridos > (int)$cot['dias_vigencia']) {
            $cot['estado'] = 'vencida';
        }

        return $cot;
    }
}
