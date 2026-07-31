<?php

use Illuminate\Support\Facades\Route;
use Modules\Dgii\Http\Controllers\DgiiRncController;

Route::prefix('api/dgii')->middleware(['web'])->group(function () {
    Route::post('validate-rnc', [DgiiRncController::class, 'validateRnc'])->name('ns.api.dgii-validate-rnc');
});
