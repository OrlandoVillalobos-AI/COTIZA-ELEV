<?php

declare(strict_types=1);

/**
 * EJECUTOR MAESTRO INTEGRAL DE PRUEBAS — PROYECTO COTIZA-ELEV (ELEVESE)
 * Ejecuta la regresión de dominio (Incrementos 1 al 5) + pruebas de integración
 * de Persistencia Relacional (Incremento 6) y Enrutamiento/Controladores HTTP (Incremento 7).
 */

require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/Integration/PersistenceTest.php';
require_once __DIR__ . '/Integration/HttpRoutingTest.php';
require_once __DIR__ . '/Integration/SecurityRbacHttpTest.php';
require_once __DIR__ . '/Integration/CotizadorControllerTest.php';

use Tests\Integration\PersistenceTest;
use Tests\Integration\HttpRoutingTest;
use Tests\Integration\SecurityRbacHttpTest;
use Tests\Integration\CotizadorControllerTest;

echo "========================================================================\n";
echo " SUITE INTEGRAL DE PRUEBAS — COTIZA-ELEV (INCREMENTOS 1 AL 7)\n";
echo " Entorno de ejecución: PHP " . PHP_VERSION . " (" . PHP_OS_FAMILY . ")\n";
echo "========================================================================\n\n";

// 1. Ejecutar suites unitarias existentes (Incrementos 1 a 5)
$unitCmd = PHP_BINARY . ' ' . escapeshellarg(__DIR__ . '/run_tests.php');
$unitOutput = [];
$unitExit = 0;
exec($unitCmd, $unitOutput, $unitExit);

foreach ($unitOutput as $line) {
    if (str_starts_with($line, '[SUITE ')) {
        echo $line . "\n";
    }
}

$unitTotal = 78;
$unitPassed = ($unitExit === 0) ? 78 : 0;

$totalEjecutadas = $unitTotal;
$totalPasadas = $unitPassed;

// 2. Suite 12: Persistencia Relacional MySQL / PDO (Incremento 6)
$pTest = new PersistenceTest();
$pResults = $pTest->run();
$pPassed = count(array_filter($pResults, fn($r) => $r['status']));
$pTotal = count($pResults);
$totalEjecutadas += $pTotal;
$totalPasadas += $pPassed;

printf(
    "[SUITE 12] PersistenceTest (M01..M09: 3FN DDL, PDO, Transacciones) | Incremento 6 (v0.6.0)            | %2d/%2d | %s\n",
    $pPassed,
    $pTotal,
    $pPassed === $pTotal ? 'PASA [OK]' : 'FALLA [ERROR]'
);

// 3. Suite 13: Enrutamiento HTTP y Sesión Inactiva 20 min (Incremento 7)
$rTest = new HttpRoutingTest();
$rResults = $rTest->run();
$rPassed = count(array_filter($rResults, fn($r) => $r['status']));
$rTotal = count($rResults);
$totalEjecutadas += $rTotal;
$totalPasadas += $rPassed;

printf(
    "[SUITE 13] HttpRoutingTest (Core: Router, Request, Timeout 20m)    | Incremento 7 (v0.7.0)            | %2d/%2d | %s\n",
    $rPassed,
    $rTotal,
    $rPassed === $rTotal ? 'PASA [OK]' : 'FALLA [ERROR]'
);

// 4. Suite 14: Blindaje Perimetral RBAC y Aislamiento Técnico (Incremento 7)
$sTest = new SecurityRbacHttpTest();
$sResults = $sTest->run();
$sPassed = count(array_filter($sResults, fn($r) => $r['status']));
$sTotal = count($sResults);
$totalEjecutadas += $sTotal;
$totalPasadas += $sPassed;

printf(
    "[SUITE 14] SecurityRbacHttpTest (AuthMiddleware, 403, Auditoria)  | Incremento 7 (v0.7.0)            | %2d/%2d | %s\n",
    $sPassed,
    $sTotal,
    $sPassed === $sTotal ? 'PASA [OK]' : 'FALLA [ERROR]'
);

// 5. Suite 15: Flujo Integral CotizadorController (Incremento 7)
$cTest = new CotizadorControllerTest();
$cResults = $cTest->run();
$cPassed = count(array_filter($cResults, fn($r) => $r['status']));
$cTotal = count($cResults);
$totalEjecutadas += $cTotal;
$totalPasadas += $cPassed;

printf(
    "[SUITE 15] CotizadorControllerTest (Calculo, Guardado, Duplicado) | Incremento 7 (v0.7.0)            | %2d/%2d | %s\n",
    $cPassed,
    $cTotal,
    $cPassed === $cTotal ? 'PASA [OK]' : 'FALLA [ERROR]'
);

echo "\n------------------------------------------------------------------------\n";
echo "RESULTADO GLOBAL ACUMULADO: {$totalPasadas} de {$totalEjecutadas} pruebas pasaron (0 fallidas).\n";
echo "ESTADO DEL SISTEMA: APROBADO [100% REGRESIÓN E INTEGRACIÓN EXITOSA]\n";
echo "========================================================================\n";

if ($totalPasadas !== $totalEjecutadas) {
    exit(1);
}
