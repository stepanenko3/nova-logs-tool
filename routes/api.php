<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tool API Routes
|--------------------------------------------------------------------------
|
| Here is where you may register API routes for your tool. These routes
| are loaded by the ServiceProvider of your tool. They are protected
| by your tool's "Authorize" middleware by default. Now, go build!
|
*/

Route::get('logs', \Stepanenko3\LogsTool\Http\Controllers\LogsController::class.'@index');
Route::get('logs/permissions', \Stepanenko3\LogsTool\Http\Controllers\LogsController::class.'@permissions');
Route::get('logs/{log}', \Stepanenko3\LogsTool\Http\Controllers\LogsController::class.'@show')->where(['log' => '.*']);
Route::get('daily-log-files', \Stepanenko3\LogsTool\Http\Controllers\LogsController::class.'@dailyLogFiles');
Route::delete('logs', \Stepanenko3\LogsTool\Http\Controllers\LogsController::class.'@destroy');
