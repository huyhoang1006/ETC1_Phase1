<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnalyticController;

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


Route::prefix('admin')->middleware('auth.custom')->group(function() {
    Route::get('/bao-cao-cao-ap', [AnalyticController::class, 'index'])->name('admin.analytic');
    Route::get('/bao-cao-cao-ap/bao-cao-kiem-tra-ben-ngoai',function () {
        return view('pages.embed_demo');
    })->name('admin.analyticEmbed');
    Route::get('/bao-cao-cao-ap/bao-cao-dong-dien-va-ton-hao-khong-tai',function () {
        return view('pages.embed_demo_2');
    })->name('admin.analyticEmbed_2');
});
Route::get('/', function () {
    return redirect(route('admin.dashboard.index'));
});
