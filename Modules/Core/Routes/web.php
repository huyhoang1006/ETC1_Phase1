<?php
use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\AuthController;
use Modules\Core\Http\Controllers\CoreController;

Route::prefix('admin')->group(function() {
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('admin.auth.login');
    Route::post('login', [AuthController::class, 'processLogin'])->name('admin.auth.login');
    Route::get('logout', [AuthController::class, 'processLogout'])->name('admin.auth.logout');

    Route::middleware(['auth.custom'])->group(function () {
        Route::get('dashboard', 'CoreController@index')->name('admin.dashboard.index');
    });

    Route::middleware(['auth.custom', 'permission.custom:admin'])->group(function () {
        Route::get('/permission', [CoreController::class, 'viewPermission'])->name('admin.permission');
        Route::get('/permission/{userid}', [CoreController::class, 'editPermission'])->name('admin.permission.edit');
        Route::post('/permission/update', [CoreController::class, 'postEditPermission'])->name('admin.postEditPermission');
    });
});
