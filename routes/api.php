<?php

use App\Http\Controllers\Kent911IncidentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

// Route::middleware(['auth:sanctum', 'web'])->group(function () {
Route::middleware([])->group(function () {
    Route::get('/v1/user', [\App\Http\Controllers\Controller::class, 'user']);
    /**
     * Kent911 Common
     */
    // Only GET requests are performed by the user, throttled to 10 requests per minute
    Route::middleware(['throttle:kent911incidents'])->group(function () {
        Route::get('/v1/kent911/incidents/.geojson', [Kent911IncidentController::class, 'geoJSON']);
        Route::apiResource('/v1/kent911/incidents', Kent911IncidentController::class);
    });
});