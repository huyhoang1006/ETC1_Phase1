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

use Modules\Electromechanical\Http\Controllers\ElectromechanicalController;
use Modules\Electromechanical\Http\Controllers\ElectromechanicalController2;
use Modules\Electromechanical\Http\Controllers\ElectromechanicalSupplierController;

Route::prefix('admin/bao-cao-thong-ke/pxcd')->middleware(['auth.custom', 'permission.custom:'.config('constant.permissions.electromechanical')])->group(function() {
    Route::get('bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-tong-quan',[ElectromechanicalController2::class, 'conductorStatisticsReport'])->name('admin.conductorStatisticsReport');
    Route::post('bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem//bao-cao-tong-quan-export',[ElectromechanicalController2::class, 'exportConductorStatisticsReport'])->name('admin.exportConductorStatisticsReport');

    Route::get('bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-thoi-gian/bao-cao-theo-thang',[ElectromechanicalController::class, 'monthlyReport'])->name('admin.monthlyReport');
    Route::get('bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-thoi-gian/bao-cao-theo-quy',[ElectromechanicalController::class, 'quarterlyReport'])->name('admin.quarterlyReport');
    Route::post('bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-thoi-gian/preview',[ElectromechanicalController::class, 'timePeriodReportPreview'])->name('admin.timePeriodReportPreview');

    Route::get('bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-thoi-gian/bao-cao-theo-nam',[ElectromechanicalController::class, 'annuallyReport'])->name('admin.annuallyReport');
    Route::post('bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-thoi-gian/bao-cao-theo-nam/preview',[ElectromechanicalController::class, 'annuallyReportPreview'])->name('admin.annuallyReportPreview');

    Route::get('bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-nha-cung-cap/bao-cao-so-sanh-chat-luong-nha-cung-cap', [ElectromechanicalSupplierController::class, 'supplierQuality'])->name('admin.supplierQualityComparisonReport');
    Route::post('bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-nha-cung-cap/bao-cao-so-sanh-chat-luong-nha-cung-cap/export', [ElectromechanicalSupplierController::class, 'supplierQualityExport'])->name('admin.supplierQualityExport');
    Route::get('bao-cao-thong-ke-he-thong-bao-ve-cac-ngan-lo-trung-ap/bao-cao-so-luong-role-bao-ve-qua-dong-theo-cac-hang-lap-tren-luoi-dien-trung-ap-trong-tong-cong-ty', [ElectromechanicalSupplierController::class, 'protectionRelayReport'])->name('admin.protectionRelayReport');
    Route::post('bao-cao-thong-ke-he-thong-bao-ve-cac-ngan-lo-trung-ap/bao-cao-so-luong-role-bao-ve-qua-dong-theo-cac-hang-lap-tren-luoi-dien-trung-ap-trong-tong-cong-ty/export', [ElectromechanicalSupplierController::class, 'protectionRelayExport'])->name('admin.protectionRelayExport');

    Route::prefix('bao-cao-thi-nghiem-thu-nhat')->group(function(){
        Route::post('may-cat/bao-cao-so-luong-may-cat-trung-ap-da-thuc-hien/export', [ElectromechanicalSupplierController::class, 'cuttingMachinesExperimentedExport'])->name('admin.cuttingMachinesExperimentedExport');

        Route::post('may-cat/bao-cao-so-luong-may-cat-trung-ap-theo-cac-hang-san-xuat/export', [ElectromechanicalSupplierController::class, 'cuttersByManufacturerExport'])->name('admin.cuttersByManufacturerExport');

        Route::post('may-cat/bao-cao-hang-muc-thi-nghiem-khong-dat-theo-hang-san-xuat/export', [ElectromechanicalSupplierController::class, 'cuttingMachineCategoryComparisonExport'])->name('admin.cuttingMachineCategoryComparisonExport');
        Route::post('may-bien-dong-dien/bao-cao-hang-muc-thi-nghiem-khong-dat-theo-hang-san-xuat/export', [ElectromechanicalSupplierController::class, 'cuttingTranferMachineCategoryComparisonExport'])->name('admin.cuttingTranferMachineCategoryComparisonExport');
        Route::post('may-bien-ap-phan-phoi/bao-cao-hang-muc-thi-nghiem-khong-dat-theo-hang-san-xuat/export', [ElectromechanicalSupplierController::class, 'distributionTransformerExport'])->name('admin.distributionTransformerExport');

        Route::get('/{title}',[ElectromechanicalSupplierController::class, 'indexShare'])->name('admin.indexShare');
        Route::get('report-two/{title}',[ElectromechanicalSupplierController::class, 'indexShareReportTwo'])->name('admin.indexShareReportTwo');
        Route::get('report-three/{title}',[ElectromechanicalSupplierController::class, 'indexShareReportThree'])->name('admin.indexShareReportThree');

        Route::post('cap-luc/bao-cao-hang-muc-thi-nghiem-khong-dat-theo-hang-san-xuat/export', [ElectromechanicalSupplierController::class, 'capbleCategoryComparisonExport'])->name('admin.capbleCategoryComparisonExport');
        Route::post('may-bien-dien-ap/bao-cao-hang-muc-thi-nghiem-khong-dat-theo-hang-san-xuat/export', [ElectromechanicalSupplierController::class, 'voltageCategoryComparisonExport'])->name('admin.voltageCategoryComparisonExport');
    });

    Route::get('bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-doanh-so-va-chat-luong-nha-cung-cap',[ElectromechanicalController2::class, 'supplierQualityReport'])->name('admin.supplierQualityReport');
    Route::post('bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-doanh-so-va-chat-luong-nha-cung-cap-export',[ElectromechanicalController2::class, 'exportSupplierQualityReport'])->name('admin.exportSupplierQualityReport');

    Route::get('bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-nha-cung-cap/bao-cao-doanh-so-giua-cac-nha-san-xuat',[ElectromechanicalController::class, 'manufacturersSalesReport'])->name('admin.manufacturersSalesReport');
    Route::post('bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-nha-cung-cap/bao-cao-doanh-so-giua-cac-nha-san-xuat/preview',[ElectromechanicalController::class, 'manufacturersSalesReportPreview'])->name('admin.manufacturersSalesReportPreview');

    Route::get('bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-don-vi-su-dung/bang-so-lieu-cho-tung-don-vi',[ElectromechanicalController::class, 'figuresForEachUnit'])->name('admin.figuresForEachUnit');
    Route::post('bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-don-vi-su-dung/bang-so-lieu-cho-tung-don-vi/preview',[ElectromechanicalController::class, 'figuresForEachUnitPreview'])->name('admin.figuresForEachUnitPreview');
    Route::get('bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-don-vi-su-dung/bang-so-lieu-cho-tung-don-vi/export',[ElectromechanicalController::class, 'figuresForEachUnitExport'])->name('admin.figuresForEachUnitExport');

    Route::get('bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-don-vi-su-dung',[ElectromechanicalController2::class, 'perUnitReport'])->name('admin.perUnitReport');
    Route::post('bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-don-vi-su-dung-export',[ElectromechanicalController2::class, 'exportPerUnitReport'])->name('admin.exportPerUnitReport');

    Route::get('bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-ket-qua-thi-nghiem-mau-cap-va-day-dan-thi-nghiem',[ElectromechanicalController::class, 'testResultsReport'])->name('admin.testResultsReport');
    Route::post('bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-ket-qua-thi-nghiem-mau-cap-va-day-dan-thi-nghiem/preview',[ElectromechanicalController::class, 'testResultsReportPreview'])->name('admin.testResultsReportPreview');

    Route::get('bao-cao-thi-nghiem-thiet-bi-le/bao-cao-thi-nghiem/{class}/{type?}',[ElectromechanicalController::class, 'deviceTestReport'])->name('admin.deviceTestReport');
    Route::post('bao-cao-thi-nghiem-thiet-bi-le/bao-cao-thi-nghiem/preview/{class}/{type?}',[ElectromechanicalController::class, 'deviceTestReportPreview'])->name('admin.deviceTestReportPreview');

    Route::get('bao-cao-thi-nghiem-thiet-bi-le/bao-cao-so-luong-thi-nghiem',[ElectromechanicalController::class, 'numberOfExperimentsReport'])->name('admin.numberOfExperimentsReport');
    Route::post('bao-cao-thi-nghiem-thiet-bi-le/bao-cao-so-luong-thi-nghiem/preview',[ElectromechanicalController::class, 'numberOfExperimentsReportPreview'])->name('admin.numberOfExperimentsReportPreview');
    Route::get('bao-cao-thi-nghiem-thiet-bi-le/bao-cao-so-luong-thi-nghiem/export',[ElectromechanicalController::class, 'numberOfExperimentsExport'])->name('admin.numberOfExperimentsExport');

    Route::get('bao-cao-thong-ke-he-thong-bao-ve-cac-ngan-lo-trung-ap/bao-cao-thong-ke-hu-hong-ro-le',[ElectromechanicalController2::class, 'reportRelayFailureStatistics'])->name('admin.reportRelayFailureStatistics');
    Route::post('bao-cao-thong-ke-he-thong-bao-ve-cac-ngan-lo-trung-ap/bao-cao-thong-ke-hu-hong-ro-le-export',[ElectromechanicalController2::class, 'exportReportRelayFailureStatistics'])->name('admin.exportReportRelayFailureStatistics');

    Route::get('bao-cao-cong-tac-thong-ke-thi-nghiem',[ElectromechanicalController2::class, 'experimentalStatisticsReport'])->name('admin.experimentalStatisticsReport');
    Route::post('bao-cao-cong-tac-thong-ke-thi-nghiem-preview',[ElectromechanicalController2::class, 'previewExperimentalStatisticsReport'])->name('admin.previewExperimentalStatisticsReport');
    Route::get('bao-cao-cong-tac-thong-ke-thi-nghiem-export',[ElectromechanicalController2::class, 'exportExperimentalStatisticsReport'])->name('admin.exportExperimentalStatisticsReport');
});
