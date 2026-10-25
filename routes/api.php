<?php

use App\Http\Controllers\Api\SyncController;
use App\Http\Middleware\VerifySyncToken;
use Illuminate\Support\Facades\Route;

Route::middleware(VerifySyncToken::class)->prefix('sync')->group(function () {
    Route::get('ping', [SyncController::class, 'ping']);
    Route::post('push', [SyncController::class, 'push']);
    Route::get('pull', [SyncController::class, 'pull']);
});
