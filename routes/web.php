<?php

use App\Http\Controllers\AccountSecurityController;
use App\Http\Controllers\Themes\ThemePreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/account/security', AccountSecurityController::class)
    ->middleware('auth:web')
    ->name('account.security');

Route::get('/themes/{theme:slug}/preview', ThemePreviewController::class)
    ->middleware('auth:web')
    ->name('themes.preview');
