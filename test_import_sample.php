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
use App\Services\ProductCategoryService;

echo "Checking Unit & Category services...\n";

$unitGroup = UnitGroup::firstOrCreate(
    ['name' => 'General'],
    ['author_id' => 72]
);

$unit = Unit::firstOrCreate(
    ['name' => 'Unidad'],
    ['identifier' => 'ud', 'group_id' => $unitGroup->id, 'value' => 1, 'base_unit' => 1, 'author_id' => 72]
);

echo "Unit Group ID: {$unitGroup->id}, Unit ID: {$unit->id}\n";

$category = ProductCategory::firstOrCreate(
    ['name' => 'Bebidas'],
    ['author_id' => 72, 'displays_on_pos' => 1]
);

$productService = app()->make(ProductService::class);

$sampleData = [
    'name' => 'Ron Test Vocatus',
    'product_type' => 'product',
    'type' => 'single',
    'status' => 'available',
    'category_id' => $category->id,
    'author_id' => 72,
    'barcode_type' => 'code128',
    'barcode' => 'VOC-TEST-001',
    'sku' => 'VOC-TEST-001',
    'stock_management' => 'enabled',
    'units' => [
        'unit_group' => $unitGroup->id,
        'selling_group' => [
            [
                'unit_id' => $unit->id,
                'sale_price_edit' => 1250.00,
                'wholesale_price_edit' => 1250.00,
                'cogs' => 900.00,
                'visible' => true,
                'barcode' => 'VOC-TEST-001',
            ]
        ]
    ]
];

try {
    $res = $productService->create($sampleData);
    $product = $res['data']['product'];
    echo "Created sample product ID: {$product->id}\n";
    
    // Set 50 units stock
    ProductUnitQuantity::where('product_id', $product->id)->update(['quantity' => 50]);
    
    echo "Successfully updated stock to 50 units for product ID: {$product->id}\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
