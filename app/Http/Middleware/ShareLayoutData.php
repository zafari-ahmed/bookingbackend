<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\DepartmentDirectory;
use App\Services\NotificationDispatchService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * The application shell — sidebar, top bar and notification bell — needs the
 * same handful of values on every authenticated page.
 *
 * Resolving them once here keeps them out of every controller, and loads the
 * signed-in user's departments a single time per request, which is what the
 * global scope and the case policy then read from.
 */
class ShareLayoutData
{
    public function __construct(
        private readonly NotificationDispatchService $notifications,
        private readonly DepartmentDirectory $departments,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            $user->loadMissing('departments');

            View::share([
                'currentUser' => $user,
                'unreadNotificationCount' => $this->notifications->unreadCountFor($user),
                'notificationPreview' => $user->notifications()->latest('created_at')->take(5)->get(),
                'userDepartments' => $this->departments->availableTo($user),
            ]);
        }

        return $next($request);
    }
}
