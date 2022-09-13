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

Route::prefix('admin/bao-cao-thong-ke/tu-dong-hoa')->middleware(['auth.custom', 'permission.custom:'.config('constant.permissions.automation')])->group(function() {
    Route::get('/', 'AutomationController@index')->name('admin.automation');
    Route::get('/export', 'AutomationController@export')->name('admin.automation.export');

    Route::get('/get-list-nl', 'AutomationController@ajaxGetNL')->name('admin.automation.ajaxGetNL');
});
