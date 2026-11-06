<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BottleScanController;
use App\Http\Controllers\BottleCollectionController;

Route::get('/ping', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is working',
    ]);
});

Route::post('/bottle-scan', [BottleScanController::class, 'store']);

Route::get('/student-points', [BottleCollectionController::class, 'studentPoints']);
