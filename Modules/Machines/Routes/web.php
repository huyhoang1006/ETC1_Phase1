<?php

use Illuminate\Support\Facades\Route;
use Modules\Machines\Http\Controllers\MachinesController;
use Modules\Machines\Http\Controllers\HighPressureController;
use Modules\Machines\Http\Controllers\PetrochemicalController;

Route::get('danh-sach-object-theo-loai-thiet-bi',[PetrochemicalController::class, 'objectByDeviceType'])->name('admin.objectByDeviceType');

Route::prefix('admin/bao-cao-cao-ap/may-cat')->middleware(['auth.custom', 'permission.custom:'.config('constant.permissions.high_pressure')])->group(function() {
    Route::get('bao-cao-kiem-tra-ben-ngoai-cao-ap',[MachinesController::class, 'bao_cao_kiem_tra_ben_ngoai'])->name('admin.kiem_tra_ben_ngoai_cao_ap');
    Route::get('bao-cao-dien-tro-cach-dien-cao-ap',[MachinesController::class, 'bao_cao_dien_tro_cach_dien'])->name('admin.dien_tro_cach_dien_cao_ap');
    Route::get('bao-cao-dien-tro-tiep-xuc-cao-ap',[MachinesController::class, 'bao_cao_dien_tro_tiep_xuc'])->name('admin.dien_tro_tiep_xuc_cao_ap');
    Route::get('export',[MachinesController::class, 'export'])->name('admin.machines.export');
    Route::get('export_dien_tro',[MachinesController::class, 'export_dien_tro'])->name('admin.machines.export_dien_tro');
    Route::get('export_tiep_xuc',[MachinesController::class, 'export_tiep_xuc'])->name('admin.machines.export_tiep_xuc');

    Route::get('/bao-cao-ap-luc-khi-nap-preview', [MachinesController::class, 'intakeAirPressureReport'])->name('admin.intakeAirPressureReport');
    Route::get('/dien-tro-cach-dien-cuon-dong-cuon-cat-preview', [MachinesController::class, 'insulationIndex'])->name('admin.insulationIndex');
    Route::get('/bao-cao-dong-co-tich-nang-preview', [MachinesController::class, 'accumulativeEngineReport'])->name('admin.accumulativeEngineReport');
    Route::get('/bao-cao-thi-nghiem-dien-ap-xoay-chieu-tang-cao-preview', [MachinesController::class, 'voltageRisesHighReport'])->name('admin.voltageRisesHighReport');
    Route::get('/bao-cao-kiem-tra-co-cau-truyen-dong-preview', [MachinesController::class, 'checkTransmissionMechanismReport'])->name('admin.checkTransmissionMechanism');

    Route::get('/kiem-tra-ben-ngoai-preview', [HighPressureController::class, 'externalInspectionReport'])->name('admin.highPressure.externalInspectionReport');
    Route::get('/thoi-gian-cat-preview', [HighPressureController::class, 'cuttingTimeReport'])->name('admin.highPressure.cuttingTimeReport');

    Route::post('/ajax-validate', [HighPressureController::class, 'ajaxValidate'])->name('admin.ajaxValidate');
    Route::get('/dien-tro-tiep-xuc-preview', [HighPressureController::class, 'contactTimeReport'])->name('admin.highPressure.contactTimeReport');
    // route for report 6->8 follow specs
    Route::get('/share-report-preview', [HighPressureController::class, 'shareReportPreview'])->name('admin.shareReportPreview');
    Route::get('/share-report-table-preview', [HighPressureController::class, 'shareReportTable'])->name('admin.shareReportTable');
    Route::get('/dien-tro-cach-dien-preview', [HighPressureController::class, 'insulationResistanceReport'])->name('admin.highPressure.insulationResistanceReport');
    Route::get('/', [HighPressureController::class, 'indexShare'])->name('admin.highPressure.indexShare');
});

// Statistics
Route::prefix('admin/bao-cao-thong-ke/cao-ap')->middleware(['auth.custom', 'permission.custom:'.config('constant.permissions.high_pressure')])->group(function() {
    Route::get('/bao-cao-thong-ke-so-luong-va-danh-sach-thiet-bi', [HighPressureController::class, 'statisticalListAndNumberDevice'])->name('admin.statisticalListAndNumberDevice');
    Route::post('/bao-cao-thong-ke-so-luong-va-danh-sach-thiet-bi-perview', [HighPressureController::class, 'statisticalListAndNumberDeviceExport'])->name('admin.statisticalListAndNumberDeviceExport');
    Route::get('bao-cao-thong-ke-ket-qua-thi-nghiem-thiet-bi', [HighPressureController::class, 'experimentalResultsDevice'])->name('admin.experimentalResultsDevice');
    Route::post('bao-cao-thong-ke-ket-qua-thi-nghiem-thiet-bi-export', [HighPressureController::class, 'experimentalResultsDeviceExport'])->name('admin.experimentalResultsDeviceExport');

    Route::post('get-device-type', [HighPressureController::class, 'ajaxGetDeviceType'])->name('admin.ajaxGetDeviceType');

    Route::get('/{type}',[PetrochemicalController::class, 'highPressureStatisticsReport'])->name('admin.highPressureStatisticsReport');

    Route::post('bao-cao-theo-thoi-gian-tuy-chon',[PetrochemicalController::class, 'highPressureStatisticsOptionalTime'])->name('admin.highPressureStatisticsOptionalTime');
    Route::post('bao-cao-theo-thoi-gian-chinh-xac',[PetrochemicalController::class, 'highPressureStatisticsExactlyTime'])->name('admin.highPressureStatisticsExactlyTime');
    Route::post('bao-cao-theo-quy',[PetrochemicalController::class, 'highPressureStatisticsQuarterly'])->name('admin.highPressureStatisticsQuarterly');
    Route::post('bao-cao-theo-nam',[PetrochemicalController::class, 'highPressureStatisticsAnnually'])->name('admin.highPressureStatisticsAnnually');
    Route::post('bao-cao-theo-theo-doanh-so-va-chat-luong-tung-nha-san-xuat',[PetrochemicalController::class, 'highPressureStatisticsSalesAndQuality'])->name('admin.highPressureStatisticsSalesAndQuality');
    Route::post('bao-cao-so-sanh-doanh-so-giua-cac-nha-san-xuat',[PetrochemicalController::class, 'highPressureStatisticsSalesByManufacture'])->name('admin.highPressureStatisticsSalesByManufacture');
    Route::post('bao-cao-so-sanh-chat-luong-nha-san-xuat',[PetrochemicalController::class, 'highPressureStatisticsQualityByManufacture'])->name('admin.highPressureStatisticsQualityByManufacture');
    Route::post('bao-cao-theo-don-vi-su-dung',[PetrochemicalController::class, 'highPressureStatisticsByUnit'])->name('admin.highPressureStatisticsByUnit');
    Route::post('bao-cao-thong-ke-chat-luong-ton-hao',[PetrochemicalController::class, 'highPressureStatisticsLossQuality'])->name('admin.highPressureStatisticsLossQuality');
});

Route::prefix('admin/bao-cao-cao-ap/may-bien-ap')->middleware(['auth.custom', 'permission.custom:'.config('constant.permissions.high_pressure')])->group(function() {
    Route::get('/', [HighPressureController::class, 'indexMBA'])->name('admin.highPressure.transformers.index');
    Route::post('/get-report', [ HighPressureController::class, 'ajaxGetReport' ])->name('admin.ajaxGetReport');
    Route::post('/get-device', [ HighPressureController::class, 'ajaxGetDevice' ])->name('admin.ajaxGetDevice');
    Route::get('/kiem-tra-tong-quan-preview', [ HighPressureController::class, 'overviewCheckReport'])->name('admin.highPressure.transformers.overviewCheckReport');
    Route::get('/share-report-preview', [HighPressureController::class, 'writeDataShareReportTransformers'])->name('admin.writeDataShareReportTransformers');
    Route::get('/bao-cao-dong-dien-va-ton-hao-khong-tai-preview', [MachinesController::class, 'currentAndNoLoadLossReport'])->name('admin.currentAndNoLoadLossReport');
    Route::get('/bao-cao-phan-tich-ket-qua-thi-nghiem-dien-tro-mot-chieu-cua-cac-cuon-day-preview', [MachinesController::class, 'oneWayResistorReport'])->name('admin.oneWayResistorReport');
    Route::get('/bao-cao-phan-tich-ket-qua-thi-nghiem-cac-su-dau-vao-preview', [HighPressureController::class, 'reportPorcelainTest'])->name('admin.reportPorcelainTest');
    Route::post('/validate-report-one-type-of-recode', [HighPressureController::class, 'ajaxValidateReportOneTypeOfRecord'])->name('admin.ajaxValidateReportOneTypeOfRecord');
    Route::get('/bao-cao-phan-tich-ket-qua-thi-nghiem-dien-tro-cach-dien-gong-tu-mach-tu-preview', [MachinesController::class, 'syllableWordCircuitReport'])->name('admin.syllableWordCircuitReport');
    Route::get('/bao-cao-phan-tich-ket-qua-thi-nghiem-ti-so-bien-preview', [HighPressureController::class, 'rateOfChangeReport'])->name('admin.rateOfChangeReport');
    Route::get('/bao-cao-phan-tich-ket-qua-thi-nghiem-ton-hao-dien-moi-va-dien-dung-cac-cuon-day-may-bien-ap-preview', [MachinesController::class, 'dielectricLossReport'])->name('admin.dielectricLossReport');

    Route::get('/bao-cao-thong-ke-cong-tac-thi-nghiem', [HighPressureController::class, 'statisticalExperimental'])->name('admin.statisticalExperimental');
    Route::post('/get-device-mba', [HighPressureController::class, 'ajaxFilterDevice'])->name('admin.ajaxFilterDevice');
    Route::post('/get-number-report', [HighPressureController::class, 'getNumberOfExperiments'])->name('admin.getNumberOfExperiments');
    Route::get('/get-number-report-export', [HighPressureController::class, 'getNumberOfExperimentsExport'])->name('admin.getNumberOfExperimentsExport');
});