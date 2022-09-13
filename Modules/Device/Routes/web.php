<?php
use Illuminate\Support\Facades\Route;


Route::prefix('admin/device')->middleware(['auth.custom'])->group(function() {
    Route::get('/', 'DeviceController@index')->name('admin.device.index');
    Route::get('/master-tree', 'DeviceController@masterTree')->name('admin.device.mastertree');
    Route::get('/stations', 'DeviceController@getStations')->name('admin.device.stations');
    Route::get('/blocks', 'DeviceController@getBlocks')->name('admin.device.blocks');
    Route::get('/block-devices', 'DeviceController@getDeviceByBlock')->name('admin.device.block_devices');
    Route::get('/all-devices', 'DeviceController@getAllDevices')->name('admin.device.all_devices');
    Route::get('/preview-{id}', 'DeviceController@detail')->name('admin.device.detail');

    Route::get('/filter-device-ajax', 'DeviceController@ajaxFilterDevice')->name('admin.ajaxFilterDeviceModules');
    Route::get('/get-view-device', 'DeviceController@getViewDevice')->name('admin.getViewDevice');
    Route::get('/get-td', 'DeviceController@getTD')->name('admin.getTD');
    Route::get('/get-nl', 'DeviceController@getNL')->name('admin.getNL');
});
