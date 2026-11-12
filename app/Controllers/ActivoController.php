<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\ActivoModel;
use App\Models\AuditoriaModel;

class ActivoController
{
    private ActivoModel $activos;
    private AuditoriaModel $auditoria;

    public function __construct(?ActivoModel $activos = null, ?AuditoriaModel $auditoria = null)
    {
        $this->activos = $activos ?? new ActivoModel();
        $this->auditoria = $auditoria ?? new AuditoriaModel();
    }

    public function crear(Request $request): Response
    {
        $idCliente = (int)$request->get('id_cliente', 0);
        $serie = trim((string)$request->get('numero_serie', ''));
        $capacidad = (int)$request->get('capacidad_kg', 0);
        $paradas = (int)$request->get('numero_paradas', 0);

        if ($idCliente <= 0 || empty($serie)) {
            return Response::json(['error' => 'Cliente y número de serie son requeridos.', 'codigo' => 'VAL_ERROR'], 422);
        }

        // Validación de rangos técnicos normativos (RF-13, RF-14)
        if ($paradas < 2 || $paradas > 60) {
            return Response::json(['error' => 'El número de paradas debe estar entre 2 y 60 niveles.', 'codigo' => 'RANGO_PARADAS_INVALIDO'], 422);
        }

        if ($capacidad < 100 || $capacidad > 10000) {
            return Response::json(['error' => 'La capacidad del equipo debe estar entre 100 y 10,000 kg.', 'codigo' => 'RANGO_CAPACIDAD_INVALIDO'], 422);
        }

        if ($this->activos->buscarPorSerie($serie)) {
            return Response::json(['error' => 'Ya existe un equipo registrado con este número de serie.', 'codigo' => 'SERIE_DUPLICADA'], 409);
        }

        $id = $this->activos->crear([
            'id_cliente' => $idCliente,
            'numero_serie' => $serie,
            'tipo_equipo' => $request->get('tipo_equipo', 'pasajeros'),
            'marca' => $request->get('marca', 'Desconocida'),
            'modelo' => $request->get('modelo', 'Desconocido'),
            'capacidad_kg' => $capacidad,
            'numero_paradas' => $paradas,
            'ubicacion_instalacion' => $request->get('ubicacion_instalacion', ''),
        ]);

        $this->auditoria->registrar(
            Session::getUserId(),
            'EQUIPOS_ACTIVOS',
            'INSERT',
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $request->getHeader('user-agent', 'Unknown'),
            "Equipo activo registrado: {$serie} (ID {$id})"
        );

        return Response::json(['mensaje' => 'Elevador registrado exitosamente.', 'id_activo' => $id], 201);
    }

    public function historial(Request $request, array $params): Response
    {
        $id = (int)($params['id'] ?? 0);
        $activo = $this->activos->buscarPorId($id);
        if (!$activo) {
            return Response::json(['error' => 'Equipo elevador no encontrado.', 'codigo' => 'NOT_FOUND'], 404);
        }

        $historial = $this->activos->obtenerHistorial($id);
        $rol = Session::getRole() ?? 'tecnico';

        // Blindaje al Técnico (RF-15): Sanitizar precios y montos
        if ($rol === 'tecnico') {
            $historialSanitizado = [];
            foreach ($historial as $h) {
                unset($h['total']);
                $historialSanitizado[] = $h;
            }
            return Response::json([
                'activo' => $activo,
                'historial' => $historialSanitizado,
                'vista_restringida' => true,
            ], 200);
        }

        return Response::json([
            'activo' => $activo,
            'historial' => $historial,
            'vista_restringida' => false,
        ], 200);
    }
}
