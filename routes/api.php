<?php

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::post('/users', [UserController::class, 'store']);
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

// Rutas protegidas con autenticación
Route::middleware('auth:sanctum')->group(function () {
    // Ruta para obtener el usuario autenticado
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Rutas de usuarios
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    
    // Rutas de transacciones
    Route::post('/transactions/transfer', [TransactionController::class, 'transfer']);
    
    // Rutas de reportes
    Route::get('/reports/transactions/export', [ReportController::class, 'exportTransactionsCsv']);
    Route::get('/reports/transfers/totals', [ReportController::class, 'getTransferTotalsByUser']);
    Route::get('/reports/transfers/averages', [ReportController::class, 'getAverageAmountByUser']);
    
    // Ruta para cerrar sesión
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
});