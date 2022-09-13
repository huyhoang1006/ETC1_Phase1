<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;
use Modules\Role\Http\Controllers\RoleController;

Route::prefix('admin/bao-cao-thong-ke/ro-le')->middleware(['auth.custom', 'permission.custom:'.config('constant.permissions.relay')])->group(function() {
    Route::get('bao-cao-thong-ke-hang-thiet-bi',[RoleController::class, 'equipmentManufacturerStatisticsReport'])->name('admin.equipmentManufacturerStatisticsReport');
    Route::post('bao-cao-thong-ke-hang-thiet-bi-preview',[RoleController::class, 'equipmentManufacturerStatisticsReportPreview'])->name('admin.equipmentManufacturerStatisticsReportPreview');

    Route::get('bao-cao-thong-ke-ro-le-hu-hong',[RoleController::class, 'reportDamagedRelay'])->name('admin.reportDamagedRelay');
    Route::post('bao-cao-thong-ke-ro-le-hu-hong-preview',[RoleController::class, 'reportDamagedRelayPreview'])->name('admin.reportDamagedRelayPreview');

    Route::get('bao-cao-thong-ke-cong-tac-thi-nghiem-ro-le', [RoleController::class, 'reportRelayTestManifestSystem'])->name('admin.reportRelayTestManifestSystem');
    Route::post('bao-cao-thong-ke-cong-tac-thi-nghiem-ro-le/export', [RoleController::class, 'reportRelayTestManifestSystemExport'])->name('admin.reportRelayTestManifestSystemExport');
});
