<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Netauratech\MediaManager\Http\Controllers\MediaAltController;
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

    /**
     * Media Alts
     */
    Route::prefix('/{media}/alts')->group(function () {
        Route::get('/', [MediaAltController::class, 'index'])->name('media.alts.index');
        Route::post('/', [MediaAltController::class, 'store'])->withoutMiddleware(VerifyCsrfToken::class)->name('media.alts.store');
        Route::put('/{alt}', [MediaAltController::class, 'update'])->withoutMiddleware(VerifyCsrfToken::class)->name('media.alts.update');
        Route::delete('/{alt}', [MediaAltController::class, 'destroy'])->withoutMiddleware(VerifyCsrfToken::class)->name('media.alts.destroy');
    });
});