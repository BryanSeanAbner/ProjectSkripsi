<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RecommendationController;

Route::get('/', fn() => redirect('/login'));

// ─── Auth ─────────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

// ─── Dashboard & Recommendations ────────────────────────────
Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/article/{id}', function ($id) {
    return view('article', ['id' => $id]);
});

Route::get('/section/{id}', function ($id) {
    return view('section', ['sectionId' => $id]);
})->name('section');

Route::get('/profile', function () {
    return view('profile');
})->name('profile');

// ─── Test / Demo Pipeline ─────────────────────────────────────────────────────
Route::get('/test',  [\App\Http\Controllers\TestController::class, 'index'])->name('test');
Route::post('/test/train', [\App\Http\Controllers\TestController::class, 'train'])->name('test.train');

// API endpoints untuk Realtime fallback & manual refresh
Route::prefix('api/recommendations')->group(function () {
    Route::get('/data', [RecommendationController::class, 'data'])->name('recs.data');
    Route::get('/status', [RecommendationController::class, 'status'])->name('recs.status');
    Route::post('/regenerate', [RecommendationController::class, 'regenerate'])->name('recs.regenerate');
});

