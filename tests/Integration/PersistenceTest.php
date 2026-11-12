<?php

declare(strict_types=1);

namespace Tests\Integration;

require_once __DIR__ . '/../../app/autoload.php';
require_once __DIR__ . '/../TestDatabaseHelper.php';

use Tests\TestDatabaseHelper;
use App\Models\UsuarioModel;
use App\Models\ClienteModel;
use App\Models\ActivoModel;
use App\Models\CatalogoModel;
use App\Models\SolicitudModel;
use App\Models\CotizacionModel;
use App\Models\AuditoriaModel;
use PDOException;

class PersistenceTest
{
    private array $results = [];

    public function run(): array
    {
        $pdo = TestDatabaseHelper::createTestPdo();

        // 1. Verificar búsqueda de usuario y bloqueo tras 5 intentos (RF-01, RF-02)
        $userModel = new UsuarioModel();
        $admin = $userModel->buscarPorCorreo('admin@elevese.mx');
        $this->assert($admin !== null && $admin['rol'] === 'admin_ventas', 'Persistencia: Búsqueda de usuario admin por correo');

        for ($i = 0; $i < 5; $i++) {
            $userModel->incrementarIntentosFallidos((int)$admin['id_usuario']);
        }
        $adminBloqueado = $userModel->buscarPorId((int)$admin['id_usuario']);
        $this->assert($adminBloqueado['intentos_fallidos'] == 5 && !empty($adminBloqueado['bloqueado_hasta']), 'Persistencia: Bloqueo de cuenta tras 5 intentos fallidos');
        $userModel->restablecerIntentos((int)$admin['id_usuario']);

        // 2. Cliente: creación y consulta (RF-08)
        $clienteModel = new ClienteModel();
        $idCli = $clienteModel->crear([
            'clave_cliente' => 'CLI-002',
            'razon_social' => 'Plaza Mayor León',
            'rfc' => 'PML150912XX1',
            'nombre_contacto' => 'Laura Morales',
            'correo' => 'lmorales@plazamayor.mx',
            'telefono' => '4779876543',
            'direccion_fiscal' => 'Juan Alonso de Torres 2002',
        ]);
        $cliGuardado = $clienteModel->buscarPorId($idCli);
        $this->assert($cliGuardado !== null && $cliGuardado['rfc'] === 'PML150912XX1', 'Persistencia: Creación y recuperación de cliente');

        // 3. Activo: creación ligada a cliente con paradas y capacidad (RF-13, RF-14)
        $activoModel = new ActivoModel();
        $idAct = $activoModel->crear([
            'id_cliente' => $idCli,
            'numero_serie' => 'ELV-PM-01',
            'tipo_equipo' => 'pasajeros',
            'marca' => 'Otis',
            'modelo' => 'SkyRise',
            'capacidad_kg' => 1250,
            'numero_paradas' => 15,
            'ubicacion_instalacion' => 'Torre Corporativa Piso 1',
        ]);
        $actGuardado = $activoModel->buscarPorId($idAct);
        $this->assert($actGuardado !== null && (int)$actGuardado['capacidad_kg'] === 1250, 'Persistencia: Creación de activo con capacidades válidas');

        // 4. Catálogo: consulta con vigencia (RF-16..RF-18)
        $catModel = new CatalogoModel();
        $vigentes = $catModel->listarVigentes('mano_obra');
        $this->assert(count($vigentes) >= 1 && (float)$vigentes[0]['precio_base'] === 700.00, 'Persistencia: Consulta de catálogo con filtro de vigencia');

        // 5. Solicitud técnica (RF-20..RF-23)
        $solModel = new SolicitudModel();
        $idSol = $solModel->crear([
            'folio_solicitud' => 'SOL-2026-0001',
            'id_tecnico' => 2,
            'id_cliente' => $idCli,
            'id_activo' => $idAct,
            'tipo_servicio' => 'correctivo',
            'diagnostico_falla' => 'Falla en sensor de piso 3',
            'piezas_requeridas' => '1 sensor óptico',
            'urgencia' => 'alta',
        ]);
        $solGuardada = $solModel->buscarPorId($idSol);
        $this->assert($solGuardada !== null && $solGuardada['folio_solicitud'] === 'SOL-2026-0001', 'Persistencia: Creación de solicitud técnica por técnico');
        $this->assert($solModel->existeDuplicadaEnActivo($idAct), 'Persistencia: Detección de solicitud duplicada pendiente en mismo activo');

        // 6. Cotización Transaccional Atómica con Partidas (RF-25..RF-32)
        $cotModel = new CotizacionModel();
        $idCot = $cotModel->crear([
            'folio_cotizacion' => 'COT-2026-0001',
            'id_cliente' => $idCli,
            'id_activo' => $idAct,
            'id_usuario_creador' => 1,
            'id_solicitud' => $idSol,
            'tipo_cotizacion' => 'correctivo',
            'subtotal' => 3900.00,
            'porcentaje_descuento' => 5.00,
            'monto_descuento' => 195.00,
            'iva' => 592.80,
            'total' => 4297.80,
            'moneda' => 'MXN',
        ], [
            [
                'id_concepto' => 1,
                'descripcion_concepto' => 'Mano de obra correctiva (1 hr)',
                'cantidad' => 1.0,
                'precio_unitario' => 700.00,
                'total_partida' => 700.00,
            ],
            [
                'id_concepto' => 2,
                'descripcion_concepto' => 'Sensor óptico',
                'cantidad' => 1.0,
                'precio_unitario' => 3200.00,
                'total_partida' => 3200.00,
            ],
        ]);
        $cotGuardada = $cotModel->buscarPorId($idCot);
        $partidas = $cotModel->listarPartidas($idCot);
        $this->assert($cotGuardada !== null && (float)$cotGuardada['total'] === 4297.80, 'Persistencia: Guardado atómico de cotización');
        $this->assert(count($partidas) === 2, 'Persistencia: Inserción íntegra de partidas de cotización');

        // 7. Auditoría inmutable (RF-41)
        $audModel = new AuditoriaModel();
        $idLog = $audModel->registrar(
            1,
            'COTIZACIONES',
            'INSERT',
            '192.168.1.10',
            'Mozilla/5.0 (IntegrationTest)',
            'Creación de cotización COT-2026-0001'
        );
        $logs = $audModel->listarPaginado(10);
        $this->assert($idLog > 0 && count($logs) >= 1 && $logs[0]['modulo_afectado'] === 'COTIZACIONES', 'Persistencia: Registro en bitácora inmutable');

        // 8. Integridad referencial: ON DELETE RESTRICT (RNF-25)
        $bloqueoBorrado = false;
        try {
            // Intentar borrar cliente con activo dependiente
            $pdo->exec("DELETE FROM clientes WHERE id_cliente = {$idCli}");
        } catch (PDOException $e) {
            $bloqueoBorrado = true;
        }
        $this->assert($bloqueoBorrado, 'Persistencia: Regla ON DELETE RESTRICT bloquea borrado en cascada');

        return $this->results;
    }

    private function assert(bool $condition, string $description): void
    {
        $this->results[] = [
            'desc' => $description,
            'status' => $condition,
        ];
    }
}
