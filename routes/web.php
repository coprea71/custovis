<?php

use App\Http\Controllers\Themes\ThemePreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/themes/{theme:slug}/preview', ThemePreviewController::class)
    ->middleware('auth:web')
    ->name('themes.preview');
