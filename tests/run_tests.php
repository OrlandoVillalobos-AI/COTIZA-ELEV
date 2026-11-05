<?php

declare(strict_types=1);

/**
 * Ejecutor Maestro de Pruebas Unitarias y de Regresión — Proyecto COTIZA-ELEV (ELEVESE)
 * Ejecuta las suites de pruebas de todos los incrementos activos (v0.1.0 a v0.5.0).
 */

require_once __DIR__ . '/../src/Services/CalculoMotorService.php';

use CotizaElev\Services\CalculoMotorService;

echo "========================================================================\n";
echo " SUITE DE PRUEBAS Y REGRESIÓN ACUMULADA — PROYECTO COTIZA-ELEV (ELEVESE)\n";
echo " Entorno de ejecución: PHP " . PHP_VERSION . " (" . PHP_OS_FAMILY . ")\n";
echo "========================================================================\n\n";

$totalEjecutadas = 0;
$totalPasadas = 0;
$resumenSuites = [];

// 1. Suite base Incremento 1 (v0.1.0): CalculoMotorService (M07)
$motor = new CalculoMotorService();
$inc1Ejecutadas = 0;
$inc1Pasadas = 0;

// Caso Normal CA-16.1
$inc1Ejecutadas++;
$r1 = $motor->calcularTotales([
    ['descripcion' => 'Sensor de límite electromecánico', 'cantidad' => 2, 'precio_unitario' => 1000.00],
    ['descripcion' => 'Servicio de mano de obra técnica', 'cantidad' => 1, 'precio_unitario' => 500.00],
], 0.16);
if ($r1['subtotal'] === 2500.00 && $r1['monto_iva'] === 400.00 && $r1['total'] === 2900.00) {
    $inc1Pasadas++;
}

// Caso Límite CA-16.2
$inc1Ejecutadas++;
$r2 = $motor->calcularTotales([
    ['descripcion' => 'Juego de patines de freno', 'cantidad' => 3, 'precio_unitario' => 333.33],
], 0.16);
if ($r2['subtotal'] === 999.99 && $r2['monto_iva'] === 160.00 && $r2['total'] === 1159.99) {
    $inc1Pasadas++;
}

// Caso Error CA-16.3 (Precio negativo)
$inc1Ejecutadas++;
try {
    $motor->calcularTotales([['descripcion' => 'Err', 'cantidad' => 1, 'precio_unitario' => -500.00]], 0.16);
} catch (\InvalidArgumentException $e) {
    $inc1Pasadas++;
}

// Caso Error CA-16.3 (Cantidad cero)
$inc1Ejecutadas++;
try {
    $motor->calcularImportePartida(0, 250.00);
} catch (\InvalidArgumentException $e) {
    $inc1Pasadas++;
}

// Si CalculoMotorServiceTest tiene ejecutarTodas() (extendido en Inc 3), ejecutar también los casos extendidos
$archivoMotorTest = __DIR__ . '/Unit/CalculoMotorServiceTest.php';
if (file_exists($archivoMotorTest)) {
    require_once $archivoMotorTest;
    if (class_exists('\\Tests\\Unit\\CalculoMotorServiceTest') && method_exists('\\Tests\\Unit\\CalculoMotorServiceTest', 'ejecutarTodas')) {
        $suiteM07 = new \Tests\Unit\CalculoMotorServiceTest();
        $resM07 = $suiteM07->ejecutarTodas();
        // Los primeros 3 casos corresponden a Inc 1, los demás a Inc 3 (RF-27, RF-28 desc, RF-29, RF-30)
        if ($resM07['ejecutadas'] > 3) {
            $inc3M07Ejec = $resM07['ejecutadas'] - 3;
            $inc3M07Pas = $resM07['pasadas'] - 3;
        }
    }
}

$totalEjecutadas += $inc1Ejecutadas;
$totalPasadas += $inc1Pasadas;
$resumenSuites[] = [
    'suite' => 'CalculoMotorServiceTest (Base M07: RF-28)',
    'origen' => 'Incremento 1 (v0.1.0)',
    'ejecutadas' => $inc1Ejecutadas,
    'pasadas' => $inc1Pasadas,
];

$catalogoSuites = [
    [
        'archivo' => __DIR__ . '/Unit/AuthRbacServiceTest.php',
        'clase' => '\\Tests\\Unit\\AuthRbacServiceTest',
        'nombre' => 'AuthRbacServiceTest (M01: RF-01..RF-07)',
        'origen_base' => 'Incremento 2 (v0.2.0)',
        'origen_ext' => 'Incremento 2 y 5 (v0.2.0 / v0.5.0)',
        'umbral_ext' => 9,
    ],
    [
        'archivo' => __DIR__ . '/Unit/ClienteServiceTest.php',
        'clase' => '\\Tests\\Unit\\ClienteServiceTest',
        'nombre' => 'ClienteServiceTest (M02: RF-08..RF-12)',
        'origen_base' => 'Incremento 2 (v0.2.0)',
        'origen_ext' => 'Incremento 2 (v0.2.0)',
        'umbral_ext' => 99,
    ],
    [
        'archivo' => __DIR__ . '/Unit/ActivoServiceTest.php',
        'clase' => '\\Tests\\Unit\\ActivoServiceTest',
        'nombre' => 'ActivoServiceTest (M03: RF-13..RF-15)',
        'origen_base' => 'Incremento 2 (v0.2.0)',
        'origen_ext' => 'Incremento 2 y 5 (v0.2.0 / v0.5.0)',
        'umbral_ext' => 5,
    ],
    [
        'archivo' => __DIR__ . '/Unit/CatalogoTarifaServiceTest.php',
        'clase' => '\\Tests\\Unit\\CatalogoTarifaServiceTest',
        'nombre' => 'CatalogoTarifaServiceTest (M04: RF-16..RF-19)',
        'origen_base' => 'Incremento 2 (v0.2.0)',
        'origen_ext' => 'Incremento 2 (v0.2.0)',
        'umbral_ext' => 99,
    ],
    [
        'archivo' => __DIR__ . '/Unit/SolicitudServiceTest.php',
        'clase' => '\\Tests\\Unit\\SolicitudServiceTest',
        'nombre' => 'SolicitudServiceTest (M05: RF-20..RF-24)',
        'origen_base' => 'Incremento 3 (v0.3.0)',
        'origen_ext' => 'Incremento 3 (v0.3.0)',
        'umbral_ext' => 99,
    ],
];

foreach ($catalogoSuites as $meta) {
    if (file_exists($meta['archivo'])) {
        require_once $meta['archivo'];
        if (class_exists($meta['clase'])) {
            $instancia = new $meta['clase']();
            $res = $instancia->ejecutarTodas();
            $totalEjecutadas += $res['ejecutadas'];
            $totalPasadas += $res['pasadas'];
            $origen = ($res['ejecutadas'] >= $meta['umbral_ext']) ? $meta['origen_ext'] : $meta['origen_base'];
            $resumenSuites[] = [
                'suite' => $meta['nombre'],
                'origen' => $origen,
                'ejecutadas' => $res['ejecutadas'],
                'pasadas' => $res['pasadas'],
            ];
        }
    }
}

if (isset($inc3M07Ejec) && $inc3M07Ejec > 0) {
    $totalEjecutadas += $inc3M07Ejec;
    $totalPasadas += $inc3M07Pas;
    $resumenSuites[] = [
        'suite' => 'CalculoMotorServiceTest (Ext. M07: RF-27, RF-29, RF-30)',
        'origen' => 'Incremento 3 (v0.3.0)',
        'ejecutadas' => $inc3M07Ejec,
        'pasadas' => $inc3M07Pas,
    ];
}

$catalogoSuitesFinales = [
    [
        'archivo' => __DIR__ . '/Unit/CotizadorServiceTest.php',
        'clase' => '\\Tests\\Unit\\CotizadorServiceTest',
        'nombre' => 'CotizadorServiceTest (M06: RF-25, RF-26, RF-31..RF-34)',
        'origen_base' => 'Incremento 3 (v0.3.0)',
        'origen_ext' => 'Incremento 3 y 5 (v0.3.0 / v0.5.0)',
        'umbral_ext' => 6,
    ],
    [
        'archivo' => __DIR__ . '/Unit/PropuestaSeguimientoServiceTest.php',
        'clase' => '\\Tests\\Unit\\PropuestaSeguimientoServiceTest',
        'nombre' => 'PropuestaSeguimientoServiceTest (M08: RF-35..RF-38)',
        'origen_base' => 'Incremento 4 (v0.4.0)',
        'origen_ext' => 'Incremento 4 (v0.4.0)',
        'umbral_ext' => 99,
    ],
    [
        'archivo' => __DIR__ . '/Unit/ReporteTableroServiceTest.php',
        'clase' => '\\Tests\\Unit\\ReporteTableroServiceTest',
        'nombre' => 'ReporteTableroServiceTest (M09: RF-39, RF-40)',
        'origen_base' => 'Incremento 4 (v0.4.0)',
        'origen_ext' => 'Incremento 4 (v0.4.0)',
        'umbral_ext' => 99,
    ],
    [
        'archivo' => __DIR__ . '/Unit/AuditoriaErrorServiceTest.php',
        'clase' => '\\Tests\\Unit\\AuditoriaErrorServiceTest',
        'nombre' => 'AuditoriaErrorServiceTest (M09: RF-41..RF-43)',
        'origen_base' => 'Incremento 4 (v0.4.0)',
        'origen_ext' => 'Incremento 4 (v0.4.0)',
        'umbral_ext' => 99,
    ],
];

foreach ($catalogoSuitesFinales as $meta) {
    if (file_exists($meta['archivo'])) {
        require_once $meta['archivo'];
        if (class_exists($meta['clase'])) {
            $instancia = new $meta['clase']();
            $res = $instancia->ejecutarTodas();
            $totalEjecutadas += $res['ejecutadas'];
            $totalPasadas += $res['pasadas'];
            $origen = ($res['ejecutadas'] >= $meta['umbral_ext']) ? $meta['origen_ext'] : $meta['origen_base'];
            $resumenSuites[] = [
                'suite' => $meta['nombre'],
                'origen' => $origen,
                'ejecutadas' => $res['ejecutadas'],
                'pasadas' => $res['pasadas'],
            ];
        }
    }
}

foreach ($resumenSuites as $idx => $fila) {
    $num = $idx + 1;
    $estado = ($fila['pasadas'] === $fila['ejecutadas']) ? 'PASA [OK]' : 'FALLA';
    echo sprintf(
        "[SUITE %02d] %-54s | %-32s | %2d/%2d | %s\n",
        $num,
        $fila['suite'],
        $fila['origen'],
        $fila['pasadas'],
        $fila['ejecutadas'],
        $estado
    );
}

$fallidas = $totalEjecutadas - $totalPasadas;
echo "\n------------------------------------------------------------------------\n";
echo "RESULTADO DE REGRESIÓN ACUMULADA: {$totalPasadas} de {$totalEjecutadas} pruebas pasaron ({$fallidas} fallidas).\n";
echo ($fallidas === 0) ? "ESTADO GLOBAL: APROBADO [100% REGRESIÓN EXITOSA]\n" : "ESTADO GLOBAL: FALLIDO\n";
echo "========================================================================\n";

exit($fallidas === 0 ? 0 : 1);
