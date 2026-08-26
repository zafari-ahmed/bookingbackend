<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CaseModel;
use App\Services\NotificationDispatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Filter tabs on the Notifications page. Each one is a query constraint,
     * not a client-side filter over a fully-loaded list.
     */
    private const TABS = ['all', 'unread', 'assignments', 'comments', 'escalations'];

    public function __construct(private readonly NotificationDispatchService $notifications) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $tab = in_array($request->query('tab'), self::TABS, true)
            ? (string) $request->query('tab')
            : 'all';

        $notifications = $user->notifications()
            ->when($tab === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when(
                in_array($tab, ['assignments', 'comments', 'escalations'], true),
                fn ($query) => $query->where('data->group', $tab)
            )
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'tab' => $tab,
            'tabs' => self::TABS,
            'unreadCount' => $this->notifications->unreadCountFor($user),
            'totalCount' => $user->notifications()->count(),
        ]);
    }

    /**
     * Opening a notification marks it read and jumps to the case it concerns.
     */
    public function open(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        abort_unless($this->belongsToUser($request, $notification), 403);

        $notification->markAsRead();
        $this->notifications->forgetUnreadCount($request->user());

        $caseNumber = $notification->data['case_number'] ?? null;

        $case = $caseNumber !== null
            ? CaseModel::where('case_number', $caseNumber)->first()
            : null;

        return $case !== null
            ? redirect()->route('cases.show', $case)
            : redirect()->route('notifications.index');
    }

    public function markAsRead(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        abort_unless($this->belongsToUser($request, $notification), 403);

        $notification->markAsRead();
        $this->notifications->forgetUnreadCount($request->user());

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->unreadNotifications()->update(['read_at' => Carbon::now()]);
        $this->notifications->forgetUnreadCount($user);

        return back()->with('status', 'All notifications marked as read.');
    }

    private function belongsToUser(Request $request, DatabaseNotification $notification): bool
    {
        return (int) $notification->notifiable_id === (int) $request->user()->getKey()
            && $notification->notifiable_type === $request->user()->getMorphClass();
    }
}
