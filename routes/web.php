<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\KaprodiController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SekretarisController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/docs', 'docs.index')->name('docs');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', fn (Request $request) => redirect()->route($request->user()->role->homeRoute()))->name('dashboard');

    Route::middleware('can:approve-kaprodi')->group(function () {
        Route::get('/kaprodi', [KaprodiController::class, 'index'])->name('kaprodi.index');
        Route::post('/kaprodi/proposals/{proposal}/approve', [KaprodiController::class, 'approve'])->name('kaprodi.approve');
    });

    Route::middleware('can:approve-sekretaris')->group(function () {
        Route::get('/sekretaris', [SekretarisController::class, 'index'])->name('sekretaris.index');
        Route::put('/sekretaris/budget', [SekretarisController::class, 'updateBudget'])->name('sekretaris.budget');
        Route::post('/sekretaris/proposals/{proposal}/approve', [SekretarisController::class, 'approve'])->name('sekretaris.approve');
    });

    Route::middleware('can:submit-proposal')->group(function () {
        Route::get('/proposals', [ProposalController::class, 'index'])->name('proposals.index');
        Route::get('/proposals/create', [ProposalController::class, 'create'])->name('proposals.create');
        Route::post('/proposals', [ProposalController::class, 'store'])->name('proposals.store');
    });

    Route::get('/proposals/{proposal}', [ProposalController::class, 'show'])->name('proposals.show');
    Route::get('/proposals/{proposal}/file', [ProposalController::class, 'download'])->name('proposals.file');

    Route::middleware('can:review')->group(function () {
        Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::get('/reviews/create', [ReviewController::class, 'create'])->name('reviews.create');
        Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    });
});
