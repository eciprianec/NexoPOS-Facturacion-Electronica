<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\ModulesService;
use App\Services\Options;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

echo "1. Registering Dgii module in enabled_modules...\n";

$options = app()->make(Options::class);
$enabledModules = $options->get('enabled_modules', []);
if (!in_array('Dgii', (array)$enabledModules)) {
    $enabledModules[] = 'Dgii';
    $options->set('enabled_modules', $enabledModules);
    echo "Module 'Dgii' added to enabled_modules!\n";
} else {
    echo "Module 'Dgii' was already enabled!\n";
}

echo "2. Running database migrations for Dgii...\n";
$migration = require __DIR__ . '/modules/Dgii/Migrations/2026_07_29_000001_create_dgii_tables.php';
$migration->up();
echo "Migration executed successfully!\n";

echo "3. Checking database tables...\n";
echo "nexopos_dgii_settings exists: " . (Schema::hasTable('nexopos_dgii_settings') ? 'YES' : 'NO') . "\n";
echo "nexopos_dgii_sequences exists: " . (Schema::hasTable('nexopos_dgii_sequences') ? 'YES' : 'NO') . "\n";
echo "nexopos_dgii_invoices exists: " . (Schema::hasTable('nexopos_dgii_invoices') ? 'YES' : 'NO') . "\n";

echo "4. Testing DgiiModule discovery...\n";
$modulesService = app()->make(ModulesService::class);
$modulesService->load();
$dgii = $modulesService->get('Dgii');

if ($dgii) {
    echo "Successfully loaded Dgii module! Name: {$dgii['name']}, Version: {$dgii['version']}\n";
} else {
    echo "Warning: Dgii module not returned by ModulesService.\n";
}
