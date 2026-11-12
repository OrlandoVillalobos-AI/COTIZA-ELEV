<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\CatalogoModel;
use App\Models\AuditoriaModel;

class CatalogoController
{
    private CatalogoModel $catalogo;
    private AuditoriaModel $auditoria;

    public function __construct(?CatalogoModel $catalogo = null, ?AuditoriaModel $auditoria = null)
    {
        $this->catalogo = $catalogo ?? new CatalogoModel();
        $this->auditoria = $auditoria ?? new AuditoriaModel();
    }

    public function index(Request $request): Response
    {
        $tipo = $request->get('tipo', null);
        $items = $this->catalogo->listarVigentes($tipo ? (string)$tipo : null);

        return Response::json([
            'conceptos' => $items,
            'total' => count($items),
            'fecha_consulta' => date('Y-m-d H:i:s'),
        ], 200);
    }

    public function importarCsv(Request $request): Response
    {
        $contenido = (string)$request->get('csv_contenido', '');
        if (empty($contenido)) {
            return Response::json(['error' => 'Contenido CSV no proporcionado.', 'codigo' => 'VAL_ERROR'], 422);
        }

        $lineas = explode("\n", trim($contenido));
        $actualizados = 0;
        $creados = 0;
        $errores = [];

        foreach ($lineas as $idx => $linea) {
            if ($idx === 0 && str_contains(strtolower($linea), 'sku')) {
                continue; // Cabecera
            }
            $cols = str_getcsv(trim($linea));
            if (count($cols) < 4) {
                continue;
            }

            [$sku, $tipo, $desc, $precio] = $cols;
            $precioFloat = (float)$precio;

            if ($precioFloat <= 0) {
                $errores[] = "Línea {$idx}: Precio inválido para SKU {$sku}";
                continue;
            }

            $existente = $this->catalogo->buscarPorSku($sku);
            if ($existente) {
                $this->catalogo->actualizarPrecio((int)$existente['id_concepto'], $precioFloat);
                $actualizados++;
            } else {
                $this->catalogo->crear([
                    'codigo_sku' => $sku,
                    'tipo_concepto' => $tipo,
                    'descripcion' => $desc,
                    'precio_base' => $precioFloat,
                ]);
                $creados++;
            }
        }

        $this->auditoria->registrar(
            Session::getUserId(),
            'CATALOGO_TARIFAS',
            'UPDATE',
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $request->getHeader('user-agent', 'Unknown'),
            "Importación CSV catálogo: {$creados} creados, {$actualizados} actualizados."
        );

        return Response::json([
            'mensaje' => 'Importación de catálogo procesada.',
            'creados' => $creados,
            'actualizados' => $actualizados,
            'errores' => $errores,
        ], 200);
    }
}
