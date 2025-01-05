<?php

use Illuminate\Http\Request;
use App\Http\Middleware\VerifyRole;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ManagerController;
use Tymon\JWTAuth\Http\Middleware\Authenticate;


Route::middleware(Authenticate::class)->group(function () {
    Route::delete('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Route companies with middleware superadmin
    Route::prefix('/companies')->middleware(VerifyRole::class . ':superadmin')->controller(CompanyController::class)->group(function () {
        Route::get('', 'index');
        Route::get('/{company}', 'show');
        Route::post('', 'store');
        Route::put('/{company}', 'update');
        Route::delete('/{company}', 'destroy');
    });


    // Route managers with middleware superadmin
    Route::prefix('/managers')->middleware(VerifyRole::class . ':superadmin')->controller(ManagerController::class)->group(function () {
        Route::post('',  'store');
        Route::put('/{manager}/edit',  'update');
        Route::get('/{id}/edit',  'edit');
        Route::delete('/{manager}', 'destroy');
    });

    // Route managers with middleware manager, superadmin can access it but employee can't
    Route::prefix('/managers')->middleware(VerifyRole::class . ':manager')->controller(ManagerController::class)->group(function () {
        Route::get('', 'index');
        Route::get('/self', 'show');
        Route::put('/self',  'selfUpdate');
    });


    // Route managers with middleware manager, superadmin can access it but employee can't
    Route::prefix('employees')->middleware(VerifyRole::class . ':manager')->controller(EmployeeController::class)->group(function () {
        Route::post('',  'store');
        Route::put('/{employee}/edit',  'update');
        Route::get('/{id}/edit',  'edit');
        Route::delete('/{employee}', 'destroy');
    });

    Route::prefix('employees')->middleware(VerifyRole::class . ':employee')->controller(EmployeeController::class)->group(function () {
        Route::get('', 'index');
        Route::get('/self', 'show');
        Route::put('/self',  'selfUpdate');
    });
});


// // Soft Delete Management
// Route::get('/companies/trashed', [CompanyController::class, 'trashed'])->middleware('role:superadmin');
// Route::post('/companies/{id}/restore', [CompanyController::class, 'restore'])->middleware('role:superadmin');
// Route::delete('/companies/{id}/force', [CompanyController::class, 'forceDelete'])->middleware('role:superadmin');

// Route::get('/employees/trashed', [EmployeeController::class, 'trashed'])->middleware('role:manager');
// Route::post('/employees/{id}/restore', [EmployeeController::class, 'restore'])->middleware('role:manager');
// Route::delete('/employees/{id}/force', [EmployeeController::class, 'forceDelete'])->middleware('role:manager');



Route::post('/login', [AuthController::class, 'login']);
Route::post('/password/reset', [AuthController::class, 'reset']);
