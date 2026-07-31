<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Modules\Dgii\Services\DgiiRncValidatorService;
use Modules\Dgii\Services\DgiiSequenceService;
use Modules\Dgii\Services\DgiiReportService;
use Illuminate\Support\Facades\DB;

echo "=== TEST 1: RNC VALIDATION & LOOKUP ===\n";
$validator = new DgiiRncValidatorService();
$resRnc1 = $validator->validateAndLookup('101000000');
echo "Result RNC 101000000: " . json_encode($resRnc1, JSON_UNESCAPED_UNICODE) . "\n\n";

$resRncInvalid = $validator->validateAndLookup('123');
echo "Result Invalid RNC 123: " . json_encode($resRncInvalid, JSON_UNESCAPED_UNICODE) . "\n\n";

echo "=== TEST 2: NCF / e-CF SEQUENCE GENERATION ===\n";
$seqService = new DgiiSequenceService();
$e31 = $seqService->getNextNcf('E31');
echo "Generated e-CF 31 (Crédito Fiscal): {$e31['ncf']}\n";

$e32 = $seqService->getNextNcf('E32');
echo "Generated e-CF 32 (Consumo): {$e32['ncf']}\n";

echo "\n=== TEST 3: SIMULATING ORDER & INVOICE CREATION ===\n";
$dummyOrderId = 99991;
DB::table('nexopos_dgii_invoices')->insert([
    'order_id' => $dummyOrderId,
    'ncf' => $e31['ncf'],
    'ecf_type' => 'E31',
    'rnc_buyer' => '101000000',
    'buyer_name' => $resRnc1['name'] ?? 'EMPRESA DE PRUEBA SRL',
    'total_amount' => 5500.00,
    'tax_amount' => 990.00,
    'track_id' => 'TRK-TEST-001',
    'security_code' => 'SEC001',
    'status' => 'accepted',
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "Created test invoice record for Order #{$dummyOrderId} with NCF {$e31['ncf']}.\n\n";

echo "=== TEST 4: FORMATO 607 REPORT GENERATION (.TXT) ===\n";
$reportService = new DgiiReportService();
$period = date('Ym');
$report607 = $reportService->generate607($period);

echo "Report 607 Period: {$report607['period']}\n";
echo "Total Records: {$report607['total_records']}\n";
echo "Total Sales: RD$ {$report607['total_amount']}\n";
echo "Total ITBIS: RD$ {$report607['total_tax']}\n\n";
echo "--- PREVIEW TXT FORMAT FOR DGII ---\n";
echo $report607['txt_content'] . "\n";
echo "-----------------------------------\n";

// Cleanup test order invoice
DB::table('nexopos_dgii_invoices')->where('order_id', $dummyOrderId)->delete();
echo "\nTEST SUITE COMPLETED SUCCESSFULLY!\n";
