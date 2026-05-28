<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountSettingsController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\IdRequirementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AcademicConfigurationController;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('enrollment.create');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/submit-id-requirements', [IdRequirementController::class, 'create'])
    ->name('id-requirements.create');
Route::post('/submit-id-requirements', [IdRequirementController::class, 'store'])
    ->name('id-requirements.store');

Route::get('/enrollment', [EnrollmentController::class, 'create'])
    ->name('enrollment.create');

Route::post('/enrollment', [EnrollmentController::class, 'store'])
    ->name('enrollment.store');

Route::post('/enrollment/check-existing', [EnrollmentController::class, 'checkExisting'])
    ->name('enrollment.check-existing');

Route::post('/enrollment/preview', [EnrollmentController::class, 'preview'])
    ->name('enrollment.preview');

Route::prefix('address-data')->name('address-data.')->group(function () {
    Route::get('/provinces', function () {
        return response()->json(collect(config('address_data.provinces'))
            ->map(fn ($province) => [
                'code' => $province['code'],
                'name' => $province['name'],
            ])
            ->values());
    })->name('provinces');

    Route::get('/provinces/{provinceCode}/cities', function (string $provinceCode) {
        $province = collect(config('address_data.provinces'))
            ->firstWhere('code', $provinceCode);

        return response()->json(collect($province['cities'] ?? [])
            ->map(fn ($city) => [
                'code' => $city['code'],
                'name' => $city['name'],
            ])
            ->values());
    })->name('cities');

    Route::get('/cities/{cityCode}/barangays', function (string $cityCode) {
        $city = collect(config('address_data.provinces'))
            ->flatMap(fn ($province) => $province['cities'] ?? [])
            ->firstWhere('code', $cityCode);

        return response()->json(collect($city['barangays'] ?? [])
            ->map(fn ($name) => ['name' => $name])
            ->values());
    })->name('barangays');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
    Route::get('/dashboard/enrollments/live', [DashboardController::class, 'liveEnrollments'])
        ->name('dashboard.enrollments.live');
    Route::get('/activity-logs', [DashboardController::class, 'activityLogs'])
        ->middleware('role:admin')
        ->name('activity-logs.index');

    Route::put('/account', [AccountSettingsController::class, 'updateOwn'])
        ->name('account.update');

    Route::middleware('role:admin,registrar,department_head')->group(function () {
        Route::get('/enrollments/{enrollment}/form', [EnrollmentController::class, 'show'])
            ->name('enrollments.form.show');
        Route::put('/enrollments/{enrollment}', [DashboardController::class, 'updateEnrollment'])
            ->name('enrollments.update');
        Route::patch('/enrollments/{enrollment}/status', [DashboardController::class, 'updateEnrollmentStatus'])
            ->name('enrollments.status.update');
        Route::get('/enrollments/{enrollment}/id-card-data', [DashboardController::class, 'idCardData'])
            ->name('enrollments.id-card-data');
        Route::get('/id-generation/statuses', [DashboardController::class, 'idGenerationStatuses'])
            ->name('id-generation.statuses');
        Route::post('/enrollments/{enrollment}/id-generated', [DashboardController::class, 'markIdGenerated'])
            ->name('enrollments.id-generated');
        Route::post('/enrollments/{enrollment}/id-photo', [DashboardController::class, 'uploadIdPhoto'])
            ->name('enrollments.id-photo');
        Route::post('/enrollments/{enrollment}/id-signature', [DashboardController::class, 'uploadIdSignature'])
            ->name('enrollments.id-signature');

        
    });

    Route::middleware('role:admin')->prefix('accounts')->name('accounts.')->group(function () {
        Route::post('/', [AccountSettingsController::class, 'store'])->name('store');
        Route::put('/{user}', [AccountSettingsController::class, 'update'])->name('update');
        Route::delete('/{user}', [AccountSettingsController::class, 'destroy'])->name('destroy');
    });

    Route::middleware('role:admin,registrar,department_head')->prefix('academic-configuration')->name('academic.')->group(function () {
        Route::put('/academic-year', [AcademicConfigurationController::class, 'updateAcademicYear'])->name('academic-year.update');
        Route::post('/subjects', [AcademicConfigurationController::class, 'storeSubject'])->name('subjects.store');
        Route::put('/subjects/{subject}', [AcademicConfigurationController::class, 'updateSubject'])->name('subjects.update');
        Route::delete('/subjects/{subject}', [AcademicConfigurationController::class, 'destroySubject'])->name('subjects.destroy');
        Route::post('/days', [AcademicConfigurationController::class, 'storeDay'])->name('days.store');
        Route::post('/rooms', [AcademicConfigurationController::class, 'storeRoom'])->name('rooms.store');
        Route::post('/time-slots', [AcademicConfigurationController::class, 'storeTimeSlot'])->name('time-slots.store');
        Route::post('/schedules', [AcademicConfigurationController::class, 'storeSchedule'])->name('schedules.store');
        Route::get('/schedules/pdf', [AcademicConfigurationController::class, 'downloadSchedulePdf'])->name('schedules.pdf');
        Route::put('/schedules/{schedule}', [AcademicConfigurationController::class, 'updateSchedule'])->name('schedules.update');
        Route::delete('/schedules/{schedule}', [AcademicConfigurationController::class, 'destroySchedule'])->name('schedules.destroy');
        Route::post('/templates', [AcademicConfigurationController::class, 'storeEnrollmentTemplate'])->name('templates.store');
        Route::post('/template-fields', [AcademicConfigurationController::class, 'storeCustomTemplateField'])->name('template-fields.store');
        Route::put('/templates/{template}/mappings', [AcademicConfigurationController::class, 'updateEnrollmentTemplateMappings'])->name('templates.mappings.update');
        Route::get('/templates/{template}/pdf', [AcademicConfigurationController::class, 'showEnrollmentTemplatePdf'])->name('templates.pdf');
        Route::post('/id-templates', [AcademicConfigurationController::class, 'storeIdTemplate'])->name('id-templates.store');
        Route::post('/id-templates/fonts', [AcademicConfigurationController::class, 'storeIdTemplateFont'])->name('id-templates.fonts.store');
        Route::put('/id-templates/{template}/layout', [AcademicConfigurationController::class, 'updateIdTemplateLayout'])->name('id-templates.layout.update');
        Route::get('/id-templates/{template}/background', [AcademicConfigurationController::class, 'showIdTemplateBackground'])->name('id-templates.background');
    });

    Route::middleware('role:admin,registrar')->prefix('academic-configuration')->name('academic.')->group(function () {
        Route::post('/department-heads', [AcademicConfigurationController::class, 'storeDepartmentHead'])->name('department-heads.store');
        Route::put('/fees', [AcademicConfigurationController::class, 'updateFees'])->name('fees.update');
    });
});
