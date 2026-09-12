<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\NotificationService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useTailwind();
        date_default_timezone_set(config('app.timezone', 'Asia/Karachi'));

        View::composer('layouts.app', function ($view) {
            try {
                $notifications = app(NotificationService::class);
                $view->with([
                    'clubName' => Setting::get('club_name', config('app.name')),
                    'unreadNotifications' => $notifications->unreadCount(auth()->id()),
                ]);
            } catch (\Throwable) {
                $view->with([
                    'clubName' => config('app.name'),
                    'unreadNotifications' => 0,
                ]);
            }
        });
    }
}
