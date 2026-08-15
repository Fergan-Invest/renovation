<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MapFeatureController;
use App\Http\Controllers\Api\DriveImportController;
use App\Http\Controllers\Api\KmlKmzImportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/map/features', [MapFeatureController::class, 'index']);
Route::get('/features/{feature}', [MapFeatureController::class, 'show']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('simple.auth')->group(function () {
    Route::get('/drive-imports', [DriveImportController::class, 'index']);
    Route::post('/imports/kml-kmz', [KmlKmzImportController::class, 'store']);
});
