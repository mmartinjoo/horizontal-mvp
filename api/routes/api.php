<?php

use App\Http\Controllers\JiraIntegrationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/test', [\App\Http\Controllers\TestController::class, 'index']);
Route::get('/test/jira', [\App\Http\Controllers\TestController::class, 'jira']);
Route::get('/test/token', [\App\Http\Controllers\TestController::class, 'token']);
Route::get('/test/refresh', [\App\Http\Controllers\TestController::class, 'refresh']);
Route::get('/test/search', [\App\Http\Controllers\TestController::class, 'search']);

// Jira OAuth integration routes
Route::get('/integrations/jira/oauth/callback', [JiraIntegrationController::class, 'callback']);
Route::middleware('auth:sanctum')->prefix('/integrations/jira/oauth')->group(function () {
    Route::post('authorize', [JiraIntegrationController::class, 'authorize']);
    Route::get('status', [JiraIntegrationController::class, 'status']);
    Route::delete('disconnect', [JiraIntegrationController::class, 'disconnect']);
});
