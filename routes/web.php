<?php
// Tempel ke routes/web.php (ganti isi route bawaan "/")

use App\Http\Controllers\UsageController;
use App\Http\Middleware\DashboardAuth;
use Illuminate\Support\Facades\Route;

Route::middleware(DashboardAuth::class)->group(function () {
    Route::view('/', 'dashboard');
    Route::get('/api/stats', [UsageController::class, 'stats']);
    Route::get('/api/chart', [UsageController::class, 'chart']);
    Route::post('/api/wake-up', [UsageController::class, 'wakeUp']);
});
