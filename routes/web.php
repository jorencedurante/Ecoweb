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
// Public certificate access (scoped signed URLs only; never exposes storage paths)
Route::get('/certificates/{award}/view', [CertificateController::class, 'publicView'])->middleware('signed')->name('public.certificate.view');
Route::get('/certificates/{award}/file', [CertificateController::class, 'publicFile'])->middleware('signed')->name('public.certificate.file');
Route::get('/certificates/{award}/download', [CertificateController::class, 'publicDownload'])->middleware('signed')->name('public.certificate.download');
Route::post('/items/request-claim', [PublicController::class, 'requestItemClaim'])->name('public.items.request');
Route::get('/items/points', [PublicController::class, 'points'])->name('public.items.points');

// Guest routes (redirect authenticated users to dashboard)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

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

$registerPortalRoutes = function (string $asPrefix): void {
    // Account Settings (all authenticated roles in their portal)
    Route::get('/settings', [AccountSettingsController::class, 'edit'])->name($asPrefix . 'settings.edit');
    Route::post('/settings/profile', [AccountSettingsController::class, 'updateProfile'])->name($asPrefix . 'settings.profile.update');
    Route::post('/settings/password', [AccountSettingsController::class, 'updatePassword'])->name($asPrefix . 'settings.password.update');
    Route::post('/settings/email-change/initiate', [AccountSettingsController::class, 'initiateEmailChange'])->name($asPrefix . 'settings.email-change.initiate');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name($asPrefix . 'dashboard');

    // Students
    Route::get('/students/search', [StudentController::class, 'search'])->name($asPrefix . 'students.search');
    Route::get('/students', [StudentController::class, 'index'])->name($asPrefix . 'students');
    Route::get('/students/filter', [StudentController::class, 'filterStudents'])->name($asPrefix . 'students.filter');
    Route::post('/students', [StudentController::class, 'store'])->name($asPrefix . 'students.store');
    Route::post('/students/import', [StudentController::class, 'import'])->name($asPrefix . 'students.import');
    Route::get('/students/import-template', [StudentController::class, 'downloadTemplate'])->name($asPrefix . 'students.import.template');
    Route::put('/students/{student}', [StudentController::class, 'update'])->name($asPrefix . 'students.update');
    Route::patch('/students/{student}/archive', [StudentController::class, 'archive'])->name($asPrefix . 'students.archive');
    Route::post('/students/archive-all', [StudentController::class, 'archiveAll'])->name($asPrefix . 'students.archive-all');
    Route::post('/students/fix-school-years', [StudentController::class, 'fixSchoolYears'])->name($asPrefix . 'students.fix-school-years');
    Route::get('/students/archived', [StudentController::class, 'archived'])->name($asPrefix . 'students.archived');
    Route::get('/students/archived/school-years', [StudentController::class, 'schoolYearArchives'])->name($asPrefix . 'students.school-year-archives');
    Route::patch('/students/{student}/re-enroll', [StudentController::class, 'reEnroll'])->name($asPrefix . 'students.re-enroll');
    Route::get('/students/{student}/historical', [StudentController::class, 'historical'])->name($asPrefix . 'students.historical');
    Route::patch('/students/{student}/restore', [StudentController::class, 'restore'])->name($asPrefix . 'students.restore');
    Route::patch('/students/{student}/move-to-school-year', [StudentController::class, 'moveToSchoolYear'])->name($asPrefix . 'students.move-to-school-year');
    Route::patch('/students/{student}/unarchive', [StudentController::class, 'unarchive'])->name($asPrefix . 'students.unarchive');
    Route::patch('/students/unarchive-all', [StudentController::class, 'unarchiveAll'])->name($asPrefix . 'students.unarchive-all');
    Route::get('/students/{student}/info', [StudentController::class, 'info'])->name($asPrefix . 'students.info');
    Route::get('/students/{student}/achievements', [StudentController::class, 'achievements'])->name($asPrefix . 'students.achievements');
    Route::patch('/students/{student}/achievements/progress', [StudentController::class, 'updateAchievementProgress'])->name($asPrefix . 'students.achievements.progress.update');
    Route::get('/students/{student}/awards', [StudentController::class, 'awards'])->name($asPrefix . 'students.awards');
    Route::get('/students/{student}/overview', [StudentController::class, 'overview'])->name($asPrefix . 'students.overview');

    // Bottle Collection
    Route::get('/bottle-collection', [BottleCollectionController::class, 'index'])->name($asPrefix . 'bottle-collection');
    Route::get('/bottle-collection/fetch', [BottleCollectionController::class, 'fetchJson'])->name($asPrefix . 'bottle-collection.fetch');
    Route::post('/bottle-collection', [BottleCollectionController::class, 'store'])->name($asPrefix . 'bottle-collection.store');

    // Certificate / Awards
    Route::get('/certificate', [CertificateController::class, 'index'])->name($asPrefix . 'certificate');
    Route::post('/certificate', [CertificateController::class, 'store'])->name($asPrefix . 'certificate.store');
    Route::post('/certificate/template', [CertificateController::class, 'storeTemplate'])->name($asPrefix . 'certificate.template.store');
    Route::get('/certificate/create', [CertificateController::class, 'create'])->name($asPrefix . 'certificate.create');
    Route::post('/certificate/save-canvas', [CertificateController::class, 'saveCanvas'])->name($asPrefix . 'certificate.save-canvas');
    Route::post('/certificate/award', [CertificateController::class, 'award'])->name($asPrefix . 'certificate.award');
    Route::get('/certificate/{award}/edit', [CertificateController::class, 'edit'])->name($asPrefix . 'certificate.edit');
    Route::put('/certificate/{award}/update-canvas', [CertificateController::class, 'updateCanvas'])->name($asPrefix . 'certificate.update-canvas');
    Route::get('/certificate/templates/list', [CertificateController::class, 'templatesList'])->name($asPrefix . 'certificate.templates.list');
    Route::get('/certificate/{award}/print', [CertificateController::class, 'print'])->name($asPrefix . 'certificate.print');
    Route::get('/certificate/{award}/download', [CertificateController::class, 'download'])->name($asPrefix . 'certificate.download');
    Route::get('/certificate/{award}/file', [CertificateController::class, 'file'])->name($asPrefix . 'certificate.file');
    Route::patch('/certificate/{award}/archive', [CertificateController::class, 'archive'])->name($asPrefix . 'certificate.archive');
    Route::delete('/certificate/{award}', [CertificateController::class, 'destroy'])->name($asPrefix . 'certificate.destroy');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name($asPrefix . 'reports');
    Route::get('/student-report', [ReportController::class, 'studentReport'])->name($asPrefix . 'student-report');
    Route::get('/bottle-report', [ReportController::class, 'bottleReport'])->name($asPrefix . 'bottle-report');
    Route::get('/bottle-report/search', [ReportController::class, 'bottleReportSearch'])->name($asPrefix . 'bottle-report.search');
    Route::get('/admin-activities', [ReportController::class, 'adminActivities'])->name($asPrefix . 'admin-activities');
    Route::get('/admin-activities/teacher-students', [ReportController::class, 'teacherStudents'])->name($asPrefix . 'admin-activities.teacher-students');
    Route::get('/admin-activities/student-claims', [ReportController::class, 'studentClaims'])->name($asPrefix . 'admin-activities.student-claims');

    // Reports - Print routes
    Route::get('/reports/print/top-students', [ReportController::class, 'printTopStudents'])->name($asPrefix . 'reports.top-students.print');
    Route::get('/reports/top-students-data', [ReportController::class, 'topStudentsData'])->name($asPrefix . 'reports.top-students.data');
    Route::get('/reports/print/student-ranking', [ReportController::class, 'printStudentRanking'])->name($asPrefix . 'reports.student-ranking.print');
    Route::get('/reports/print/bottle-collection', [ReportController::class, 'printBottleCollection'])->name($asPrefix . 'reports.bottle-collection.print');
    Route::get('/reports/print/item-claims', [ReportController::class, 'printItemClaims'])->name($asPrefix . 'reports.item-claims.print');
    Route::get('/reports/print/student-activities', [ReportController::class, 'printStudentActivities'])->name($asPrefix . 'reports.student-activities.print');

    // Student Report - Student Details (AJAX) and Print Single Student
    Route::get('/student-report/student/{id}/details', [ReportController::class, 'studentReportDetails'])->name($asPrefix . 'student-report.details');
    Route::get('/reports/print/student/{id}', [ReportController::class, 'printStudentReport'])->name($asPrefix . 'reports.student.print');

    // QR Code
    Route::get('/qrcode', [QrCodeController::class, 'index'])->name($asPrefix . 'qrcode');
    Route::get('/qrcode/search-students', [QrCodeController::class, 'searchStudents'])->name($asPrefix . 'qrcode.searchStudents');
    Route::post('/qrcode/generate', [QrCodeController::class, 'generate'])->name($asPrefix . 'qrcode.generate');
    Route::get('/qrcode/{qrCode}/download', [QrCodeController::class, 'download'])->name($asPrefix . 'qrcode.download');
    Route::get('/qrcode/{qrCode}/print', [QrCodeController::class, 'printPdf'])->name($asPrefix . 'qrcode.print');
};

// Protected routes
Route::middleware(['auth', 'account.active'])->group(function () use ($registerPortalRoutes) {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Email change routes (outside 'verified' middleware — user's email may not be verified yet)
    Route::get('/account/email-change/verify', [AccountSettingsController::class, 'showEmailChangeVerify'])->name('account.email-change.verify');
    Route::post('/account/email-change/verify', [AccountSettingsController::class, 'confirmEmailChange'])->name('account.email-change.confirm');
    Route::post('/account/email-change/resend', [AccountSettingsController::class, 'resendEmailChangeOtp'])->name('account.email-change.resend');
    Route::post('/account/email-change/cancel', [AccountSettingsController::class, 'cancelEmailChange'])->name('account.email-change.cancel');

    // Admin & Super Admin portal (/admin/*) — share URLs; sensitive actions keep server-side role checks
    Route::middleware(['verified', 'admin.or.super'])->prefix('admin')->group(function () use ($registerPortalRoutes) {
        $registerPortalRoutes('admin.');

        // Claims / Rewards (admin & super admin only)
        Route::middleware('admin.only')->group(function () {
            Route::get('/claims', [ClaimController::class, 'index'])->name('admin.claims.index');
            Route::get('/claims/search-students', [ClaimController::class, 'searchStudents'])->name('admin.claims.searchStudents');
            Route::post('/claim-items', [ClaimController::class, 'storeItem'])->name('admin.claim-items.store');
            Route::put('/claim-items/{claimItem}', [ClaimController::class, 'updateItem'])->name('admin.claim-items.update');
            Route::post('/claims', [ClaimController::class, 'claimItem'])->name('admin.claims.store');
            Route::get('/claims/history', [ClaimController::class, 'history'])->name('admin.claims.history');
            Route::get('/claims/items/filter', [ClaimController::class, 'filterItems'])->name('admin.claims.items.filter');
            Route::get('/claims/history/filter', [ClaimController::class, 'filterHistory'])->name('admin.claims.history.filter');
            Route::get('/claims/approved/filter', [ClaimController::class, 'filterApproved'])->name('admin.claims.approved.filter');
            Route::get('/claims/pending/filter', [ClaimController::class, 'filterPending'])->name('admin.claims.pending.filter');
            Route::get('/claims/pending', fn() => redirect()->route('admin.claims.index'))->name('admin.claims.pending');
            Route::patch('/claims/{claim}/approve', [ClaimController::class, 'approve'])->name('admin.claims.approve');
            Route::patch('/claims/{claim}/reject', [ClaimController::class, 'reject'])->name('admin.claims.reject');
            Route::patch('/claims/student/{student}/archive-all', [ClaimController::class, 'archiveAllByStudent'])->name('admin.claims.archiveAllByStudent');
            Route::patch('/claims/{claim}/archive', [ClaimController::class, 'archive'])->name('admin.claims.archive');
        });

        // Achievement Quests (teachers manage their own; controller enforces ownership/role)
        Route::post('/certificate/achievement-quests', [AchievementController::class, 'store'])->name('admin.achievement-quests.store');
        Route::put('/certificate/achievement-quests/{achievement}', [AchievementController::class, 'update'])->name('admin.achievement-quests.update');
        Route::delete('/certificate/achievement-quests/{achievement}', [AchievementController::class, 'destroy'])->name('admin.achievement-quests.destroy');

        // Teachers / Accounts (admin & super admin)
        Route::middleware('admin.only')->group(function () {
            Route::get('/teachers', [TeacherController::class, 'index'])->name('admin.teachers');
            Route::get('/teachers/fetch', [TeacherController::class, 'fetch'])->name('admin.teachers.fetch');
            Route::post('/teachers', [TeacherController::class, 'store'])->name('admin.teachers.store');
            Route::put('/teachers/{teacher}', [TeacherController::class, 'update'])->name('admin.teachers.update');
            Route::post('/teachers/{teacher}/email-change/initiate', [TeacherController::class, 'initiateEmailChange'])->name('admin.teachers.email-change.initiate')->middleware('throttle:5,1');
            Route::post('/teachers/{teacher}/email-change/confirm', [TeacherController::class, 'confirmEmailChange'])->name('admin.teachers.email-change.confirm')->middleware('throttle:10,1');
            Route::post('/teachers/{teacher}/change-password', [TeacherController::class, 'changePassword'])->name('admin.teachers.change-password');
            Route::patch('/teachers/{teacher}/restrict', [TeacherController::class, 'restrict'])->name('admin.teachers.restrict');
            Route::patch('/teachers/{teacher}/unrestrict', [TeacherController::class, 'unrestrict'])->name('admin.teachers.unrestrict');
            Route::patch('/teachers/{teacher}/archive', [TeacherController::class, 'archive'])->name('admin.teachers.archive');
            Route::patch('/teachers/{teacher}/reactivate', [TeacherController::class, 'reactivate'])->name('admin.teachers.reactivate');
            Route::get('/teachers/archived', [TeacherController::class, 'archived'])->name('admin.teachers.archived');

            // System Settings
            Route::get('/system-settings', [SettingsController::class, 'index'])->name('admin.system-settings');
            Route::post('/settings/general', [SettingsController::class, 'updateGeneral'])->name('admin.settings.general');
            Route::post('/settings/security', [SettingsController::class, 'updateSecurity'])->name('admin.settings.security');
        });
    });

    // Teacher portal (/teacher/*)
    Route::middleware(['verified', 'teacher.only'])->prefix('teacher')->group(function () use ($registerPortalRoutes) {
        $registerPortalRoutes('teacher.');

        // Achievement quests (teachers manage their own)
        Route::post('/certificate/achievement-quests', [AchievementController::class, 'store'])->name('teacher.achievement-quests.store');
        Route::put('/certificate/achievement-quests/{achievement}', [AchievementController::class, 'update'])->name('teacher.achievement-quests.update');
        Route::delete('/certificate/achievement-quests/{achievement}', [AchievementController::class, 'destroy'])->name('teacher.achievement-quests.destroy');
    });
});