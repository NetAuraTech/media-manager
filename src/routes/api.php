<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Netauratech\MediaManager\Http\Controllers\MediaController;

/**
 * Medias
 */
Route::prefix('/media')->group(function () {
    Route::post('/', [MediaController::class, 'upload'])->withoutMiddleware(VerifyCsrfToken::class)->name('media.upload');
    Route::delete('/{media}', [MediaController::class, 'destroy'])->withoutMiddleware(VerifyCsrfToken::class)->name('media.delete');
    Route::get('/folders', [MediaController::class, 'folders'])->name('media.folders');
    Route::get('/files', [MediaController::class, 'files']);
    Route::get('/files/{media}', [MediaController::class, 'normalize']);
});