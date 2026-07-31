<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$invoices = DB::table('nexopos_dgii_invoices')->get();
echo "Facturas DGII registradas: " . count($invoices) . "\n";
foreach($invoices as $inv) {
    echo "  Order #{$inv->order_id} → NCF: {$inv->ncf} | Tipo: {$inv->ecf_type} | RNC: " . ($inv->rnc_buyer ?: 'CONSUMIDOR FINAL') . " | {$inv->buyer_name}\n";
}

echo "\nÚltimas 3 órdenes:\n";
$orders = DB::table('nexopos_orders')->orderBy('id','desc')->limit(3)->get();
foreach($orders as $o) {
    echo "  #{$o->id} → Code: {$o->code} | Total: {$o->total} | Customer ID: {$o->customer_id}\n";
}
