<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BottleScanController;

Route::get('/ping', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is working',
    ]);
});

Route::post('/bottle-scan', [BottleScanController::class, 'store']);
