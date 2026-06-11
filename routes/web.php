<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;

Route::get('/', [PageController::class, 'login'])->name('login');

Route::prefix('admin')->group(function () {
    Route::get('/dashboard', [PageController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/students', [PageController::class, 'students'])->name('admin.students');
    Route::get('/bottle-collection', [PageController::class, 'bottleCollection'])->name('admin.bottle-collection');
    Route::get('/certificate', [PageController::class, 'certificate'])->name('admin.certificate');
    Route::get('/reports', [PageController::class, 'reports'])->name('admin.reports');
    Route::get('/student-report', [PageController::class, 'studentReport'])->name('admin.student-report');
    Route::get('/bottle-report', [PageController::class, 'bottleReport'])->name('admin.bottle-report');
    Route::get('/teachers', [PageController::class, 'teachers'])->name('admin.teachers');
    Route::get('/settings', [PageController::class, 'settings'])->name('admin.settings');
    Route::get('/qrcode', [PageController::class, 'qrcode'])->name('admin.qrcode');
    Route::get('/students-filtered', [PageController::class, 'studentsFiltered'])->name('admin.students-filtered');
    Route::get('/admin-activities', [PageController::class, 'adminActivities'])->name('admin.admin-activities');
});
