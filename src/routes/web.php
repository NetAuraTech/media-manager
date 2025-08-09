<?php

use Illuminate\Support\Facades\Route;
use Netauratech\MediaManager\Http\Controllers\MediaController;

/**
 * Media
 */
Route::get('/media/resize/{path}', [MediaController::class, 'resize'])->where(['path' => '.+'])->name('media.resize');