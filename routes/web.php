<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('index');
})->name('index');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

