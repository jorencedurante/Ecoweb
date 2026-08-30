<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\BottleCollectionController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\AdminActivityController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\AchievementController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\AccountSettingsController;

// First-time setup (only works when no Super Admin exists)
Route::get('/setup', [SetupController::class, 'show'])->name('setup.show');
Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');


// Public landing routes (no auth required)
Route::get('/', [PublicController::class, 'landing'])->name('landing');
Route::get('/items', [PublicController::class, 'items'])->name('public.items');
Route::get('/student-lookup', [PublicController::class, 'studentLookup'])->name('student.lookup');
Route::get('/student/{lrn}/details', [PublicController::class, 'studentDetails'])->name('landing.student.details');
Route::post('/items/request-claim', [PublicController::class, 'requestItemClaim'])->name('public.items.request');

// Guest routes (redirect authenticated users to dashboard)
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/admin/login', [AuthController::class, 'login'])->name('login.submit');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');

    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');

    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// Email verification routes (guest and authenticated users)
Route::get('/email/verify', [AuthController::class, 'showVerify'])->name('verification.notice');
Route::post('/email/verify', [AuthController::class, 'verifyOtp'])->name('verification.verify');
Route::post('/email/verify/resend', [AuthController::class, 'resendOtp'])->name('verification.resend');

// Protected admin routes
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Email change routes (outside 'verified' middleware — user's email may not be verified yet)
    Route::get('/account/email-change/verify', [AccountSettingsController::class, 'showEmailChangeVerify'])->name('account.email-change.verify');
    Route::post('/account/email-change/verify', [AccountSettingsController::class, 'confirmEmailChange'])->name('account.email-change.confirm');
    Route::post('/account/email-change/resend', [AccountSettingsController::class, 'resendEmailChangeOtp'])->name('account.email-change.resend');
    Route::post('/account/email-change/cancel', [AccountSettingsController::class, 'cancelEmailChange'])->name('account.email-change.cancel');

    Route::middleware('verified')->prefix('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

        // Claims / Rewards (admin & super admin only)
        Route::middleware('admin.only')->group(function () {
            Route::get('/claims', [ClaimController::class, 'index'])->name('claims.index');
            Route::post('/claim-items', [ClaimController::class, 'storeItem'])->name('claim-items.store');
            Route::put('/claim-items/{claimItem}', [ClaimController::class, 'updateItem'])->name('claim-items.update');
            Route::post('/claims', [ClaimController::class, 'claimItem'])->name('claims.store');
            Route::get('/claims/history', [ClaimController::class, 'history'])->name('claims.history');
            Route::get('/claims/items/filter', [ClaimController::class, 'filterItems'])->name('claims.items.filter');
            Route::get('/claims/history/filter', [ClaimController::class, 'filterHistory'])->name('claims.history.filter');
            Route::get('/claims/pending', fn() => redirect()->route('claims.index'))->name('claims.pending');
            Route::patch('/claims/{claim}/approve', [ClaimController::class, 'approve'])->name('claims.approve');
            Route::patch('/claims/{claim}/reject', [ClaimController::class, 'reject'])->name('claims.reject');
            Route::patch('/claims/student/{student}/archive-all', [ClaimController::class, 'archiveAllByStudent'])->name('claims.archiveAllByStudent');
            Route::patch('/claims/{claim}/archive', [ClaimController::class, 'archive'])->name('claims.archive');
        });

        // Students
        Route::get('/students/search', [StudentController::class, 'search'])->name('admin.students.search');
        Route::get('/students', [StudentController::class, 'index'])->name('admin.students');
        Route::post('/students', [StudentController::class, 'store'])->name('admin.students.store');
        Route::post('/students/import', [StudentController::class, 'import'])->name('admin.students.import');
        Route::get('/students/import-template', [StudentController::class, 'downloadTemplate'])->name('admin.students.import.template');
        Route::put('/students/{student}', [StudentController::class, 'update'])->name('admin.students.update');
        Route::patch('/students/{student}/archive', [StudentController::class, 'archive'])->name('admin.students.archive');
        Route::get('/students/archived', [StudentController::class, 'archived'])->name('admin.students.archived');
        Route::patch('/students/{student}/restore', [StudentController::class, 'restore'])->name('students.restore');
        Route::get('/students/{student}/info', [StudentController::class, 'info'])->name('students.info');
        Route::get('/students/{student}/achievements', [StudentController::class, 'achievements'])->name('students.achievements');
        Route::patch('/students/{student}/achievements/progress', [StudentController::class, 'updateAchievementProgress'])->name('students.achievements.progress.update');
        Route::get('/students/{student}/awards', [StudentController::class, 'awards'])->name('students.awards');

        // Bottle Collection
        Route::get('/bottle-collection', [BottleCollectionController::class, 'index'])->name('admin.bottle-collection');
        Route::get('/bottle-collection/fetch', [BottleCollectionController::class, 'fetchJson'])->name('admin.bottle-collection.fetch');
        Route::post('/bottle-collection', [BottleCollectionController::class, 'store'])->name('admin.bottle-collection.store');

        // Certificate / Awards
        Route::get('/certificate', [CertificateController::class, 'index'])->name('admin.certificate');
        Route::post('/certificate', [CertificateController::class, 'store'])->name('admin.certificate.store');
        Route::post('/certificate/template', [CertificateController::class, 'storeTemplate'])->name('admin.certificate.template.store');
        Route::get('/certificate/create', [CertificateController::class, 'create'])->name('admin.certificate.create');
        Route::post('/certificate/save-canvas', [CertificateController::class, 'saveCanvas'])->name('admin.certificate.save-canvas');
        Route::get('/certificate/{award}/edit', [CertificateController::class, 'edit'])->name('admin.certificate.edit');
        Route::put('/certificate/{award}/update-canvas', [CertificateController::class, 'updateCanvas'])->name('admin.certificate.update-canvas');
        Route::get('/certificate/templates/list', [CertificateController::class, 'templatesList'])->name('admin.certificate.templates.list');
        Route::get('/certificate/{award}/print', [CertificateController::class, 'print'])->name('admin.certificate.print');
        Route::get('/certificate/{award}/download', [CertificateController::class, 'download'])->name('admin.certificate.download');
        Route::patch('/certificate/{award}/archive', [CertificateController::class, 'archive'])->name('admin.certificate.archive');
        Route::delete('/certificate/{award}', [CertificateController::class, 'destroy'])->name('admin.certificate.destroy');

        // Reports
        Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports');
        Route::get('/student-report', [ReportController::class, 'studentReport'])->name('admin.student-report');
        Route::get('/bottle-report', [ReportController::class, 'bottleReport'])->name('admin.bottle-report');
        Route::get('/admin-activities', [ReportController::class, 'adminActivities'])->name('admin.admin-activities');

        // Reports - Print routes
        Route::get('/reports/print/top-students', [ReportController::class, 'printTopStudents'])->name('reports.top-students.print');
        Route::get('/reports/print/student-ranking', [ReportController::class, 'printStudentRanking'])->name('reports.student-ranking.print');
        Route::get('/reports/print/bottle-collection', [ReportController::class, 'printBottleCollection'])->name('reports.bottle-collection.print');
        Route::get('/reports/print/item-claims', [ReportController::class, 'printItemClaims'])->name('reports.item-claims.print');
        Route::get('/reports/print/student-activities', [ReportController::class, 'printStudentActivities'])->name('reports.student-activities.print');

        // Account Settings (all authenticated users)
        Route::get('/settings', [AccountSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings/profile', [AccountSettingsController::class, 'updateProfile'])->name('settings.profile.update');
        Route::put('/settings/password', [AccountSettingsController::class, 'updatePassword'])->name('settings.password.update');

        // Teachers / Accounts (admin only)
        Route::middleware('admin.only')->group(function () {
            Route::get('/teachers', [TeacherController::class, 'index'])->name('admin.teachers');
            Route::post('/teachers', [TeacherController::class, 'store'])->name('admin.teachers.store');
            Route::put('/teachers/{teacher}', [TeacherController::class, 'update'])->name('admin.teachers.update');
            Route::delete('/teachers/{teacher}', [TeacherController::class, 'destroy'])->name('admin.teachers.destroy');

            // System Settings
            Route::get('/system-settings', [SettingsController::class, 'index'])->name('admin.settings');
            Route::post('/settings/general', [SettingsController::class, 'updateGeneral'])->name('admin.settings.general');
            Route::post('/settings/security', [SettingsController::class, 'updateSecurity'])->name('admin.settings.security');
        });

        // QR Code
        Route::get('/qrcode', [QrCodeController::class, 'index'])->name('admin.qrcode');
        Route::post('/qrcode/generate', [QrCodeController::class, 'generate'])->name('admin.qrcode.generate');
        Route::get('/qrcode/{qrCode}/download', [QrCodeController::class, 'download'])->name('admin.qrcode.download');
        Route::get('/qrcode/{qrCode}/print', [QrCodeController::class, 'printPdf'])->name('admin.qrcode.print');

        // Achievement Quests (managed from the Certificate Award module; Admin/Super Admin only)
        Route::post('/certificate/achievement-quests', [AchievementController::class, 'store'])->name('achievement-quests.store');
        Route::put('/certificate/achievement-quests/{achievement}', [AchievementController::class, 'update'])->name('achievement-quests.update');
        Route::delete('/certificate/achievement-quests/{achievement}', [AchievementController::class, 'destroy'])->name('achievement-quests.destroy');
    });
});
