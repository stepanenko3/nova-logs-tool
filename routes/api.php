<?php

use Illuminate\Support\Facades\Route;
use Stepanenko3\LogsTool\Http\Controllers\LogsController;

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

Route::controller(LogsController::class)->group(function (): void {
    Route::get('logs', 'index');

    Route::get('logs/permissions', 'permissions');

    Route::delete('logs/cache', 'cacheClear');

    Route::get('logs/{log}', 'download')
        ->where(['log' => '.*']);

    Route::delete('logs', 'delete');
});
