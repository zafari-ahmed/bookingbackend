<?php

declare(strict_types=1);

use App\Http\Controllers\CaseAttachmentController;
use App\Http\Controllers\CaseCommentController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\CaseRoutingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TwoFactorSetupController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Blade Portal Routes
|--------------------------------------------------------------------------
|
| Session-authenticated through Laravel Fortify. Fortify registers login,
| logout, password reset, email verification and two-factor challenge routes
| itself; everything below is the application proper.
|
| There is deliberately no routes/api.php — token authentication arrives with
| the Phase 2 mobile app and will sit against these same models and policies.
|
*/

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'verified', 'active'])->group(function (): void {
    // Reachable before two-factor enrolment is complete.
    Route::get('two-factor-setup', TwoFactorSetupController::class)->name('two-factor.setup');

    Route::middleware('two-factor')->group(function (): void {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::controller(CaseController::class)->group(function (): void {
            Route::get('cases', 'index')->name('cases.index');
            Route::get('cases/create', 'create')->name('cases.create');
            Route::post('cases', 'store')->name('cases.store');
            Route::get('cases/{case}', 'show')->name('cases.show');
            Route::patch('cases/{case}', 'update')->name('cases.update');
        });

        Route::post('cases/{case}/comments', [CaseCommentController::class, 'store'])
            ->name('cases.comments.store');

        Route::post('cases/{case}/routing', [CaseRoutingController::class, 'store'])
            ->name('cases.routing.store');

        Route::post('cases/{case}/attachments', [CaseAttachmentController::class, 'store'])
            ->name('cases.attachments.store');

        // Attachments live outside the web root; these are the only ways to
        // read one, and both re-check the case policy on every request.
        Route::get('cases/{case}/attachments/{attachment}', [CaseAttachmentController::class, 'download'])
            ->name('cases.attachments.download');
        Route::get('cases/{case}/attachments/{attachment}/view', [CaseAttachmentController::class, 'view'])
            ->name('cases.attachments.view');

        Route::controller(DepartmentController::class)->group(function (): void {
            Route::get('departments', 'index')->name('departments.index');
            Route::post('departments', 'store')->name('departments.store');
            Route::get('departments/{department}', 'show')->name('departments.show');
        });

        Route::controller(UserController::class)->group(function (): void {
            Route::get('users', 'index')->name('users.index');
            Route::get('users/create', 'create')->name('users.create');
            Route::post('users', 'store')->name('users.store');
            Route::get('users/{user}/edit', 'edit')->name('users.edit');
            Route::patch('users/{user}', 'update')->name('users.update');
            Route::patch('users/{user}/active', 'toggleActive')->name('users.toggle-active');
            Route::patch('users/{user}/role', 'revokeRole')->name('users.revoke-role');
            Route::delete('users/{user}/departments/{department}', 'revokeDepartment')
                ->name('users.departments.revoke');
        });

        Route::controller(NotificationController::class)->group(function (): void {
            Route::get('notifications', 'index')->name('notifications.index');
            Route::get('notifications/{notification}', 'open')->name('notifications.open');
            Route::patch('notifications/{notification}/read', 'markAsRead')->name('notifications.read');
            Route::post('notifications/read-all', 'markAllAsRead')->name('notifications.read-all');
        });

        Route::controller(ProfileController::class)->group(function (): void {
            Route::get('profile', 'show')->name('profile.show');
            Route::patch('profile', 'update')->name('profile.update');
            Route::patch('profile/password', 'updatePassword')->name('profile.password');
        });

        Route::get('activity', ActivityLogController::class)->name('activity.index');
    });
});
