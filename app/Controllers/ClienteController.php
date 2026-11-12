<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\ClienteModel;
use App\Models\AuditoriaModel;

class ClienteController
{
    private ClienteModel $clientes;
    private AuditoriaModel $auditoria;

    public function __construct(?ClienteModel $clientes = null, ?AuditoriaModel $auditoria = null)
    {
        $this->clientes = $clientes ?? new ClienteModel();
        $this->auditoria = $auditoria ?? new AuditoriaModel();
    }

    public function index(Request $request): Response
    {
        $limite = max(1, min(100, (int)$request->get('limite', 50)));
        $offset = max(0, (int)$request->get('offset', 0));
        $busqueda = $request->get('q', null);

        $lista = $this->clientes->listar($limite, $offset, $busqueda ? (string)$busqueda : null);
        return Response::json([
            'clientes' => $lista,
            'total_retornados' => count($lista),
            'limite' => $limite,
            'offset' => $offset,
        ], 200);
    }

    public function crear(Request $request): Response
    {
        $rfc = strtoupper(trim((string)$request->get('rfc', '')));
        $razon = trim((string)$request->get('razon_social', ''));
        $clave = strtoupper(trim((string)$request->get('clave_cliente', '')));

        if (empty($rfc) || empty($razon) || empty($clave)) {
            return Response::json(['error' => 'RFC, razón social y clave son obligatorios.', 'codigo' => 'VAL_ERROR'], 422);
        }

        if ($this->clientes->buscarPorRfc($rfc)) {
            return Response::json(['error' => 'Ya existe un cliente registrado con este RFC.', 'codigo' => 'RFC_DUPLICADO'], 409);
        }

        if ($this->clientes->buscarPorClave($clave)) {
            return Response::json(['error' => 'Ya existe un cliente con esta clave.', 'codigo' => 'CLAVE_DUPLICADA'], 409);
        }

        $id = $this->clientes->crear([
            'clave_cliente' => $clave,
            'razon_social' => $razon,
            'rfc' => $rfc,
            'nombre_contacto' => $request->get('nombre_contacto', ''),
            'correo' => $request->get('correo', ''),
            'telefono' => $request->get('telefono', ''),
            'direccion_fiscal' => $request->get('direccion_fiscal', ''),
        ]);

        $this->auditoria->registrar(
            Session::getUserId(),
            'CLIENTES',
            'INSERT',
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $request->getHeader('user-agent', 'Unknown'),
            "Cliente creado: {$razon} ({$rfc}) ID {$id}"
        );

        return Response::json(['mensaje' => 'Cliente registrado exitosamente.', 'id_cliente' => $id], 201);
    }

    public function editar(Request $request, array $params): Response
    {
        $id = (int)($params['id'] ?? 0);
        $cliente = $this->clientes->buscarPorId($id);
        if (!$cliente) {
            return Response::json(['error' => 'Cliente no encontrado.', 'codigo' => 'NOT_FOUND'], 404);
        }

        $datos = [
            'razon_social' => $request->get('razon_social', $cliente['razon_social']),
            'nombre_contacto' => $request->get('nombre_contacto', $cliente['nombre_contacto']),
            'correo' => $request->get('correo', $cliente['correo']),
            'telefono' => $request->get('telefono', $cliente['telefono']),
            'direccion_fiscal' => $request->get('direccion_fiscal', $cliente['direccion_fiscal']),
        ];

        $this->clientes->actualizar($id, $datos);

        $this->auditoria->registrar(
            Session::getUserId(),
            'CLIENTES',
            'UPDATE',
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $request->getHeader('user-agent', 'Unknown'),
            "Cliente actualizado ID {$id}",
            $cliente,
            $datos
        );

        return Response::json(['mensaje' => 'Cliente actualizado correctamente.'], 200);
    }

    public function baja(Request $request, array $params): Response
    {
        $id = (int)($params['id'] ?? 0);
        $cliente = $this->clientes->buscarPorId($id);
        if (!$cliente) {
            return Response::json(['error' => 'Cliente no encontrado.', 'codigo' => 'NOT_FOUND'], 404);
        }

        $this->clientes->bajaLogica($id);

        $this->auditoria->registrar(
            Session::getUserId(),
            'CLIENTES',
            'STATUS_CHANGE',
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $request->getHeader('user-agent', 'Unknown'),
            "Baja lógica de cliente ID {$id} ({$cliente['razon_social']})"
        );

        return Response::json(['mensaje' => 'Cliente dado de baja lógicamente con éxito.'], 200);
    }
}
