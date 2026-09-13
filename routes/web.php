<?php

use App\Http\Controllers\Api\CalendarApiController;
use App\Http\Controllers\Api\MemberApiController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SportController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/bookings/create', [BookingController::class, 'create'])->name('bookings.create');
    Route::get('/bookings/quote/amount', [BookingController::class, 'quote'])->name('bookings.quote');
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::put('/bookings/{booking}', [BookingController::class, 'update'])->name('bookings.update');
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::post('/bookings/hold', [BookingController::class, 'hold'])->name('bookings.hold');
    Route::post('/bookings/{booking}/release', [BookingController::class, 'release'])->name('bookings.release');
    Route::post('/bookings/{booking}/payments', [BookingController::class, 'payment'])->name('bookings.payment');
    Route::delete('/bookings/{booking}/payments/{payment}', [BookingController::class, 'destroyPayment'])->name('bookings.payments.destroy');

    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::post('/members', [MemberController::class, 'store'])->name('members.store');
    Route::get('/members/{member}', [MemberController::class, 'show'])->name('members.show');
    Route::put('/members/{member}', [MemberController::class, 'update'])->name('members.update');
    Route::delete('/members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');

    Route::get('/sports', [SportController::class, 'index'])->name('sports.index');
    Route::post('/sports', [SportController::class, 'store'])->name('sports.store');
    Route::put('/sports/{sport}', [SportController::class, 'update'])->name('sports.update');
    Route::delete('/sports/{sport}', [SportController::class, 'destroy'])->name('sports.destroy');
    Route::post('/courts', [SportController::class, 'storeCourt'])->name('courts.store');
    Route::put('/courts/{court}', [SportController::class, 'updateCourt'])->name('courts.update');
    Route::post('/courts/{court}/toggle', [SportController::class, 'toggleCourt'])->name('courts.toggle');
    Route::delete('/courts/{court}', [SportController::class, 'destroyCourt'])->name('courts.destroy');

    Route::middleware('role:admin,manager')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export/{format}', [ReportController::class, 'export'])->name('reports.export');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/users', [SettingsController::class, 'storeUser'])->name('settings.users.store');
        Route::put('/settings/users/{user}', [SettingsController::class, 'updateUser'])->name('settings.users.update');
    });

    Route::get('/search', SearchController::class)->name('search');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

    Route::prefix('api')->group(function () {
        Route::get('/calendar', CalendarApiController::class)->name('api.calendar');
        Route::get('/calendar/events', [CalendarApiController::class, 'events'])->name('api.calendar.events');
        Route::get('/members/search', [MemberApiController::class, 'search'])->name('api.members.search');
    });
});
