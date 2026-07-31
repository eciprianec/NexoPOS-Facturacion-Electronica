<?php
/**
 * Script de prueba para verificar que los recibos fiscales DGII
 * se generan correctamente con los distintos tipos de comprobante.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Modules\Dgii\Services\DgiiSequenceService;

echo "=== PRUEBA DE RECIBOS FISCALES DGII 80mm ===\n\n";

// ── TEST 1: Verificar que el módulo intercepta el template ──
echo "1. Hook ns-web-receipt-template:\n";
$template = \App\Classes\Hook::filter('ns-web-receipt-template', 'pages.dashboard.orders.templates._receipt');
echo "   Template actual: {$template}\n";
if ($template === 'Dgii::_fiscal_receipt') {
    echo "   ✅ ¡CORRECTO! El módulo intercepta el recibo estándar.\n\n";
} else {
    echo "   ⚠️  El template NO fue interceptado. Verificar que el módulo esté habilitado.\n\n";
}

// ── TEST 2: Crear facturas de prueba para cada tipo ──
$seqService = new DgiiSequenceService();

$tiposTest = [
    'E32' => ['rnc' => '',          'name' => 'CONSUMIDOR FINAL',      'desc' => 'Cliente genérico (sin RNC)'],
    'E31' => ['rnc' => '101000000', 'name' => 'EMPRESA DE PRUEBA SRL', 'desc' => 'Cliente con RNC válido'],
    'E33' => ['rnc' => '101000000', 'name' => 'EMPRESA DE PRUEBA SRL', 'desc' => 'Nota de Débito'],
    'E34' => ['rnc' => '101000000', 'name' => 'EMPRESA DE PRUEBA SRL', 'desc' => 'Nota de Crédito'],
];

echo "2. Generación de NCF para cada tipo de comprobante:\n";
foreach ($tiposTest as $tipo => $data) {
    $seq = $seqService->getNextNcf($tipo);
    echo "   [{$tipo}] {$data['desc']}:\n";
    echo "       NCF: {$seq['ncf']}\n";
    echo "       RNC: " . ($data['rnc'] ?: 'N/A (Consumidor Final)') . "\n";
    echo "       Nombre: {$data['name']}\n\n";
}

// ── TEST 3: Verificar tabla de facturas ──
$count = DB::table('nexopos_dgii_invoices')->count();
echo "3. Total de registros en nexopos_dgii_invoices: {$count}\n\n";

// ── TEST 4: Verificar que las órdenes existentes son accesibles ──
$lastOrder = DB::table('nexopos_orders')->orderBy('id', 'desc')->first();
if ($lastOrder) {
    echo "4. Última orden en NexoPOS: #{$lastOrder->id} (Code: {$lastOrder->code})\n";
    echo "   URL del recibo fiscal: http://127.0.0.1:8000/dashboard/orders/receipt/{$lastOrder->id}?dash-visibility=disabled\n";
    echo "   URL impresión directa: http://127.0.0.1:8000/dashboard/orders/receipt/{$lastOrder->id}?dash-visibility=disabled&autoprint=true\n\n";
} else {
    echo "4. No hay órdenes en el sistema aún. Crea una venta en el POS para probar.\n\n";
}

echo "=== PRUEBA COMPLETADA ===\n";
echo "\nPara ver un recibo de ejemplo, abre en tu navegador:\n";
echo "  http://127.0.0.1:8000/dashboard/orders/receipt/<ORDER_ID>?dash-visibility=disabled\n";
