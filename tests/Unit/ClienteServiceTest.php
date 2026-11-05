<?php

declare(strict_types=1);

namespace Tests\Unit;

require_once __DIR__ . '/../../src/Services/ClienteService.php';

use CotizaElev\Services\ClienteService;
use InvalidArgumentException;
use RuntimeException;

/**
 * Suite de pruebas unitarias para ClienteService (M02 - Incremento 2)
 * Trazabilidad: HU-06, HU-07
 */
class ClienteServiceTest
{
    private int $ejecutadas = 0;
    private int $pasadas = 0;

    public function ejecutarTodas(): array
    {
        $this->ejecutadas = 0;
        $this->pasadas = 0;

        $this->testAltaClienteNormal_CA06_1();
        $this->testAltaClienteErrorRfcVacioOCorreoInvalido_CA06_2();
        $this->testAltaClienteErrorRfcDuplicado_CA06_3();
        $this->testAltaClienteLimiteRazonSocial300Caracteres_CA06_4();
        $this->testBusquedaClientesNormal_CA07_1();
        $this->testEdicionClienteConHistorial_CA07_2();
        $this->testBajaLogicaConservaActivosYCotizaciones_CA07_3();
        $this->testEdicionClienteRfcAjeno_CA07_4();

        return [
            'ejecutadas' => $this->ejecutadas,
            'pasadas' => $this->pasadas,
        ];
    }

    private function testAltaClienteNormal_CA06_1(): void
    {
        $this->ejecutadas++;
        $service = new ClienteService();
        $cliente = $service->registrarCliente([
            'razon_social' => 'Torre Corporativa Bajío S.A. de C.V.',
            'rfc' => 'TCB210510AB1',
            'contacto' => 'Ing. Roberto Soto',
            'correo' => 'rsoto@torrebajio.mx',
            'telefono' => '4771234567',
            'domicilio' => 'Blvd. Campestre 1502, León, Gto.',
        ]);

        if ($cliente['id'] === 1 && $cliente['rfc'] === 'TCB210510AB1' && $cliente['activo'] === true) {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-06.1');
        }
    }

    private function testAltaClienteErrorRfcVacioOCorreoInvalido_CA06_2(): void
    {
        $this->ejecutadas++;
        $service = new ClienteService();

        $rechazaRfcVacio = false;
        try {
            $service->registrarCliente([
                'razon_social' => 'Hospital Central',
                'rfc' => '',
                'contacto' => 'Dra. Elena Ruiz',
                'correo' => 'eruiz@hospital.mx',
                'telefono' => '4779998877',
                'domicilio' => 'Av. Reforma 100',
            ]);
        } catch (InvalidArgumentException) {
            $rechazaRfcVacio = true;
        }

        $rechazaCorreoSinArroba = false;
        try {
            $service->registrarCliente([
                'razon_social' => 'Hospital Central',
                'rfc' => 'HCE190101XY9',
                'contacto' => 'Dra. Elena Ruiz',
                'correo' => 'eruiz_sin_arroba.mx',
                'telefono' => '4779998877',
                'domicilio' => 'Av. Reforma 100',
            ]);
        } catch (InvalidArgumentException) {
            $rechazaCorreoSinArroba = true;
        }

        if ($rechazaRfcVacio && $rechazaCorreoSinArroba) {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-06.2');
        }
    }

    private function testAltaClienteErrorRfcDuplicado_CA06_3(): void
    {
        $this->ejecutadas++;
        $service = new ClienteService();

        $service->registrarCliente([
            'razon_social' => 'Inmobiliaria del Centro S.A.',
            'rfc' => 'ICE180202AA1',
            'contacto' => 'Lic. Mario Luna',
            'correo' => 'mluna@inmocentro.mx',
            'telefono' => '4771112233',
            'domicilio' => 'Calle Madero 205',
        ]);

        try {
            $service->registrarCliente([
                'razon_social' => 'Otra Razón Social S.A.',
                'rfc' => 'ICE180202AA1',
                'contacto' => 'Juan Pérez',
                'correo' => 'jperez@otra.mx',
                'telefono' => '4774445566',
                'domicilio' => 'Av. Hidalgo 400',
            ]);
            throw new RuntimeException('Debió rechazar RFC duplicado en CA-06.3');
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'Ya existe un cliente con ese RFC') {
                $this->pasadas++;
                return;
            }
            throw $e;
        }
    }

    private function testAltaClienteLimiteRazonSocial300Caracteres_CA06_4(): void
    {
        $this->ejecutadas++;
        $service = new ClienteService();
        $razon300 = str_repeat('A', 300);

        $cliente = $service->registrarCliente([
            'razon_social' => $razon300,
            'rfc' => 'LIM300101ZZ1',
            'contacto' => 'Contacto Límite',
            'correo' => 'limite@empresa.mx',
            'telefono' => '4770001111',
            'domicilio' => 'Domicilio Fiscal 300',
        ]);

        if (mb_strlen($cliente['razon_social'], 'UTF-8') === 300 && $cliente['razon_social'] === $razon300) {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-06.4');
        }
    }

    private function testBusquedaClientesNormal_CA07_1(): void
    {
        $this->ejecutadas++;
        $service = new ClienteService();

        $service->registrarCliente([
            'razon_social' => 'Condominio Los Pinos A.C.',
            'rfc' => 'CPI150303BB2',
            'contacto' => 'Ana Gómez',
            'correo' => 'agomez@lospinos.mx',
            'telefono' => '4773334455',
            'domicilio' => 'Paseo de los Pinos 88',
        ]);

        $resRazon = $service->buscarClientes('Pinos');
        $resRfc = $service->buscarClientes('CPI150303BB2');
        $resCorreo = $service->buscarClientes('agomez@lospinos.mx');

        if (count($resRazon) === 1 && count($resRfc) === 1 && count($resCorreo) === 1) {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-07.1');
        }
    }

    private function testEdicionClienteConHistorial_CA07_2(): void
    {
        $this->ejecutadas++;
        $service = new ClienteService();

        $c = $service->registrarCliente([
            'razon_social' => 'Plaza Mayor León S.A.',
            'rfc' => 'PML200101CC3',
            'contacto' => 'Pedro Infante',
            'correo' => 'pinfante@plaza.mx',
            'telefono' => '4771002000',
            'domicilio' => 'Blvd. López Mateos 1000',
        ]);

        $actualizado = $service->editarCliente(
            $c['id'],
            ['telefono' => '4779990000', 'contacto' => 'Ing. Pedro Infante'],
            'admin@elevese.mx'
        );

        if (
            $actualizado['telefono'] === '4779990000'
            && count($actualizado['historial_cambios']) === 1
            && $actualizado['historial_cambios'][0]['usuario'] === 'admin@elevese.mx'
        ) {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-07.2');
        }
    }

    private function testBajaLogicaConservaActivosYCotizaciones_CA07_3(): void
    {
        $this->ejecutadas++;
        $service = new ClienteService();

        $c = $service->registrarCliente([
            'razon_social' => 'Hotel Real de Minas S.A.',
            'rfc' => 'HRM101010DD4',
            'contacto' => 'Sofía Lara',
            'correo' => 'slara@realdeminas.mx',
            'telefono' => '4775556677',
            'domicilio' => 'Blvd. Adolfo López Mateos 2200',
        ]);

        $service->darDeBajaLogica(
            $c['id'],
            [['activo_id' => 10, 'serie' => 'ELEV-01'], ['activo_id' => 11, 'serie' => 'ELEV-02']],
            [['folio' => 'COT-2026-0001'], ['folio' => 'COT-2026-0002'], ['folio' => 'COT-2026-0003']]
        );

        $activosBusqueda = $service->buscarClientes('Real de Minas');
        $historico = $service->obtenerClienteConHistorial($c['id']);

        if (
            count($activosBusqueda) === 0
            && $historico['activo'] === false
            && count($historico['activos_vinculados']) === 2
            && count($historico['cotizaciones_vinculadas']) === 3
        ) {
            $this->pasadas++;
        } else {
            throw new RuntimeException('Fallo en CA-07.3');
        }
    }

    private function testEdicionClienteRfcAjeno_CA07_4(): void
    {
        $this->ejecutadas++;
        $service = new ClienteService();

        $c1 = $service->registrarCliente([
            'razon_social' => 'Cliente Uno S.A.',
            'rfc' => 'CUN010101AA1',
            'contacto' => 'Uno',
            'correo' => 'uno@cliente.mx',
            'telefono' => '4771111111',
            'domicilio' => 'Calle 1',
        ]);

        $c2 = $service->registrarCliente([
            'razon_social' => 'Cliente Dos S.A.',
            'rfc' => 'CDO020202BB2',
            'contacto' => 'Dos',
            'correo' => 'dos@cliente.mx',
            'telefono' => '4772222222',
            'domicilio' => 'Calle 2',
        ]);

        try {
            $service->editarCliente($c2['id'], ['rfc' => $c1['rfc']], 'admin@elevese.mx');
            throw new RuntimeException('Debió fallar CA-07.4');
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'Ese RFC ya pertenece a otro cliente') {
                $this->pasadas++;
                return;
            }
            throw $e;
        }
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $res = (new ClienteServiceTest())->ejecutarTodas();
    echo "ClienteServiceTest (inc2): {$res['pasadas']}/{$res['ejecutadas']} pasadas\n";
    exit($res['pasadas'] === $res['ejecutadas'] ? 0 : 1);
}
