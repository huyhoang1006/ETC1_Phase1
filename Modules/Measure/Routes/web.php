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
use Modules\Measure\Http\Controllers\MeasureController;
use Illuminate\Http\Request;

Route::prefix('admin/bao-cao-thong-ke/do-luong')->middleware(['auth.custom', 'permission.custom:'.config('constant.permissions.measure')])->group(function() {
    Route::get('danh-sach-thiet-bi-theo-han-diem-dinh',[MeasureController::class, 'equipmentUnderInspection'])->name('admin.equipmentUnderInspection');
    Route::get('export-danh-sach-thiet-bi-theo-han-diem-dinh',[MeasureController::class, 'exportEquipmentUnderInspection'])->name('admin.exportEquipmentUnderInspection');

    Route::get('bao-cao-so-luong-thiet-bi-cua-tung-nam',[MeasureController::class, 'equipmentEveryYear'])->name('admin.equipmentEveryYear');
    Route::get('export-bao-cao-so-luong-thiet-bi-cua-tung-nam',[MeasureController::class, 'exportEquipmentEveryYear'])->name('admin.exportEquipmentEveryYear');

    Route::get('liet-ke-danh-sach-thiet-bi',[MeasureController::class, 'deviceListingReport'])->name('admin.deviceListingReport');
    Route::get('liet-ke-danh-sach-thiet-bi/export',[MeasureController::class, 'deviceListingExport'])->name('admin.deviceListingExport');

    Route::get('bao-cao-so-luong-su-co-theo-hang',[MeasureController::class, 'incidentByCompany'])->name('admin.incidentByCompany');

    Route::get('bao-cao-so-luong-thiet-bi-sai-so-khong-dat-theo-hang-san-xuat',[MeasureController::class, 'defectiveDevicesByManufacturer'])->name('admin.defectiveDevicesByManufacturer');
    Route::post('export-bao-cao-so-luong-thiet-bi-sai-so-khong-dat-theo-hang-san-xuat',[MeasureController::class, 'exportDefectiveDevicesByManufacturer'])->name('admin.exportDefectiveDevicesByManufacturer');

    Route::get('/get-list-td', [MeasureController::class, 'ajaxGetTD'])->name('admin.measure.ajaxGetTD');
});
