<?php

use Illuminate\Support\Facades\Route;
use Modules\Dgii\Http\Controllers\DgiiSettingsController;
use Modules\Dgii\Http\Controllers\DgiiSequenceController;
use Modules\Dgii\Http\Controllers\DgiiInvoiceController;
use Modules\Dgii\Http\Controllers\DgiiReportsController;

Route::prefix('dashboard/dgii')->middleware(['web', 'auth'])->group(function () {
    Route::get('settings', [DgiiSettingsController::class, 'index'])->name('ns.dashboard.dgii-settings');
    Route::post('settings', [DgiiSettingsController::class, 'save'])->name('ns.dashboard.dgii-settings-save');

    Route::get('sequences', [DgiiSequenceController::class, 'index'])->name('ns.dashboard.dgii-sequences');
    Route::post('sequences', [DgiiSequenceController::class, 'save'])->name('ns.dashboard.dgii-sequences-save');

    Route::get('invoices', [DgiiInvoiceController::class, 'index'])->name('ns.dashboard.dgii-invoices');

    Route::get('reports', [DgiiReportsController::class, 'index'])->name('ns.dashboard.dgii-reports');
    Route::post('reports', [DgiiReportsController::class, 'generate'])->name('ns.dashboard.dgii-reports-generate');
});
