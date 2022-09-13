<?php


use Illuminate\Support\Facades\Route;
use Modules\Enery\Http\Controllers\EneryController;
use Illuminate\Http\Request;

Route::prefix('admin/bao-cao-thong-ke/cong-nghe-nang-luong')->middleware(['auth.custom', 'permission.custom:'.config('constant.permissions.energy')])->group(function() {
    Route::get('/get-data-obj', [EneryController::class, 'getDataObject'])->name('admin.getDataObject');

    Route::get('bao-cao-ket-qua-danh-gia-do-khong-dam-bao-do-cua-thiet-bị-hieu-chuan-ap-xuat',[EneryController::class, 'thiet_bi_hieu_chuan_ap_xuat'])->name('admin.thiet_bi_hieu_chuan_ap_xuat');
    Route::get('bao-cao-ket-qua-danh-gia-do-khong-dam-bao-do-cua-thiet-bị-hieu-chuan-ap-xuat-preview',[EneryController::class, 'thiet_bi_hieu_chuan_ap_xuat_preview'])->name('admin.thiet_bi_hieu_chuan_ap_xuat_preview');

    Route::get('bao-cao-ket-qua-danh-gia-do-khong-dam-bao-do-cua-thiet-bị-hieu-chuan-nhiet-am-ke',[EneryController::class, 'thiet_bi_hieu_chuan_nhiet_am_ke'])->name('admin.thiet_bi_hieu_chuan_nhiet_am_ke');
    Route::get('bao-cao-ket-qua-danh-gia-do-khong-dam-bao-do-cua-thiet-bị-hieu-chuan-nhiet-am-ke-preview',[EneryController::class, 'thiet_bi_hieu_chuan_nhiet_am_ke_preview'])->name('admin.thiet_bi_hieu_chuan_nhiet_am_ke_preview');

    Route::get('bao-cao-ket-qua-danh-gia-do-khong-dam-bao-do-cua-thiet-bị-hieu-chuan-nhiet-do',[EneryController::class, 'thiet_bi_hieu_chuan_nhiet_do'])->name('admin.thiet_bi_hieu_chuan_nhiet_do');
    Route::get('bao-cao-ket-qua-danh-gia-do-khong-dam-bao-do-cua-thiet-bị-hieu-chuan-nhiet-do-preview',[EneryController::class, 'thiet_bi_hieu_chuan_nhiet_do_preview'])->name('admin.thiet_bi_hieu_chuan_nhiet_do_preview');

    Route::get('export_steam',[EneryController::class, 'export_steam'])->name('admin.enery.export_steam');

    Route::get('hieu-suat-noi-hoi-theo-hang-san-xuat',[EneryController::class, 'boilersByManufacture'])->name('admin.boilersByManufacture');
    Route::get('export-hieu-suat-noi-hoi-theo-hang-san-xuat',[EneryController::class, 'exportBoilersByManufacture'])->name('admin.exportBoilersByManufacture');

    Route::get('hieu-suat-lo-cong-nghiep-theo-hang-nhien-lieu',[EneryController::class, 'industrialFurnaceByManufacture'])->name('admin.industrialFurnaceByManufacture');
    Route::get('export-hieu-suat-lo-cong-nghiep-theo-hang-nhien-lieu',[EneryController::class, 'exportIndustrialFurnaceByManufacture'])->name('admin.exportIndustrialFurnaceByManufacture');

    Route::get('bao-cao-so-sanh-ket-qua-thi-nghiem-thong-so-tua-bin-hoi-preview',[EneryController::class, 'steamTurbineParametersDetail'])->name('admin.steamTurbineParametersDetail');

    Route::get('bao-cao-danh-gia-ket-qua-thi-nghiem-do-dac-tuyen-to-may-preview',[EneryController::class, 'unitCharacteristicMeasurementDetail'])->name('admin.unitCharacteristicMeasurementDetail');

    Route::get('bao-cao-so-sanh-ket-qua-thi-nghiem-thong-so-lo-hoi-lon-preview',[EneryController::class, 'largeBoilerParametersDetail'])->name('admin.largeBoilerParametersDetail');

    Route::get('bao-cao-so-sanh-ket-qua-thi-nghiem-thong-so-lo-hoi-nho-preview',[EneryController::class, 'smallBoilerParametersDetail'])->name('admin.smallBoilerParametersDetail');

    Route::get('bao-cao-ket-qua-danh-gia-do-khong-dam-bao-do-cua-thiet-bị-hieu-chuan-nhiet-am-ke',[EneryController::class, 'thiet_bi_hieu_chuan_nhiet_am_ke'])->name('admin.thiet_bi_hieu_chuan_nhiet_am_ke');
    Route::get('bao-cao-ket-qua-danh-gia-do-khong-dam-bao-do-cua-thiet-bị-hieu-chuan-nhiet-do',[EneryController::class, 'thiet_bi_hieu_chuan_nhiet_do'])->name('admin.thiet_bi_hieu_chuan_nhiet_do');

    Route::get('ket-qua-thi-nghiem-thong-so-tuabin-khi-preview',[EneryController::class, 'gasTurbineParametersDetail'])->name('admin.gasTurbineParametersDetail');

    Route::get('export-hieu-suat-lo-cong-nghiep-theo-hang-nhien-lieu',[EneryController::class, 'exportIndustrialFurnaceByManufacture'])->name('admin.exportIndustrialFurnaceByManufacture');
    Route::get('/{title}',[EneryController::class, 'index'])->name('admin.index');
});
