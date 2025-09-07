<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UniversalMCPTestController;

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

// Universal MCP Test Routes
Route::prefix('universal-mcp')->group(function () {
    Route::post('/initialize-servers', [UniversalMCPTestController::class, 'initializeServers']);
    Route::get('/test-connections', [UniversalMCPTestController::class, 'testConnections']);
    Route::get('/get-all-tools', [UniversalMCPTestController::class, 'getAllTools']);
    Route::post('/test-tool', [UniversalMCPTestController::class, 'testTool']);
    Route::post('/test-agent', [UniversalMCPTestController::class, 'testAgent']);
    Route::post('/test-crew', [UniversalMCPTestController::class, 'testCrew']);
    Route::get('/health', [UniversalMCPTestController::class, 'getHealth']);
});
