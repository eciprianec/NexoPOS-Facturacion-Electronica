<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductCategory;
use App\Models\UnitGroup;
use App\Models\Unit;
use App\Models\Product;
use App\Models\ProductUnitQuantity;
use App\Services\ProductService;

// Find JSON file
$jsonPath = 'C:\\Users\\administrator\\.gemini\\antigravity-ide\\scratch\\NexoPOS\\vocatus_cleaned_products.json';

if (!file_exists($jsonPath)) {
    die("Error: vocatus_products.json not found!\n");
}

echo "Loading dataset from: {$jsonPath}\n";
$data = json_decode(file_get_contents($jsonPath), true);
$categories = $data['categories'] ?? [];
$products = $data['products'] ?? [];

echo "Found " . count($categories) . " categories and " . count($products) . " products to import.\n";

// Ensure Unit Group and Unit exist
$unitGroup = UnitGroup::firstOrCreate(
    ['name' => 'General'],
    ['author_id' => 72]
);

$unit = Unit::firstOrCreate(
    ['name' => 'Unidad'],
    ['identifier' => 'ud', 'group_id' => $unitGroup->id, 'value' => 1, 'base_unit' => 1, 'author_id' => 72]
);

echo "Using Unit Group ID: {$unitGroup->id}, Unit ID: {$unit->id}\n";

// Create Categories map
$categoryMap = [];
foreach ($categories as $catHref => $catName) {
    $category = ProductCategory::firstOrCreate(
        ['name' => $catName],
        ['author_id' => 72, 'displays_on_pos' => 1, 'description' => 'Categoría importada de Vocatus.com.do']
    );
    $categoryMap[$catName] = $category->id;
}

echo "Created/validated " . count($categoryMap) . " categories in NexoPOS.\n";

$productService = app()->make(ProductService::class);

$importedCount = 0;
$skippedCount = 0;
$errorCount = 0;

$startTime = microtime(true);

foreach ($products as $index => $p) {
    $pName = trim($p['name'] ?? '');
    if (empty($pName)) {
        $skippedCount++;
        continue;
    }

    $catName = $p['category'] ?? 'General';
    $catId = $categoryMap[$catName] ?? null;
    if (!$catId) {
        $catObj = ProductCategory::firstOrCreate(
            ['name' => $catName],
            ['author_id' => 72, 'displays_on_pos' => 1]
        );
        $catId = $catObj->id;
        $categoryMap[$catName] = $catId;
    }

    $barcode = sprintf('VOC-P%05d', $index + 1);
    $sku = $barcode;
    $price = floatval($p['price'] ?? 0.0);
    $imageUrl = $p['image'] ?? '';

    // Check if barcode or sku already exists
    $existing = Product::where('barcode', $barcode)->orWhere('sku', $sku)->first();
    if ($existing) {
        ProductUnitQuantity::where('product_id', $existing->id)->update(['quantity' => 50]);
        $importedCount++;
        continue;
    }

    $productData = [
        'name' => $pName,
        'product_type' => 'product',
        'type' => 'single',
        'status' => 'available',
        'category_id' => $catId,
        'author_id' => 72,
        'barcode_type' => 'code128',
        'barcode' => $barcode,
        'sku' => $sku,
        'stock_management' => 'enabled',
        'units' => [
            'unit_group' => $unitGroup->id,
            'selling_group' => [
                [
                    'unit_id' => $unit->id,
                    'sale_price_edit' => $price,
                    'wholesale_price_edit' => $price,
                    'cogs' => round($price * 0.7, 2),
                    'preview_url' => $imageUrl,
                    'visible' => true,
                    'barcode' => $barcode,
                ]
            ]
        ],
        'images' => $imageUrl ? [
            ['url' => $imageUrl, 'featured' => 1]
        ] : []
    ];

    try {
        $res = $productService->create($productData);
        if (isset($res['data']['product'])) {
            $createdProd = $res['data']['product'];
            // Set 50 units stock
            ProductUnitQuantity::where('product_id', $createdProd->id)->update(['quantity' => 50]);
            $importedCount++;
        }
    } catch (\Throwable $e) {
        $errorCount++;
        if ($errorCount <= 5) {
            echo "Error importing product '{$pName}': " . $e->getMessage() . "\n";
        }
    }

    if (($index + 1) % 250 === 0) {
        $elapsed = round(microtime(true) - $startTime, 1);
        echo "Processed " . ($index + 1) . " / " . count($products) . " products ({$importedCount} imported, {$errorCount} errors, {$elapsed}s)\n";
    }
}

$totalTime = round(microtime(true) - $startTime, 1);
echo "\n=========================================\n";
echo "IMPORT COMPLETE!\n";
echo "Total Products Processed: " . count($products) . "\n";
echo "Successfully Imported: {$importedCount}\n";
echo "Skipped: {$skippedCount}\n";
echo "Errors: {$errorCount}\n";
echo "Time Taken: {$totalTime} seconds\n";
echo "=========================================\n";
