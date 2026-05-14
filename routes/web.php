<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('enrollment.create');
});

Route::get('/enrollment', [EnrollmentController::class, 'create'])
     ->name('enrollment.create');

Route::post('/enrollment', [EnrollmentController::class, 'store'])
     ->name('enrollment.store');
     
Route::post('/enrollment/preview', [EnrollmentController::class, 'preview'])
     ->name('enrollment.preview');
     
Route::get('/dashboard', [DashboardController::class, 'index'])
     ->name('dashboard');