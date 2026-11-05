<?php

declare(strict_types=1);

namespace CotizaElev\Services;

use InvalidArgumentException;
use RuntimeException;

/**
 * M02: Gestión de Clientes y Contactos (ClienteService)
 *
 * Atiende a: RF-08, RF-09, RF-10, RF-11, RF-12
 * Historias de usuario: HU-06, HU-07
 */
class ClienteService
{
    public const MAX_LONGITUD_RAZON_SOCIAL = 300;

    /** @var array<int, array<string, mixed>> */
    private array $clientes = [];

    private int $siguienteId = 1;

    /**
     * RF-08, RF-11 (CA-06.1, CA-06.2, CA-06.3, CA-06.4): Alta de cliente con validación de campos,
     * longitud límite de 300 caracteres y detección de duplicados por RFC o razón social.
     */
    public function registrarCliente(array $datos, bool $cancelarSiSimilar = false): array
    {
        $camposObligatorios = ['razon_social', 'rfc', 'contacto', 'correo', 'telefono', 'domicilio'];
        foreach ($camposObligatorios as $campo) {
            if (!isset($datos[$campo]) || trim((string)$datos[$campo]) === '') {
                throw new InvalidArgumentException("El campo obligatorio '{$campo}' es requerido");
            }
        }

        $razonSocial = (string)$datos['razon_social'];
        $longitudRazon = mb_strlen($razonSocial, 'UTF-8');
        if ($longitudRazon > self::MAX_LONGITUD_RAZON_SOCIAL) {
            throw new InvalidArgumentException('La razón social excede el límite máximo de 300 caracteres');
        }

        $rfc = strtoupper(trim((string)$datos['rfc']));
        $correo = trim((string)$datos['correo']);

        if (!str_contains($correo, '@') || filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('El formato del correo electrónico no es válido');
        }

        foreach ($this->clientes as $existente) {
            if ($existente['rfc'] === $rfc) {
                throw new RuntimeException('Ya existe un cliente con ese RFC');
            }
            if (mb_strtolower($existente['razon_social'], 'UTF-8') === mb_strtolower($razonSocial, 'UTF-8')) {
                if ($cancelarSiSimilar) {
                    return [
                        'registrado' => false,
                        'cancelado_por_similitud' => true,
                        'cliente_existente_id' => $existente['id'],
                    ];
                }
                throw new RuntimeException('Ya existe un cliente con ese RFC o razón social');
            }
        }

        $id = $this->siguienteId++;
        $nuevoCliente = [
            'id' => $id,
            'razon_social' => $razonSocial,
            'rfc' => $rfc,
            'contacto' => trim((string)$datos['contacto']),
            'correo' => $correo,
            'telefono' => trim((string)$datos['telefono']),
            'domicilio' => trim((string)$datos['domicilio']),
            'activo' => true,
            'historial_cambios' => [],
            'activos_vinculados' => [],
            'cotizaciones_vinculadas' => [],
        ];

        $this->clientes[$id] = $nuevoCliente;

        return $nuevoCliente;
    }

    /**
     * RF-09 (CA-07.1): Búsqueda de clientes activos por razón social, RFC o correo.
     */
    public function buscarClientes(string $termino): array
    {
        $terminoNorm = mb_strtolower(trim($termino), 'UTF-8');
        $resultados = [];

        foreach ($this->clientes as $cliente) {
            if ($cliente['activo'] !== true) {
                continue;
            }

            if ($terminoNorm === '') {
                $resultados[] = $cliente;
                continue;
            }

            $coincideRazon = str_contains(mb_strtolower($cliente['razon_social'], 'UTF-8'), $terminoNorm);
            $coincideRfc = str_contains(mb_strtolower($cliente['rfc'], 'UTF-8'), $terminoNorm);
            $coincideCorreo = str_contains(mb_strtolower($cliente['correo'], 'UTF-8'), $terminoNorm);

            if ($coincideRazon || $coincideRfc || $coincideCorreo) {
                $resultados[] = $cliente;
            }
        }

        return $resultados;
    }

    /**
     * RF-10 (CA-07.2, CA-07.4): Edición de datos de cliente con validación de RFC único e historial de cambios.
     */
    public function editarCliente(int $id, array $cambios, string $usuarioEditor): array
    {
        if (!isset($this->clientes[$id])) {
            throw new InvalidArgumentException("El cliente con ID {$id} no existe");
        }

        if (isset($cambios['rfc'])) {
            $nuevoRfc = strtoupper(trim((string)$cambios['rfc']));
            if ($nuevoRfc === '') {
                throw new InvalidArgumentException('El RFC no puede quedar vacío');
            }
            foreach ($this->clientes as $otroId => $otroCliente) {
                if ($otroId !== $id && $otroCliente['rfc'] === $nuevoRfc) {
                    throw new RuntimeException('Ese RFC ya pertenece a otro cliente');
                }
            }
            $cambios['rfc'] = $nuevoRfc;
        }

        if (isset($cambios['correo'])) {
            $nuevoCorreo = trim((string)$cambios['correo']);
            if (!str_contains($nuevoCorreo, '@') || filter_var($nuevoCorreo, FILTER_VALIDATE_EMAIL) === false) {
                throw new InvalidArgumentException('El formato del correo electrónico no es válido');
            }
            $cambios['correo'] = $nuevoCorreo;
        }

        $camposEditables = ['razon_social', 'rfc', 'contacto', 'correo', 'telefono', 'domicilio'];
        $camposModificados = [];

        foreach ($camposEditables as $campo) {
            if (array_key_exists($campo, $cambios)) {
                $valorAnterior = $this->clientes[$id][$campo];
                $valorNuevo = (string)$cambios[$campo];
                if ($valorAnterior !== $valorNuevo) {
                    $this->clientes[$id][$campo] = $valorNuevo;
                    $camposModificados[$campo] = [
                        'anterior' => $valorAnterior,
                        'nuevo' => $valorNuevo,
                    ];
                }
            }
        }

        $this->clientes[$id]['historial_cambios'][] = [
            'usuario' => $usuarioEditor,
            'fecha' => date('Y-m-d H:i:s'),
            'cambios' => $camposModificados,
        ];

        return $this->clientes[$id];
    }

    /**
     * RF-12 (CA-07.3): Baja lógica de cliente conservando sus activos y cotizaciones históricas.
     */
    public function darDeBajaLogica(
        int $id,
        array $activosVinculados = [],
        array $cotizacionesVinculadas = []
    ): array {
        if (!isset($this->clientes[$id])) {
            throw new InvalidArgumentException("El cliente con ID {$id} no existe");
        }

        $this->clientes[$id]['activo'] = false;

        if (!empty($activosVinculados)) {
            $this->clientes[$id]['activos_vinculados'] = $activosVinculados;
        }
        if (!empty($cotizacionesVinculadas)) {
            $this->clientes[$id]['cotizaciones_vinculadas'] = $cotizacionesVinculadas;
        }

        return $this->clientes[$id];
    }

    public function obtenerClienteConHistorial(int $id): array
    {
        if (!isset($this->clientes[$id])) {
            throw new InvalidArgumentException("El cliente con ID {$id} no existe");
        }

        return $this->clientes[$id];
    }
}
