<?php

use Illuminate\Support\Facades\Route;
use Modules\Report\Http\Controllers\ReportController;
use Modules\Report\Http\Controllers\PetrochemicalController;

Route::prefix('admin/bao-cao-phan-tich')->middleware(['auth.custom', 'permission.custom:'.config('constant.permissions.petrochemical')])->group(function() {
    Route::get('/', [ReportController::class, 'index'])->name('admin.report.index');
    Route::get('/bien-ban-khi-hoa-tan', [ReportController::class, 'hoa_dau__bien_ban_khi_hoa_tan_trong_dau_cach_dien'])->name('admin.report.1');
    Route::get('/bien-ban-khi-hoa-tan/preview/{id}', [ReportController::class, 'preview'])->name('admin.report.preview');
    Route::get('/bien-ban-khi-hoa-tan/export/{id}', [ReportController::class, 'export'])->name('admin.report.export');

    Route::get('/bien-ban-oltc', [ReportController::class, 'oltcAnalytic'])->name('admin.oltcAnalytic');
    Route::get('/bien-ban-oltc/preview/{id}', [ReportController::class, 'oltcAnalytic_preview'])->name('admin.oltcAnalytic_preview');
    Route::get('/bien-ban-oltc/export/{id}', [ReportController::class, 'oltcAnalytic_export'])->name('adminoltcAnalytic_export');

});

Route::prefix('admin/bao-cao-thong-ke/hoa-dau')->middleware(['auth.custom', 'permission.custom:'.config('constant.permissions.petrochemical')])->group(function() {
    Route::get('/bao-cao-thong-ke-hang-san-xuat',[PetrochemicalController::class, 'petrochemicalManufactures'])->name('admin.petrochemicalManufactures');
    Route::post('/bao-cao-thong-ke-hang-san-xuat/preview',[PetrochemicalController::class, 'petrochemicalManufacturesPreview'])->name('admin.petrochemicalManufacturesPreview');
    Route::get('/bao-cao-thong-ke-hang-san-xuat/export',[PetrochemicalController::class, 'petrochemicalManufacturesExport'])->name('admin.petrochemicalManufacturesExport');

    Route::get('/bao-cao-so-luong-thiet-bi-cua-tung-hang-san-xuat',[PetrochemicalController::class, 'numberOfDevicesByManufactureReport'])->name('admin.numberOfDevicesByManufactureReport');
    Route::post('/bao-cao-so-luong-thiet-bi-cua-tung-hang-san-xuat/preview',[PetrochemicalController::class, 'numberOfDevicesByManufactureReportPreview'])->name('admin.numberOfDevicesByManufactureReportPreview');
    Route::get('/bao-cao-so-luong-thiet-bi-cua-tung-hang-san-xuat/export',[PetrochemicalController::class, 'numberOfDevicesByManufactureReportExport'])->name('admin.numberOfDevicesByManufactureReportExport');

    Route::get('/bao-cao-ty-le-theo-so-luong-tung-hang-san-xuat',[PetrochemicalController::class, 'quantityPercentageByManufacturerReport'])->name('admin.quantityPercentageByManufacturerReport');
    Route::post('/bao-cao-ty-le-theo-so-luong-tung-hang-san-xuat/preview',[PetrochemicalController::class, 'quantityPercentageByManufacturerReportPreview'])->name('admin.quantityPercentageByManufacturerReportPreview');
    Route::get('/bao-cao-ty-le-theo-so-luong-tung-hang-san-xuat/export',[PetrochemicalController::class, 'quantityPercentageByManufacturerReportExport'])->name('admin.quantityPercentageByManufacturerReportExport');

    Route::get('bao-cao-ti-le-theo-so-luong-thiet-bi-cua-tung-hang-san-xuat-ung-voi-nam-hoac-khoang-thoi-gian-san-xuat', [ReportController::class, 'deviceReport'])->name('admin.deviceReport');
    Route::post('bao-cao-ti-le-theo-so-luong-thiet-bi-cua-tung-hang-san-xuat-ung-voi-nam-hoac-khoang-thoi-gian-san-xuat/export', [ReportController::class, 'deviceReportExport'])->name('admin.deviceReportExport');

    Route::get('bao-cao-thong-ke-thi-nghiem-theo-khu-vuc', [ReportController::class, 'reportExperimentalByRegion'])->name('admin.reportExperimentalByRegion');
    Route::post('bao-cao-thong-ke-thi-nghiem-theo-khu-vuc/export', [ReportController::class, 'reportExperimentalByRegionExport'])->name('admin.reportExperimentalByRegionExport');

    Route::get('bao-cao-thong-ke-ket-qua-thi-nghiem-dinh-ky', [ReportController::class, 'reportStatisticalPeriodicallyPetrochemical'])->name('admin.reportStatisticalPeriodicallyPetrochemical');
    Route::post('bao-cao-thong-ke-ket-qua-thi-nghiem-dinh-ky/export', [ReportController::class, 'reportStatisticalPeriodicallyPetrochemicalExport'])->name('admin.reportStatisticalPeriodicallyPetrochemicalExport');

    Route::get('/bao-cao-chat-luong-dau', [ReportController::class, 'oilQualityReport'])->name('admin.oilQualityReport');
    Route::get('/bao-cao-chat-luong-dau-preview', [ReportController::class, 'oilQualityReportPreview'])->name('admin.oilQualityReportPreview');

    Route::get('/bao-cao-khi-hoa-tan-trong-dau', [ReportController::class, 'reportDissolvedGasOil'])->name('admin.reportDissolvedGasOil');
    Route::get('/bao-cao-khi-hoa-tan-trong-dau-preview', [ReportController::class, 'reportDissolvedGasOilPreview'])->name('admin.reportDissolvedGasOilPreview');

    Route::get('/bao-cao-chat-luong-khi-sf6', [ReportController::class, 'sf6GasQualityReport'])->name('admin.sf6GasQualityReport');
    Route::get('/bao-cao-chat-luong-khi-sf6-preview', [ReportController::class, 'sf6GasQualityReportPreview'])->name('admin.sf6GasQualityReportPreview');

    Route::get('/get-list-device-name', [ReportController::class, 'ajaxGetListDeviceName'])->name('admin.ajaxGetListDeviceName');
});
