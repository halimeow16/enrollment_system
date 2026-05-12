<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\EnrollmentController;

// Route::get('/', function () {
//     return view('create');
// });

Route::get('/enrollment', [EnrollmentController::class, 'create'])
     ->name('enrollment.create');

Route::post('/enrollment', [EnrollmentController::class, 'store'])
     ->name('enrollment.store');
